<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Install {

    public static function activate() {
        self::create_tables();
        update_option( 'skc_mm_db_version', SKC_MM_DB_VERSION );

        if ( ! wp_next_scheduled( 'skc_mm_check_schedule' ) ) {
            wp_schedule_event( time(), 'skc_mm_five_minutes', 'skc_mm_check_schedule' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'skc_mm_check_schedule' );
    }

    public static function maybe_upgrade() {
        if ( get_option( 'skc_mm_db_version' ) !== SKC_MM_DB_VERSION ) {
            self::create_tables();
            update_option( 'skc_mm_db_version', SKC_MM_DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate    = $wpdb->get_charset_collate();
        $subscribers_table  = $wpdb->prefix . 'skc_mm_subscribers';
        $log_table          = $wpdb->prefix . 'skc_mm_log';

        $sql = "CREATE TABLE {$subscribers_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            synced TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$log_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event VARCHAR(40) NOT NULL DEFAULT '',
            detail LONGTEXT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event (event)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}

add_filter( 'cron_schedules', function ( $schedules ) {
    $schedules['skc_mm_five_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => __( 'Cada 5 minutos (Skycode Maintenance Mode)', 'skycode-maintenance' ),
    );
    return $schedules;
} );
