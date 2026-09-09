<?php
/**
 * Plugin Name: Skycode Modal Pro
 * Description: Modales/popups enterprise con captura de suscriptores: triggers avanzados (tiempo, scroll, exit intent, inactividad), segmentación por página/dispositivo/UTM, control de frecuencia, A/B testing, analítica de conversión e integraciones. Genérico y reutilizable entre proyectos.
 * Version: 1.1.2
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-modal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SKC_MD_VERSION', '1.1.2' );
define( 'SKC_MD_DB_VERSION', '1' );
define( 'SKC_MD_FILE', __FILE__ );
define( 'SKC_MD_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKC_MD_URL', plugins_url( '/', __FILE__ ) );

// -----------------------------------------------------------------------
// Carga de clases
// -----------------------------------------------------------------------

require_once SKC_MD_DIR . 'includes/class-skc-md-install.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-settings.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-campaign.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-targeting.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-renderer.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-frontend.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-leads.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-events.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-integrations.php';
require_once SKC_MD_DIR . 'includes/class-skc-md-shortcode.php';

if ( is_admin() ) {
    require_once SKC_MD_DIR . 'includes/admin/class-skc-md-admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once SKC_MD_DIR . 'includes/class-skc-md-cli.php';
}

// -----------------------------------------------------------------------
// Activación / desactivación
// -----------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
    SKC_MD_Install::activate();
} );

register_deactivation_hook( __FILE__, function () {
    SKC_MD_Install::deactivate();
} );

add_action( 'plugins_loaded', function () {
    SKC_MD_Install::maybe_upgrade();

    load_plugin_textdomain( 'skycode-modal', false, dirname( plugin_basename( SKC_MD_FILE ) ) . '/languages' );

    SKC_MD_Campaign::instance();
    SKC_MD_Frontend::instance();
    SKC_MD_Leads::instance();
    SKC_MD_Events::instance();
    SKC_MD_Shortcode::instance();

    if ( is_admin() ) {
        SKC_MD_Admin::instance();
    }
}, 1 );
