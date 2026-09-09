<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var WP_Post[] $campaigns */
?>
<div class="wrap skc-md-wrap">
    <?php include __DIR__ . '/nav.php'; ?>

    <?php if ( isset( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaña eliminada.', 'skycode-modal' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="skc-md-inline-form">
        <?php wp_nonce_field( 'skc_md_create_campaign' ); ?>
        <input type="hidden" name="action" value="skc_md_create_campaign" />
        <input type="text" name="title" placeholder="<?php esc_attr_e( 'Nombre de la campaña', 'skycode-modal' ); ?>" required />
        <button type="submit" class="button button-primary"><?php esc_html_e( '+ Nueva campaña', 'skycode-modal' ); ?></button>
    </form>

    <table class="widefat striped skc-md-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Nombre', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Estado', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Skin', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Trigger', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Impresiones', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Conversiones', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Tasa', 'skycode-modal' ); ?></th>
                <th><?php esc_html_e( 'Acciones', 'skycode-modal' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $campaigns ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Aún no hay campañas.', 'skycode-modal' ); ?></td></tr>
            <?php endif; ?>
            <?php foreach ( $campaigns as $post ) :
                $config = SKC_MD_Campaign::get_config( $post->ID );
                $stats  = SKC_MD_Events::stats_for_campaign( $post->ID );
                $edit_url = admin_url( 'admin.php?page=skc-md-editor&id=' . $post->ID );
            ?>
                <tr>
                    <td><a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $post->post_title ); ?></strong></a></td>
                    <td><span class="skc-md-badge skc-md-badge-<?php echo esc_attr( $config['status'] ); ?>"><?php echo 'active' === $config['status'] ? esc_html__( 'Activa', 'skycode-modal' ) : esc_html__( 'Pausada', 'skycode-modal' ); ?></span></td>
                    <td><?php echo esc_html( $config['skin'] ); ?></td>
                    <td><?php echo esc_html( $config['trigger_type'] ); ?></td>
                    <td><?php echo esc_html( $stats['impression'] ); ?></td>
                    <td><?php echo esc_html( $stats['convert'] ); ?></td>
                    <td><?php echo esc_html( $stats['conversion_rate'] ); ?>%</td>
                    <td>
                        <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'skycode-modal' ); ?></a>
                        |
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=skc_md_duplicate_campaign&id=' . $post->ID ), 'skc_md_duplicate_campaign' ) ); ?>"><?php esc_html_e( 'Duplicar', 'skycode-modal' ); ?></a>
                        |
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=skc_md_delete_campaign&id=' . $post->ID ), 'skc_md_delete_campaign' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( '¿Eliminar esta campaña?', 'skycode-modal' ) ); ?>');" class="skc-md-danger"><?php esc_html_e( 'Eliminar', 'skycode-modal' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
