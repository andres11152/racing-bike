<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Compat {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'skc_mm_compat_purge_cache', array( $this, 'purge_cache' ) );
        add_action( 'update_option_skc_mm_settings', array( $this, 'purge_cache' ) );
        add_filter( 'wpseo_sitemap_index', array( $this, 'maybe_suppress_sitemap' ) );
        add_action( 'template_redirect', array( $this, 'suspend_cache_addition' ), 1 );
    }

    public function suspend_cache_addition() {
        if ( 'off' !== SKC_MM_Gatekeeper::effective_mode() ) {
            wp_suspend_cache_addition( true );
        }
    }

    public function maybe_suppress_sitemap( $sitemap ) {
        if ( 'off' !== SKC_MM_Gatekeeper::effective_mode() ) {
            return '';
        }
        return $sitemap;
    }

    public function purge_cache() {
        // LiteSpeed Cache
        if ( has_action( 'litespeed_purge_all' ) ) {
            do_action( 'litespeed_purge_all' );
        }

        // WP Rocket
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }

        // W3 Total Cache
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        // WP Super Cache
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        // Nginx Helper
        if ( has_action( 'rt_nginx_helper_purge_all' ) ) {
            do_action( 'rt_nginx_helper_purge_all' );
        }
    }
}
