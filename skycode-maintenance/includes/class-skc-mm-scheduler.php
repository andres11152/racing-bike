<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * El Gatekeeper ya evalúa la ventana programada en tiempo real en cada
 * request. Este cron solo persiste el cambio de estado en la opción y en
 * el log, para que la UI y las integraciones externas (caché, etc.) lo
 * reflejen aunque no haya tráfico.
 */
class SKC_MM_Scheduler {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'skc_mm_check_schedule', array( $this, 'check' ) );
    }

    public function check() {
        if ( SKC_MM_Settings::is_forced() ) {
            return;
        }

        $settings = SKC_MM_Settings::all();
        $start    = (int) $settings['schedule_start'];
        $end      = (int) $settings['schedule_end'];

        if ( ! $start || ! $end ) {
            return;
        }

        $now = time();

        if ( $now >= $end && 'off' !== $settings['mode'] ) {
            SKC_MM_Settings::update( array(
                'mode'           => 'off',
                'schedule_start' => 0,
                'schedule_end'   => 0,
            ) );
            SKC_MM_Logger::record( 'schedule_ended', array( 'end' => $end ) );
            do_action( 'skc_mm_compat_purge_cache' );
        }
    }
}
