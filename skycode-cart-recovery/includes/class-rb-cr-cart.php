<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modelo de acceso a wp_rb_cr_carts. Cada fila es un carrito en curso o
 * abandonado; una vez recuperado o marcado como perdido deja de ser
 * candidato del cron.
 */
class RB_CR_Cart {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'rb_cr_carts';
    }

    /**
     * Snapshot ligero del carrito actual de WooCommerce: lo mínimo para
     * mostrar miniatura, nombre, cantidad y precio en el correo, y para
     * reconstruirlo en woocommerce_cart_loaded_from_session-like.
     */
    public static function snapshot_current_cart() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return array();
        }

        $items = array();
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            if ( ! $product instanceof WC_Product ) {
                continue;
            }

            $items[] = array(
                'product_id'   => $cart_item['product_id'],
                'variation_id' => $cart_item['variation_id'],
                'variation'    => $cart_item['variation'],
                'quantity'     => $cart_item['quantity'],
                'name'         => $product->get_name(),
                'price'        => (float) $product->get_price(),
                'image'        => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src(),
                'permalink'    => get_permalink( $product->get_id() ),
            );
        }

        return $items;
    }

    private static function current_session_key() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return '';
        }
        $key = WC()->session->get_customer_id();
        return $key ? substr( (string) $key, 0, 64 ) : '';
    }

    /**
     * Crea o actualiza el carrito "activo" ligado a la sesión actual.
     * Se llama en cada captura de contacto y en cada cambio del carrito
     * para que el snapshot y el total nunca queden desactualizados.
     */
    public static function upsert_from_session( array $contact = array() ) {
        global $wpdb;

        $session_key = self::current_session_key();
        if ( ! $session_key ) {
            return 0;
        }

        $items = self::snapshot_current_cart();
        if ( empty( $items ) ) {
            return 0;
        }

        $total = 0;
        foreach ( $items as $item ) {
            $total += $item['price'] * $item['quantity'];
        }

        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE session_key = %s AND status IN ('active','abandoned','recovering') ORDER BY id DESC LIMIT 1",
            $session_key
        ), ARRAY_A );

        $now = current_time( 'mysql' );

        $data = array(
            'session_key' => $session_key,
            'user_id'     => get_current_user_id(),
            'cart_json'   => wp_json_encode( $items ),
            'total'       => $total,
            'currency'    => get_woocommerce_currency(),
            'updated_at'  => $now,
        );

        if ( ! empty( $contact['email'] ) ) {
            $data['email'] = sanitize_email( $contact['email'] );
        }
        if ( ! empty( $contact['phone'] ) ) {
            $data['phone'] = self::normalize_phone( $contact['phone'] );
        }
        if ( ! empty( $contact['first_name'] ) ) {
            $data['first_name'] = sanitize_text_field( $contact['first_name'] );
        }
        if ( ! empty( $contact['consent'] ) ) {
            $data['consent'] = sanitize_text_field( $contact['consent'] );
        }

        if ( $existing ) {
            $data['status'] = 'active';
            $wpdb->update( self::table(), $data, array( 'id' => $existing['id'] ) );
            return (int) $existing['id'];
        }

        if ( empty( $data['email'] ) ) {
            // Sin contacto todavía no vale la pena crear la fila: se crea
            // en cuanto llegue el primer email o teléfono capturado.
            return 0;
        }

        $data['token']      = self::generate_token();
        $data['status']     = 'active';
        $data['created_at'] = $now;

        $wpdb->insert( self::table(), $data );
        return (int) $wpdb->insert_id;
    }

    public static function normalize_phone( $phone ) {
        $digits = preg_replace( '/[^0-9]/', '', (string) $phone );
        if ( '' === $digits ) {
            return '';
        }
        if ( strlen( $digits ) === 10 && '3' === $digits[0] ) {
            return '+57' . $digits;
        }
        if ( strlen( $digits ) === 12 && 0 === strpos( $digits, '57' ) ) {
            return '+' . $digits;
        }
        if ( '+' === substr( (string) $phone, 0, 1 ) ) {
            return '+' . $digits;
        }
        return $digits;
    }

    public static function generate_token() {
        return substr( str_replace( array( '-', '.' ), '', wp_generate_password( 40, false, false ) ), 0, 32 );
    }

    public static function find( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", $id ), ARRAY_A );
    }

    public static function find_by_token( $token ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE token = %s", $token ), ARRAY_A );
    }

    public static function find_open_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE email = %s AND status IN ('abandoned','recovering') ORDER BY id DESC LIMIT 1",
            $email
        ), ARRAY_A );
    }

    public static function update( $id, array $data ) {
        global $wpdb;
        $data['updated_at'] = current_time( 'mysql' );
        return $wpdb->update( self::table(), $data, array( 'id' => $id ) );
    }

    public static function items( array $cart_row ) {
        $items = json_decode( $cart_row['cart_json'] ?? '[]', true );
        return is_array( $items ) ? $items : array();
    }
}
