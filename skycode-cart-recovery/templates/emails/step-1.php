<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** Paso 1 — recordatorio simple, sin descuento. */
?>
<h1 style="margin:0 0 12px;font-size:22px;color:#191713;font-weight:700;">
    <?php echo esc_html( sprintf( __( 'Hola %s, se te quedó algo en el carrito', 'skycode-cart-recovery' ), $cart_row['first_name'] ?: '' ) ); ?>
</h1>
<p style="margin:0;font-size:15px;color:#403A31;line-height:1.6;">
    <?php esc_html_e( 'Todavía está esperándote, tal como lo dejaste. Termina tu pedido en un par de clics.', 'skycode-cart-recovery' ); ?>
</p>
