<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'log';
include __DIR__ . '/nav.php';
?>

<div class="rb-cr-card">
    <p class="rb-cr-muted"><?php esc_html_e( 'Cada intento de envío queda aquí, con la respuesta del servidor de correo. Si un cliente dice que no le llegó, este es el primer sitio para revisar.', 'skycode-cart-recovery' ); ?></p>

    <?php if ( empty( $events ) ) : ?>
        <p class="rb-cr-empty"><?php esc_html_e( 'Todavía no hay eventos registrados.', 'skycode-cart-recovery' ); ?></p>
    <?php else : ?>
    <table class="widefat striped rb-cr-table">
        <thead><tr>
            <th><?php esc_html_e( 'Fecha', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Cliente', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Canal', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Paso', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Evento', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Detalle', 'skycode-cart-recovery' ); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $events as $e ) : ?>
            <tr>
                <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $e['created_at'] ) ) ); ?></td>
                <td><?php echo esc_html( $e['email'] ?: '—' ); ?></td>
                <td><?php echo esc_html( ucfirst( $e['channel'] ) ); ?></td>
                <td><?php echo (int) $e['step']; ?></td>
                <td><span class="rb-cr-pill rb-cr-pill-<?php echo esc_attr( $e['event'] ); ?>"><?php echo esc_html( ucfirst( $e['event'] ) ); ?></span></td>
                <td class="rb-cr-muted"><?php echo esc_html( wp_trim_words( (string) $e['detail'], 12 ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div>
