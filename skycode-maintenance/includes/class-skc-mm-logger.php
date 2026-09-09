<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Logger {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'skc_mm_log';
    }

    public static function record( $event, $detail = '' ) {
        global $wpdb;

        $wpdb->insert( self::table(), array(
            'event'      => sanitize_key( $event ),
            'detail'     => is_string( $detail ) ? $detail : wp_json_encode( $detail ),
            'user_id'    => get_current_user_id(),
            'created_at' => current_time( 'mysql' ),
        ) );
    }

    public static function recent( $limit = 50 ) {
        global $wpdb;
        $limit = absint( $limit );
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " ORDER BY id DESC LIMIT {$limit}",
            ARRAY_A
        );
    }
}
