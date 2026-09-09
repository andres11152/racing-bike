<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'subscribers';
include __DIR__ . '/nav.php';
?>

<div class="skc-mm-card">
    <h2>
        <?php
        printf(
            /* translators: %d: number of subscribers */
            esc_html__( 'Suscriptores (%d)', 'skycode-maintenance' ),
            (int) $total
        );
        ?>
    </h2>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:16px;">
        <?php wp_nonce_field( 'skc_mm_export_subscribers' ); ?>
        <input type="hidden" name="action" value="skc_mm_export_subscribers">
        <button type="submit" class="button"><?php esc_html_e( 'Exportar CSV', 'skycode-maintenance' ); ?></button>
    </form>

    <?php if ( empty( $subscribers ) ) : ?>
        <p><?php esc_html_e( 'Aún no hay suscriptores.', 'skycode-maintenance' ); ?></p>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Email', 'skycode-maintenance' ); ?></th>
                    <th><?php esc_html_e( 'IP', 'skycode-maintenance' ); ?></th>
                    <th><?php esc_html_e( 'Fecha', 'skycode-maintenance' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $subscribers as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['email'] ); ?></td>
                        <td><?php echo esc_html( $row['ip'] ); ?></td>
                        <td><?php echo esc_html( $row['created_at'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
