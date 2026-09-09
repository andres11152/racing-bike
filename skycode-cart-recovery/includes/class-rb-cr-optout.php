<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bajas por email y teléfono. Se consulta antes de cualquier envío, de
 * cualquier canal, así que vive en su propia tabla en vez de una columna
 * en wp_rb_cr_carts: un mismo email puede tener varios carritos.
 */
class RB_CR_Optout {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'rb_cr_optouts';
    }

    public static function add( $email = '', $phone = '' ) {
        global $wpdb;

        if ( empty( $email ) && empty( $phone ) ) {
            return false;
        }

        if ( self::is_opted_out( $email, $phone ) ) {
            return true;
        }

        return $wpdb->insert( self::table(), array(
            'email'      => sanitize_email( $email ),
            'phone'      => $phone ? RB_CR_Cart::normalize_phone( $phone ) : '',
            'created_at' => current_time( 'mysql' ),
        ) );
    }

    public static function is_opted_out( $email = '', $phone = '' ) {
        global $wpdb;

        if ( empty( $email ) && empty( $phone ) ) {
            return false;
        }

        $where  = array();
        $params = array();

        if ( $email ) {
            $where[]  = 'email = %s';
            $params[] = sanitize_email( $email );
        }
        if ( $phone ) {
            $where[]  = 'phone = %s';
            $params[] = RB_CR_Cart::normalize_phone( $phone );
        }

        $sql = "SELECT COUNT(*) FROM " . self::table() . " WHERE " . implode( ' OR ', $where );
        return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    }
}
