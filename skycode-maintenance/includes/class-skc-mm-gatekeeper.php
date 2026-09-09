<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Gatekeeper {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'template_redirect', array( $this, 'maybe_block' ), 0 );
    }

    /**
     * Rutas que nunca se bloquean, sin importar la configuración.
     * Protege pagos (webhooks/IPN de Mercado Pago y otras pasarelas),
     * la API REST, el cron y el login. No es configurable a propósito.
     */
    private function hard_excluded_paths() {
        return array(
            '/wp-json/',
            '/wp-admin/',
            'wp-login.php',
            'wp-cron.php',
            'admin-ajax.php',
            'wc-api=',
            '/wc-auth/',
            '/wc-api/',
            'rest_route=',
            'robots.txt',
            'favicon.ico',
            '/.well-known/',
        );
    }

    public function should_block() {
        $mode = self::effective_mode();

        if ( 'off' === $mode ) {
            return false;
        }

        if ( wp_doing_cron() || wp_doing_ajax() || is_admin() ) {
            return false;
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $excluded    = apply_filters( 'skc_mm_excluded_paths', $this->hard_excluded_paths() );

        foreach ( $excluded as $pattern ) {
            if ( '' !== $pattern && false !== strpos( $request_uri, $pattern ) ) {
                return false;
            }
        }

        if ( SKC_MM_Access::is_allowed() ) {
            return false;
        }

        return (bool) apply_filters( 'skc_mm_should_block', true, $mode );
    }

    /**
     * Combina el modo guardado con la ventana programada. Se evalúa en
     * cada request (no solo por cron) porque el entorno puede tener
     * DISABLE_WP_CRON definido.
     */
    public static function effective_mode() {
        $settings = SKC_MM_Settings::all();
        $mode     = SKC_MM_Settings::current_mode();

        if ( SKC_MM_Settings::is_forced() ) {
            return $mode;
        }

        $now   = time();
        $start = (int) $settings['schedule_start'];
        $end   = (int) $settings['schedule_end'];

        if ( $start && $end ) {
            if ( $now >= $start && $now < $end ) {
                return 'maintenance' === $mode || 'coming_soon' === $mode ? $mode : 'maintenance';
            }
            if ( $now >= $end ) {
                return 'off';
            }
        }

        return $mode;
    }

    public function maybe_block() {
        if ( ! $this->should_block() ) {
            return;
        }

        SKC_MM_Renderer::render( self::effective_mode() );
        exit;
    }
}
