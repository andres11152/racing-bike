<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'skc-md-campaigns';
$tabs = array(
    'skc-md-campaigns' => __( 'Campañas', 'skycode-modal' ),
    'skc-md-leads'     => __( 'Suscriptores', 'skycode-modal' ),
    'skc-md-stats'     => __( 'Estadísticas', 'skycode-modal' ),
    'skc-md-settings'  => __( 'Ajustes', 'skycode-modal' ),
);
?>
<h1 class="skc-md-title"><?php esc_html_e( 'Skycode Modal Pro', 'skycode-modal' ); ?></h1>
<nav class="nav-tab-wrapper skc-md-nav">
    <?php foreach ( $tabs as $slug => $label ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
    <?php endforeach; ?>
</nav>
