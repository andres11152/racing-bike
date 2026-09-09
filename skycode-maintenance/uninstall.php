<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'skc_mm_settings', array() );

if ( empty( $settings['delete_on_uninstall'] ) ) {
    return;
}

global $wpdb;

delete_option( 'skc_mm_settings' );
delete_option( 'skc_mm_db_version' );

wp_clear_scheduled_hook( 'skc_mm_check_schedule' );

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}skc_mm_subscribers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}skc_mm_log" );
