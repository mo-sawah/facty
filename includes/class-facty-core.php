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
        
        // SmartMag theme integration - Add fact-checked badge to post meta
        add_filter('bunyad_post_meta_item', array($this, 'add_fact_check_meta_item'), 10, 2);
        
        // Add schema markup for fact-checked articles
        add_action('wp_head', array($this, 'add_fact_check_schema'), 10);
        
        // Register demo page
        add_action('init', array($this, 'register_demo_page'));
        add_action('template_redirect', array($this, 'handle_demo_page'));
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
            'perplexity_api_key' => '',
            'perplexity_model' => 'sonar-pro',
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
            
            // Enqueue badge styles for fact-checked posts
            wp_enqueue_style(
                'facty-badge-style',
                FACTY_PLUGIN_URL . 'assets/css/facty-badge.css',
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
        if ($this->options['fact_check_mode'] === 'perplexity') {
            $mode_label = 'Deep Research';
        } elseif ($this->options['fact_check_mode'] === 'firecrawl') {
            $mode_label = 'Crawler Deep Research';
        } elseif ($this->options['fact_check_mode'] === 'jina') {
            $mode_label = 'Reader DeepSearch';
        }
        
        ob_start();
        include FACTY_PLUGIN_PATH . 'templates/fact-checker-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Add fact-checked badge to SmartMag post meta
     */
    public function add_fact_check_meta_item($output, $item) {
        // Only add for 'fact_check' custom item
        if ($item !== 'fact_check') {
            return $output;
        }
        
        // Check if this post has been fact-checked
        $post_id = get_the_ID();
        if (!$this->is_post_fact_checked($post_id)) {
            return '';
        }
        
        $cached_result = Facty_Cache::get($post_id, get_post($post_id)->post_content, $this->options['fact_check_mode']);
        
        if ($cached_result && isset($cached_result['score'])) {
            $score = intval($cached_result['score']);
            $status = isset($cached_result['status']) ? $cached_result['status'] : 'Checked';
            
            // Determine badge color based on score
            $badge_class = 'facty-badge-success';
            if ($score < 70) {
                $badge_class = 'facty-badge-warning';
            }
            if ($score < 50) {
                $badge_class = 'facty-badge-error';
            }
            
            $output = sprintf(
                '<span class="meta-item facty-verified-badge %1$s" title="%2$s">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <span class="fact-check-text">Fact-Checked</span>
                    <span class="fact-check-score">%3$d/100</span>
                </span>',
                esc_attr($badge_class),
                esc_attr('Fact-checked: ' . $status . ' - Score: ' . $score . '/100'),
                $score
            );
        }
        
        return $output;
    }
    
    /**
     * Add ClaimReview schema markup for fact-checked articles
     */
    public function add_fact_check_schema() {
        if (!is_single()) {
            return;
        }
        
        $post_id = get_the_ID();
        if (!$this->is_post_fact_checked($post_id)) {
            return;
        }
        
        $cached_result = Facty_Cache::get($post_id, get_post($post_id)->post_content, $this->options['fact_check_mode']);
        
        if (!$cached_result || !isset($cached_result['score'])) {
            return;
        }
        
        $score = intval($cached_result['score']);
        $status = isset($cached_result['status']) ? $cached_result['status'] : 'Checked';
        $description = isset($cached_result['description']) ? $cached_result['description'] : '';
        
        // Determine rating value based on score (1-5 scale)
        $rating = max(1, min(5, round($score / 20)));
        
        // Determine ClaimReview rating
        if ($score >= 90) {
            $truth_rating = 'True';
        } elseif ($score >= 70) {
            $truth_rating = 'Mostly True';
        } elseif ($score >= 50) {
            $truth_rating = 'Mixture';
        } elseif ($score >= 30) {
            $truth_rating = 'Mostly False';
        } else {
            $truth_rating = 'False';
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ClaimReview',
            'url' => get_permalink($post_id),
            'claimReviewed' => get_the_title($post_id),
            'itemReviewed' => array(
                '@type' => 'CreativeWork',
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author_meta('display_name')
                ),
                'datePublished' => get_the_date('c', $post_id),
                'name' => get_the_title($post_id)
            ),
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'reviewRating' => array(
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => 5,
                'worstRating' => 1,
                'alternateName' => $truth_rating
            ),
            'datePublished' => get_the_modified_date('c', $post_id)
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    /**
     * Check if post has been fact-checked
     */
    private function is_post_fact_checked($post_id) {
        global $wpdb;
        
        if (!$post_id) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'facty_cache';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Register demo page
     */
    public function register_demo_page() {
        // Demo page
        add_rewrite_rule(
            '^fact-check-demo/?$',
            'index.php?facty_demo=1',
            'top'
        );
        add_rewrite_tag('%facty_demo%', '1');
        
        // Editor page
        add_rewrite_rule(
            '^fact-check-editor/?$',
            'index.php?facty_editor=1',
            'top'
        );
        add_rewrite_tag('%facty_editor%', '1');
    }
    
    /**
     * Handle demo page display
     */
    public function handle_demo_page() {
        if (get_query_var('facty_demo') == '1') {
            include FACTY_PLUGIN_PATH . 'templates/demo-page.php';
            exit;
        }
        
        if (get_query_var('facty_editor') == '1') {
            include FACTY_PLUGIN_PATH . 'templates/editor-page.php';
            exit;
        }
    }
}