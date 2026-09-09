<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Events {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_skc_md_track', array( $this, 'handle_track' ) );
        add_action( 'wp_ajax_nopriv_skc_md_track', array( $this, 'handle_track' ) );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'skc_md_events';
    }

    public function handle_track() {
        check_ajax_referer( 'skc_md_track', 'nonce' );

        $campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
        $event       = isset( $_POST['event'] ) ? sanitize_key( $_POST['event'] ) : '';
        $allowed     = array( 'impression', 'view', 'close', 'submit', 'convert' );

        if ( ! $campaign_id || ! in_array( $event, $allowed, true ) ) {
            wp_send_json_error( null, 400 );
        }

        global $wpdb;
        $wpdb->insert( self::table(), array(
            'campaign_id'  => $campaign_id,
            'event'        => $event,
            'variant'      => isset( $_POST['variant'] ) ? sanitize_key( $_POST['variant'] ) : '',
            'visitor_hash' => isset( $_POST['visitor'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor'] ) ) : '',
            'url'          => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
            'created_at'   => current_time( 'mysql' ),
        ) );

        wp_send_json_success();
    }

    public static function stats_for_campaign( $campaign_id ) {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT event, COUNT(*) as total FROM {$table} WHERE campaign_id = %d GROUP BY event",
            $campaign_id
        ), ARRAY_A );

        $stats = array( 'impression' => 0, 'close' => 0, 'submit' => 0, 'convert' => 0 );
        foreach ( $rows as $row ) {
            $stats[ $row['event'] ] = (int) $row['total'];
        }

        $stats['conversion_rate'] = $stats['impression'] > 0
            ? round( ( $stats['convert'] / $stats['impression'] ) * 100, 2 )
            : 0.0;

        return $stats;
    }

    public static function variant_stats( $campaign_id ) {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT variant, event, COUNT(*) as total FROM {$table} WHERE campaign_id = %d AND variant != '' GROUP BY variant, event",
            $campaign_id
        ), ARRAY_A );

        $variants = array();
        foreach ( $rows as $row ) {
            $key = $row['variant'];
            if ( ! isset( $variants[ $key ] ) ) {
                $variants[ $key ] = array( 'impression' => 0, 'convert' => 0 );
            }
            $variants[ $key ][ $row['event'] ] = (int) $row['total'];
        }

        foreach ( $variants as $key => $data ) {
            $variants[ $key ]['conversion_rate'] = $data['impression'] > 0
                ? round( ( $data['convert'] / $data['impression'] ) * 100, 2 )
                : 0.0;
        }

        return $variants;
    }
}
