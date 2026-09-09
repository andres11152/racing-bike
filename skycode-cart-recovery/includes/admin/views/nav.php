<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** Barra de pestañas compartida por las seis pantallas. Espera $current (slug de página sin 'rb-cr-'). */
$tabs = array(
    'dashboard' => __( 'Panel', 'skycode-cart-recovery' ),
    'carts'     => __( 'Carritos', 'skycode-cart-recovery' ),
    'sequence'  => __( 'Secuencia', 'skycode-cart-recovery' ),
    'templates' => __( 'Plantillas', 'skycode-cart-recovery' ),
    'settings'  => __( 'Ajustes', 'skycode-cart-recovery' ),
    'log'       => __( 'Registro', 'skycode-cart-recovery' ),
);
?>
<div class="rb-cr-wrap">
<div class="rb-cr-head">
    <h1>
        <img src="<?php echo esc_url( RB_CR_URL . 'assets/images/logo-negro.png' ); ?>" alt="" class="rb-cr-logo">
        <?php esc_html_e( 'Carritos abandonados', 'skycode-cart-recovery' ); ?>
    </h1>
    <nav class="rb-cr-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rb-cr-' . $slug ) ); ?>" class="rb-cr-tab <?php echo $current === $slug ? 'active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
