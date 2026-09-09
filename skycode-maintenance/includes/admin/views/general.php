<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'general';
include __DIR__ . '/nav.php';

$start_value = $settings['schedule_start'] ? gmdate( 'Y-m-d\TH:i', $settings['schedule_start'] ) : '';
$end_value   = $settings['schedule_end'] ? gmdate( 'Y-m-d\TH:i', $settings['schedule_end'] ) : '';
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'skc_mm_save_general' ); ?>
    <input type="hidden" name="action" value="skc_mm_save_general">

    <div class="skc-mm-grid">
        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Modo', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Estado del sitio', 'skycode-maintenance' ); ?></span>
                <select name="mode">
                    <option value="off" <?php selected( $settings['mode'], 'off' ); ?>><?php esc_html_e( 'Desactivado', 'skycode-maintenance' ); ?></option>
                    <option value="coming_soon" <?php selected( $settings['mode'], 'coming_soon' ); ?>><?php esc_html_e( 'Próximamente (200 OK, visible para SEO)', 'skycode-maintenance' ); ?></option>
                    <option value="maintenance" <?php selected( $settings['mode'], 'maintenance' ); ?>><?php esc_html_e( 'Mantenimiento (503, oculto para SEO)', 'skycode-maintenance' ); ?></option>
                </select>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Segundos de Retry-After (solo mantenimiento)', 'skycode-maintenance' ); ?></span>
                <input type="number" min="60" step="60" name="retry_after" value="<?php echo esc_attr( $settings['retry_after'] ); ?>" class="small-text">
            </label>
            <label class="skc-mm-field skc-mm-field-inline">
                <input type="checkbox" name="seo_noindex" value="1" <?php checked( $settings['seo_noindex'] ); ?>>
                <span><?php esc_html_e( 'Enviar cabecera noindex mientras esté activo', 'skycode-maintenance' ); ?></span>
            </label>
        </div>

        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Programación automática', 'skycode-maintenance' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Opcional. Si defines ambas fechas, el sitio se bloquea al llegar la fecha de inicio y se reabre solo al llegar la de fin (se evalúa en cada visita, no depende del cron).', 'skycode-maintenance' ); ?></p>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Inicio', 'skycode-maintenance' ); ?></span>
                <input type="datetime-local" name="schedule_start" value="<?php echo esc_attr( $start_value ); ?>">
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Fin', 'skycode-maintenance' ); ?></span>
                <input type="datetime-local" name="schedule_end" value="<?php echo esc_attr( $end_value ); ?>">
            </label>
            <p class="description"><?php esc_html_e( 'Deja ambos campos vacíos para desactivar la programación.', 'skycode-maintenance' ); ?></p>
        </div>
    </div>

    <?php submit_button( __( 'Guardar ajustes', 'skycode-maintenance' ) ); ?>
</form>

</div>
