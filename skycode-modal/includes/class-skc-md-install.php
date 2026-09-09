<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Install {

    public static function activate() {
        self::create_tables();
        update_option( 'skc_md_db_version', SKC_MD_DB_VERSION );

        if ( ! wp_next_scheduled( 'skc_md_purge_events' ) ) {
            wp_schedule_event( time(), 'daily', 'skc_md_purge_events' );
        }

        // Fuerza los rewrite rules del CPT si algo depende de ellos.
        flush_rewrite_rules();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'skc_md_purge_events' );
        flush_rewrite_rules();
    }

    public static function maybe_upgrade() {
        if ( get_option( 'skc_md_db_version' ) !== SKC_MD_DB_VERSION ) {
            self::create_tables();
            update_option( 'skc_md_db_version', SKC_MD_DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $leads_table      = $wpdb->prefix . 'skc_md_leads';
        $events_table     = $wpdb->prefix . 'skc_md_events';

        $sql = "CREATE TABLE {$leads_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(190) NOT NULL,
            name VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(40) NOT NULL DEFAULT '',
            consent TINYINT UNSIGNED NOT NULL DEFAULT 0,
            source_url VARCHAR(500) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
            synced TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY campaign_email (campaign_id, email),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event VARCHAR(20) NOT NULL DEFAULT '',
            variant VARCHAR(20) NOT NULL DEFAULT '',
            visitor_hash VARCHAR(64) NOT NULL DEFAULT '',
            url VARCHAR(500) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY campaign_event (campaign_id, event),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}

add_filter( 'cron_schedules', function ( $schedules ) {
    if ( ! isset( $schedules['skc_md_weekly'] ) ) {
        $schedules['skc_md_weekly'] = array(
            'interval' => 7 * DAY_IN_SECONDS,
            'display'  => __( 'Semanal (Skycode Modal Pro)', 'skycode-modal' ),
        );
    }
    return $schedules;
} );

add_action( 'skc_md_purge_events', function () {
    global $wpdb;

    $days = (int) SKC_MD_Settings::get( 'events_retention_days' );
    if ( $days <= 0 ) {
        return;
    }

    $table = $wpdb->prefix . 'skc_md_events';
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$table} WHERE created_at < %s",
        gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
    ) );
} );
