<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var array $leads */
/** @var WP_Post[] $campaigns */
/** @var int $campaign_id */
?>
<div class="wrap skc-md-wrap">
    <?php include __DIR__ . '/nav.php'; ?>

    <form method="get" class="skc-md-inline-form">
        <input type="hidden" name="page" value="skc-md-leads" />
        <select name="campaign" onchange="this.form.submit()">
            <option value="0"><?php esc_html_e( 'Todas las campañas', 'skycode-modal' ); ?></option>
            <?php foreach ( $campaigns as $c ) : ?>
                <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $campaign_id, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=skc_md_export_leads&campaign=' . $campaign_id ), 'skc_md_export_leads' ) ); ?>"><?php esc_html_e( 'Exportar CSV', 'skycode-modal' ); ?></a>

    <table class="widefat striped skc-md-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Email', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Nombre', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Teléfono', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Campaña', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Consentimiento', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Fecha', 'skycode-modal' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $leads ) ) : ?>
                <tr><td colspan="6"><?php esc_html_e( 'Sin suscriptores todavía.', 'skycode-modal' ); ?></td></tr>
            <?php endif; ?>
            <?php foreach ( $leads as $lead ) : ?>
                <tr>
                    <td><?php echo esc_html( $lead['email'] ); ?></td>
                    <td><?php echo esc_html( $lead['name'] ); ?></td>
                    <td><?php echo esc_html( $lead['phone'] ); ?></td>
                    <td><?php echo esc_html( get_the_title( $lead['campaign_id'] ) ); ?></td>
                    <td><?php echo $lead['consent'] ? esc_html__( 'Sí', 'skycode-modal' ) : esc_html__( 'No', 'skycode-modal' ); ?></td>
                    <td><?php echo esc_html( $lead['created_at'] ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
