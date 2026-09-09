<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var int $post_id */
/** @var array $config */

$image_url = $config['image_id'] ? wp_get_attachment_image_url( $config['image_id'], 'medium' ) : '';
$has_image = ! empty( $image_url );
?>
<div
    class="skc-md-modal skc-md-skin-<?php echo esc_attr( $config['skin'] ); ?><?php echo $has_image ? ' skc-md-has-image' : ''; ?>"
    id="skc-md-modal-<?php echo esc_attr( $post_id ); ?>"
    data-campaign-id="<?php echo esc_attr( $post_id ); ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="skc-md-headline-<?php echo esc_attr( $post_id ); ?>"
    hidden
    style="--skc-md-accent: <?php echo esc_attr( $config['accent'] ); ?>; --skc-md-bg: <?php echo esc_attr( $config['bg_color'] ); ?>; --skc-md-text: <?php echo esc_attr( $config['text_color'] ); ?>;"
>
    <div class="skc-md-overlay" data-skc-md-close></div>
    <div class="skc-md-panel">
        <button type="button" class="skc-md-close" data-skc-md-close aria-label="<?php esc_attr_e( 'Cerrar', 'skycode-modal' ); ?>">&times;</button>

        <?php if ( $has_image ) : ?>
            <div class="skc-md-media">
                <img src="<?php echo esc_url( $image_url ); ?>" alt="" />
                <?php $watermark = SKC_MD_Settings::watermark(); ?>
                <?php if ( $watermark ) : ?>
                    <img
                        class="skc-md-watermark"
                        src="<?php echo esc_url( $watermark ); ?>"
                        alt=""
                        aria-hidden="true"
                        style="--skc-md-watermark-size: <?php echo absint( SKC_MD_Settings::get( 'watermark_size' ) ); ?>px;"
                    />
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="skc-md-body">
            <h2 class="skc-md-headline" id="skc-md-headline-<?php echo esc_attr( $post_id ); ?>" data-skc-md-field="headline"><?php echo esc_html( $config['headline'] ); ?></h2>
            <p class="skc-md-subtext" data-skc-md-field="subtext"><?php echo esc_html( $config['subtext'] ); ?></p>

            <form class="skc-md-form" novalidate>
                <?php wp_nonce_field( 'skc_md_lead', 'skc_md_nonce' ); ?>
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr( $post_id ); ?>" />
                <input type="hidden" name="variant" class="skc-md-variant-input" value="" />
                <input type="hidden" name="started_at" class="skc-md-started-at" value="" />
                <input type="text" name="<?php echo esc_attr( SKC_MD_Settings::get( 'honeypot_field' ) ); ?>" class="skc-md-hp" tabindex="-1" autocomplete="off" />

                <?php if ( ! empty( $config['fields_name'] ) ) : ?>
                    <label class="skc-md-label" for="skc-md-name-<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Nombre', 'skycode-modal' ); ?></label>
                    <input type="text" id="skc-md-name-<?php echo esc_attr( $post_id ); ?>" name="name" class="skc-md-input" autocomplete="name" placeholder="<?php esc_attr_e( 'Tu nombre', 'skycode-modal' ); ?>" />
                <?php endif; ?>

                <label class="skc-md-label" for="skc-md-email-<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Correo electrónico', 'skycode-modal' ); ?></label>
                <input type="email" id="skc-md-email-<?php echo esc_attr( $post_id ); ?>" name="email" class="skc-md-input" required autocomplete="email" placeholder="<?php esc_attr_e( 'tucorreo@ejemplo.com', 'skycode-modal' ); ?>" />

                <?php if ( ! empty( $config['fields_phone'] ) ) : ?>
                    <label class="skc-md-label" for="skc-md-phone-<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Teléfono', 'skycode-modal' ); ?></label>
                    <input type="tel" id="skc-md-phone-<?php echo esc_attr( $post_id ); ?>" name="phone" class="skc-md-input" autocomplete="tel" placeholder="<?php esc_attr_e( '+57 300 000 0000', 'skycode-modal' ); ?>" />
                <?php endif; ?>

                <?php if ( ! empty( $config['require_consent'] ) ) : ?>
                    <label class="skc-md-consent">
                        <input type="checkbox" name="consent" required />
                        <span><?php echo wp_kses_post( $config['consent_text'] ? $config['consent_text'] : SKC_MD_Settings::get( 'gdpr_consent_text' ) ); ?></span>
                    </label>
                <?php endif; ?>

                <button type="submit" class="skc-md-submit" data-skc-md-field="button_text"><?php echo esc_html( $config['button_text'] ); ?></button>

                <p class="skc-md-message" role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>
</div>
