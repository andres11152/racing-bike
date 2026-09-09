<?php
/**
 * Plugin Name: Skycode Maintenance Mode
 * Description: Modo "en construcción" / mantenimiento enterprise: bloqueo real del front, control de acceso granular (roles, IP, token), plantillas propias, captura de suscriptores, programación por fecha y WP-CLI. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-maintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SKC_MM_VERSION', '1.0.0' );
define( 'SKC_MM_DB_VERSION', '1' );
define( 'SKC_MM_FILE', __FILE__ );
define( 'SKC_MM_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKC_MM_URL', plugins_url( '/', __FILE__ ) );

// -----------------------------------------------------------------------
// Carga de clases
// -----------------------------------------------------------------------

require_once SKC_MM_DIR . 'includes/class-skc-mm-install.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-settings.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-logger.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-access.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-renderer.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-gatekeeper.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-scheduler.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-subscribers.php';
require_once SKC_MM_DIR . 'includes/class-skc-mm-compat.php';

if ( is_admin() ) {
    require_once SKC_MM_DIR . 'includes/admin/class-skc-mm-admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once SKC_MM_DIR . 'includes/class-skc-mm-cli.php';
}

// -----------------------------------------------------------------------
// Activación / desactivación
// -----------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
    SKC_MM_Install::activate();
} );

register_deactivation_hook( __FILE__, function () {
    SKC_MM_Install::deactivate();
} );

add_action( 'plugins_loaded', function () {
    SKC_MM_Install::maybe_upgrade();

    load_plugin_textdomain( 'skycode-maintenance', false, dirname( plugin_basename( SKC_MM_FILE ) ) . '/languages' );

    SKC_MM_Scheduler::instance();
    SKC_MM_Subscribers::instance();
    SKC_MM_Compat::instance();
    SKC_MM_Gatekeeper::instance();

    if ( is_admin() ) {
        SKC_MM_Admin::instance();
    }
}, 1 );
