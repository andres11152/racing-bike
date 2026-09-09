<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Scheduler {

    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rb_cr_scan_event', array( $this, 'run' ) );
        add_action( 'rb_cr_cleanup_event', array( $this, 'cleanup' ) );

        // Atribución: si el pedido se completa, el carrito pasa a recuperado.
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'attribute_order' ), 10, 1 );
    }

    public function run() {
        $this->mark_abandoned();
        $this->send_due_steps();
    }

    /**
     * Todo carrito 'active' sin actividad más allá del umbral pasa a
     * 'abandoned' y queda listo para el paso 1.
     */
    private function mark_abandoned() {
        global $wpdb;

        $settings = RB_CR_Settings::all();
        $minutes  = max( 15, (int) $settings['threshold_minutes'] );
        $min_val  = (float) $settings['min_cart_value'];

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . RB_CR_Cart::table() . "
             WHERE status = 'active'
               AND updated_at < DATE_SUB(NOW(), INTERVAL %d MINUTE)",
            $minutes
        ), ARRAY_A );

        foreach ( $rows as $row ) {
            if ( $min_val > 0 && (float) $row['total'] < $min_val ) {
                RB_CR_Cart::update( $row['id'], array( 'status' => 'lost' ) );
                continue;
            }

            if ( RB_CR_Optout::is_opted_out( $row['email'], $row['phone'] ) ) {
                RB_CR_Cart::update( $row['id'], array( 'status' => 'unsubscribed' ) );
                continue;
            }

            $sequence = RB_CR_Settings::sequence();
            $first    = ! empty( $sequence ) ? $sequence[0] : null;

            RB_CR_Cart::update( $row['id'], array(
                'status'       => 'abandoned',
                'next_send_at' => $first ? current_time( 'mysql' ) : null,
            ) );
        }
    }

    /**
     * Envía cada paso cuya hora ya llegó, respetando frecuencia por
     * email (14 días entre secuencias) y franja horaria de envío.
     */
    private function send_due_steps() {
        global $wpdb;

        if ( ! $this->within_send_window() ) {
            return;
        }

        $sequence = RB_CR_Settings::sequence();
        if ( empty( $sequence ) ) {
            return;
        }

        $rows = $wpdb->get_results(
            "SELECT * FROM " . RB_CR_Cart::table() . "
             WHERE status IN ('abandoned','recovering')
               AND next_send_at IS NOT NULL
               AND next_send_at <= NOW()
             ORDER BY next_send_at ASC
             LIMIT 20",
            ARRAY_A
        );

        foreach ( $rows as $row ) {
            $this->send_step( $row, $sequence );
        }
    }

    private function send_step( array $row, array $sequence ) {
        if ( RB_CR_Optout::is_opted_out( $row['email'], $row['phone'] ) ) {
            RB_CR_Cart::update( $row['id'], array( 'status' => 'unsubscribed', 'next_send_at' => null ) );
            return;
        }

        $next_step_number = (int) $row['step_sent'] + 1;
        $step = null;
        foreach ( $sequence as $candidate ) {
            if ( (int) $candidate['step'] === $next_step_number ) {
                $step = $candidate;
                break;
            }
        }

        if ( ! $step ) {
            RB_CR_Cart::update( $row['id'], array( 'next_send_at' => null ) );
            return;
        }

        $channel = RB_CR_Channels::get( $step['channel'] );
        if ( ! $channel || ! $channel->is_ready() || ! $channel->can_reach( $row ) ) {
            RB_CR_Log::record( $row['id'], $step['channel'], $step['step'], 'failed', 'canal no disponible o sin contacto' );
            $this->schedule_next( $row, $sequence, $next_step_number );
            return;
        }

        $sent = $channel->send( $row, $step );

        RB_CR_Log::record( $row['id'], $step['channel'], $step['step'], $sent ? 'sent' : 'failed' );

        RB_CR_Cart::update( $row['id'], array( 'status' => 'recovering', 'step_sent' => $next_step_number ) );

        $this->schedule_next( $row, $sequence, $next_step_number );
    }

    private function schedule_next( array $row, array $sequence, $sent_step_number ) {
        $next_number = $sent_step_number + 1;
        $next_step   = null;

        foreach ( $sequence as $candidate ) {
            if ( (int) $candidate['step'] === $next_number ) {
                $next_step = $candidate;
                break;
            }
        }

        if ( ! $next_step ) {
            RB_CR_Cart::update( $row['id'], array( 'next_send_at' => null ) );
            return;
        }

        // El cálculo es relativo a la creación del carrito (~momento del
        // abandono) para que un retraso en un paso no se acumule en el
        // siguiente: el paso 2 siempre sale N horas después del abandono,
        // no N horas después del paso 1.
        $created_at = strtotime( $row['created_at'] . ' UTC' );

        RB_CR_Cart::update( $row['id'], array(
            'next_send_at' => gmdate( 'Y-m-d H:i:s', $created_at + (int) $next_step['delay_hours'] * HOUR_IN_SECONDS ),
        ) );
    }

    private function within_send_window() {
        $settings = RB_CR_Settings::all();
        $hour     = (int) current_time( 'G' );
        return $hour >= (int) $settings['send_start_hour'] && $hour < (int) $settings['send_end_hour'];
    }

    public function attribute_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $email = $order->get_billing_email();
        if ( empty( $email ) ) {
            return;
        }

        $settings = RB_CR_Settings::all();
        $row      = RB_CR_Cart::find_open_by_email( $email );

        if ( ! $row ) {
            return;
        }

        $window_seconds = (int) $settings['attribution_days'] * DAY_IN_SECONDS;
        if ( ( time() - strtotime( $row['updated_at'] . ' UTC' ) ) > $window_seconds ) {
            return;
        }

        RB_CR_Cart::update( $row['id'], array(
            'status'          => 'recovered',
            'order_id'        => $order_id,
            'recovered_total' => (float) $order->get_total(),
            'next_send_at'    => null,
        ) );

        RB_CR_Log::record( $row['id'], 'email', $row['step_sent'], 'recovered', 'order #' . $order_id );
    }

    public function cleanup() {
        global $wpdb;

        $settings = RB_CR_Settings::all();
        $days     = max( 7, (int) $settings['retention_days'] );

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . RB_CR_Cart::table() . "
             WHERE status IN ('lost','unsubscribed','recovered')
               AND updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );

        RB_CR_Log::cleanup( $days );
    }
}
