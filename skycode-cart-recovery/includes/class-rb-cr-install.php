<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RB_CR_Install {

    public static function activate() {
        self::create_tables();
        update_option( 'rb_cr_db_version', RB_CR_DB_VERSION );

        if ( ! wp_next_scheduled( 'rb_cr_scan_event' ) ) {
            wp_schedule_event( time(), 'rb_cr_five_minutes', 'rb_cr_scan_event' );
        }

        if ( ! wp_next_scheduled( 'rb_cr_cleanup_event' ) ) {
            wp_schedule_event( time(), 'daily', 'rb_cr_cleanup_event' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'rb_cr_scan_event' );
        wp_clear_scheduled_hook( 'rb_cr_cleanup_event' );
    }

    public static function maybe_upgrade() {
        if ( get_option( 'rb_cr_db_version' ) !== RB_CR_DB_VERSION ) {
            self::create_tables();
            update_option( 'rb_cr_db_version', RB_CR_DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $carts_table      = $wpdb->prefix . 'rb_cr_carts';
        $events_table     = $wpdb->prefix . 'rb_cr_events';
        $optouts_table    = $wpdb->prefix . 'rb_cr_optouts';

        $sql = "CREATE TABLE {$carts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token CHAR(32) NOT NULL,
            session_key VARCHAR(64) NOT NULL DEFAULT '',
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(24) NOT NULL DEFAULT '',
            first_name VARCHAR(80) NOT NULL DEFAULT '',
            cart_json LONGTEXT NULL,
            total DECIMAL(14,2) NOT NULL DEFAULT 0,
            currency CHAR(3) NOT NULL DEFAULT 'COP',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            step_sent TINYINT UNSIGNED NOT NULL DEFAULT 0,
            next_send_at DATETIME NULL,
            opened_at DATETIME NULL,
            clicked_at DATETIME NULL,
            coupon_code VARCHAR(40) NOT NULL DEFAULT '',
            order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            recovered_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            consent VARCHAR(20) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY email (email),
            KEY status (status),
            KEY next_send_at (next_send_at),
            KEY session_key (session_key)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cart_id BIGINT UNSIGNED NOT NULL,
            channel VARCHAR(20) NOT NULL DEFAULT '',
            step TINYINT UNSIGNED NOT NULL DEFAULT 0,
            event VARCHAR(20) NOT NULL DEFAULT '',
            detail LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY cart_id (cart_id),
            KEY event (event)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$optouts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(24) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY phone (phone)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}

add_filter( 'cron_schedules', function ( $schedules ) {
    $schedules['rb_cr_five_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => __( 'Cada 5 minutos (Racing Bike Cart Recovery)', 'skycode-cart-recovery' ),
    );
    return $schedules;
} );
