<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var WP_Post[] $campaigns */
/** @var int $campaign_id */
/** @var array|null $stats */
/** @var array $variants */
?>
<div class="wrap skc-md-wrap">
    <?php include __DIR__ . '/nav.php'; ?>

    <form method="get" class="skc-md-inline-form">
        <input type="hidden" name="page" value="skc-md-stats" />
        <select name="campaign" onchange="this.form.submit()">
            <option value="0"><?php esc_html_e( 'Selecciona una campaña', 'skycode-modal' ); ?></option>
            <?php foreach ( $campaigns as $c ) : ?>
                <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $campaign_id, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ( $stats ) : ?>
        <div class="skc-md-stat-cards">
            <div class="skc-md-stat-card"><span class="skc-md-stat-num"><?php echo esc_html( $stats['impression'] ); ?></span><span><?php esc_html_e( 'Impresiones', 'skycode-modal' ); ?></span></div>
            <div class="skc-md-stat-card"><span class="skc-md-stat-num"><?php echo esc_html( $stats['close'] ); ?></span><span><?php esc_html_e( 'Cierres', 'skycode-modal' ); ?></span></div>
            <div class="skc-md-stat-card"><span class="skc-md-stat-num"><?php echo esc_html( $stats['convert'] ); ?></span><span><?php esc_html_e( 'Conversiones', 'skycode-modal' ); ?></span></div>
            <div class="skc-md-stat-card"><span class="skc-md-stat-num"><?php echo esc_html( $stats['conversion_rate'] ); ?>%</span><span><?php esc_html_e( 'Tasa de conversión', 'skycode-modal' ); ?></span></div>
        </div>

        <?php if ( $variants ) : ?>
            <h3><?php esc_html_e( 'Por variante', 'skycode-modal' ); ?></h3>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Variante', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Impresiones', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Conversiones', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Tasa', 'skycode-modal' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $variants as $key => $data ) : ?>
                    <tr><td><?php echo esc_html( $key ); ?></td><td><?php echo esc_html( $data['impression'] ); ?></td><td><?php echo esc_html( $data['convert'] ); ?></td><td><?php echo esc_html( $data['conversion_rate'] ); ?>%</td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p><?php esc_html_e( 'Selecciona una campaña para ver sus estadísticas.', 'skycode-modal' ); ?></p>
    <?php endif; ?>
</div>
