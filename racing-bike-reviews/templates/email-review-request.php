<?php
/**
 * Email de solicitud de reseña post-compra.
 *
 * Variables disponibles: $order (WC_Order), $items_html (string), $optout_url (string), $is_reminder (bool).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$heading = $is_reminder
    ? __( '¿Ya la probaste?', 'racing-bike-reviews' )
    : __( '¿Cómo te fue con tu compra?', 'racing-bike-reviews' );

$intro = $is_reminder
    ? __( 'Hace unos días recibiste tu pedido. Nos encantaría saber qué te pareció — tu opinión ayuda a otros ciclistas a elegir mejor.', 'racing-bike-reviews' )
    : __( 'Esperamos que ya estés rodando. Cuéntanos tu experiencia: calificación, fotos y tu estatura/talla ayudan a otros compradores a encontrar su marco ideal.', 'racing-bike-reviews' );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:8px;overflow:hidden;">
          <tr>
            <td style="background:#000000;padding:24px 32px;">
              <span style="color:#ffffff;font-size:14px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">RACING BIKE 1998</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 12px;font-size:20px;color:#111111;text-transform:uppercase;letter-spacing:0.02em;"><?php echo esc_html( $heading ); ?></h1>
              <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#444444;"><?php echo esc_html( $intro ); ?></p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
                <?php echo $items_html; ?>
              </table>

              <p style="margin:24px 0 0;font-size:11px;line-height:1.6;color:#999999;">
                <?php esc_html_e( 'Al hacer clic entrarás directo al formulario de reseña de ese producto.', 'racing-bike-reviews' ); ?>
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px;background:#f9f9f9;border-top:1px solid #eeeeee;">
              <p style="margin:0;font-size:11px;color:#999999;">
                <?php esc_html_e( 'Pedido', 'racing-bike-reviews' ); ?> #<?php echo esc_html( $order->get_order_number() ); ?>
                &middot;
                <a href="<?php echo esc_url( $optout_url ); ?>" style="color:#999999;"><?php esc_html_e( 'No quiero recibir esta solicitud', 'racing-bike-reviews' ); ?></a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
