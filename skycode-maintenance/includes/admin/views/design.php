<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current = 'design';
include __DIR__ . '/nav.php';

$countdown_value = $settings['countdown_to'] ? gmdate( 'Y-m-d\TH:i', $settings['countdown_to'] ) : '';
$logo_url        = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'thumbnail' ) : '';
$preview_url      = add_query_arg( array( 'skc_preview' => 1, 'skc_preview_mode' => 'coming_soon' ), home_url( '/' ) );
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'skc_mm_save_design' ); ?>
    <input type="hidden" name="action" value="skc_mm_save_design">

    <div class="skc-mm-grid">

        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Estilo', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Plantilla', 'skycode-maintenance' ); ?></span>
                <select name="skin">
                    <?php foreach ( $skins as $skin ) : ?>
                        <option value="<?php echo esc_attr( $skin ); ?>" <?php selected( $settings['skin'], $skin ); ?>><?php echo esc_html( ucfirst( $skin ) ); ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" class="button"><?php esc_html_e( 'Vista previa', 'skycode-maintenance' ); ?></a>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Color de acento', 'skycode-maintenance' ); ?></span>
                <input type="text" name="accent" value="<?php echo esc_attr( $settings['accent'] ); ?>" class="skc-mm-color-picker">
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Color de texto', 'skycode-maintenance' ); ?></span>
                <input type="text" name="text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>" class="skc-mm-color-picker">
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Tipo de fondo', 'skycode-maintenance' ); ?></span>
                <select name="bg_type" id="skc-mm-bg-type">
                    <option value="color" <?php selected( $settings['bg_type'], 'color' ); ?>><?php esc_html_e( 'Color sólido', 'skycode-maintenance' ); ?></option>
                    <option value="image" <?php selected( $settings['bg_type'], 'image' ); ?>><?php esc_html_e( 'Imagen', 'skycode-maintenance' ); ?></option>
                    <option value="video" <?php selected( $settings['bg_type'], 'video' ); ?>><?php esc_html_e( 'Video (mp4)', 'skycode-maintenance' ); ?></option>
                </select>
            </label>
            <label class="skc-mm-field" id="skc-mm-bg-value-wrap">
                <span><?php esc_html_e( 'Color / URL de imagen o video de fondo', 'skycode-maintenance' ); ?></span>
                <input type="text" name="bg_value" id="skc-mm-bg-value" value="<?php echo esc_attr( $settings['bg_value'] ); ?>" class="regular-text" placeholder="https://.../video-desktop.mp4">
                <p class="description"><?php esc_html_e( 'Para video, pega la URL directa a un .mp4 (por ejemplo, el mismo video del hero de escritorio del sitio).', 'skycode-maintenance' ); ?></p>
            </label>
            <label class="skc-mm-field" id="skc-mm-bg-value-mobile-wrap" style="<?php echo 'video' === $settings['bg_type'] ? '' : 'display:none;'; ?>">
                <span><?php esc_html_e( 'URL de video para móvil (opcional)', 'skycode-maintenance' ); ?></span>
                <input type="text" name="bg_value_mobile" value="<?php echo esc_attr( $settings['bg_value_mobile'] ); ?>" class="regular-text" placeholder="https://.../video-mobile.mp4">
                <p class="description"><?php esc_html_e( 'Si lo dejas vacío, se usa el mismo video en móvil.', 'skycode-maintenance' ); ?></p>
            </label>
            <label class="skc-mm-field" id="skc-mm-bg-poster-wrap" style="<?php echo 'video' === $settings['bg_type'] ? '' : 'display:none;'; ?>">
                <span><?php esc_html_e( 'Imagen de portada mientras carga el video (opcional)', 'skycode-maintenance' ); ?></span>
                <input type="text" name="bg_poster" value="<?php echo esc_attr( $settings['bg_poster'] ); ?>" class="regular-text">
            </label>
        </div>

        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Logo', 'skycode-maintenance' ); ?></h2>
            <div class="skc-mm-logo-picker">
                <img id="skc-mm-logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="<?php echo $logo_url ? '' : 'display:none;'; ?>max-width:120px;display:block;margin-bottom:8px;">
                <input type="hidden" name="logo_id" id="skc-mm-logo-id" value="<?php echo esc_attr( $settings['logo_id'] ); ?>">
                <button type="button" class="button" id="skc-mm-logo-select"><?php esc_html_e( 'Elegir logo', 'skycode-maintenance' ); ?></button>
                <button type="button" class="button" id="skc-mm-logo-remove"><?php esc_html_e( 'Quitar', 'skycode-maintenance' ); ?></button>
            </div>
        </div>

        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Textos', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Título', 'skycode-maintenance' ); ?></span>
                <input type="text" name="headline" value="<?php echo esc_attr( $settings['headline'] ); ?>" class="regular-text">
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Subtítulo', 'skycode-maintenance' ); ?></span>
                <textarea name="subtext" rows="3" class="large-text"><?php echo esc_textarea( $settings['subtext'] ); ?></textarea>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Pie de página', 'skycode-maintenance' ); ?></span>
                <input type="text" name="footer_text" value="<?php echo esc_attr( $settings['footer_text'] ); ?>" class="regular-text">
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Cuenta atrás hasta', 'skycode-maintenance' ); ?></span>
                <input type="datetime-local" name="countdown_to" value="<?php echo esc_attr( $countdown_value ); ?>">
                <p class="description"><?php esc_html_e( 'Déjalo vacío para ocultar el contador.', 'skycode-maintenance' ); ?></p>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Redes sociales (una URL por línea)', 'skycode-maintenance' ); ?></span>
                <textarea name="socials" rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", (array) $settings['socials'] ) ); ?></textarea>
            </label>
        </div>

        <div class="skc-mm-card">
            <h2><?php esc_html_e( 'Suscripción', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field skc-mm-field-inline">
                <input type="checkbox" name="subscribe_enabled" value="1" <?php checked( $settings['subscribe_enabled'] ); ?>>
                <span><?php esc_html_e( 'Mostrar formulario "Avísame" y guardar suscriptores', 'skycode-maintenance' ); ?></span>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'Webhook al recibir un nuevo suscriptor (opcional)', 'skycode-maintenance' ); ?></span>
                <input type="url" name="subscribe_webhook" value="<?php echo esc_attr( $settings['subscribe_webhook'] ); ?>" class="regular-text" placeholder="https://tu-crm.com/webhook">
                <p class="description"><?php esc_html_e( 'Se envía un POST no bloqueante con email, site y fecha.', 'skycode-maintenance' ); ?></p>
            </label>
        </div>

        <div class="skc-mm-card skc-mm-card-wide">
            <h2><?php esc_html_e( 'Personalización avanzada', 'skycode-maintenance' ); ?></h2>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'CSS propio', 'skycode-maintenance' ); ?></span>
                <textarea name="custom_css" rows="6" class="large-text code"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
            </label>
            <label class="skc-mm-field">
                <span><?php esc_html_e( 'HTML adicional (se muestra debajo del contenido principal)', 'skycode-maintenance' ); ?></span>
                <textarea name="custom_html" rows="6" class="large-text code"><?php echo esc_textarea( $settings['custom_html'] ); ?></textarea>
            </label>
        </div>

    </div>

    <?php submit_button( __( 'Guardar ajustes', 'skycode-maintenance' ) ); ?>
</form>

</div>
