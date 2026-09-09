<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'dashboard';
include __DIR__ . '/nav.php';

$abandoned       = (int) ( $stats['abandoned'] ?? 0 );
$lost_value      = (float) ( $stats['lost_value'] ?? 0 );
$recovered       = (int) ( $stats['recovered'] ?? 0 );
$recovered_value = (float) ( $stats['recovered_value'] ?? 0 );

$max_day = 1;
foreach ( $weekly as $w ) {
    $max_day = max( $max_day, (int) $w['total'] );
}
?>

<div class="rb-cr-kpis">
    <div class="rb-cr-kpi">
        <span class="k"><?php esc_html_e( 'Abandonados ahora', 'skycode-cart-recovery' ); ?></span>
        <span class="n"><?php echo esc_html( number_format_i18n( $abandoned ) ); ?></span>
    </div>
    <div class="rb-cr-kpi">
        <span class="k"><?php esc_html_e( 'Valor en carritos', 'skycode-cart-recovery' ); ?></span>
        <span class="n"><?php echo wp_kses_post( wc_price( $lost_value ) ); ?></span>
    </div>
    <div class="rb-cr-kpi ok">
        <span class="k"><?php esc_html_e( 'Recuperados (30 días)', 'skycode-cart-recovery' ); ?></span>
        <span class="n"><?php echo esc_html( number_format_i18n( $recovered ) ); ?></span>
    </div>
    <div class="rb-cr-kpi ok">
        <span class="k"><?php esc_html_e( 'Ingreso recuperado', 'skycode-cart-recovery' ); ?></span>
        <span class="n"><?php echo wp_kses_post( wc_price( $recovered_value ) ); ?></span>
    </div>
</div>

<div class="rb-cr-card">
    <h2><?php esc_html_e( 'Carritos abandonados por día — últimos 14 días', 'skycode-cart-recovery' ); ?></h2>
    <div class="rb-cr-bars">
        <?php foreach ( $weekly as $w ) :
            $pct = round( ( (int) $w['total'] / $max_day ) * 100 );
        ?>
            <div class="rb-cr-bar-col" title="<?php echo esc_attr( $w['day'] . ': ' . $w['total'] ); ?>">
                <div class="rb-cr-bar" style="height:<?php echo (int) max( 4, $pct ); ?>%"></div>
                <span class="rb-cr-bar-label"><?php echo esc_html( date_i18n( 'd/m', strtotime( $w['day'] ) ) ); ?></span>
            </div>
        <?php endforeach; ?>
        <?php if ( empty( $weekly ) ) : ?>
            <p class="rb-cr-empty"><?php esc_html_e( 'Todavía no hay datos suficientes.', 'skycode-cart-recovery' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="rb-cr-card">
    <h2><?php esc_html_e( 'Actividad reciente', 'skycode-cart-recovery' ); ?></h2>
    <?php if ( empty( $events ) ) : ?>
        <p class="rb-cr-empty"><?php esc_html_e( 'Todavía no hay eventos. En cuanto un cliente abandone un carrito con email, aparecerá aquí.', 'skycode-cart-recovery' ); ?></p>
    <?php else : ?>
        <table class="widefat striped rb-cr-table">
            <thead><tr>
                <th><?php esc_html_e( 'Cuándo', 'skycode-cart-recovery' ); ?></th>
                <th><?php esc_html_e( 'Cliente', 'skycode-cart-recovery' ); ?></th>
                <th><?php esc_html_e( 'Paso', 'skycode-cart-recovery' ); ?></th>
                <th><?php esc_html_e( 'Evento', 'skycode-cart-recovery' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $events as $e ) : ?>
                <tr>
                    <td><?php echo esc_html( human_time_diff( strtotime( $e['created_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'atrás', 'skycode-cart-recovery' ) ); ?></td>
                    <td><?php echo esc_html( $e['email'] ?: '—' ); ?></td>
                    <td><?php echo (int) $e['step']; ?></td>
                    <td><span class="rb-cr-pill rb-cr-pill-<?php echo esc_attr( $e['event'] ); ?>"><?php echo esc_html( ucfirst( $e['event'] ) ); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
