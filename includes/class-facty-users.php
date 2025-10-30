<?php
/**
 * Facty Users Handler
 * Manages user tracking, usage limits, and registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Users {
    
    /**
     * Get user status (logged in, email, usage count, etc.)
     */
    public static function get_status($options) {
        if (is_user_logged_in()) {
            return array(
                'logged_in' => true,
                'usage_count' => 0,
                'can_use' => true,
                'email' => wp_get_current_user()->user_email,
                'is_registered' => true
            );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        $ip_address = self::get_client_ip();
        
        // Check by cookie first (email)
        $email = isset($_COOKIE['fact_checker_email']) ? sanitize_email($_COOKIE['fact_checker_email']) : '';
        
        if ($email) {
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s",
                $email
            ));
            
            if ($user) {
                return array(
                    'logged_in' => false,
                    'email' => $user->email,
                    'usage_count' => intval($user->usage_count),
                    'can_use' => $user->is_registered || intval($user->usage_count) < $options['free_limit'],
                    'is_registered' => (bool) $user->is_registered
                );
            }
        }
        
        // Check by IP as fallback
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE ip_address = %s ORDER BY created_at DESC LIMIT 1",
            $ip_address
        ));
        
        if ($user) {
            return array(
                'logged_in' => false,
                'email' => $user->email,
                'usage_count' => intval($user->usage_count),
                'can_use' => $user->is_registered || intval($user->usage_count) < $options['free_limit'],
                'is_registered' => (bool) $user->is_registered
            );
        }
        
        return array(
            'logged_in' => false,
            'email' => '',
            'usage_count' => 0,
            'can_use' => true,
            'is_registered' => false
        );
    }
    
    /**
     * Save email for visitor
     */
    public static function save_email($email) {
        $ip_address = self::get_client_ip();
        
        if (!is_email($email)) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        
        // Check if email already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            // Update IP and last used
            $wpdb->update(
                $table_name,
                array(
                    'ip_address' => $ip_address,
                    'last_used' => current_time('mysql')
                ),
                array('email' => $email),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new user
            $wpdb->insert(
                $table_name,
                array(
                    'email' => $email,
                    'ip_address' => $ip_address,
                    'usage_count' => 0
                ),
                array('%s', '%s', '%d')
            );
        }
        
        // Set cookie
        setcookie('fact_checker_email', $email, time() + (86400 * 365), '/');
        
        return true;
    }
    
    /**
     * Register new user
     */
    public static function register($name, $email, $password) {
        $ip_address = self::get_client_ip();
        
        if (!is_email($email) || empty($name) || empty($password)) {
            return array('success' => false, 'message' => 'Please fill in all required fields');
        }
        
        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            return array('success' => false, 'message' => $user_id->get_error_message());
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name
        ));
        
        // Update fact checker user record
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        
        $wpdb->replace(
            $table_name,
            array(
                'email' => $email,
                'ip_address' => $ip_address,
                'usage_count' => 0,
                'is_registered' => 1
            ),
            array('%s', '%s', '%d', '%d')
        );
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        return array('success' => true, 'user_id' => $user_id, 'email' => $email);
    }
    
    /**
     * Update usage count for visitor
     */
    public static function increment_usage($user_status) {
        if ($user_status['logged_in'] || $user_status['is_registered']) {
            return; // No limits for registered users
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        
        if (!empty($user_status['email'])) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET usage_count = usage_count + 1, last_used = %s WHERE email = %s",
                current_time('mysql'),
                $user_status['email']
            ));
        }
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ipkeys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ipkeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Get all users for admin page
     */
    public static function get_all($limit = 500) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d", $limit)
        );
    }
    
    /**
     * Get user statistics
     */
    public static function get_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_users';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $registered = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_registered = 1");
        
        return array(
            'total' => intval($total),
            'registered' => intval($registered),
            'free' => intval($total) - intval($registered)
        );
    }
}
