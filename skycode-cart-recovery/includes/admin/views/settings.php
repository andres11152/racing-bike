<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'settings';
include __DIR__ . '/nav.php';
?>

<?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="rb-cr-notice"><?php esc_html_e( 'Ajustes guardados.', 'skycode-cart-recovery' ); ?></div>
<?php endif; ?>

<?php if ( $settings['test_mode'] ) : ?>
    <div class="rb-cr-notice warn"><?php esc_html_e( 'Modo prueba activo: ningún cliente real recibirá correos. Todo se redirige al correo de prueba.', 'skycode-cart-recovery' ); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'rb_cr_save_settings' ); ?>
    <input type="hidden" name="action" value="rb_cr_save_settings">

    <div class="rb-cr-settings-grid">

        <div class="rb-cr-card">
            <h2><?php esc_html_e( 'Cuándo se considera abandonado', 'skycode-cart-recovery' ); ?></h2>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Minutos sin actividad', 'skycode-cart-recovery' ); ?></span>
                <input type="number" min="15" max="180" name="threshold_minutes" value="<?php echo esc_attr( $settings['threshold_minutes'] ); ?>" class="small-text">
                <p class="description"><?php esc_html_e( 'Entre 15 y 180 minutos.', 'skycode-cart-recovery' ); ?></p>
            </label>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Valor mínimo del carrito para entrar (COP)', 'skycode-cart-recovery' ); ?></span>
                <input type="number" min="0" step="1000" name="min_cart_value" value="<?php echo esc_attr( $settings['min_cart_value'] ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Deja en 0 para incluir cualquier valor.', 'skycode-cart-recovery' ); ?></p>
            </label>
        </div>

        <div class="rb-cr-card">
            <h2><?php esc_html_e( 'Horario y atribución', 'skycode-cart-recovery' ); ?></h2>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Enviar entre estas horas (hora de la tienda)', 'skycode-cart-recovery' ); ?></span>
                <div class="rb-cr-field-inline">
                    <input type="number" min="0" max="23" name="send_start_hour" value="<?php echo esc_attr( $settings['send_start_hour'] ); ?>" class="small-text"> —
                    <input type="number" min="1" max="24" name="send_end_hour" value="<?php echo esc_attr( $settings['send_end_hour'] ); ?>" class="small-text">
                </div>
            </label>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Ventana de atribución (días)', 'skycode-cart-recovery' ); ?></span>
                <input type="number" min="1" name="attribution_days" value="<?php echo esc_attr( $settings['attribution_days'] ); ?>" class="small-text">
                <p class="description"><?php esc_html_e( 'Si el cliente compra dentro de este plazo tras abandonar, se cuenta como recuperado.', 'skycode-cart-recovery' ); ?></p>
            </label>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'El enlace de recuperación lleva a', 'skycode-cart-recovery' ); ?></span>
                <select name="recovery_redirect">
                    <option value="cart" <?php selected( $settings['recovery_redirect'], 'cart' ); ?>><?php esc_html_e( 'El carrito', 'skycode-cart-recovery' ); ?></option>
                    <option value="checkout" <?php selected( $settings['recovery_redirect'], 'checkout' ); ?>><?php esc_html_e( 'El checkout directo', 'skycode-cart-recovery' ); ?></option>
                </select>
            </label>
        </div>

        <div class="rb-cr-card">
            <h2><?php esc_html_e( 'Remitente', 'skycode-cart-recovery' ); ?></h2>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Nombre', 'skycode-cart-recovery' ); ?></span>
                <input type="text" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="widefat">
            </label>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Correo', 'skycode-cart-recovery' ); ?></span>
                <input type="email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="widefat">
                <p class="description">
                    <?php
                    echo class_exists( 'RB_SMTP_Mailer' )
                        ? esc_html__( 'Los correos salen por Skycode SMTP Mailer — asegúrate de que este remitente esté verificado ahí.', 'skycode-cart-recovery' )
                        : esc_html__( 'Skycode SMTP Mailer no está activo: los correos usarán el envío por defecto de WordPress.', 'skycode-cart-recovery' );
                    ?>
                </p>
            </label>
        </div>

        <div class="rb-cr-card">
            <h2><?php esc_html_e( 'Modo prueba', 'skycode-cart-recovery' ); ?></h2>
            <label class="rb-cr-field rb-cr-field-inline">
                <input type="checkbox" name="test_mode" value="1" <?php checked( $settings['test_mode'] ); ?>>
                <span><?php esc_html_e( 'Redirigir todos los envíos a un correo interno', 'skycode-cart-recovery' ); ?></span>
            </label>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Correo interno', 'skycode-cart-recovery' ); ?></span>
                <input type="email" name="test_email" value="<?php echo esc_attr( $settings['test_email'] ); ?>" class="widefat">
            </label>
            <p class="description"><?php esc_html_e( 'Actívalo mientras pruebas en local: sin esto, el webhook de confirmación no llega y no hay forma de simular una tienda real sin arriesgar enviarle correos a un cliente.', 'skycode-cart-recovery' ); ?></p>
        </div>

        <div class="rb-cr-card">
            <h2><?php esc_html_e( 'Datos y privacidad', 'skycode-cart-recovery' ); ?></h2>
            <label class="rb-cr-field">
                <span><?php esc_html_e( 'Conservar carritos cerrados durante (días)', 'skycode-cart-recovery' ); ?></span>
                <input type="number" min="7" name="retention_days" value="<?php echo esc_attr( $settings['retention_days'] ); ?>" class="small-text">
            </label>
            <label class="rb-cr-field rb-cr-field-inline">
                <input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $settings['delete_on_uninstall'] ); ?>>
                <span><?php esc_html_e( 'Borrar todos los datos del plugin al desinstalarlo', 'skycode-cart-recovery' ); ?></span>
            </label>
        </div>

    </div>

    <?php submit_button( __( 'Guardar ajustes', 'skycode-cart-recovery' ) ); ?>
</form>

</div>
