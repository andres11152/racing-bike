<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Restore {

    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'maybe_handle_request' ) );
    }

    public static function url( $token ) {
        $settings = RB_CR_Settings::all();
        $target   = 'checkout' === $settings['recovery_redirect'] ? wc_get_checkout_url() : wc_get_cart_url();
        return add_query_arg( 'rb_cr', $token, $target );
    }

    public static function optout_url( $token ) {
        return add_query_arg( array( 'rb_cr_out' => $token ), home_url( '/' ) );
    }

    public function maybe_handle_request() {
        if ( isset( $_GET['rb_cr_out'] ) ) {
            $this->handle_optout( sanitize_text_field( wp_unslash( $_GET['rb_cr_out'] ) ) );
            return;
        }

        if ( isset( $_GET['rb_cr'] ) ) {
            $this->handle_recovery( sanitize_text_field( wp_unslash( $_GET['rb_cr'] ) ) );
        }
    }

    private function handle_recovery( $token ) {
        $row = RB_CR_Cart::find_by_token( $token );
        if ( ! $row || ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        RB_CR_Cart::update( $row['id'], array( 'clicked_at' => current_time( 'mysql' ) ) );
        RB_CR_Log::record( $row['id'], 'email', $row['step_sent'], 'clicked' );

        WC()->cart->empty_cart();

        $missing = array();
        foreach ( RB_CR_Cart::items( $row ) as $item ) {
            $product = wc_get_product( $item['variation_id'] ?: $item['product_id'] );

            if ( ! $product || ! $product->is_purchasable() || ( $product->managing_stock() && ! $product->is_in_stock() ) ) {
                $missing[] = $item['name'];
                continue;
            }

            WC()->cart->add_to_cart(
                $item['product_id'],
                $item['quantity'],
                $item['variation_id'] ?: 0,
                is_array( $item['variation'] ) ? $item['variation'] : array()
            );
        }

        if ( $missing ) {
            wc_add_notice(
                sprintf(
                    /* translators: %s: nombres de producto separados por coma */
                    __( 'Algunos productos de tu carrito ya no están disponibles y no se agregaron: %s', 'skycode-cart-recovery' ),
                    esc_html( implode( ', ', $missing ) )
                ),
                'notice'
            );
        }

        if ( ! empty( $row['email'] ) && function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( 'billing_email', $row['email'] );
        }

        setcookie( 'rb_cr_token', $token, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN );

        $settings = RB_CR_Settings::all();
        $target   = 'checkout' === $settings['recovery_redirect'] ? wc_get_checkout_url() : wc_get_cart_url();

        wp_safe_redirect( remove_query_arg( 'rb_cr', $target ) );
        exit;
    }

    private function handle_optout( $token ) {
        $row = RB_CR_Cart::find_by_token( $token );
        if ( $row ) {
            RB_CR_Optout::add( $row['email'], $row['phone'] );
            RB_CR_Cart::update( $row['id'], array( 'status' => 'unsubscribed', 'next_send_at' => null ) );
            RB_CR_Log::record( $row['id'], 'email', $row['step_sent'], 'unsubscribed' );
        }

        wp_die(
            '<p style="font-family:sans-serif;">' . esc_html__( 'Listo, no volverás a recibir estos avisos.', 'skycode-cart-recovery' ) . '</p>',
            esc_html__( 'Baja confirmada', 'skycode-cart-recovery' ),
            array( 'response' => 200 )
        );
    }
}
