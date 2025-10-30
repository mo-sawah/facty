<?php
/**
 * Plugin Name: Facty
 * Description: AI-powered fact-checking plugin that verifies article accuracy using OpenRouter with web search or Firecrawl for deep multi-step research
 * Version: 4.6.3
 * Author: Mohamed Sawah
 * Author URI: https://sawahsolutions.com
 * License: GPL v2 or later
 * Text Domain: facty
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FACTY_VERSION', '4.6.3');
define('FACTY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FACTY_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-cache.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-users.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-analyzer.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-firecrawl.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-ajax.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-admin.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-core.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-jina-analyzer.php';
require_once FACTY_PLUGIN_PATH . 'includes/class-facty-perplexity-analyzer.php';

// Initialize the plugin
function facty_init() {
    new Facty_Core();
}
add_action('plugins_loaded', 'facty_init');

// Activation hook
register_activation_hook(__FILE__, 'facty_activate');

function facty_activate() {
    global $wpdb;
    
    $cache_table = $wpdb->prefix . 'facty_cache';
    $users_table = $wpdb->prefix . 'facty_users';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql1 = "CREATE TABLE IF NOT EXISTS $cache_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        content_hash varchar(64) NOT NULL,
        result longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_content (post_id, content_hash)
    ) $charset_collate;";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS $users_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        ip_address varchar(45) NOT NULL,
        usage_count int(11) DEFAULT 0,
        is_registered boolean DEFAULT FALSE,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        last_used datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY ip_address (ip_address)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}