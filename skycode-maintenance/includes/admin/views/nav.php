<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** Barra de pestañas compartida. Espera $current (slug sin 'skc-mm-'). */
$tabs = array(
    'general'     => __( 'General', 'skycode-maintenance' ),
    'design'      => __( 'Diseño', 'skycode-maintenance' ),
    'access'      => __( 'Acceso', 'skycode-maintenance' ),
    'subscribers' => __( 'Suscriptores', 'skycode-maintenance' ),
    'tools'       => __( 'Herramientas', 'skycode-maintenance' ),
);
$effective_mode = SKC_MM_Gatekeeper::effective_mode();
?>
<div class="skc-mm-wrap-admin">
<div class="skc-mm-head">
    <h1>
        <?php esc_html_e( 'Skycode Maintenance Mode', 'skycode-maintenance' ); ?>
        <?php if ( 'off' !== $effective_mode ) : ?>
            <span class="skc-mm-status-badge on"><?php esc_html_e( 'Activo', 'skycode-maintenance' ); ?> — <?php echo esc_html( 'maintenance' === $effective_mode ? __( 'Mantenimiento', 'skycode-maintenance' ) : __( 'Próximamente', 'skycode-maintenance' ) ); ?></span>
        <?php else : ?>
            <span class="skc-mm-status-badge off"><?php esc_html_e( 'Inactivo', 'skycode-maintenance' ); ?></span>
        <?php endif; ?>
    </h1>
    <nav class="skc-mm-tabs">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=skc-mm-' . $slug ) ); ?>" class="skc-mm-tab <?php echo $current === $slug ? 'active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<?php if ( isset( $_GET['skc_mm_saved'] ) ) : ?>
    <div class="skc-mm-notice"><?php esc_html_e( 'Ajustes guardados.', 'skycode-maintenance' ); ?></div>
<?php endif; ?>
<?php if ( SKC_MM_Settings::is_forced() ) : ?>
    <div class="skc-mm-notice warn">
        <?php
        printf(
            /* translators: %s: constant name */
            esc_html__( 'El modo está forzado por la constante %s en wp-config.php. Los ajustes de esta pantalla no tendrán efecto hasta que la elimines.', 'skycode-maintenance' ),
            '<code>SKC_MM_FORCE_MODE</code>'
        );
        ?>
    </div>
<?php endif; ?>
