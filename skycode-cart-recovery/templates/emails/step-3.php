<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** Paso 3 — última oportunidad, con cupón único opcional. */
?>
<h1 style="margin:0 0 12px;font-size:22px;color:#191713;font-weight:700;">
    <?php esc_html_e( 'Tu carrito sigue esperando', 'skycode-cart-recovery' ); ?>
</h1>
<?php if ( ! empty( $coupon_code ) ) : ?>
<p style="margin:0 0 16px;font-size:15px;color:#403A31;line-height:1.6;">
    <?php echo wp_kses_post( sprintf(
        /* translators: 1: código de cupón, 2: porcentaje, 3: horas de validez */
        __( 'Usa el código <strong>%1$s</strong> para un %2$s%% de descuento, válido por %3$s horas.', 'skycode-cart-recovery' ),
        esc_html( $coupon_code ),
        (float) $step['coupon_percent'],
        (int) $step['coupon_hours']
    ) ); ?>
</p>
<?php endif; ?>
<p style="margin:0;font-size:15px;color:#403A31;line-height:1.6;">
    <?php esc_html_e( 'Esta es la última vez que te lo recordamos.', 'skycode-cart-recovery' ); ?>
</p>
