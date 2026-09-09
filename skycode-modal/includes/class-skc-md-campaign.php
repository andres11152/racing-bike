<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cada campaña es un post del CPT `skc_md_campaign`. Toda su configuración
 * vive en un único meta `_skc_md_config` (array asociativo), siguiendo el
 * mismo patrón defaults+sanitize que SKC_MD_Settings / SKC_MM_Settings.
 */
class SKC_MD_Campaign {

    const POST_TYPE = 'skc_md_campaign';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
    }

    public function register_post_type() {
        register_post_type( self::POST_TYPE, array(
            'label'           => __( 'Campañas de modal', 'skycode-modal' ),
            'public'          => false,
            'show_ui'         => false, // gestión propia en includes/admin
            'show_in_menu'    => false,
            'supports'        => array( 'title' ),
            'capability_type' => 'page',
            'map_meta_cap'    => true,
        ) );
    }

    public static function defaults() {
        return array(
            'status'               => 'paused', // paused | active
            'priority'             => 10,
            'skin'                 => 'center', // center | slide-in | bottom-bar | fullscreen | sidebar
            'headline'             => __( '¡No te lo pierdas!', 'skycode-modal' ),
            'subtext'              => __( 'Suscríbete y recibe novedades.', 'skycode-modal' ),
            'button_text'          => __( 'Suscribirme', 'skycode-modal' ),
            'image_id'             => 0,
            'accent'               => '#f97316',
            'bg_color'             => '#ffffff',
            'text_color'           => '#111827',

            'fields_name'          => false,
            'fields_phone'         => false,
            'require_consent'      => true,
            'consent_text'         => '',

            'trigger_type'         => 'time_delay', // time_delay | scroll_percent | exit_intent | click_selector | inactivity | page_count | on_load
            'trigger_value'        => 5,
            'trigger_selector'     => '',

            'freq_max_impressions' => 1,
            'freq_cooldown_days'   => 7,
            'freq_hide_on_convert' => true,

            'target_pages'         => 'all', // all | include | exclude
            'target_post_ids'      => array(),
            'target_device'        => 'all', // all | desktop | mobile
            'target_user_state'    => 'all', // all | guest | logged_in
            'target_roles'         => array(),
            'target_first_visit'   => 'all', // all | first_time | returning
            'target_utm_source'    => '',

            'schedule_start'       => 0,
            'schedule_end'         => 0,

            'variants'             => array(), // A/B: array de { key, label, weight, headline, subtext, button_text }

            'webhook'               => '',
            'redirect_after_submit' => '',
        );
    }

    public static function get_config( $post_id ) {
        $stored = get_post_meta( $post_id, '_skc_md_config', true );
        return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
    }

    public static function save_config( $post_id, $values ) {
        $config = array_merge( self::get_config( $post_id ), self::sanitize( $values ) );
        update_post_meta( $post_id, '_skc_md_config', $config );
        return $config;
    }

    public static function all_campaigns( $status = null ) {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => array( 'publish', 'draft' ),
        );

        $posts = get_posts( $args );

        if ( null === $status ) {
            return $posts;
        }

        return array_values( array_filter( $posts, function ( $post ) use ( $status ) {
            $config = self::get_config( $post->ID );
            return $config['status'] === $status;
        } ) );
    }

    public static function create( $title = '' ) {
        $post_id = wp_insert_post( array(
            'post_type'   => self::POST_TYPE,
            'post_title'  => $title ? $title : __( 'Nueva campaña', 'skycode-modal' ),
            'post_status' => 'publish',
        ) );

        if ( ! is_wp_error( $post_id ) && $post_id ) {
            update_post_meta( $post_id, '_skc_md_config', self::defaults() );
        }

        return $post_id;
    }

    public static function duplicate( $post_id ) {
        $source = get_post( $post_id );
        if ( ! $source || self::POST_TYPE !== $source->post_type ) {
            return false;
        }

        $new_id = wp_insert_post( array(
            'post_type'   => self::POST_TYPE,
            /* translators: %s: original campaign title. */
            'post_title'  => sprintf( __( '%s (copia)', 'skycode-modal' ), $source->post_title ),
            'post_status' => 'draft',
        ) );

        if ( is_wp_error( $new_id ) || ! $new_id ) {
            return false;
        }

        $config           = self::get_config( $post_id );
        $config['status'] = 'paused';
        update_post_meta( $new_id, '_skc_md_config', $config );

        return $new_id;
    }

    private static function sanitize( $values ) {
        $clean   = array();
        $allowed = array_keys( self::defaults() );

        foreach ( $values as $key => $value ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                continue;
            }

            switch ( $key ) {
                case 'status':
                    $clean[ $key ] = in_array( $value, array( 'active', 'paused' ), true ) ? $value : 'paused';
                    break;

                case 'skin':
                    $clean[ $key ] = in_array( $value, array( 'center', 'slide-in', 'bottom-bar', 'fullscreen', 'sidebar' ), true ) ? $value : 'center';
                    break;

                case 'trigger_type':
                    $clean[ $key ] = in_array( $value, array( 'time_delay', 'scroll_percent', 'exit_intent', 'click_selector', 'inactivity', 'page_count', 'on_load' ), true ) ? $value : 'time_delay';
                    break;

                case 'trigger_selector':
                    $clean[ $key ] = sanitize_text_field( $value );
                    break;

                case 'target_pages':
                    $clean[ $key ] = in_array( $value, array( 'all', 'include', 'exclude' ), true ) ? $value : 'all';
                    break;

                case 'target_device':
                    $clean[ $key ] = in_array( $value, array( 'all', 'desktop', 'mobile' ), true ) ? $value : 'all';
                    break;

                case 'target_user_state':
                    $clean[ $key ] = in_array( $value, array( 'all', 'guest', 'logged_in' ), true ) ? $value : 'all';
                    break;

                case 'target_first_visit':
                    $clean[ $key ] = in_array( $value, array( 'all', 'first_time', 'returning' ), true ) ? $value : 'all';
                    break;

                case 'target_post_ids':
                case 'target_roles':
                    $clean[ $key ] = array_values( array_filter( array_map( 'absint', (array) $value ) ) );
                    if ( 'target_roles' === $key ) {
                        $clean[ $key ] = array_values( array_filter( array_map( 'sanitize_key', (array) $value ) ) );
                    }
                    break;

                case 'priority':
                case 'trigger_value':
                case 'freq_max_impressions':
                case 'freq_cooldown_days':
                case 'image_id':
                case 'schedule_start':
                case 'schedule_end':
                    $clean[ $key ] = absint( $value );
                    break;

                case 'fields_name':
                case 'fields_phone':
                case 'require_consent':
                case 'freq_hide_on_convert':
                    $clean[ $key ] = (bool) $value;
                    break;

                case 'accent':
                case 'bg_color':
                case 'text_color':
                    $hex = sanitize_hex_color( $value );
                    if ( ! $hex && is_string( $value ) && '' !== $value && '#' !== $value[0] ) {
                        $hex = sanitize_hex_color( '#' . $value );
                    }
                    // Si sigue sin ser un hex válido, no se toca: save_config() hace
                    // merge sobre la config existente, así que el valor previo se conserva.
                    if ( $hex ) {
                        $clean[ $key ] = $hex;
                    }
                    break;

                case 'webhook':
                    $clean[ $key ] = esc_url_raw( $value );
                    break;

                case 'redirect_after_submit':
                    $clean[ $key ] = $value ? esc_url_raw( $value ) : '';
                    break;

                case 'consent_text':
                    $clean[ $key ] = wp_kses_post( $value );
                    break;

                case 'target_utm_source':
                    $clean[ $key ] = sanitize_text_field( $value );
                    break;

                case 'variants':
                    $clean[ $key ] = self::sanitize_variants( $value );
                    break;

                default:
                    $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    private static function sanitize_variants( $variants ) {
        if ( ! is_array( $variants ) ) {
            return array();
        }

        $clean = array();

        foreach ( $variants as $variant ) {
            if ( ! is_array( $variant ) || empty( $variant['key'] ) ) {
                continue;
            }

            $clean[] = array(
                'key'         => sanitize_key( $variant['key'] ),
                'label'       => sanitize_text_field( $variant['label'] ?? '' ),
                'weight'      => max( 1, absint( $variant['weight'] ?? 1 ) ),
                'headline'    => sanitize_text_field( $variant['headline'] ?? '' ),
                'subtext'     => sanitize_text_field( $variant['subtext'] ?? '' ),
                'button_text' => sanitize_text_field( $variant['button_text'] ?? '' ),
            );
        }

        return $clean;
    }
}
