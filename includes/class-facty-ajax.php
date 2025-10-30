<?php
/**
 * Facty AJAX Handler
 * Handles all AJAX requests from the frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_AJAX {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
        
        // Register AJAX handlers
        add_action('wp_ajax_facty_check_article', array($this, 'start_fact_check'));
        add_action('wp_ajax_nopriv_facty_check_article', array($this, 'start_fact_check'));
        
        add_action('wp_ajax_facty_check_progress', array($this, 'check_progress'));
        add_action('wp_ajax_nopriv_facty_check_progress', array($this, 'check_progress'));
        
        add_action('wp_ajax_facty_email_submit', array($this, 'submit_email'));
        add_action('wp_ajax_nopriv_facty_email_submit', array($this, 'submit_email'));
        
        add_action('wp_ajax_facty_signup', array($this, 'signup'));
        add_action('wp_ajax_nopriv_facty_signup', array($this, 'signup'));
        
        add_action('wp_ajax_test_facty_api', array($this, 'test_api'));
    }
    
    /**
     * Start fact check with background processing
     */
    public function start_fact_check() {
        check_ajax_referer('facty_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
            return;
        }
        
        // Check if API key is configured
        if (empty($this->options['api_key'])) {
            wp_send_json_error('API key not configured. Please check plugin settings.');
            return;
        }
        
        // Check user permissions
        $user_status = Facty_Users::get_status($this->options);
        if (!$user_status['can_use']) {
            wp_send_json_error('Usage limit exceeded. Please sign up for unlimited access.');
            return;
        }
        
        // Create task ID for background processing
        $task_id = 'facty_task_' . $post_id . '_' . time();
        
        // Set initial progress
        set_transient($task_id, array(
            'status' => 'processing',
            'progress' => 5,
            'stage' => 'starting',
            'message' => 'Starting fact check...'
        ), 300); // 5 minutes
        
        // Start background processing
        $this->process_fact_check_background($task_id, $post_id);
        
        // Return task ID to frontend
        wp_send_json_success(array(
            'task_id' => $task_id,
            'message' => 'Fact check started'
        ));
    }
    
    /**
     * Background processing function
     */
    private function process_fact_check_background($task_id, $post_id) {
        try {
            $post = get_post($post_id);
            
            $this->update_progress($task_id, 10, 'extracting', 'Reading article content...');
            
            // Get article content
            $content = strip_tags($post->post_content);
            $content = wp_trim_words($content, 800);
            
            if (empty(trim($content))) {
                throw new Exception('No content to analyze');
            }
            
            // Check cache first
            $mode = $this->options['fact_check_mode'];
            $cached_result = Facty_Cache::get($post_id, $post->post_content, $mode);
            
            if ($cached_result) {
                $this->update_progress($task_id, 100, 'complete', 'Using cached results');
                set_transient($task_id, array(
                    'status' => 'complete',
                    'progress' => 100,
                    'result' => $cached_result
                ), 300);
                return;
            }
            
            // Analyze content based on mode
            if ($mode === 'firecrawl' && !empty($this->options['firecrawl_api_key'])) {
                $analyzer = new Facty_Firecrawl($this->options);
                $result = $analyzer->analyze($content, $task_id);
            } else {
                $analyzer = new Facty_Analyzer($this->options);
                $result = $analyzer->analyze($content, $task_id);
            }
            
            // Cache the result
            Facty_Cache::set($post_id, $post->post_content, $mode, $result);
            
            // Update usage count
            $user_status = Facty_Users::get_status($this->options);
            Facty_Users::increment_usage($user_status);
            
            $this->update_progress($task_id, 100, 'complete', 'Fact check complete!');
            set_transient($task_id, array(
                'status' => 'complete',
                'progress' => 100,
                'result' => $result
            ), 300);
            
        } catch (Exception $e) {
            error_log('Facty Error: ' . $e->getMessage());
            set_transient($task_id, array(
                'status' => 'error',
                'progress' => 0,
                'message' => $e->getMessage()
            ), 300);
        }
    }
    
    /**
     * Check progress of background task
     */
    public function check_progress() {
        check_ajax_referer('facty_nonce', 'nonce');
        
        $task_id = sanitize_text_field($_POST['task_id']);
        $progress = get_transient($task_id);
        
        if ($progress === false) {
            wp_send_json_error('Task not found or expired');
            return;
        }
        
        wp_send_json_success($progress);
    }
    
    /**
     * Submit email for visitor
     */
    public function submit_email() {
        check_ajax_referer('facty_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error('Please enter a valid email address');
            return;
        }
        
        if (Facty_Users::save_email($email)) {
            wp_send_json_success(array('email' => $email));
        } else {
            wp_send_json_error('Failed to save email');
        }
    }
    
    /**
     * Sign up new user
     */
    public function signup() {
        check_ajax_referer('facty_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        $result = Facty_Users::register($name, $email, $password);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Test API connection
     */
    public function test_api() {
        check_ajax_referer('test_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $model = sanitize_text_field($_POST['model']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        try {
            $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => home_url(),
                    'X-Title' => get_bloginfo('name')
                ),
                'body' => json_encode(array(
                    'model' => $model,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => 'Say "Connection successful" if you receive this message.'
                        )
                    ),
                    'max_tokens' => 50
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error('Connection failed: ' . $response->get_error_message());
                return;
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($http_code !== 200) {
                $error_data = json_decode($body, true);
                $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';
                wp_send_json_error('API Error (' . $http_code . '): ' . $error_message);
                return;
            }
            
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['choices'][0]['message']['content'])) {
                wp_send_json_error('Invalid API response format');
                return;
            }
            
            wp_send_json_success('API connection successful! Model: ' . $model);
            
        } catch (Exception $e) {
            wp_send_json_error('Test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update progress for task
     */
    private function update_progress($task_id, $percentage, $stage, $message) {
        set_transient($task_id, array(
            'status' => 'processing',
            'progress' => $percentage,
            'stage' => $stage,
            'message' => $message
        ), 300);
    }
}
