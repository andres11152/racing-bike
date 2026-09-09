<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'sequence';
include __DIR__ . '/nav.php';
?>

<?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="rb-cr-notice"><?php esc_html_e( 'Secuencia guardada.', 'skycode-cart-recovery' ); ?></div>
<?php endif; ?>

<p class="rb-cr-lede"><?php esc_html_e( 'Estos son los recordatorios que recibe un cliente después de abandonar el carrito. Los tres salen por email; SMS y WhatsApp se activarán como canal en próximas versiones sin que tengas que rehacer nada de esto.', 'skycode-cart-recovery' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'rb_cr_save_sequence' ); ?>
    <input type="hidden" name="action" value="rb_cr_save_sequence">

    <div class="rb-cr-steps">
        <?php foreach ( $sequence as $i => $step ) : ?>
        <div class="rb-cr-step">
            <div class="rb-cr-step-head">
                <span class="rb-cr-step-n"><?php echo (int) $step['step']; ?></span>
                <div class="rb-cr-step-delay">
                    <label><?php esc_html_e( 'Se envía', 'skycode-cart-recovery' ); ?>
                        <input type="number" min="1" name="step[<?php echo (int) $i; ?>][delay_hours]" value="<?php echo esc_attr( $step['delay_hours'] ); ?>" class="small-text">
                        <?php esc_html_e( 'horas después del abandono', 'skycode-cart-recovery' ); ?>
                    </label>
                </div>
                <div class="rb-cr-step-channels">
                    <span class="rb-cr-chan on"><?php esc_html_e( 'Email', 'skycode-cart-recovery' ); ?></span>
                    <span class="rb-cr-chan off"><?php esc_html_e( 'SMS · próximamente', 'skycode-cart-recovery' ); ?></span>
                    <span class="rb-cr-chan off"><?php esc_html_e( 'WhatsApp · próximamente', 'skycode-cart-recovery' ); ?></span>
                </div>
            </div>
            <div class="rb-cr-step-body">
                <label class="rb-cr-field">
                    <span><?php esc_html_e( 'Asunto del correo', 'skycode-cart-recovery' ); ?></span>
                    <input type="text" name="step[<?php echo (int) $i; ?>][subject]" value="<?php echo esc_attr( $step['subject'] ); ?>" class="widefat">
                </label>
                <input type="hidden" name="step[<?php echo (int) $i; ?>][template]" value="<?php echo esc_attr( $step['template'] ); ?>">
                <label class="rb-cr-field rb-cr-field-inline">
                    <input type="checkbox" name="step[<?php echo (int) $i; ?>][coupon_enabled]" value="1" <?php checked( ! empty( $step['coupon_enabled'] ) ); ?>>
                    <span><?php esc_html_e( 'Incluir cupón de descuento en este paso', 'skycode-cart-recovery' ); ?></span>
                </label>
                <div class="rb-cr-coupon-fields">
                    <label><?php esc_html_e( 'Descuento %', 'skycode-cart-recovery' ); ?>
                        <input type="number" min="1" max="90" name="step[<?php echo (int) $i; ?>][coupon_percent]" value="<?php echo esc_attr( $step['coupon_percent'] ); ?>" class="small-text">
                    </label>
                    <label><?php esc_html_e( 'Vence en (horas)', 'skycode-cart-recovery' ); ?>
                        <input type="number" min="1" name="step[<?php echo (int) $i; ?>][coupon_hours]" value="<?php echo esc_attr( $step['coupon_hours'] ); ?>" class="small-text">
                    </label>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rb-cr-templates&step=' . (int) $step['step'] ) ); ?>" class="rb-cr-edit-template">
                    <?php esc_html_e( 'Editar el contenido de este correo →', 'skycode-cart-recovery' ); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php submit_button( __( 'Guardar secuencia', 'skycode-cart-recovery' ) ); ?>
</form>

</div>
