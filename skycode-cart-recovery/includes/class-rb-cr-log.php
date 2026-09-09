<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Log {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'rb_cr_events';
    }

    public static function record( $cart_id, $channel, $step, $event, $detail = '' ) {
        global $wpdb;

        $wpdb->insert( self::table(), array(
            'cart_id'    => (int) $cart_id,
            'channel'    => sanitize_key( $channel ),
            'step'       => (int) $step,
            'event'      => sanitize_key( $event ),
            'detail'     => is_string( $detail ) ? $detail : wp_json_encode( $detail ),
            'created_at' => current_time( 'mysql' ),
        ) );
    }

    public static function recent( $limit = 50 ) {
        global $wpdb;
        $limit = absint( $limit );
        return $wpdb->get_results(
            "SELECT e.*, c.email, c.phone FROM " . self::table() . " e
             LEFT JOIN " . RB_CR_Cart::table() . " c ON c.id = e.cart_id
             ORDER BY e.id DESC LIMIT {$limit}",
            ARRAY_A
        );
    }

    public static function for_cart( $cart_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE cart_id = %d ORDER BY id DESC",
            $cart_id
        ), ARRAY_A );
    }

    public static function cleanup( $days ) {
        global $wpdb;
        $days = max( 1, absint( $days ) * 2 ); // Los eventos se conservan el doble que los carritos.
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::table() . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );
    }
}
