<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Cache_Service {
    public function clear_all() {
        global $wpdb;

        $results = array();

        wp_cache_flush();
        $results[] = 'object_cache';

        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
        $results[] = 'transients';

        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
            $results[] = 'wp_rocket';
        }

        if ( function_exists( 'litespeed_purge_all' ) ) {
            litespeed_purge_all();
            $results[] = 'litespeed';
        }

        if ( function_exists( 'wp_cache_flush_runtime' ) ) {
            wp_cache_flush_runtime();
            $results[] = 'redis_runtime';
        }

        return array(
            'message' => 'Caches cleared.',
            'layers'  => $results,
        );
    }
}
