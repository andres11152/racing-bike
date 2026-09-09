<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'carts';
include __DIR__ . '/nav.php';

$labels = array(
    ''             => __( 'Todos', 'skycode-cart-recovery' ),
    'active'       => __( 'En curso', 'skycode-cart-recovery' ),
    'abandoned'    => __( 'Abandonado', 'skycode-cart-recovery' ),
    'recovering'   => __( 'En secuencia', 'skycode-cart-recovery' ),
    'recovered'    => __( 'Recuperado', 'skycode-cart-recovery' ),
    'lost'         => __( 'Perdido', 'skycode-cart-recovery' ),
    'unsubscribed' => __( 'Dado de baja', 'skycode-cart-recovery' ),
);

$total_pages = max( 1, ceil( $total / $per_page ) );
?>

<div class="rb-cr-card">
    <div class="rb-cr-filters">
        <?php foreach ( $labels as $key => $label ) :
            $count = '' === $key ? array_sum( $counts_by_status ) : ( $counts_by_status[ $key ] ?? 0 );
        ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rb-cr-carts' . ( $key ? '&status=' . $key : '' ) ) ); ?>"
               class="rb-cr-filter <?php echo $status_filter === $key ? 'active' : ''; ?>">
                <?php echo esc_html( $label ); ?> <span><?php echo (int) $count; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ( empty( $rows ) ) : ?>
        <p class="rb-cr-empty"><?php esc_html_e( 'No hay carritos en este filtro.', 'skycode-cart-recovery' ); ?></p>
    <?php else : ?>
    <table class="widefat striped rb-cr-table">
        <thead><tr>
            <th><?php esc_html_e( 'Cliente', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Carrito', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Estado', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Valor', 'skycode-cart-recovery' ); ?></th>
            <th><?php esc_html_e( 'Actualizado', 'skycode-cart-recovery' ); ?></th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $rows as $row ) :
            $items = RB_CR_Cart::items( $row );
            $names = wp_list_pluck( $items, 'name' );
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html( $row['first_name'] ?: __( 'Sin nombre', 'skycode-cart-recovery' ) ); ?></strong><br>
                    <span class="rb-cr-muted"><?php echo esc_html( $row['email'] ?: __( 'Sin contacto', 'skycode-cart-recovery' ) ); ?></span>
                </td>
                <td><?php echo esc_html( implode( ', ', array_slice( $names, 0, 2 ) ) . ( count( $names ) > 2 ? ' +' . ( count( $names ) - 2 ) : '' ) ); ?></td>
                <td><span class="rb-cr-pill rb-cr-status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $labels[ $row['status'] ] ?? $row['status'] ); ?></span></td>
                <td><?php echo $row['status'] === 'recovered' ? wp_kses_post( wc_price( $row['recovered_total'] ) ) : wp_kses_post( wc_price( $row['total'] ) ); ?></td>
                <td><?php echo esc_html( human_time_diff( strtotime( $row['updated_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', 'skycode-cart-recovery' ) ); ?></td>
                <td class="rb-cr-row-actions">
                    <?php if ( in_array( $row['status'], array( 'abandoned', 'recovering' ), true ) ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                            <?php wp_nonce_field( 'rb_cr_cart_action' ); ?>
                            <input type="hidden" name="action" value="rb_cr_cart_action">
                            <input type="hidden" name="cart_id" value="<?php echo (int) $row['id']; ?>">
                            <button type="submit" name="do" value="send_now" class="button button-small"><?php esc_html_e( 'Enviar ahora', 'skycode-cart-recovery' ); ?></button>
                            <button type="submit" name="do" value="exclude" class="button button-small"><?php esc_html_e( 'Excluir', 'skycode-cart-recovery' ); ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="rb-cr-pagination">
            <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                <a class="<?php echo $p === $paged ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $p ) ); ?>"><?php echo (int) $p; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</div>
