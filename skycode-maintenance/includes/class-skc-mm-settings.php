<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Settings {

    public static function defaults() {
        return array(
            'mode'                => 'off', // off | coming_soon | maintenance
            'bypass_roles'        => array( 'administrator' ),
            'bypass_caps'         => array( 'manage_options' ),
            'allowed_ips'         => array(),
            'bypass_token_hash'   => '',
            'token_expires'       => 0,
            'excluded_paths'      => array(),
            'schedule_start'      => 0,
            'schedule_end'        => 0,
            'skin'                => 'minimal',
            'logo_id'             => 0,
            'bg_type'             => 'color', // color | image | video
            'bg_value'            => '#0f172a',
            'bg_value_mobile'     => '',
            'bg_poster'           => '',
            'accent'              => '#f97316',
            'text_color'          => '#ffffff',
            'headline'            => __( 'Estamos preparando algo nuevo', 'skycode-maintenance' ),
            'subtext'             => __( 'Volvemos pronto. Gracias por tu paciencia.', 'skycode-maintenance' ),
            'footer_text'         => '',
            'countdown_to'        => 0,
            'socials'             => array(),
            'custom_css'          => '',
            'custom_html'         => '',
            'seo_noindex'         => true,
            'retry_after'         => 3600,
            'subscribe_enabled'   => false,
            'subscribe_webhook'   => '',
            'delete_on_uninstall' => false,
        );
    }

    public static function all() {
        $stored = get_option( 'skc_mm_settings', array() );
        return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
    }

    public static function get( $key ) {
        $settings = self::all();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    public static function update( $values ) {
        $settings = self::all();
        $settings = array_merge( $settings, self::sanitize( $values ) );
        update_option( 'skc_mm_settings', $settings );
        return $settings;
    }

    /**
     * El modo real puede estar forzado por wp-config.php (útil para dejar
     * un entorno de staging siempre cerrado sin depender de la BD).
     */
    public static function current_mode() {
        if ( defined( 'SKC_MM_FORCE_MODE' ) && in_array( SKC_MM_FORCE_MODE, array( 'off', 'coming_soon', 'maintenance' ), true ) ) {
            return SKC_MM_FORCE_MODE;
        }
        return self::get( 'mode' );
    }

    public static function is_forced() {
        return defined( 'SKC_MM_FORCE_MODE' );
    }

    private static function sanitize( $values ) {
        $clean   = array();
        $allowed = array_keys( self::defaults() );

        foreach ( $values as $key => $value ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                continue;
            }

            switch ( $key ) {
                case 'mode':
                    $clean[ $key ] = in_array( $value, array( 'off', 'coming_soon', 'maintenance' ), true ) ? $value : 'off';
                    break;

                case 'bypass_roles':
                case 'bypass_caps':
                case 'excluded_paths':
                case 'socials':
                    $clean[ $key ] = array_values( array_filter( array_map( 'sanitize_text_field', (array) $value ) ) );
                    break;

                case 'allowed_ips':
                    $clean[ $key ] = array_values( array_filter( array_map( function ( $ip ) {
                        $ip = trim( sanitize_text_field( $ip ) );
                        return self::is_valid_ip_or_cidr( $ip ) ? $ip : '';
                    }, (array) $value ) ) );
                    break;

                case 'token_expires':
                case 'schedule_start':
                case 'schedule_end':
                case 'countdown_to':
                case 'retry_after':
                case 'logo_id':
                    $clean[ $key ] = absint( $value );
                    break;

                case 'seo_noindex':
                case 'subscribe_enabled':
                case 'delete_on_uninstall':
                    $clean[ $key ] = (bool) $value;
                    break;

                case 'accent':
                case 'text_color':
                    $clean[ $key ] = sanitize_hex_color( $value ) ? sanitize_hex_color( $value ) : '#000000';
                    break;

                case 'bg_type':
                    $clean[ $key ] = in_array( $value, array( 'color', 'image', 'video' ), true ) ? $value : 'color';
                    break;

                case 'bg_value':
                    $clean[ $key ] = in_array( $values['bg_type'] ?? '', array( 'image', 'video' ), true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
                    break;

                case 'bg_value_mobile':
                case 'bg_poster':
                    $clean[ $key ] = esc_url_raw( $value );
                    break;

                case 'skin':
                    $clean[ $key ] = sanitize_key( $value );
                    break;

                case 'subscribe_webhook':
                    $clean[ $key ] = esc_url_raw( $value );
                    break;

                case 'custom_css':
                    $clean[ $key ] = wp_strip_all_tags( $value );
                    break;

                case 'custom_html':
                    $clean[ $key ] = wp_kses_post( $value );
                    break;

                case 'headline':
                case 'subtext':
                case 'footer_text':
                    $clean[ $key ] = sanitize_text_field( $value );
                    break;

                case 'bypass_token_hash':
                    $clean[ $key ] = sanitize_text_field( $value );
                    break;

                default:
                    $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    public static function is_valid_ip_or_cidr( $value ) {
        if ( '' === $value ) {
            return false;
        }
        if ( strpos( $value, '/' ) !== false ) {
            list( $ip, $mask ) = explode( '/', $value, 2 );
            return filter_var( $ip, FILTER_VALIDATE_IP ) && is_numeric( $mask );
        }
        return (bool) filter_var( $value, FILTER_VALIDATE_IP );
    }

    public static function export() {
        $settings = self::all();
        unset( $settings['bypass_token_hash'], $settings['token_expires'] );
        return wp_json_encode( $settings, JSON_PRETTY_PRINT );
    }

    public static function import( $json ) {
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'skc_mm_invalid_json', __( 'El archivo no es un JSON válido.', 'skycode-maintenance' ) );
        }
        unset( $data['bypass_token_hash'], $data['token_expires'] );
        return self::update( $data );
    }
}
