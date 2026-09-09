<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/** @var WP_Post $post */
/** @var array $config */
/** @var array $stats */

$all_posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'posts_per_page' => 50, 'orderby' => 'title', 'order' => 'ASC' ) );
$roles     = wp_roles()->get_names();
?>
<div class="wrap skc-md-wrap">
    <?php include __DIR__ . '/nav.php'; ?>

    <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=skc-md-campaigns' ) ); ?>">&larr; <?php esc_html_e( 'Volver a campañas', 'skycode-modal' ); ?></a></p>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaña guardada.', 'skycode-modal' ); ?></p></div>
    <?php endif; ?>

    <div class="skc-md-editor-layout">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="skc-md-editor-form" id="skc-md-editor-form">
            <?php wp_nonce_field( 'skc_md_save_campaign' ); ?>
            <input type="hidden" name="action" value="skc_md_save_campaign" />
            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>" />

            <div class="skc-md-field-row">
                <input type="text" name="title" value="<?php echo esc_attr( $post->post_title ); ?>" class="large-text skc-md-campaign-title" />
                <label class="skc-md-status-toggle">
                    <input type="radio" name="status" value="active" <?php checked( $config['status'], 'active' ); ?> /> <?php esc_html_e( 'Activa', 'skycode-modal' ); ?>
                    <input type="radio" name="status" value="paused" <?php checked( $config['status'], 'paused' ); ?> /> <?php esc_html_e( 'Pausada', 'skycode-modal' ); ?>
                </label>
            </div>

            <nav class="nav-tab-wrapper skc-md-subtabs">
                <a href="#tab-design" class="nav-tab nav-tab-active" data-skc-md-tab="tab-design"><?php esc_html_e( 'Diseño', 'skycode-modal' ); ?></a>
                <a href="#tab-triggers" class="nav-tab" data-skc-md-tab="tab-triggers"><?php esc_html_e( 'Triggers', 'skycode-modal' ); ?></a>
                <a href="#tab-targeting" class="nav-tab" data-skc-md-tab="tab-targeting"><?php esc_html_e( 'Segmentación', 'skycode-modal' ); ?></a>
                <a href="#tab-form" class="nav-tab" data-skc-md-tab="tab-form"><?php esc_html_e( 'Formulario', 'skycode-modal' ); ?></a>
                <a href="#tab-integrations" class="nav-tab" data-skc-md-tab="tab-integrations"><?php esc_html_e( 'Integraciones', 'skycode-modal' ); ?></a>
                <a href="#tab-ab" class="nav-tab" data-skc-md-tab="tab-ab"><?php esc_html_e( 'A/B Testing', 'skycode-modal' ); ?></a>
            </nav>

            <div id="tab-design" class="skc-md-tab-panel">
                <table class="form-table">
                    <tr>
                        <th><label for="skin"><?php esc_html_e( 'Skin', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="skin" id="skin" class="skc-md-preview-field" data-preview="skin">
                                <?php foreach ( array( 'center' => 'Centro', 'slide-in' => 'Slide-in (lateral izq.)', 'bottom-bar' => 'Barra inferior', 'fullscreen' => 'Pantalla completa', 'sidebar' => 'Sidebar derecha' ) as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $config['skin'], $val ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="headline"><?php esc_html_e( 'Titular', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="headline" id="headline" class="large-text skc-md-preview-field" data-preview="headline" value="<?php echo esc_attr( $config['headline'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="subtext"><?php esc_html_e( 'Subtexto', 'skycode-modal' ); ?></label></th>
                        <td><textarea name="subtext" id="subtext" class="large-text skc-md-preview-field" data-preview="subtext" rows="2"><?php echo esc_textarea( $config['subtext'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="button_text"><?php esc_html_e( 'Texto del botón', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="button_text" id="button_text" class="regular-text skc-md-preview-field" data-preview="button_text" value="<?php echo esc_attr( $config['button_text'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="image_id"><?php esc_html_e( 'Imagen', 'skycode-modal' ); ?></label></th>
                        <td>
                            <input type="hidden" name="image_id" id="image_id" value="<?php echo esc_attr( $config['image_id'] ); ?>" />
                            <div id="skc-md-image-preview"><?php if ( $config['image_id'] ) { echo wp_get_attachment_image( $config['image_id'], 'thumbnail' ); } ?></div>
                            <button type="button" class="button" id="skc-md-select-image"><?php esc_html_e( 'Seleccionar imagen', 'skycode-modal' ); ?></button>
                            <button type="button" class="button" id="skc-md-remove-image"><?php esc_html_e( 'Quitar', 'skycode-modal' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="accent"><?php esc_html_e( 'Color de acento', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="accent" id="accent" class="skc-md-color skc-md-preview-field" data-preview="accent" value="<?php echo esc_attr( $config['accent'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="bg_color"><?php esc_html_e( 'Color de fondo', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="bg_color" id="bg_color" class="skc-md-color skc-md-preview-field" data-preview="bg_color" value="<?php echo esc_attr( $config['bg_color'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="text_color"><?php esc_html_e( 'Color de texto', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="text_color" id="text_color" class="skc-md-color skc-md-preview-field" data-preview="text_color" value="<?php echo esc_attr( $config['text_color'] ); ?>" /></td>
                    </tr>
                </table>
            </div>

            <div id="tab-triggers" class="skc-md-tab-panel" hidden>
                <table class="form-table">
                    <tr>
                        <th><label for="trigger_type"><?php esc_html_e( 'Disparador', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="trigger_type" id="trigger_type">
                                <?php foreach ( array(
                                    'time_delay'     => __( 'Retraso de tiempo (segundos)', 'skycode-modal' ),
                                    'scroll_percent'  => __( '% de scroll', 'skycode-modal' ),
                                    'exit_intent'     => __( 'Intención de salida', 'skycode-modal' ),
                                    'inactivity'      => __( 'Inactividad (segundos)', 'skycode-modal' ),
                                    'page_count'      => __( 'Nº de páginas vistas', 'skycode-modal' ),
                                    'click_selector'  => __( 'Clic en elemento (selector CSS)', 'skycode-modal' ),
                                    'on_load'         => __( 'Al cargar la página', 'skycode-modal' ),
                                ) as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $config['trigger_type'], $val ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="trigger_value"><?php esc_html_e( 'Valor', 'skycode-modal' ); ?></label></th>
                        <td><input type="number" min="0" name="trigger_value" id="trigger_value" value="<?php echo esc_attr( $config['trigger_value'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="trigger_selector"><?php esc_html_e( 'Selector CSS (solo para "Clic en elemento")', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="trigger_selector" id="trigger_selector" class="regular-text" value="<?php echo esc_attr( $config['trigger_selector'] ); ?>" placeholder=".mi-boton" /></td>
                    </tr>
                    <tr>
                        <th><label for="priority"><?php esc_html_e( 'Prioridad (menor = primero)', 'skycode-modal' ); ?></label></th>
                        <td><input type="number" min="1" name="priority" id="priority" value="<?php echo esc_attr( $config['priority'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="freq_max_impressions"><?php esc_html_e( 'Impresiones máx. por visitante', 'skycode-modal' ); ?></label></th>
                        <td><input type="number" min="0" name="freq_max_impressions" id="freq_max_impressions" value="<?php echo esc_attr( $config['freq_max_impressions'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="freq_cooldown_days"><?php esc_html_e( 'Días de espera tras alcanzar el máximo', 'skycode-modal' ); ?></label></th>
                        <td><input type="number" min="0" name="freq_cooldown_days" id="freq_cooldown_days" value="<?php echo esc_attr( $config['freq_cooldown_days'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Ocultar tras conversión', 'skycode-modal' ); ?></th>
                        <td><label><input type="checkbox" name="freq_hide_on_convert" value="1" <?php checked( $config['freq_hide_on_convert'] ); ?> /> <?php esc_html_e( 'No volver a mostrar tras enviar el formulario', 'skycode-modal' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Programación', 'skycode-modal' ); ?></th>
                        <td>
                            <label><?php esc_html_e( 'Desde', 'skycode-modal' ); ?>
                                <input type="datetime-local" name="schedule_start" value="<?php echo $config['schedule_start'] ? esc_attr( date( 'Y-m-d\TH:i', $config['schedule_start'] ) ) : ''; ?>" />
                            </label>
                            <label><?php esc_html_e( 'Hasta', 'skycode-modal' ); ?>
                                <input type="datetime-local" name="schedule_end" value="<?php echo $config['schedule_end'] ? esc_attr( date( 'Y-m-d\TH:i', $config['schedule_end'] ) ) : ''; ?>" />
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-targeting" class="skc-md-tab-panel" hidden>
                <table class="form-table">
                    <tr>
                        <th><label for="target_pages"><?php esc_html_e( 'Páginas', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_pages" id="target_pages">
                                <option value="all" <?php selected( $config['target_pages'], 'all' ); ?>><?php esc_html_e( 'Todas las páginas', 'skycode-modal' ); ?></option>
                                <option value="include" <?php selected( $config['target_pages'], 'include' ); ?>><?php esc_html_e( 'Solo en estas páginas', 'skycode-modal' ); ?></option>
                                <option value="exclude" <?php selected( $config['target_pages'], 'exclude' ); ?>><?php esc_html_e( 'En todas menos estas', 'skycode-modal' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_post_ids"><?php esc_html_e( 'Páginas/entradas', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_post_ids[]" id="target_post_ids" multiple size="6" style="min-width:300px;">
                                <?php foreach ( $all_posts as $p ) : ?>
                                    <option value="<?php echo esc_attr( $p->ID ); ?>" <?php echo in_array( $p->ID, $config['target_post_ids'], true ) ? 'selected' : ''; ?>><?php echo esc_html( $p->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_device"><?php esc_html_e( 'Dispositivo', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_device" id="target_device">
                                <option value="all" <?php selected( $config['target_device'], 'all' ); ?>><?php esc_html_e( 'Todos', 'skycode-modal' ); ?></option>
                                <option value="desktop" <?php selected( $config['target_device'], 'desktop' ); ?>><?php esc_html_e( 'Solo escritorio', 'skycode-modal' ); ?></option>
                                <option value="mobile" <?php selected( $config['target_device'], 'mobile' ); ?>><?php esc_html_e( 'Solo móvil', 'skycode-modal' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_user_state"><?php esc_html_e( 'Estado de sesión', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_user_state" id="target_user_state">
                                <option value="all" <?php selected( $config['target_user_state'], 'all' ); ?>><?php esc_html_e( 'Todos', 'skycode-modal' ); ?></option>
                                <option value="guest" <?php selected( $config['target_user_state'], 'guest' ); ?>><?php esc_html_e( 'Solo invitados', 'skycode-modal' ); ?></option>
                                <option value="logged_in" <?php selected( $config['target_user_state'], 'logged_in' ); ?>><?php esc_html_e( 'Solo logueados', 'skycode-modal' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_roles"><?php esc_html_e( 'Roles (si logueado)', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_roles[]" id="target_roles" multiple size="4">
                                <?php foreach ( $roles as $slug => $label ) : ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>" <?php echo in_array( $slug, $config['target_roles'], true ) ? 'selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_first_visit"><?php esc_html_e( 'Visita', 'skycode-modal' ); ?></label></th>
                        <td>
                            <select name="target_first_visit" id="target_first_visit">
                                <option value="all" <?php selected( $config['target_first_visit'], 'all' ); ?>><?php esc_html_e( 'Todas', 'skycode-modal' ); ?></option>
                                <option value="first_time" <?php selected( $config['target_first_visit'], 'first_time' ); ?>><?php esc_html_e( 'Solo primera visita', 'skycode-modal' ); ?></option>
                                <option value="returning" <?php selected( $config['target_first_visit'], 'returning' ); ?>><?php esc_html_e( 'Solo visitantes recurrentes', 'skycode-modal' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="target_utm_source"><?php esc_html_e( 'UTM source requerido', 'skycode-modal' ); ?></label></th>
                        <td><input type="text" name="target_utm_source" id="target_utm_source" class="regular-text" value="<?php echo esc_attr( $config['target_utm_source'] ); ?>" placeholder="<?php esc_attr_e( 'ej: newsletter (vacío = sin filtro)', 'skycode-modal' ); ?>" /></td>
                    </tr>
                </table>
            </div>

            <div id="tab-form" class="skc-md-tab-panel" hidden>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Campos', 'skycode-modal' ); ?></th>
                        <td>
                            <label><input type="checkbox" name="fields_name" value="1" <?php checked( $config['fields_name'] ); ?> /> <?php esc_html_e( 'Pedir nombre', 'skycode-modal' ); ?></label><br />
                            <label><input type="checkbox" name="fields_phone" value="1" <?php checked( $config['fields_phone'] ); ?> /> <?php esc_html_e( 'Pedir teléfono', 'skycode-modal' ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Consentimiento RGPD', 'skycode-modal' ); ?></th>
                        <td>
                            <label><input type="checkbox" name="require_consent" value="1" <?php checked( $config['require_consent'] ); ?> /> <?php esc_html_e( 'Exigir casilla de consentimiento', 'skycode-modal' ); ?></label><br />
                            <textarea name="consent_text" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Deja vacío para usar el texto global de Ajustes.', 'skycode-modal' ); ?>"><?php echo esc_textarea( $config['consent_text'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="redirect_after_submit"><?php esc_html_e( 'Redirigir tras enviar (opcional)', 'skycode-modal' ); ?></label></th>
                        <td><input type="url" name="redirect_after_submit" id="redirect_after_submit" class="regular-text" value="<?php echo esc_attr( $config['redirect_after_submit'] ); ?>" placeholder="https://" /></td>
                    </tr>
                </table>
            </div>

            <div id="tab-integrations" class="skc-md-tab-panel" hidden>
                <table class="form-table">
                    <tr>
                        <th><label for="webhook"><?php esc_html_e( 'Webhook de esta campaña', 'skycode-modal' ); ?></label></th>
                        <td>
                            <input type="url" name="webhook" id="webhook" class="large-text" value="<?php echo esc_attr( $config['webhook'] ); ?>" placeholder="https://hooks.zapier.com/..." />
                            <p class="description"><?php esc_html_e( 'POST en JSON con email, nombre, teléfono y consentimiento. Compatible con Zapier, Make, n8n y la mayoría de ESPs. Si se deja vacío se usa el webhook global de Ajustes.', 'skycode-modal' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-ab" class="skc-md-tab-panel" hidden>
                <p class="description"><?php esc_html_e( 'Añade variantes para repartir el tráfico y comparar su tasa de conversión. Si no añades ninguna, se usa el titular/subtexto/botón de la pestaña Diseño para todos los visitantes.', 'skycode-modal' ); ?></p>
                <div id="skc-md-variants"></div>
                <button type="button" class="button" id="skc-md-add-variant"><?php esc_html_e( '+ Añadir variante', 'skycode-modal' ); ?></button>
                <input type="hidden" name="variants_json" id="variants_json" value="<?php echo esc_attr( wp_json_encode( $config['variants'] ) ); ?>" />

                <?php if ( ! empty( $stats ) ) : $vstats = SKC_MD_Events::variant_stats( $post->ID ); if ( $vstats ) : ?>
                    <h3><?php esc_html_e( 'Resultados por variante', 'skycode-modal' ); ?></h3>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e( 'Variante', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Impresiones', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Conversiones', 'skycode-modal' ); ?></th><th><?php esc_html_e( 'Tasa', 'skycode-modal' ); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ( $vstats as $key => $data ) : ?>
                            <tr><td><?php echo esc_html( $key ); ?></td><td><?php echo esc_html( $data['impression'] ); ?></td><td><?php echo esc_html( $data['convert'] ); ?></td><td><?php echo esc_html( $data['conversion_rate'] ); ?>%</td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; endif; ?>
            </div>

            <p class="submit"><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Guardar campaña', 'skycode-modal' ); ?></button></p>
        </form>

        <aside class="skc-md-preview-pane">
            <h3><?php esc_html_e( 'Vista previa', 'skycode-modal' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Usa el mismo HTML y CSS que el sitio real; cambia el skin para ver cómo se comporta cada uno.', 'skycode-modal' ); ?></p>
            <div class="skc-md-preview-viewport">
                <div
                    class="skc-md-modal skc-md-open skc-md-skin-<?php echo esc_attr( $config['skin'] ); ?>"
                    id="skc-md-live-preview"
                    style="--skc-md-accent: <?php echo esc_attr( $config['accent'] ); ?>; --skc-md-bg: <?php echo esc_attr( $config['bg_color'] ); ?>; --skc-md-text: <?php echo esc_attr( $config['text_color'] ); ?>;"
                >
                    <div class="skc-md-overlay"></div>
                    <div class="skc-md-panel" id="skc-md-preview-panel">
                        <button type="button" class="skc-md-close" aria-hidden="true">&times;</button>
                        <div class="skc-md-media" id="skc-md-preview-image">
                            <?php if ( $config['image_id'] ) { echo wp_get_attachment_image( $config['image_id'], 'medium' ); } ?>
                            <?php $preview_watermark = SKC_MD_Settings::watermark(); ?>
                            <?php if ( $preview_watermark ) : ?>
                                <img class="skc-md-watermark" src="<?php echo esc_url( $preview_watermark ); ?>" alt="" aria-hidden="true" style="--skc-md-watermark-size: <?php echo absint( SKC_MD_Settings::get( 'watermark_size' ) ); ?>px;" />
                            <?php endif; ?>
                        </div>
                        <h2 class="skc-md-headline" id="skc-md-preview-headline"><?php echo esc_html( $config['headline'] ); ?></h2>
                        <p class="skc-md-subtext" id="skc-md-preview-subtext"><?php echo esc_html( $config['subtext'] ); ?></p>
                        <button type="button" class="skc-md-submit" id="skc-md-preview-button" disabled><?php echo esc_html( $config['button_text'] ); ?></button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
