<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * wp skycode-maintenance <enable|disable|status|token|export|import>
 */
class SKC_MM_CLI {

    /**
     * Activa el modo en construcción / mantenimiento.
     *
     * ## OPTIONS
     *
     * [--mode=<mode>]
     * : coming_soon o maintenance.
     * ---
     * default: coming_soon
     * options:
     *   - coming_soon
     *   - maintenance
     * ---
     *
     * [--until=<datetime>]
     * : Fecha/hora (formato strtotime) hasta la que estará activo.
     */
    public function enable( $args, $assoc_args ) {
        $mode  = $assoc_args['mode'] ?? 'coming_soon';
        $until = $assoc_args['until'] ?? '';

        $values = array( 'mode' => $mode );

        if ( $until ) {
            $timestamp = strtotime( $until );
            if ( ! $timestamp ) {
                WP_CLI::error( 'Fecha inválida en --until.' );
            }
            $values['schedule_start'] = time();
            $values['schedule_end']   = $timestamp;
        }

        SKC_MM_Settings::update( $values );
        SKC_MM_Logger::record( 'cli_enable', $values );
        WP_CLI::success( "Modo '{$mode}' activado." );
    }

    /**
     * Desactiva el modo en construcción / mantenimiento.
     */
    public function disable( $args, $assoc_args ) {
        SKC_MM_Settings::update( array(
            'mode'           => 'off',
            'schedule_start' => 0,
            'schedule_end'   => 0,
        ) );
        SKC_MM_Logger::record( 'cli_disable' );
        WP_CLI::success( 'Modo desactivado.' );
    }

    /**
     * Muestra el estado actual.
     */
    public function status( $args, $assoc_args ) {
        $settings = SKC_MM_Settings::all();
        WP_CLI::log( 'Modo guardado: ' . $settings['mode'] );
        WP_CLI::log( 'Modo efectivo: ' . SKC_MM_Gatekeeper::effective_mode() );
        WP_CLI::log( 'Forzado por wp-config: ' . ( SKC_MM_Settings::is_forced() ? 'sí' : 'no' ) );
        if ( $settings['schedule_start'] && $settings['schedule_end'] ) {
            WP_CLI::log( 'Ventana: ' . gmdate( 'Y-m-d H:i:s', $settings['schedule_start'] ) . ' → ' . gmdate( 'Y-m-d H:i:s', $settings['schedule_end'] ) );
        }
    }

    /**
     * Genera un nuevo token de acceso y lo imprime (solo se ve una vez).
     */
    public function token( $args, $assoc_args ) {
        $token = SKC_MM_Access::generate_token();
        SKC_MM_Logger::record( 'cli_token_generated' );
        WP_CLI::success( 'Token generado: ' . $token );
        WP_CLI::log( 'URL: ' . add_query_arg( 'skc_access', $token, home_url( '/' ) ) );
    }

    /**
     * Exporta la configuración como JSON.
     */
    public function export( $args, $assoc_args ) {
        WP_CLI::log( SKC_MM_Settings::export() );
    }

    /**
     * Importa configuración desde un archivo JSON.
     *
     * ## OPTIONS
     *
     * <file>
     * : Ruta al archivo JSON.
     */
    public function import( $args, $assoc_args ) {
        list( $file ) = $args;

        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "Archivo no encontrado: {$file}" );
        }

        $result = SKC_MM_Settings::import( file_get_contents( $file ) );

        if ( is_wp_error( $result ) ) {
            WP_CLI::error( $result->get_error_message() );
        }

        SKC_MM_Logger::record( 'cli_import' );
        WP_CLI::success( 'Configuración importada.' );
    }
}

WP_CLI::add_command( 'skycode-maintenance', 'SKC_MM_CLI' );
