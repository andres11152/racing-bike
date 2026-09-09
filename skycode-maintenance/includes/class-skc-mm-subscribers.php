<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Subscribers {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_skc_mm_subscribe', array( $this, 'handle_subscribe' ) );
        add_action( 'wp_ajax_nopriv_skc_mm_subscribe', array( $this, 'handle_subscribe' ) );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'skc_mm_subscribers';
    }

    public function handle_subscribe() {
        check_ajax_referer( 'skc_mm_subscribe', 'nonce' );

        if ( ! SKC_MM_Settings::get( 'subscribe_enabled' ) ) {
            wp_send_json_error( array( 'message' => __( 'La suscripción no está habilitada.', 'skycode-maintenance' ) ), 400 );
        }

        // Honeypot: si el campo oculto viene relleno, es un bot.
        if ( ! empty( $_POST['skc_mm_hp'] ) ) {
            wp_send_json_success();
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Introduce un correo válido.', 'skycode-maintenance' ) ), 400 );
        }

        $ip           = SKC_MM_Access::get_client_ip();
        $rate_key     = 'skc_mm_rate_' . md5( $ip );
        $attempts     = (int) get_transient( $rate_key );

        if ( $attempts >= 5 ) {
            wp_send_json_error( array( 'message' => __( 'Demasiados intentos, prueba más tarde.', 'skycode-maintenance' ) ), 429 );
        }
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

        $inserted = $this->insert( $email, $ip );

        if ( $inserted ) {
            do_action( 'skc_mm_subscriber_added', $email );
            $this->maybe_notify_webhook( $email );
        }

        wp_send_json_success( array( 'message' => __( 'Gracias, te avisaremos.', 'skycode-maintenance' ) ) );
    }

    private function insert( $email, $ip ) {
        global $wpdb;

        $existing = $wpdb->get_var( $wpdb->prepare(
            'SELECT id FROM ' . self::table() . ' WHERE email = %s',
            $email
        ) );

        if ( $existing ) {
            return false;
        }

        return (bool) $wpdb->insert( self::table(), array(
            'email'      => $email,
            'ip'         => $ip,
            'synced'     => 0,
            'created_at' => current_time( 'mysql' ),
        ) );
    }

    private function maybe_notify_webhook( $email ) {
        $webhook = SKC_MM_Settings::get( 'subscribe_webhook' );
        if ( '' === $webhook ) {
            return;
        }

        wp_remote_post( $webhook, array(
            'timeout'  => 5,
            'blocking' => false,
            'body'     => array(
                'email'   => $email,
                'site'    => home_url(),
                'created' => current_time( 'mysql' ),
            ),
        ) );
    }

    public static function all( $limit = 200 ) {
        global $wpdb;
        $limit = absint( $limit );
        return $wpdb->get_results(
            'SELECT * FROM ' . self::table() . " ORDER BY id DESC LIMIT {$limit}",
            ARRAY_A
        );
    }

    public static function count() {
        global $wpdb;
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
    }

    public static function export_csv() {
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT email, ip, created_at FROM ' . self::table() . ' ORDER BY id ASC', ARRAY_A );

        $handle = fopen( 'php://temp', 'w+' );
        fputcsv( $handle, array( 'email', 'ip', 'created_at' ) );
        foreach ( $rows as $row ) {
            fputcsv( $handle, $row );
        }
        rewind( $handle );
        $csv = stream_get_contents( $handle );
        fclose( $handle );

        return $csv;
    }
}
