<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Settings {

    public static function defaults() {
        return array(
            'max_active_modals_per_page' => 1,
            'global_frequency_cap'       => 3,      // impresiones máx. por visitante y día, todas las campañas.
            'honeypot_field'              => 'skc_md_hp',
            'min_fill_seconds'            => 2,      // anti-bot: rechaza envíos más rápidos que esto.
            'rate_limit_per_hour'         => 8,
            'events_retention_days'       => 90,
            'watermark_enabled'           => true,
            'watermark_logo'              => '',   // Vacío = autodetectar (logo del sitio o del tema).
            'watermark_size'              => 40,   // Alto en px.
            'default_webhook'             => '',
            'gdpr_consent_text'           => __( 'Acepto la política de privacidad.', 'skycode-modal' ),
            'gdpr_require_consent'        => true,
            'respect_reduced_motion'      => true,
            'delete_on_uninstall'         => false,
        );
    }

    public static function all() {
        $stored = get_option( 'skc_md_settings', array() );
        return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
    }

    public static function get( $key ) {
        $settings = self::all();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    public static function update( $values ) {
        $settings = self::all();
        $settings = array_merge( $settings, self::sanitize( $values ) );
        update_option( 'skc_md_settings', $settings );
        return $settings;
    }

    private static function sanitize( $values ) {
        $clean   = array();
        $allowed = array_keys( self::defaults() );

        foreach ( $values as $key => $value ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                continue;
            }

            switch ( $key ) {
                case 'max_active_modals_per_page':
                case 'global_frequency_cap':
                case 'min_fill_seconds':
                case 'rate_limit_per_hour':
                case 'events_retention_days':
                case 'watermark_size':
                    $clean[ $key ] = absint( $value );
                    break;

                case 'gdpr_require_consent':
                case 'respect_reduced_motion':
                case 'watermark_enabled':
                case 'delete_on_uninstall':
                    $clean[ $key ] = (bool) $value;
                    break;

                case 'default_webhook':
                case 'watermark_logo':
                    $clean[ $key ] = esc_url_raw( $value );
                    break;

                case 'honeypot_field':
                    $clean[ $key ] = sanitize_key( $value );
                    break;

                case 'gdpr_consent_text':
                    $clean[ $key ] = wp_kses_post( $value );
                    break;

                default:
                    $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    /**
     * URL del logo que se superpone sobre la imagen del modal.
     *
     * Orden de resolución, para que el plugin siga siendo genérico:
     *   1. El logo elegido en Ajustes.
     *   2. El logo del sitio de WordPress (Personalizador).
     *   3. Un logo del tema activo, si existe alguno de los nombres habituales.
     *
     * Devuelve '' si no hay logo o si la marca está desactivada.
     */
    public static function watermark_url() {
        if ( ! self::get( 'watermark_enabled' ) ) {
            return '';
        }

        $configured = self::get( 'watermark_logo' );
        if ( $configured ) {
            return $configured;
        }

        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $url = wp_get_attachment_image_url( $custom_logo_id, 'medium' );
            if ( $url ) {
                return $url;
            }
        }

        foreach ( array(
            'public/images/logo-blanco.svg',
            'public/images/logo-blanco.png',
            'assets/images/logo-blanco.svg',
            'assets/images/logo.svg',
            'assets/images/logo.png',
        ) as $candidate ) {
            if ( file_exists( get_theme_file_path( $candidate ) ) ) {
                return get_theme_file_uri( $candidate );
            }
        }

        return '';
    }

    /**
     * Permite a otros proyectos sustituir el logo sin tocar el plugin.
     */
    public static function watermark() {
        return apply_filters( 'skc_md_watermark_url', self::watermark_url() );
    }
}
