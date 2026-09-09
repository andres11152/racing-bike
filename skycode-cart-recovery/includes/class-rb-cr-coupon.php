<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Coupon {

    /**
     * Emite un cupón de un solo uso, restringido al email del carrito,
     * en el momento del envío — nunca antes, para no regalar descuentos
     * que después no se usan.
     */
    public static function issue( array $cart_row, array $step ) {
        if ( ! function_exists( 'wc_get_product' ) || empty( $cart_row['email'] ) ) {
            return '';
        }

        $percent = isset( $step['coupon_percent'] ) ? (float) $step['coupon_percent'] : 10;
        $hours   = isset( $step['coupon_hours'] ) ? (int) $step['coupon_hours'] : 48;

        $code = 'VUELVE' . strtoupper( substr( $cart_row['token'], 0, 6 ) ) . (int) $step['step'];

        $existing_id = wc_get_coupon_id_by_code( $code );
        if ( $existing_id ) {
            return $code;
        }

        $coupon = array(
            'post_title'  => $code,
            'post_type'   => 'shop_coupon',
            'post_status' => 'publish',
        );

        $coupon_id = wp_insert_post( $coupon );
        if ( is_wp_error( $coupon_id ) || ! $coupon_id ) {
            return '';
        }

        update_post_meta( $coupon_id, 'discount_type', 'percent' );
        update_post_meta( $coupon_id, 'coupon_amount', $percent );
        update_post_meta( $coupon_id, 'individual_use', 'yes' );
        update_post_meta( $coupon_id, 'usage_limit', 1 );
        update_post_meta( $coupon_id, 'customer_email', array( $cart_row['email'] ) );
        update_post_meta( $coupon_id, 'date_expires', strtotime( "+{$hours} hours" ) );

        return $code;
    }
}
