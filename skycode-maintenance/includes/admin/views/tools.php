<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'tools';
include __DIR__ . '/nav.php';
?>

<?php if ( isset( $_GET['skc_mm_imported'] ) ) : ?>
    <div class="skc-mm-notice"><?php esc_html_e( 'Configuración importada correctamente.', 'skycode-maintenance' ); ?></div>
<?php elseif ( isset( $_GET['skc_mm_error'] ) ) : ?>
    <div class="skc-mm-notice warn"><?php esc_html_e( 'No se pudo importar el archivo. Verifica que sea un JSON exportado desde este plugin.', 'skycode-maintenance' ); ?></div>
<?php endif; ?>

<div class="skc-mm-grid">

    <div class="skc-mm-card">
        <h2><?php esc_html_e( 'Exportar / importar configuración', 'skycode-maintenance' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Útil para clonar la configuración de diseño y acceso a otro proyecto que use este mismo plugin.', 'skycode-maintenance' ); ?></p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'skc_mm_export_settings' ); ?>
            <input type="hidden" name="action" value="skc_mm_export_settings">
            <button type="submit" class="button"><?php esc_html_e( 'Exportar JSON', 'skycode-maintenance' ); ?></button>
        </form>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field( 'skc_mm_import_settings' ); ?>
            <input type="hidden" name="action" value="skc_mm_import_settings">
            <input type="file" name="settings_file" accept="application/json" required>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Importar', 'skycode-maintenance' ); ?></button>
        </form>
    </div>

    <div class="skc-mm-card">
        <h2><?php esc_html_e( 'Datos y privacidad', 'skycode-maintenance' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'skc_mm_save_uninstall' ); ?>
            <input type="hidden" name="action" value="skc_mm_save_uninstall">
            <label class="skc-mm-field-inline">
                <input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $settings['delete_on_uninstall'] ); ?>>
                <span><?php esc_html_e( 'Borrar todos los datos del plugin al desinstalarlo', 'skycode-maintenance' ); ?></span>
            </label>
            <?php submit_button( __( 'Guardar', 'skycode-maintenance' ) ); ?>
        </form>
    </div>

    <div class="skc-mm-card skc-mm-card-wide">
        <h2><?php esc_html_e( 'Registro de auditoría', 'skycode-maintenance' ); ?></h2>
        <?php if ( empty( $log ) ) : ?>
            <p><?php esc_html_e( 'Sin actividad registrada.', 'skycode-maintenance' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Evento', 'skycode-maintenance' ); ?></th>
                        <th><?php esc_html_e( 'Detalle', 'skycode-maintenance' ); ?></th>
                        <th><?php esc_html_e( 'Usuario', 'skycode-maintenance' ); ?></th>
                        <th><?php esc_html_e( 'Fecha', 'skycode-maintenance' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $log as $entry ) : ?>
                        <tr>
                            <td><?php echo esc_html( $entry['event'] ); ?></td>
                            <td><code><?php echo esc_html( $entry['detail'] ); ?></code></td>
                            <td><?php echo esc_html( $entry['user_id'] ? get_the_author_meta( 'display_name', $entry['user_id'] ) : '—' ); ?></td>
                            <td><?php echo esc_html( $entry['created_at'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</div>
