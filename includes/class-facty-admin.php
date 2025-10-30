<?php
/**
 * Facty Admin Settings
 * Handles all admin pages, settings, and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Admin {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
        
        // Admin menu hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'Facty Settings',
            'Facty',
            'manage_options',
            'facty-settings',
            array($this, 'render_settings_page'),
            'dashicons-yes-alt',
            30
        );
        
        add_submenu_page(
            'facty-settings',
            'Settings',
            'Settings',
            'manage_options',
            'facty-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'facty-settings',
            'Users',
            'Users',
            'manage_options',
            'facty-users',
            array($this, 'render_users_page')
        );
        
        add_submenu_page(
            'facty-settings',
            'Cache',
            'Cache',
            'manage_options',
            'facty-cache',
            array($this, 'render_cache_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('facty_options_group', 'facty_options', array($this, 'sanitize_options'));
        
        // General Settings Section
        add_settings_section(
            'facty_general_section',
            'General Settings',
            array($this, 'render_general_section'),
            'facty-settings'
        );
        
        // API Settings Section
        add_settings_section(
            'facty_api_section',
            'API Configuration',
            array($this, 'render_api_section'),
            'facty-settings'
        );
        
        // Appearance Section
        add_settings_section(
            'facty_appearance_section',
            'Appearance',
            array($this, 'render_appearance_section'),
            'facty-settings'
        );
        
        // User Management Section
        add_settings_section(
            'facty_users_section',
            'User Management',
            array($this, 'render_users_section'),
            'facty-settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add all settings fields
     */
    private function add_settings_fields() {
        // General Settings Fields
        add_settings_field(
            'enabled',
            'Enable Fact Checker',
            array($this, 'render_enabled_field'),
            'facty-settings',
            'facty_general_section'
        );
        
        add_settings_field(
            'description_text',
            'Widget Description',
            array($this, 'render_description_field'),
            'facty-settings',
            'facty_general_section'
        );
        
        // API Settings Fields
        add_settings_field(
            'fact_check_mode',
            'Fact Check Mode',
            array($this, 'render_mode_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        add_settings_field(
            'api_key',
            'OpenRouter API Key',
            array($this, 'render_api_key_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        add_settings_field(
            'model',
            'OpenRouter Model',
            array($this, 'render_model_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        add_settings_field(
            'firecrawl_api_key',
            'Firecrawl API Key',
            array($this, 'render_firecrawl_key_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        add_settings_field(
            'jina_api_key',
            'Jina API Key',
            array($this, 'render_jina_key_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        add_settings_field(
            'firecrawl_settings',
            'Firecrawl Settings',
            array($this, 'render_firecrawl_settings_field'),
            'facty-settings',
            'facty_api_section'
        );
        
        // Appearance Fields
        add_settings_field(
            'theme_mode',
            'Theme Mode',
            array($this, 'render_theme_field'),
            'facty-settings',
            'facty_appearance_section'
        );
        
        add_settings_field(
            'colors',
            'Color Customization',
            array($this, 'render_colors_field'),
            'facty-settings',
            'facty_appearance_section'
        );
        
        // User Management Fields
        add_settings_field(
            'require_email',
            'Require Email',
            array($this, 'render_require_email_field'),
            'facty-settings',
            'facty_users_section'
        );
        
        add_settings_field(
            'free_limit',
            'Free Usage Limit',
            array($this, 'render_free_limit_field'),
            'facty-settings',
            'facty_users_section'
        );
        
        add_settings_field(
            'terms_privacy',
            'Terms & Privacy URLs',
            array($this, 'render_terms_privacy_field'),
            'facty-settings',
            'facty_users_section'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'facty') === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'facty-admin-style',
            FACTY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FACTY_VERSION
        );
        
        wp_enqueue_script(
            'facty-admin-script',
            FACTY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            FACTY_VERSION,
            true
        );
        
        wp_localize_script('facty-admin-script', 'factyAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test_api_nonce')
        ));
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = isset($input['enabled']) ? true : false;
        $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        $sanitized['model'] = sanitize_text_field($input['model']);
        $sanitized['description_text'] = sanitize_textarea_field($input['description_text']);
        $sanitized['theme_mode'] = sanitize_text_field($input['theme_mode']);
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        $sanitized['success_color'] = sanitize_hex_color($input['success_color']);
        $sanitized['warning_color'] = sanitize_hex_color($input['warning_color']);
        $sanitized['background_color'] = sanitize_hex_color($input['background_color']);
        $sanitized['free_limit'] = intval($input['free_limit']);
        $sanitized['terms_url'] = esc_url_raw($input['terms_url']);
        $sanitized['privacy_url'] = esc_url_raw($input['privacy_url']);
        $sanitized['require_email'] = isset($input['require_email']) ? true : false;
        $sanitized['fact_check_mode'] = sanitize_text_field($input['fact_check_mode']);
        $sanitized['firecrawl_api_key'] = sanitize_text_field($input['firecrawl_api_key']);
        $sanitized['jina_api_key'] = sanitize_text_field($input['jina_api_key']);
        $sanitized['firecrawl_searches_per_claim'] = intval($input['firecrawl_searches_per_claim']);
        $sanitized['firecrawl_max_claims'] = intval($input['firecrawl_max_claims']);
        
        return $sanitized;
    }
    
    /**
     * Render main settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show success message
        if (isset($_GET['settings-updated'])) {
            add_settings_error('facty_messages', 'facty_message', 'Settings saved successfully', 'success');
        }
        
        settings_errors('facty_messages');
        ?>
        <div class="wrap facty-admin-wrap">
            <h1>
                <span class="dashicons dashicons-yes-alt" style="color: #3b82f6;"></span>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>
            
            <p class="facty-subtitle">Configure your AI-powered fact-checking system</p>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('facty_options_group');
                do_settings_sections('facty-settings');
                submit_button('Save Settings');
                ?>
            </form>
            
            <div class="facty-help-box">
                <h3>📚 Need Help?</h3>
                <p>
                    <strong>OpenRouter API Key:</strong> Get your key at <a href="https://openrouter.ai" target="_blank">openrouter.ai</a><br>
                    <strong>Jina API Key:</strong> Get your FREE key at <a href="https://jina.ai" target="_blank">jina.ai</a> (Ultra-fast mode!)<br>
                    <strong>Firecrawl API Key:</strong> Get your key at <a href="https://firecrawl.dev" target="_blank">firecrawl.dev</a><br>
                    <strong>Support:</strong> Visit <a href="https://sawahsolutions.com" target="_blank">sawahsolutions.com</a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render users management page
     */
    public function render_users_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $users = Facty_Users::get_all();
        $stats = Facty_Users::get_stats();
        ?>
        <div class="wrap facty-admin-wrap">
            <h1>
                <span class="dashicons dashicons-groups"></span>
                User Management
            </h1>
            
            <div class="facty-stats-grid">
                <div class="facty-stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['total']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="facty-stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['registered']); ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
                <div class="facty-stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['free']); ?></div>
                    <div class="stat-label">Free Users</div>
                </div>
            </div>
            
            <h2>Recent Users</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Usage Count</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                No users yet. Users will appear here after they use the fact checker.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo esc_html($user->email); ?></strong></td>
                                <td><code><?php echo esc_html($user->ip_address); ?></code></td>
                                <td><?php echo esc_html($user->usage_count); ?></td>
                                <td>
                                    <?php if ($user->is_registered): ?>
                                        <span class="facty-badge facty-badge-success">Registered</span>
                                    <?php else: ?>
                                        <span class="facty-badge facty-badge-warning">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($user->created_at))); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($user->last_used))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render cache management page
     */
    public function render_cache_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle cache clearing
        if (isset($_POST['clear_cache']) && check_admin_referer('facty_clear_cache')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'facty_cache';
            $wpdb->query("DELETE FROM $table_name");
            add_settings_error('facty_messages', 'facty_message', 'Cache cleared successfully', 'success');
        }
        
        // Get cache stats
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_cache';
        $total_cached = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $cache_size = $wpdb->get_var("SELECT SUM(LENGTH(result)) FROM $table_name");
        
        settings_errors('facty_messages');
        ?>
        <div class="wrap facty-admin-wrap">
            <h1>
                <span class="dashicons dashicons-database"></span>
                Cache Management
            </h1>
            
            <div class="facty-stats-grid">
                <div class="facty-stat-card">
                    <div class="stat-number"><?php echo esc_html($total_cached); ?></div>
                    <div class="stat-label">Cached Results</div>
                </div>
                <div class="facty-stat-card">
                    <div class="stat-number"><?php echo esc_html(size_format($cache_size, 2)); ?></div>
                    <div class="stat-label">Cache Size</div>
                </div>
            </div>
            
            <div class="facty-cache-actions">
                <h2>Cache Actions</h2>
                <p>Cached results expire automatically after 24 hours. Clear the cache if you need to force new fact-checks.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('facty_clear_cache'); ?>
                    <button type="submit" name="clear_cache" class="button button-secondary" onclick="return confirm('Are you sure you want to clear all cached results?');">
                        <span class="dashicons dashicons-trash"></span>
                        Clear All Cache
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
    
    // ===================================
    // Section Callbacks
    // ===================================
    
    public function render_general_section() {
        echo '<p>Configure basic plugin settings and behavior.</p>';
    }
    
    public function render_api_section() {
        echo '<p>Configure your AI API keys and fact-checking preferences.</p>';
    }
    
    public function render_appearance_section() {
        echo '<p>Customize the look and feel of the fact checker widget.</p>';
    }
    
    public function render_users_section() {
        echo '<p>Manage user access and usage limits.</p>';
    }
    
    // ===================================
    // Field Render Methods
    // ===================================
    
    public function render_enabled_field() {
        $enabled = isset($this->options['enabled']) ? $this->options['enabled'] : true;
        ?>
        <label>
            <input type="checkbox" name="facty_options[enabled]" value="1" <?php checked($enabled, true); ?>>
            Enable fact checker on single posts
        </label>
        <p class="description">When enabled, the fact checker widget will appear on all single post pages.</p>
        <?php
    }
    
    public function render_description_field() {
        $description = isset($this->options['description_text']) ? $this->options['description_text'] : 'Verify the accuracy of this article using AI analysis and real-time sources.';
        ?>
        <textarea name="facty_options[description_text]" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
        <p class="description">This text appears in the fact checker widget.</p>
        <?php
    }
    
    public function render_mode_field() {
        $mode = isset($this->options['fact_check_mode']) ? $this->options['fact_check_mode'] : 'openrouter';
        ?>
        <select name="facty_options[fact_check_mode]" class="regular-text">
            <option value="openrouter" <?php selected($mode, 'openrouter'); ?>>OpenRouter (Quick Check - 60-90s)</option>
            <option value="jina" <?php selected($mode, 'jina'); ?>>Jina DeepSearch (Ultra-Fast - 30-45s)</option>
            <option value="firecrawl" <?php selected($mode, 'firecrawl'); ?>>Firecrawl (Deep Research - 2-3min)</option>
        </select>
        <p class="description">
            <strong>OpenRouter:</strong> Balanced speed and accuracy using AI + web search<br>
            <strong>Jina DeepSearch:</strong> Ultra-fast AI-powered search, read & reason fact-checking<br>
            <strong>Firecrawl:</strong> Most thorough research with deep source analysis
        </p>
        <?php
    }
    
    public function render_api_key_field() {
        $api_key = isset($this->options['api_key']) ? $this->options['api_key'] : '';
        $has_key = !empty($api_key);
        ?>
        <input type="password" name="facty_options[api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-or-v1-...">
        <?php if ($has_key): ?>
            <span class="facty-status-badge facty-status-success">✓ Key configured</span>
        <?php endif; ?>
        <p class="description">
            Get your API key at <a href="https://openrouter.ai" target="_blank">openrouter.ai</a>
        </p>
        <button type="button" id="test-api-btn" class="button button-secondary" style="margin-top: 10px;">
            Test API Connection
        </button>
        <div id="api-test-result" style="margin-top: 10px;"></div>
        <?php
    }
    
    public function render_model_field() {
        $model = isset($this->options['model']) ? $this->options['model'] : 'openai/gpt-4o';
        ?>
        <select name="facty_options[model]" class="regular-text">
            <option value="openai/gpt-4o" <?php selected($model, 'openai/gpt-4o'); ?>>OpenAI GPT-4o (Recommended)</option>
            <option value="openai/gpt-4o-mini" <?php selected($model, 'openai/gpt-4o-mini'); ?>>OpenAI GPT-4o Mini (Faster)</option>
            <option value="anthropic/claude-3.5-sonnet" <?php selected($model, 'anthropic/claude-3.5-sonnet'); ?>>Anthropic Claude 3.5 Sonnet</option>
            <option value="anthropic/claude-3-opus" <?php selected($model, 'anthropic/claude-3-opus'); ?>>Anthropic Claude 3 Opus</option>
            <option value="google/gemini-pro-1.5" <?php selected($model, 'google/gemini-pro-1.5'); ?>>Google Gemini 1.5 Pro</option>
            <option value="meta-llama/llama-3.1-70b-instruct" <?php selected($model, 'meta-llama/llama-3.1-70b-instruct'); ?>>Meta Llama 3.1 70B</option>
        </select>
        <p class="description">Select the AI model to use for fact-checking. GPT-4o recommended for best results.</p>
        <?php
    }
    
    public function render_firecrawl_key_field() {
        $api_key = isset($this->options['firecrawl_api_key']) ? $this->options['firecrawl_api_key'] : '';
        $has_key = !empty($api_key);
        ?>
        <input type="password" name="facty_options[firecrawl_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="fc-...">
        <?php if ($has_key): ?>
            <span class="facty-status-badge facty-status-success">✓ Key configured</span>
        <?php endif; ?>
        <p class="description">
            Required for Firecrawl mode. Get your key at <a href="https://firecrawl.dev" target="_blank">firecrawl.dev</a>
        </p>
        <?php
    }
    
    public function render_jina_key_field() {
        $api_key = isset($this->options['jina_api_key']) ? $this->options['jina_api_key'] : '';
        $has_key = !empty($api_key);
        ?>
        <input type="password" name="facty_options[jina_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="jina_...">
        <?php if ($has_key): ?>
            <span class="facty-status-badge facty-status-success">✓ Key configured</span>
        <?php endif; ?>
        <p class="description">
            Required for Jina DeepSearch mode. Get your FREE API key (10M free tokens) at <a href="https://jina.ai/?sui=apikey" target="_blank">jina.ai</a> - Ultra-fast AI-powered fact checking with search, read & reason!
        </p>
        <?php
    }
    
    public function render_firecrawl_settings_field() {
        $searches = isset($this->options['firecrawl_searches_per_claim']) ? $this->options['firecrawl_searches_per_claim'] : 3;
        $max_claims = isset($this->options['firecrawl_max_claims']) ? $this->options['firecrawl_max_claims'] : 10;
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th scope="row">Searches Per Claim</th>
                <td>
                    <input type="number" name="facty_options[firecrawl_searches_per_claim]" value="<?php echo esc_attr($searches); ?>" class="small-text" min="1" max="10">
                    <p class="description">Number of sources to search for each claim (1-10)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Maximum Claims</th>
                <td>
                    <input type="number" name="facty_options[firecrawl_max_claims]" value="<?php echo esc_attr($max_claims); ?>" class="small-text" min="1" max="20">
                    <p class="description">Maximum claims to extract and verify per article (1-20)</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_theme_field() {
        $theme = isset($this->options['theme_mode']) ? $this->options['theme_mode'] : 'light';
        ?>
        <select name="facty_options[theme_mode]" class="regular-text">
            <option value="light" <?php selected($theme, 'light'); ?>>Light Theme</option>
            <option value="dark" <?php selected($theme, 'dark'); ?>>Dark Theme</option>
        </select>
        <p class="description">Choose the default theme for the fact checker widget.</p>
        <?php
    }
    
    public function render_colors_field() {
        $primary = isset($this->options['primary_color']) ? $this->options['primary_color'] : '#3b82f6';
        $success = isset($this->options['success_color']) ? $this->options['success_color'] : '#059669';
        $warning = isset($this->options['warning_color']) ? $this->options['warning_color'] : '#f59e0b';
        $background = isset($this->options['background_color']) ? $this->options['background_color'] : '#f8fafc';
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th scope="row">Primary Color</th>
                <td>
                    <input type="text" name="facty_options[primary_color]" value="<?php echo esc_attr($primary); ?>" class="facty-color-picker" data-default-color="#3b82f6">
                    <p class="description">Used for buttons and primary actions</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Success Color</th>
                <td>
                    <input type="text" name="facty_options[success_color]" value="<?php echo esc_attr($success); ?>" class="facty-color-picker" data-default-color="#059669">
                    <p class="description">Used for high accuracy scores</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Warning Color</th>
                <td>
                    <input type="text" name="facty_options[warning_color]" value="<?php echo esc_attr($warning); ?>" class="facty-color-picker" data-default-color="#f59e0b">
                    <p class="description">Used for issues and warnings</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Background Color</th>
                <td>
                    <input type="text" name="facty_options[background_color]" value="<?php echo esc_attr($background); ?>" class="facty-color-picker" data-default-color="#f8fafc">
                    <p class="description">Widget background color</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_require_email_field() {
        $require_email = isset($this->options['require_email']) ? $this->options['require_email'] : true;
        ?>
        <label>
            <input type="checkbox" name="facty_options[require_email]" value="1" <?php checked($require_email, true); ?>>
            Require email for fact checking
        </label>
        <p class="description">If enabled, visitors must provide their email before using the fact checker.</p>
        <?php
    }
    
    public function render_free_limit_field() {
        $limit = isset($this->options['free_limit']) ? $this->options['free_limit'] : 5;
        ?>
        <input type="number" name="facty_options[free_limit]" value="<?php echo esc_attr($limit); ?>" class="small-text" min="1" max="100">
        <p class="description">Number of free fact checks allowed per visitor. Registered users get unlimited access.</p>
        <?php
    }
    
    public function render_terms_privacy_field() {
        $terms_url = isset($this->options['terms_url']) ? $this->options['terms_url'] : '';
        $privacy_url = isset($this->options['privacy_url']) ? $this->options['privacy_url'] : '';
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th scope="row">Terms & Conditions URL</th>
                <td>
                    <input type="url" name="facty_options[terms_url]" value="<?php echo esc_url($terms_url); ?>" class="regular-text" placeholder="https://yoursite.com/terms">
                </td>
            </tr>
            <tr>
                <th scope="row">Privacy Policy URL</th>
                <td>
                    <input type="url" name="facty_options[privacy_url]" value="<?php echo esc_url($privacy_url); ?>" class="regular-text" placeholder="https://yoursite.com/privacy">
                </td>
            </tr>
        </table>
        <p class="description">These links will appear in the email capture form.</p>
        <?php
    }
}