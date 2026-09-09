<?php
/**
 * Plugin Name: Skycode Cart Recovery
 * Description: Recupera carritos abandonados de WooCommerce por email, con SMS y WhatsApp (API oficial de Meta) como canales adicionales próximamente. Captura el contacto, agenda una secuencia de recordatorios y mide el ingreso recuperado. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-cart-recovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'RB_CR_VERSION', '1.0.0' );
define( 'RB_CR_DB_VERSION', '1' );
define( 'RB_CR_FILE', __FILE__ );
define( 'RB_CR_DIR', plugin_dir_path( __FILE__ ) );
define( 'RB_CR_URL', plugins_url( '/', __FILE__ ) );

// -----------------------------------------------------------------------
// Carga de clases
// -----------------------------------------------------------------------

require_once RB_CR_DIR . 'includes/class-rb-cr-install.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-settings.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-cart.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-log.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-optout.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-coupon.php';
require_once RB_CR_DIR . 'includes/channels/class-rb-cr-channel.php';
require_once RB_CR_DIR . 'includes/channels/class-rb-cr-channel-email.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-channels.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-capture.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-scheduler.php';
require_once RB_CR_DIR . 'includes/class-rb-cr-restore.php';

if ( is_admin() ) {
    require_once RB_CR_DIR . 'includes/admin/class-rb-cr-admin.php';
}

// -----------------------------------------------------------------------
// Activación / desactivación
// -----------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
    RB_CR_Install::activate();
} );

register_deactivation_hook( __FILE__, function () {
    RB_CR_Install::deactivate();
} );

add_action( 'plugins_loaded', function () {
    RB_CR_Install::maybe_upgrade();

    RB_CR_Channels::register( new RB_CR_Channel_Email() );

    RB_CR_Capture::instance();
    RB_CR_Scheduler::instance();
    RB_CR_Restore::instance();

    if ( is_admin() ) {
        RB_CR_Admin::instance();
    }
} );
