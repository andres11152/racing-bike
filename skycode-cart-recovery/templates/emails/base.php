<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Envoltorio HTML común. Recibe $content (el cuerpo del paso), $cart_row,
 * $items, $recovery_url, $optout_url y $settings desde
 * RB_CR_Channel_Email::render_template().
 */
$store_name = get_bloginfo( 'name' );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#F3EFE8;font-family:Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EFE8;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E3DDD3;">
    <tr>
        <td style="background:#000000;padding:22px 28px;">
            <img src="<?php echo esc_attr( $logo_src ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="120" style="display:block;height:auto;border:0;">
        </td>
    </tr>
    <tr>
        <td style="padding:32px 28px 8px;">
            <?php echo $content; // phpcs:ignore -- HTML de paso propio, generado por el plugin. ?>
        </td>
    </tr>
    <?php if ( ! empty( $items ) ) : ?>
    <tr>
        <td style="padding:12px 28px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <?php foreach ( $items as $item ) : ?>
                <tr>
                    <td width="56" style="padding:8px 0;">
                        <img src="<?php echo esc_url( $item['image'] ); ?>" width="48" height="48" style="border-radius:6px;border:1px solid #E3DDD3;display:block;" alt="">
                    </td>
                    <td style="padding:8px 0 8px 12px;font-size:14px;color:#403A31;">
                        <?php echo esc_html( $item['name'] ); ?>
                        <?php if ( $item['quantity'] > 1 ) : ?>
                            <span style="color:#6E675D;">&times; <?php echo (int) $item['quantity']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td align="right" style="padding:8px 0;font-size:14px;color:#191713;white-space:nowrap;">
                        <?php echo wp_kses_post( wc_price( $item['price'] * $item['quantity'], array( 'currency' => $cart_row['currency'] ) ) ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td style="padding:20px 28px 32px;">
            <a href="<?php echo esc_url( $recovery_url ); ?>" style="display:inline-block;background:#10B981;color:#000000;text-decoration:none;font-weight:700;font-size:15px;padding:13px 26px;border-radius:8px;">
                <?php esc_html_e( 'Volver a mi carrito', 'skycode-cart-recovery' ); ?>
            </a>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 28px 26px;border-top:1px solid #E3DDD3;">
            <p style="margin:0;font-size:11px;color:#9C9284;line-height:1.6;">
                <?php echo esc_html( $store_name ); ?> ·
                <a href="<?php echo esc_url( $optout_url ); ?>" style="color:#9C9284;"><?php esc_html_e( 'Dejar de recibir estos avisos', 'skycode-cart-recovery' ); ?></a>
            </p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
