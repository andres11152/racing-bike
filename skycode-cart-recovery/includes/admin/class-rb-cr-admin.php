<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Admin {

    private static $instance;
    const CAP = 'manage_woocommerce';

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_rb_cr_save_settings', array( $this, 'save_settings' ) );
        add_action( 'admin_post_rb_cr_save_sequence', array( $this, 'save_sequence' ) );
        add_action( 'admin_post_rb_cr_save_template', array( $this, 'save_template' ) );
        add_action( 'admin_post_rb_cr_send_test', array( $this, 'send_test' ) );
        add_action( 'admin_post_rb_cr_cart_action', array( $this, 'cart_action' ) );
    }

    public function menu() {
        $cap = current_user_can( self::CAP ) ? self::CAP : 'manage_options';

        add_menu_page(
            __( 'Carritos abandonados', 'skycode-cart-recovery' ),
            __( 'Carritos abandonados', 'skycode-cart-recovery' ),
            $cap,
            'rb-cr-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-cart',
            56
        );

        add_submenu_page( 'rb-cr-dashboard', __( 'Panel', 'skycode-cart-recovery' ), __( 'Panel', 'skycode-cart-recovery' ), $cap, 'rb-cr-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'rb-cr-dashboard', __( 'Carritos', 'skycode-cart-recovery' ), __( 'Carritos', 'skycode-cart-recovery' ), $cap, 'rb-cr-carts', array( $this, 'render_carts' ) );
        add_submenu_page( 'rb-cr-dashboard', __( 'Secuencia', 'skycode-cart-recovery' ), __( 'Secuencia', 'skycode-cart-recovery' ), $cap, 'rb-cr-sequence', array( $this, 'render_sequence' ) );
        add_submenu_page( 'rb-cr-dashboard', __( 'Plantillas', 'skycode-cart-recovery' ), __( 'Plantillas', 'skycode-cart-recovery' ), $cap, 'rb-cr-templates', array( $this, 'render_templates' ) );
        add_submenu_page( 'rb-cr-dashboard', __( 'Ajustes', 'skycode-cart-recovery' ), __( 'Ajustes', 'skycode-cart-recovery' ), $cap, 'rb-cr-settings', array( $this, 'render_settings' ) );
        add_submenu_page( 'rb-cr-dashboard', __( 'Registro', 'skycode-cart-recovery' ), __( 'Registro', 'skycode-cart-recovery' ), $cap, 'rb-cr-log', array( $this, 'render_log' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'rb-cr-' ) === false ) {
            return;
        }
        wp_enqueue_style( 'rb-cr-admin', RB_CR_URL . 'assets/admin.css', array(), RB_CR_VERSION );
        wp_enqueue_script( 'rb-cr-admin', RB_CR_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), RB_CR_VERSION, true );
    }

    private function render( $view, array $vars = array() ) {
        extract( $vars );
        include RB_CR_DIR . 'includes/admin/views/' . $view . '.php';
    }

    // ------------------------------------------------------------------
    // Panel
    // ------------------------------------------------------------------

    public function render_dashboard() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }

        global $wpdb;
        $table = RB_CR_Cart::table();

        $stats = $wpdb->get_row(
            "SELECT
                SUM(CASE WHEN status IN ('abandoned','recovering') THEN 1 ELSE 0 END) AS abandoned,
                SUM(CASE WHEN status IN ('abandoned','recovering') THEN total ELSE 0 END) AS lost_value,
                SUM(CASE WHEN status = 'recovered' AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recovered,
                SUM(CASE WHEN status = 'recovered' AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN recovered_total ELSE 0 END) AS recovered_value
             FROM {$table} WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ARRAY_A
        );

        $weekly = $wpdb->get_results(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM {$table}
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 14 DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC",
            ARRAY_A
        );

        $events = RB_CR_Log::recent( 8 );

        $this->render( 'dashboard', compact( 'stats', 'weekly', 'events' ) );
    }

    // ------------------------------------------------------------------
    // Carritos
    // ------------------------------------------------------------------

    public function render_carts() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }

        global $wpdb;
        $table = RB_CR_Cart::table();

        $status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        $paged         = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
        $per_page      = 20;
        $offset        = ( $paged - 1 ) * $per_page;

        $where  = '1=1';
        $params = array();
        if ( $status_filter ) {
            $where    = 'status = %s';
            $params[] = $status_filter;
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $rows  = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        $total = $status_filter
            ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status_filter ) )
            : (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        $counts = $wpdb->get_results( "SELECT status, COUNT(*) AS c FROM {$table} GROUP BY status", ARRAY_A );
        $counts_by_status = array();
        foreach ( $counts as $c ) {
            $counts_by_status[ $c['status'] ] = (int) $c['c'];
        }

        $this->render( 'carts', compact( 'rows', 'total', 'paged', 'per_page', 'status_filter', 'counts_by_status' ) );
    }

    public function cart_action() {
        check_admin_referer( 'rb_cr_cart_action' );
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'Acceso no autorizado', 'skycode-cart-recovery' ) );
        }

        $cart_id = isset( $_POST['cart_id'] ) ? absint( $_POST['cart_id'] ) : 0;
        $action  = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
        $row     = $cart_id ? RB_CR_Cart::find( $cart_id ) : null;

        if ( $row ) {
            if ( 'exclude' === $action ) {
                RB_CR_Cart::update( $cart_id, array( 'status' => 'lost', 'next_send_at' => null ) );
            } elseif ( 'send_now' === $action ) {
                RB_CR_Cart::update( $cart_id, array( 'next_send_at' => current_time( 'mysql' ) ) );
            }
        }

        wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=rb-cr-carts' ) );
        exit;
    }

    // ------------------------------------------------------------------
    // Secuencia
    // ------------------------------------------------------------------

    public function render_sequence() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $sequence = RB_CR_Settings::sequence();
        $this->render( 'sequence', compact( 'sequence' ) );
    }

    public function save_sequence() {
        check_admin_referer( 'rb_cr_save_sequence' );
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'Acceso no autorizado', 'skycode-cart-recovery' ) );
        }

        $steps = isset( $_POST['step'] ) && is_array( $_POST['step'] ) ? wp_unslash( $_POST['step'] ) : array();
        $sequence = array();

        $i = 1;
        foreach ( $steps as $step ) {
            $sequence[] = array(
                'step'           => $i,
                'delay_hours'    => max( 1, absint( $step['delay_hours'] ?? 1 ) ),
                'channel'        => 'email',
                'subject'        => sanitize_text_field( $step['subject'] ?? '' ),
                'template'       => sanitize_key( $step['template'] ?? 'step-1' ),
                'coupon_enabled' => ! empty( $step['coupon_enabled'] ),
                'coupon_percent' => min( 90, max( 1, (float) ( $step['coupon_percent'] ?? 10 ) ) ),
                'coupon_hours'   => max( 1, absint( $step['coupon_hours'] ?? 48 ) ),
            );
            $i++;
        }

        if ( ! empty( $sequence ) ) {
            RB_CR_Settings::update_sequence( $sequence );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=rb-cr-sequence&saved=1' ) );
        exit;
    }

    // ------------------------------------------------------------------
    // Plantillas
    // ------------------------------------------------------------------

    public function render_templates() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }

        $sequence = RB_CR_Settings::sequence();
        $active   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : ( $sequence[0]['step'] ?? 1 );

        $step = null;
        foreach ( $sequence as $s ) {
            if ( (int) $s['step'] === (int) $active ) {
                $step = $s;
            }
        }
        if ( ! $step && ! empty( $sequence ) ) {
            $step = $sequence[0];
        }

        $preview_cart = $this->latest_cart_for_preview();

        $this->render( 'templates', compact( 'sequence', 'active', 'step', 'preview_cart' ) );
    }

    private function latest_cart_for_preview() {
        global $wpdb;
        $row = $wpdb->get_row( "SELECT * FROM " . RB_CR_Cart::table() . " ORDER BY id DESC LIMIT 1", ARRAY_A );

        if ( $row ) {
            return $row;
        }

        return array(
            'id'         => 0,
            'token'      => 'preview',
            'first_name' => 'Andrés',
            'email'      => wp_get_current_user()->user_email,
            'total'      => 590000,
            'currency'   => get_woocommerce_currency() ?: 'COP',
            'cart_json'  => wp_json_encode( array(
                array(
                    'name'     => 'Bicicleta de ruta T54',
                    'price'    => 590000,
                    'quantity' => 1,
                    'image'    => wc_placeholder_img_src(),
                ),
            ) ),
        );
    }

    public function save_template() {
        check_admin_referer( 'rb_cr_save_template' );
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'Acceso no autorizado', 'skycode-cart-recovery' ) );
        }

        $step_number = isset( $_POST['step_number'] ) ? absint( $_POST['step_number'] ) : 0;
        $subject     = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';

        $sequence = RB_CR_Settings::sequence();
        foreach ( $sequence as &$s ) {
            if ( (int) $s['step'] === $step_number ) {
                $s['subject'] = $subject;
            }
        }
        unset( $s );

        RB_CR_Settings::update_sequence( $sequence );

        wp_safe_redirect( admin_url( 'admin.php?page=rb-cr-templates&step=' . $step_number . '&saved=1' ) );
        exit;
    }

    // ------------------------------------------------------------------
    // Ajustes
    // ------------------------------------------------------------------

    public function render_settings() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $settings = RB_CR_Settings::all();
        $this->render( 'settings', compact( 'settings' ) );
    }

    public function save_settings() {
        check_admin_referer( 'rb_cr_save_settings' );
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'Acceso no autorizado', 'skycode-cart-recovery' ) );
        }

        $p = wp_unslash( $_POST );

        RB_CR_Settings::update( array(
            'threshold_minutes'   => min( 180, max( 15, absint( $p['threshold_minutes'] ?? 60 ) ) ),
            'attribution_days'    => max( 1, absint( $p['attribution_days'] ?? 7 ) ),
            'send_start_hour'     => min( 23, max( 0, absint( $p['send_start_hour'] ?? 8 ) ) ),
            'send_end_hour'       => min( 24, max( 1, absint( $p['send_end_hour'] ?? 20 ) ) ),
            'min_cart_value'      => max( 0, (float) ( $p['min_cart_value'] ?? 0 ) ),
            'retention_days'      => max( 7, absint( $p['retention_days'] ?? 90 ) ),
            'test_mode'           => ! empty( $p['test_mode'] ),
            'test_email'          => sanitize_email( $p['test_email'] ?? get_option( 'admin_email' ) ),
            'delete_on_uninstall' => ! empty( $p['delete_on_uninstall'] ),
            'from_email'          => sanitize_email( $p['from_email'] ?? get_option( 'admin_email' ) ),
            'from_name'           => sanitize_text_field( $p['from_name'] ?? get_bloginfo( 'name' ) ),
            'recovery_redirect'   => in_array( $p['recovery_redirect'] ?? 'cart', array( 'cart', 'checkout' ), true ) ? $p['recovery_redirect'] : 'cart',
        ) );

        wp_safe_redirect( admin_url( 'admin.php?page=rb-cr-settings&saved=1' ) );
        exit;
    }

    public function send_test() {
        check_admin_referer( 'rb_cr_send_test' );
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'Acceso no autorizado', 'skycode-cart-recovery' ) );
        }

        $step_number = isset( $_POST['step_number'] ) ? absint( $_POST['step_number'] ) : 1;
        $to          = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : wp_get_current_user()->user_email;

        $step = RB_CR_Settings::step( $step_number );
        $cart = $this->latest_cart_for_preview();
        $cart['email'] = $to;

        $channel = RB_CR_Channels::get( 'email' );
        $settings_backup = get_option( 'rb_cr_settings' );

        // Fuerza el envío al correo de prueba sin depender del modo prueba global.
        update_option( 'rb_cr_settings', array_merge( RB_CR_Settings::all(), array( 'test_mode' => true, 'test_email' => $to ) ) );
        $sent = $channel->send( $cart, $step ?: RB_CR_Settings::default_sequence()[0] );
        update_option( 'rb_cr_settings', $settings_backup );

        wp_safe_redirect( admin_url( 'admin.php?page=rb-cr-templates&step=' . $step_number . '&test=' . ( $sent ? 'ok' : 'fail' ) ) );
        exit;
    }

    // ------------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------------

    public function render_log() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $events = RB_CR_Log::recent( 100 );
        $this->render( 'log', compact( 'events' ) );
    }
}
