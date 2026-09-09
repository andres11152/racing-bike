<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Leads {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_skc_md_submit_lead', array( $this, 'handle_submit' ) );
        add_action( 'wp_ajax_nopriv_skc_md_submit_lead', array( $this, 'handle_submit' ) );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'skc_md_leads';
    }

    public function handle_submit() {
        check_ajax_referer( 'skc_md_lead', 'skc_md_nonce' );

        $campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
        $config      = $campaign_id ? SKC_MD_Campaign::get_config( $campaign_id ) : null;

        if ( ! $campaign_id || ! $config || SKC_MD_Campaign::POST_TYPE !== get_post_type( $campaign_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Campaña no válida.', 'skycode-modal' ) ), 400 );
        }

        $honeypot_field = SKC_MD_Settings::get( 'honeypot_field' );
        if ( ! empty( $_POST[ $honeypot_field ] ) ) {
            wp_send_json_success( array( 'message' => __( 'Gracias.', 'skycode-modal' ) ) );
        }

        $started_at = isset( $_POST['started_at'] ) ? (int) $_POST['started_at'] : 0;
        $min_fill   = (int) SKC_MD_Settings::get( 'min_fill_seconds' ) * 1000;
        if ( $started_at && ( time() * 1000 - $started_at ) < $min_fill ) {
            wp_send_json_success( array( 'message' => __( 'Gracias.', 'skycode-modal' ) ) );
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Introduce un correo válido.', 'skycode-modal' ) ), 400 );
        }

        if ( ! empty( $config['require_consent'] ) && empty( $_POST['consent'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Debes aceptar la política de privacidad.', 'skycode-modal' ) ), 400 );
        }

        $ip       = self::get_client_ip();
        $rate_key = 'skc_md_rate_' . md5( $ip );
        $attempts = (int) get_transient( $rate_key );
        $limit    = (int) SKC_MD_Settings::get( 'rate_limit_per_hour' );

        if ( $limit > 0 && $attempts >= $limit ) {
            wp_send_json_error( array( 'message' => __( 'Demasiados intentos, prueba más tarde.', 'skycode-modal' ) ), 429 );
        }
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

        $lead = array(
            'campaign_id' => $campaign_id,
            'email'       => $email,
            'name'        => ! empty( $config['fields_name'] ) && isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'phone'       => ! empty( $config['fields_phone'] ) && isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
            'consent'     => empty( $_POST['consent'] ) ? 0 : 1,
            'source_url'  => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
            'ip'          => $ip,
            'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        );

        $inserted = $this->insert( $lead );

        if ( $inserted ) {
            do_action( 'skc_md_lead_added', $lead, $config );
            SKC_MD_Integrations::maybe_notify( $lead, $config );
        }

        wp_send_json_success( array( 'message' => __( '¡Gracias! Revisa tu correo.', 'skycode-modal' ) ) );
    }

    private function insert( $lead ) {
        global $wpdb;

        $existing = $wpdb->get_var( $wpdb->prepare(
            'SELECT id FROM ' . self::table() . ' WHERE campaign_id = %d AND email = %s',
            $lead['campaign_id'],
            $lead['email']
        ) );

        if ( $existing ) {
            return false;
        }

        $lead['created_at'] = current_time( 'mysql' );

        return (bool) $wpdb->insert( self::table(), $lead );
    }

    public static function get_by_campaign( $campaign_id = 0, $limit = 200 ) {
        global $wpdb;
        $limit = absint( $limit );

        if ( $campaign_id ) {
            return $wpdb->get_results( $wpdb->prepare(
                'SELECT * FROM ' . self::table() . " WHERE campaign_id = %d ORDER BY id DESC LIMIT {$limit}",
                $campaign_id
            ), ARRAY_A );
        }

        return $wpdb->get_results(
            'SELECT * FROM ' . self::table() . " ORDER BY id DESC LIMIT {$limit}",
            ARRAY_A
        );
    }

    public static function count( $campaign_id = 0 ) {
        global $wpdb;
        if ( $campaign_id ) {
            return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE campaign_id = %d', $campaign_id ) );
        }
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
    }

    public static function export_csv( $campaign_id = 0 ) {
        global $wpdb;

        if ( $campaign_id ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT campaign_id, email, name, phone, consent, created_at FROM ' . self::table() . ' WHERE campaign_id = %d ORDER BY id ASC',
                $campaign_id
            ), ARRAY_A );
        } else {
            $rows = $wpdb->get_results(
                'SELECT campaign_id, email, name, phone, consent, created_at FROM ' . self::table() . ' ORDER BY id ASC',
                ARRAY_A
            );
        }

        $handle = fopen( 'php://temp', 'w+' );
        fputcsv( $handle, array( 'campaign_id', 'email', 'name', 'phone', 'consent', 'created_at' ) );
        foreach ( $rows as $row ) {
            fputcsv( $handle, $row );
        }
        rewind( $handle );
        $csv = stream_get_contents( $handle );
        fclose( $handle );

        return $csv;
    }

    public static function get_client_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
