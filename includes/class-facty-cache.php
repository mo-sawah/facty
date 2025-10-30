<?php
/**
 * Facty Cache Handler
 * Manages caching of fact-check results
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Cache {
    
    /**
     * Get cached result for a post
     */
    public static function get($post_id, $content, $mode) {
        global $wpdb;
        
        $content_hash = hash('sha256', $content . $mode);
        $table_name = $wpdb->prefix . 'facty_cache';
        
        $cached = $wpdb->get_var($wpdb->prepare(
            "SELECT result FROM $table_name WHERE post_id = %d AND content_hash = %s AND created_at > %s",
            $post_id,
            $content_hash,
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        return $cached ? json_decode($cached, true) : false;
    }
    
    /**
     * Save result to cache
     */
    public static function set($post_id, $content, $mode, $result) {
        global $wpdb;
        
        $content_hash = hash('sha256', $content . $mode);
        $table_name = $wpdb->prefix . 'facty_cache';
        
        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'content_hash' => $content_hash,
                'result' => json_encode($result)
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * Clear cache for a specific post
     */
    public static function clear($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_cache';
        
        $wpdb->delete(
            $table_name,
            array('post_id' => $post_id),
            array('%d')
        );
    }
    
    /**
     * Clear old cache entries
     */
    public static function clear_old($days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facty_cache';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ));
    }
}
