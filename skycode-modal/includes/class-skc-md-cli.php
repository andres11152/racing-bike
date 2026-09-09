<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * wp skycode-modal stats <campaign_id>
 * wp skycode-modal export-leads [--campaign=<id>] [--file=<path>]
 * wp skycode-modal purge-events [--days=<n>]
 */
class SKC_MD_CLI {

    public static function register() {
        WP_CLI::add_command( 'skycode-modal stats', array( __CLASS__, 'stats' ) );
        WP_CLI::add_command( 'skycode-modal export-leads', array( __CLASS__, 'export_leads' ) );
        WP_CLI::add_command( 'skycode-modal purge-events', array( __CLASS__, 'purge_events' ) );
    }

    public static function stats( $args ) {
        $campaign_id = absint( $args[0] ?? 0 );
        if ( ! $campaign_id ) {
            WP_CLI::error( 'Uso: wp skycode-modal stats <campaign_id>' );
        }

        $stats = SKC_MD_Events::stats_for_campaign( $campaign_id );
        WP_CLI\Utils\format_items( 'table', array( $stats ), array_keys( $stats ) );
    }

    public static function export_leads( $args, $assoc_args ) {
        $campaign_id = absint( $assoc_args['campaign'] ?? 0 );
        $file        = $assoc_args['file'] ?? ( 'skc-md-leads-' . date( 'Y-m-d-His' ) . '.csv' );

        $csv = SKC_MD_Leads::export_csv( $campaign_id );
        file_put_contents( $file, $csv );

        WP_CLI::success( sprintf( 'Exportado a %s', $file ) );
    }

    public static function purge_events( $args, $assoc_args ) {
        global $wpdb;
        $days  = absint( $assoc_args['days'] ?? SKC_MD_Settings::get( 'events_retention_days' ) );
        $table = $wpdb->prefix . 'skc_md_events';

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
        ) );

        WP_CLI::success( sprintf( '%d eventos purgados.', (int) $deleted ) );
    }
}

SKC_MD_CLI::register();
