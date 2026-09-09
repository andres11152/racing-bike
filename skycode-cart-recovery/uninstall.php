<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'rb_cr_settings', array() );
if ( empty( $settings['delete_on_uninstall'] ) ) {
    return;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rb_cr_carts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rb_cr_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rb_cr_optouts" );

delete_option( 'rb_cr_settings' );
delete_option( 'rb_cr_sequence' );
delete_option( 'rb_cr_db_version' );
