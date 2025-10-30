<?php
/**
 * Facty Core
 * Main plugin class that ties everything together
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Core {
    
    private $options;
    private $ajax_handler;
    private $admin_handler;
    
    public function __construct() {
        $this->init_options();
        
        // Initialize AJAX handler
        $this->ajax_handler = new Facty_AJAX($this->options);
        
        // Initialize admin handler
        if (is_admin()) {
            $this->admin_handler = new Facty_Admin($this->options);
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'add_fact_checker_to_content'));
    }
    
    /**
     * Initialize options with defaults
     */
    private function init_options() {
        $default_options = array(
            'enabled' => true,
            'api_key' => '',
            'model' => 'openai/gpt-4o',
            'description_text' => 'Verify the accuracy of this article using AI analysis and real-time sources.',
            'web_searches' => 5,
            'search_context' => 'medium',
            'theme_mode' => 'light',
            'primary_color' => '#3b82f6',
            'success_color' => '#059669',
            'warning_color' => '#f59e0b',
            'background_color' => '#f8fafc',
            'free_limit' => 5,
            'terms_url' => '',
            'privacy_url' => '',
            'require_email' => true,
            'fact_check_mode' => 'openrouter',
            'firecrawl_api_key' => '',
            'jina_api_key' => '',
            'firecrawl_searches_per_claim' => 3,
            'firecrawl_max_claims' => 10
        );
        
        $saved_options = get_option('facty_options', array());
        $this->options = array_merge($default_options, $saved_options);
        
        // Update options if new defaults were added
        if (count($this->options) > count($saved_options)) {
            update_option('facty_options', $this->options);
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_single() && $this->options['enabled']) {
            wp_enqueue_style(
                'facty-style',
                FACTY_PLUGIN_URL . 'assets/css/facty.css',
                array(),
                FACTY_VERSION
            );
            
            wp_enqueue_script(
                'facty-script',
                FACTY_PLUGIN_URL . 'assets/js/facty.js',
                array('jquery'),
                FACTY_VERSION,
                true
            );
            
            wp_localize_script('facty-script', 'factChecker', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('facty_nonce'),
                'theme_mode' => $this->options['theme_mode'],
                'require_email' => $this->options['require_email'],
                'free_limit' => $this->options['free_limit'],
                'terms_url' => $this->options['terms_url'],
                'privacy_url' => $this->options['privacy_url'],
                'fact_check_mode' => $this->options['fact_check_mode'],
                'colors' => array(
                    'primary' => $this->options['primary_color'],
                    'success' => $this->options['success_color'],
                    'warning' => $this->options['warning_color'],
                    'background' => $this->options['background_color']
                )
            ));
        }
    }
    
    /**
     * Add fact checker widget to content
     */
    public function add_fact_checker_to_content($content) {
        if (is_single() && $this->options['enabled'] && !empty($this->options['api_key'])) {
            $fact_checker_html = $this->get_fact_checker_html();
            $content .= $fact_checker_html;
        }
        return $content;
    }
    
    /**
     * Generate fact checker HTML
     */
    private function get_fact_checker_html() {
        $user_status = Facty_Users::get_status($this->options);
        
        $mode_label = 'OpenRouter Web Search';
        if ($this->options['fact_check_mode'] === 'firecrawl') {
            $mode_label = 'Firecrawl Deep Research';
        } elseif ($this->options['fact_check_mode'] === 'jina') {
            $mode_label = 'Jina DeepSearch';
        }
        
        ob_start();
        include FACTY_PLUGIN_PATH . 'templates/fact-checker-widget.php';
        return ob_get_clean();
    }
}