<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'templates';
include __DIR__ . '/nav.php';

$tags = array(
    '{{nombre}}'    => __( 'Nombre del cliente', 'skycode-cart-recovery' ),
    '{{productos}}' => __( 'Productos del carrito', 'skycode-cart-recovery' ),
    '{{total}}'     => __( 'Total del carrito', 'skycode-cart-recovery' ),
    '{{cupon}}'     => __( 'Código de cupón (si el paso lo tiene activado)', 'skycode-cart-recovery' ),
);

$preview_subject = $step ? RB_CR_Channel_Email::render_string( $step['subject'], $preview_cart, $step['coupon_enabled'] ? 'VUELVEPRUEBA' : '' ) : '';
$preview_html    = $step ? RB_CR_Channel_Email::render_template( $step['template'], $preview_cart, $step, $step['coupon_enabled'] ? 'VUELVEPRUEBA' : '' ) : '';
?>

<?php if ( isset( $_GET['saved'] ) ) : ?><div class="rb-cr-notice"><?php esc_html_e( 'Plantilla guardada.', 'skycode-cart-recovery' ); ?></div><?php endif; ?>
<?php if ( isset( $_GET['test'] ) ) : ?>
    <div class="rb-cr-notice <?php echo 'ok' === $_GET['test'] ? '' : 'error'; ?>">
        <?php echo 'ok' === $_GET['test'] ? esc_html__( 'Correo de prueba enviado.', 'skycode-cart-recovery' ) : esc_html__( 'No se pudo enviar el correo de prueba. Revisa la configuración SMTP.', 'skycode-cart-recovery' ); ?>
    </div>
<?php endif; ?>

<div class="rb-cr-tpl-tabs">
    <?php foreach ( $sequence as $s ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rb-cr-templates&step=' . (int) $s['step'] ) ); ?>" class="rb-cr-tpl-tab <?php echo (int) $active === (int) $s['step'] ? 'active' : ''; ?>">
            <?php echo esc_html( sprintf( __( 'Paso %d', 'skycode-cart-recovery' ), $s['step'] ) ); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ( $step ) : ?>
<div class="rb-cr-tpl-grid">
    <div class="rb-cr-card">
        <h2><?php esc_html_e( 'Asunto', 'skycode-cart-recovery' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rb-cr-stack">
            <?php wp_nonce_field( 'rb_cr_save_template' ); ?>
            <input type="hidden" name="action" value="rb_cr_save_template">
            <input type="hidden" name="step_number" value="<?php echo (int) $step['step']; ?>">
            <input type="text" name="subject" value="<?php echo esc_attr( $step['subject'] ); ?>" class="widefat">

            <div class="rb-cr-tags">
                <?php foreach ( $tags as $tag => $desc ) : ?>
                    <button type="button" class="rb-cr-tag-btn" data-tag="<?php echo esc_attr( $tag ); ?>" title="<?php echo esc_attr( $desc ); ?>"><?php echo esc_html( $tag ); ?></button>
                <?php endforeach; ?>
            </div>

            <?php submit_button( __( 'Guardar asunto', 'skycode-cart-recovery' ) ); ?>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Enviar prueba', 'skycode-cart-recovery' ); ?></h2>
        <p class="rb-cr-muted"><?php esc_html_e( 'Con los datos del último carrito registrado (o de ejemplo si aún no hay ninguno).', 'skycode-cart-recovery' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rb-cr-stack">
            <?php wp_nonce_field( 'rb_cr_send_test' ); ?>
            <input type="hidden" name="action" value="rb_cr_send_test">
            <input type="hidden" name="step_number" value="<?php echo (int) $step['step']; ?>">
            <input type="email" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="widefat" required>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enviar prueba a mi correo', 'skycode-cart-recovery' ); ?></button>
        </form>
    </div>

    <div class="rb-cr-card rb-cr-preview">
        <h2><?php esc_html_e( 'Vista previa', 'skycode-cart-recovery' ); ?></h2>
        <p class="rb-cr-muted"><strong><?php esc_html_e( 'Asunto:', 'skycode-cart-recovery' ); ?></strong> <?php echo esc_html( $preview_subject ); ?></p>
        <iframe class="rb-cr-preview-frame" srcdoc="<?php echo esc_attr( $preview_html ); ?>"></iframe>
    </div>
</div>
<?php endif; ?>

</div>
