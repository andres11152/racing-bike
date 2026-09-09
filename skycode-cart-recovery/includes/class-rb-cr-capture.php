<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Capture {

    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

        add_action( 'wc_ajax_rb_cr_capture', array( $this, 'ajax_capture' ) );
        add_action( 'wp_ajax_rb_cr_capture', array( $this, 'ajax_capture' ) );
        add_action( 'wp_ajax_nopriv_rb_cr_capture', array( $this, 'ajax_capture' ) );

        // Respaldo de servidor: si el JS no llegó a disparar, el email
        // de facturación se captura igual al revisar el checkout.
        add_action( 'woocommerce_checkout_update_order_review', array( $this, 'capture_from_checkout_review' ) );

        // Usuario logueado: se captura desde el primer add_to_cart, sin
        // esperar a que llegue al checkout.
        add_action( 'woocommerce_add_to_cart', array( $this, 'capture_logged_in' ) );

        // Mantiene el snapshot al día para carritos que ya tienen contacto,
        // así el correo de abandono muestra lo último que había en el carrito.
        add_action( 'woocommerce_cart_updated', array( $this, 'refresh_known_cart' ) );
    }

    public function refresh_known_cart() {
        RB_CR_Cart::upsert_from_session();
    }

    public function enqueue() {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
            return;
        }

        wp_enqueue_script(
            'rb-cr-capture',
            RB_CR_URL . 'assets/capture.js',
            array( 'jquery' ),
            RB_CR_VERSION,
            true
        );

        wp_localize_script( 'rb-cr-capture', 'rbCrCapture', array(
            'ajaxUrl' => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'rb_cr_capture' ) : admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rb_cr_capture' ),
        ) );
    }

    public function ajax_capture() {
        check_ajax_referer( 'rb_cr_capture', 'nonce' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $name  = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error();
        }

        $cart_id = RB_CR_Cart::upsert_from_session( array(
            'email'      => $email,
            'phone'      => $phone,
            'first_name' => $name,
            'consent'    => 'checkout',
        ) );

        wp_send_json_success( array( 'cart_id' => $cart_id ) );
    }

    public function capture_from_checkout_review( $post_data ) {
        parse_str( $post_data, $fields );

        $email = isset( $fields['billing_email'] ) ? sanitize_email( $fields['billing_email'] ) : '';
        if ( empty( $email ) || ! is_email( $email ) ) {
            return;
        }

        RB_CR_Cart::upsert_from_session( array(
            'email'      => $email,
            'phone'      => isset( $fields['billing_phone'] ) ? $fields['billing_phone'] : '',
            'first_name' => isset( $fields['billing_first_name'] ) ? sanitize_text_field( $fields['billing_first_name'] ) : '',
            'consent'    => 'checkout',
        ) );
    }

    public function capture_logged_in() {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() || empty( $user->user_email ) ) {
            return;
        }

        RB_CR_Cart::upsert_from_session( array(
            'email'      => $user->user_email,
            'first_name' => $user->first_name ?: $user->display_name,
            'consent'    => 'cuenta',
        ) );
    }
}
