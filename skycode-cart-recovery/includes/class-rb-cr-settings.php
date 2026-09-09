<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Settings {

    public static function defaults() {
        return array(
            'threshold_minutes'    => 60,
            'attribution_days'     => 7,
            'send_start_hour'      => 8,
            'send_end_hour'        => 20,
            'min_cart_value'       => 0,
            'retention_days'       => 90,
            'test_mode'            => false,
            'test_email'           => get_option( 'admin_email' ),
            'delete_on_uninstall'  => false,
            'from_email'           => get_option( 'admin_email' ),
            'from_name'            => get_bloginfo( 'name' ),
            'recovery_redirect'    => 'cart', // 'cart' o 'checkout'
        );
    }

    public static function all() {
        $stored = get_option( 'rb_cr_settings', array() );
        return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
    }

    public static function get( $key ) {
        $settings = self::all();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    public static function update( $values ) {
        $settings = self::all();
        $settings = array_merge( $settings, $values );
        update_option( 'rb_cr_settings', $settings );
        return $settings;
    }

    /**
     * La secuencia por defecto. Se guarda por separado de las demás
     * opciones para que el editor de pasos pueda reordenar sin arrastrar
     * el resto de los ajustes.
     */
    public static function default_sequence() {
        return array(
            array(
                'step'            => 1,
                'delay_hours'     => 1,
                'channel'         => 'email',
                'subject'         => 'Se te quedó algo en el carrito 🚲',
                'template'        => 'step-1',
                'coupon_enabled'  => false,
                'coupon_percent'  => 10,
                'coupon_hours'    => 48,
            ),
            array(
                'step'            => 2,
                'delay_hours'     => 24,
                'channel'         => 'email',
                'subject'         => '¿Dudas con la talla o el envío?',
                'template'        => 'step-2',
                'coupon_enabled'  => false,
                'coupon_percent'  => 10,
                'coupon_hours'    => 48,
            ),
            array(
                'step'            => 3,
                'delay_hours'     => 72,
                'channel'         => 'email',
                'subject'         => 'Tu cupón vence en 48 horas',
                'template'        => 'step-3',
                'coupon_enabled'  => false,
                'coupon_percent'  => 10,
                'coupon_hours'    => 48,
            ),
        );
    }

    public static function sequence() {
        $stored = get_option( 'rb_cr_sequence', array() );
        if ( ! is_array( $stored ) || empty( $stored ) ) {
            return self::default_sequence();
        }
        return $stored;
    }

    public static function update_sequence( array $sequence ) {
        update_option( 'rb_cr_sequence', array_values( $sequence ) );
        return $sequence;
    }

    public static function step( $number ) {
        foreach ( self::sequence() as $step ) {
            if ( (int) $step['step'] === (int) $number ) {
                return $step;
            }
        }
        return null;
    }

    public static function max_step() {
        $sequence = self::sequence();
        return empty( $sequence ) ? 0 : max( wp_list_pluck( $sequence, 'step' ) );
    }
}
