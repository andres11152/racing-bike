<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'access';
include __DIR__ . '/nav.php';
?>

<?php if ( $token_url ) : ?>
    <div class="skc-mm-notice">
        <strong><?php esc_html_e( 'Nuevo enlace de acceso generado. Guárdalo ahora, no volverá a mostrarse:', 'skycode-maintenance' ); ?></strong>
        <input type="text" readonly value="<?php echo esc_url( $token_url ); ?>" class="large-text" onclick="this.select();">
    </div>
<?php endif; ?>

<div class="skc-mm-grid">

    <div class="skc-mm-card">
        <h2><?php esc_html_e( 'Roles con acceso libre', 'skycode-maintenance' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'skc_mm_save_access' ); ?>
            <input type="hidden" name="action" value="skc_mm_save_access">

            <?php foreach ( $roles as $role_slug => $role_label ) : ?>
                <label class="skc-mm-field-inline">
                    <input type="checkbox" name="bypass_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, (array) $settings['bypass_roles'], true ) ); ?>>
                    <span><?php echo esc_html( $role_label ); ?></span>
                </label><br>
            <?php endforeach; ?>

            <h2><?php esc_html_e( 'IPs con acceso libre', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Una IP o rango CIDR por línea', 'skycode-maintenance' ); ?></span>
                <textarea name="allowed_ips" rows="4" class="large-text code"><?php echo esc_textarea( implode( "\n", (array) $settings['allowed_ips'] ) ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Ejemplo: 190.85.10.4 o 190.85.10.0/24. Soporta IPv4 e IPv6.', 'skycode-maintenance' ); ?></p>
            </label>

            <h2><?php esc_html_e( 'Rutas adicionales excluidas', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Un fragmento de URL por línea', 'skycode-maintenance' ); ?></span>
                <textarea name="excluded_paths" rows="3" class="large-text code"><?php echo esc_textarea( implode( "\n", (array) $settings['excluded_paths'] ) ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Los pagos, webhooks, REST API, login y cron ya están excluidos siempre y no aparecen aquí.', 'skycode-maintenance' ); ?></p>
            </label>

            <?php submit_button( __( 'Guardar ajustes de acceso', 'skycode-maintenance' ) ); ?>
        </form>
    </div>

    <div class="skc-mm-card">
        <h2><?php esc_html_e( 'Enlace secreto de acceso', 'skycode-maintenance' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Genera una URL única que abre el sitio sin necesidad de iniciar sesión. Útil para compartir con clientes o el equipo mientras el sitio está bloqueado.', 'skycode-maintenance' ); ?></p>

        <p>
            <?php if ( $settings['bypass_token_hash'] ) : ?>
                <span class="skc-mm-status-badge on"><?php esc_html_e( 'Hay un token activo', 'skycode-maintenance' ); ?></span>
                <?php if ( $settings['token_expires'] ) : ?>
                    <br><small><?php printf( esc_html__( 'Expira: %s', 'skycode-maintenance' ), esc_html( gmdate( 'Y-m-d H:i', $settings['token_expires'] ) ) ); ?></small>
                <?php endif; ?>
            <?php else : ?>
                <span class="skc-mm-status-badge off"><?php esc_html_e( 'Sin token activo', 'skycode-maintenance' ); ?></span>
            <?php endif; ?>
        </p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'skc_mm_generate_token' ); ?>
            <input type="hidden" name="action" value="skc_mm_generate_token">
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Expira el (opcional)', 'skycode-maintenance' ); ?></span>
                <input type="datetime-local" name="token_expires">
            </label>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Generar nuevo enlace', 'skycode-maintenance' ); ?></button>
        </form>

        <?php if ( $settings['bypass_token_hash'] ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
                <?php wp_nonce_field( 'skc_mm_revoke_token' ); ?>
                <input type="hidden" name="action" value="skc_mm_revoke_token">
                <button type="submit" class="button"><?php esc_html_e( 'Revocar enlace actual', 'skycode-maintenance' ); ?></button>
            </form>
        <?php endif; ?>
    </div>

</div>

</div>
