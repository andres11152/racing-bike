<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Channel_Email implements RB_CR_Channel_Interface {

    public function slug() {
        return 'email';
    }

    public function label() {
        return __( 'Email', 'skycode-cart-recovery' );
    }

    public function is_ready() {
        return true;
    }

    public function can_reach( array $cart_row ) {
        return ! empty( $cart_row['email'] ) && is_email( $cart_row['email'] );
    }

    public function send( array $cart_row, array $step ) {
        $settings = RB_CR_Settings::all();

        $to = $settings['test_mode'] ? $settings['test_email'] : $cart_row['email'];
        if ( empty( $to ) || ! is_email( $to ) ) {
            return false;
        }

        $coupon_code = '';
        if ( ! empty( $step['coupon_enabled'] ) ) {
            $coupon_code = RB_CR_Coupon::issue( $cart_row, $step );
            if ( $coupon_code ) {
                RB_CR_Cart::update( $cart_row['id'], array( 'coupon_code' => $coupon_code ) );
            }
        }

        $subject = self::render_string( $step['subject'], $cart_row, $coupon_code );
        // El logo va incrustado como CID, no enlazado por URL: así el correo
        // se ve igual sin importar si el sitio es alcanzable desde fuera
        // (en local, Gmail nunca podría cargar una imagen de localhost).
        $body    = self::render_template( $step['template'], $cart_row, $step, $coupon_code, 'cid:rb-cr-logo' );

        add_filter( 'wp_mail_content_type', array( __CLASS__, 'html_content_type' ) );
        add_action( 'phpmailer_init', array( __CLASS__, 'embed_logo' ) );

        $headers = array();
        if ( ! empty( $settings['from_name'] ) && ! empty( $settings['from_email'] ) ) {
            $headers[] = sprintf( 'From: %s <%s>', $settings['from_name'], $settings['from_email'] );
        }

        $sent = wp_mail( $to, $subject, $body, $headers );

        remove_filter( 'wp_mail_content_type', array( __CLASS__, 'html_content_type' ) );
        remove_action( 'phpmailer_init', array( __CLASS__, 'embed_logo' ) );

        return $sent;
    }

    public static function embed_logo( $phpmailer ) {
        $path = RB_CR_DIR . 'assets/images/logo.png';
        if ( file_exists( $path ) ) {
            $phpmailer->addEmbeddedImage( $path, 'rb-cr-logo', 'logo.png' );
        }
    }

    public static function html_content_type() {
        return 'text/html';
    }

    public static function render_string( $string, array $cart_row, $coupon_code = '' ) {
        $items = RB_CR_Cart::items( $cart_row );
        $product_names = wp_list_pluck( $items, 'name' );

        $replacements = array(
            '{{nombre}}'    => $cart_row['first_name'] ?: __( 'hola', 'skycode-cart-recovery' ),
            '{{productos}}' => implode( ', ', $product_names ),
            '{{total}}'     => function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $cart_row['total'], array( 'currency' => $cart_row['currency'] ) ) ) : $cart_row['total'],
            '{{cupon}}'     => $coupon_code,
        );

        return strtr( (string) $string, $replacements );
    }

    public static function render_template( $template, array $cart_row, array $step, $coupon_code, $logo_src = null ) {
        $file = RB_CR_DIR . 'templates/emails/' . sanitize_file_name( $template ) . '.php';
        if ( ! file_exists( $file ) ) {
            $file = RB_CR_DIR . 'templates/emails/step-1.php';
        }

        $recovery_url = RB_CR_Restore::url( $cart_row['token'] );
        $optout_url   = RB_CR_Restore::optout_url( $cart_row['token'] );
        $items        = RB_CR_Cart::items( $cart_row );
        $settings     = RB_CR_Settings::all();
        // En un correo real el logo va como CID incrustado (ver send()); en
        // la vista previa del admin no hay CID posible, así que se usa la
        // URL directa, que el navegador sí puede resolver.
        $logo_src     = $logo_src ?: RB_CR_URL . 'assets/images/logo.png';

        ob_start();
        include $file;
        $content = ob_get_clean();

        ob_start();
        include RB_CR_DIR . 'templates/emails/base.php';
        $wrapped = ob_get_clean();

        return $wrapped ?: $content;
    }
}
