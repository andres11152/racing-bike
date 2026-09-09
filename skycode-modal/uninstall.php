<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'skc_md_settings', array() );

if ( empty( $settings['delete_on_uninstall'] ) ) {
    return;
}

global $wpdb;

$campaigns = get_posts( array(
    'post_type'      => 'skc_md_campaign',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
) );

foreach ( $campaigns as $post_id ) {
    wp_delete_post( $post_id, true );
}

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}skc_md_leads" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}skc_md_events" );

delete_option( 'skc_md_settings' );
delete_option( 'skc_md_db_version' );

wp_clear_scheduled_hook( 'skc_md_purge_events' );
