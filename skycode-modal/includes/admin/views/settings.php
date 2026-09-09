<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var array $settings */
?>
<div class="wrap skc-md-wrap">
    <?php include __DIR__ . '/nav.php'; ?>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ajustes guardados.', 'skycode-modal' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'skc_md_save_settings' ); ?>
        <input type="hidden" name="action" value="skc_md_save_settings" />

        <table class="form-table">
            <tr>
                <th><label for="max_active_modals_per_page"><?php esc_html_e( 'Modales simultáneos por página', 'skycode-modal' ); ?></label></th>
                <td><input type="number" min="1" name="max_active_modals_per_page" id="max_active_modals_per_page" value="<?php echo esc_attr( $settings['max_active_modals_per_page'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="min_fill_seconds"><?php esc_html_e( 'Segundos mínimos antes de aceptar un envío (anti-bot)', 'skycode-modal' ); ?></label></th>
                <td><input type="number" min="0" name="min_fill_seconds" id="min_fill_seconds" value="<?php echo esc_attr( $settings['min_fill_seconds'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="rate_limit_per_hour"><?php esc_html_e( 'Límite de envíos por IP y hora', 'skycode-modal' ); ?></label></th>
                <td><input type="number" min="0" name="rate_limit_per_hour" id="rate_limit_per_hour" value="<?php echo esc_attr( $settings['rate_limit_per_hour'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="events_retention_days"><?php esc_html_e( 'Retención de eventos (días)', 'skycode-modal' ); ?></label></th>
                <td><input type="number" min="0" name="events_retention_days" id="events_retention_days" value="<?php echo esc_attr( $settings['events_retention_days'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="default_webhook"><?php esc_html_e( 'Webhook global', 'skycode-modal' ); ?></label></th>
                <td><input type="url" name="default_webhook" id="default_webhook" class="large-text" value="<?php echo esc_attr( $settings['default_webhook'] ); ?>" placeholder="https://hooks.zapier.com/..." />
                <p class="description"><?php esc_html_e( 'Se usa si una campaña no define su propio webhook.', 'skycode-modal' ); ?></p></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Logo sobre la imagen', 'skycode-modal' ); ?></th>
                <td>
                    <label><input type="checkbox" name="watermark_enabled" value="1" <?php checked( $settings['watermark_enabled'] ); ?> /> <?php esc_html_e( 'Mostrar el logo en la esquina superior derecha de la imagen del modal', 'skycode-modal' ); ?></label>

                    <p style="margin-top:0.75rem;">
                        <input type="url" name="watermark_logo" id="watermark_logo" class="large-text" value="<?php echo esc_attr( $settings['watermark_logo'] ); ?>" placeholder="<?php esc_attr_e( 'Vacío = usar automáticamente el logo del sitio o del tema', 'skycode-modal' ); ?>" />
                    </p>
                    <p>
                        <button type="button" class="button" id="skc-md-select-watermark"><?php esc_html_e( 'Seleccionar logo', 'skycode-modal' ); ?></button>
                        <button type="button" class="button" id="skc-md-clear-watermark"><?php esc_html_e( 'Vaciar (auto)', 'skycode-modal' ); ?></button>
                        <label style="margin-left:1rem;"><?php esc_html_e( 'Alto (px):', 'skycode-modal' ); ?>
                            <input type="number" min="10" max="200" name="watermark_size" value="<?php echo esc_attr( $settings['watermark_size'] ); ?>" style="width:80px;" />
                        </label>
                    </p>

                    <?php $resolved = SKC_MD_Settings::watermark(); ?>
                    <?php if ( $resolved ) : ?>
                        <p class="description"><?php esc_html_e( 'Logo en uso:', 'skycode-modal' ); ?></p>
                        <div style="display:inline-block;padding:8px 12px;background:#1e1e1e;border-radius:6px;">
                            <img src="<?php echo esc_url( $resolved ); ?>" alt="" style="height:40px;width:auto;display:block;" />
                        </div>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e( 'No se encontró ningún logo. Selecciona uno arriba.', 'skycode-modal' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'RGPD', 'skycode-modal' ); ?></th>
                <td>
                    <label><input type="checkbox" name="gdpr_require_consent" value="1" <?php checked( $settings['gdpr_require_consent'] ); ?> /> <?php esc_html_e( 'Exigir consentimiento por defecto en nuevas campañas', 'skycode-modal' ); ?></label><br />
                    <textarea name="gdpr_consent_text" rows="2" class="large-text"><?php echo esc_textarea( $settings['gdpr_consent_text'] ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Accesibilidad', 'skycode-modal' ); ?></th>
                <td><label><input type="checkbox" name="respect_reduced_motion" value="1" <?php checked( $settings['respect_reduced_motion'] ); ?> /> <?php esc_html_e( 'Respetar "prefers-reduced-motion"', 'skycode-modal' ); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Al desinstalar', 'skycode-modal' ); ?></th>
                <td><label><input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $settings['delete_on_uninstall'] ); ?> /> <?php esc_html_e( 'Eliminar todas las campañas, suscriptores y ajustes al desinstalar', 'skycode-modal' ); ?></label></td>
            </tr>
        </table>

        <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar ajustes', 'skycode-modal' ); ?></button></p>
    </form>
</div>
