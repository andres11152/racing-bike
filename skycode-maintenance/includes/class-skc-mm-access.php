<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Access {

    const COOKIE_NAME = 'skc_mm_bypass';

    public static function is_allowed() {
        $allowed = self::user_role_allowed()
            || self::ip_allowed()
            || self::cookie_allowed()
            || self::token_in_request()
            || self::path_excluded();

        return (bool) apply_filters( 'skc_mm_is_allowed', $allowed );
    }

    private static function user_role_allowed() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        $settings = SKC_MM_Settings::all();

        foreach ( (array) $settings['bypass_roles'] as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                return true;
            }
        }

        foreach ( (array) $settings['bypass_caps'] as $cap ) {
            if ( user_can( $user, $cap ) ) {
                return true;
            }
        }

        return false;
    }

    private static function ip_allowed() {
        $ip = self::get_client_ip();
        if ( '' === $ip ) {
            return false;
        }

        foreach ( (array) SKC_MM_Settings::get( 'allowed_ips' ) as $entry ) {
            if ( self::ip_matches( $ip, $entry ) ) {
                return true;
            }
        }

        return false;
    }

    public static function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        if ( defined( 'SKC_MM_TRUST_PROXY' ) && SKC_MM_TRUST_PROXY && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $parts     = explode( ',', $forwarded );
            $candidate = trim( $parts[0] );
            if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
                $ip = $candidate;
            }
        }

        return $ip;
    }

    private static function ip_matches( $ip, $entry ) {
        if ( strpos( $entry, '/' ) === false ) {
            return $ip === $entry;
        }

        list( $subnet, $mask ) = explode( '/', $entry, 2 );

        if ( strpos( $ip, ':' ) !== false || strpos( $subnet, ':' ) !== false ) {
            return self::ipv6_in_cidr( $ip, $subnet, (int) $mask );
        }

        $ip_long     = ip2long( $ip );
        $subnet_long = ip2long( $subnet );
        if ( false === $ip_long || false === $subnet_long ) {
            return false;
        }

        $mask = (int) $mask;
        if ( $mask <= 0 ) {
            return true;
        }
        $mask_long = -1 << ( 32 - $mask );
        return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
    }

    private static function ipv6_in_cidr( $ip, $subnet, $mask ) {
        $ip_bin     = @inet_pton( $ip );
        $subnet_bin = @inet_pton( $subnet );
        if ( false === $ip_bin || false === $subnet_bin ) {
            return false;
        }

        $bytes = intdiv( $mask, 8 );
        $bits  = $mask % 8;

        if ( $bytes > 0 && substr( $ip_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
            return false;
        }

        if ( 0 === $bits ) {
            return true;
        }

        $mask_byte = chr( ( 0xFF << ( 8 - $bits ) ) & 0xFF );
        return ( $ip_bin[ $bytes ] & $mask_byte ) === ( $subnet_bin[ $bytes ] & $mask_byte );
    }

    private static function cookie_allowed() {
        if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            return false;
        }

        $value     = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
        $hash      = SKC_MM_Settings::get( 'bypass_token_hash' );
        $expires   = (int) SKC_MM_Settings::get( 'token_expires' );

        if ( '' === $hash ) {
            return false;
        }
        if ( $expires && time() > $expires ) {
            return false;
        }

        return wp_check_password( $value, $hash );
    }

    private static function token_in_request() {
        if ( empty( $_GET['skc_access'] ) ) {
            return false;
        }

        $token   = sanitize_text_field( wp_unslash( $_GET['skc_access'] ) );
        $hash    = SKC_MM_Settings::get( 'bypass_token_hash' );
        $expires = (int) SKC_MM_Settings::get( 'token_expires' );

        if ( '' === $hash || ! wp_check_password( $token, $hash ) ) {
            return false;
        }
        if ( $expires && time() > $expires ) {
            return false;
        }

        $cookie_expiry = $expires ? $expires : ( time() + DAY_IN_SECONDS );
        setcookie( self::COOKIE_NAME, $token, array(
            'expires'  => $cookie_expiry,
            'path'     => '/',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ) );

        return true;
    }

    private static function path_excluded() {
        $request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $excluded_paths = (array) SKC_MM_Settings::get( 'excluded_paths' );

        foreach ( $excluded_paths as $pattern ) {
            $pattern = trim( $pattern );
            if ( '' === $pattern ) {
                continue;
            }
            if ( false !== strpos( $request_uri, $pattern ) ) {
                return true;
            }
        }

        return false;
    }

    public static function generate_token() {
        $token = wp_generate_password( 32, false );
        SKC_MM_Settings::update( array(
            'bypass_token_hash' => wp_hash_password( $token ),
        ) );
        return $token;
    }

    public static function revoke_token() {
        SKC_MM_Settings::update( array(
            'bypass_token_hash' => '',
            'token_expires'     => 0,
        ) );
    }
}
