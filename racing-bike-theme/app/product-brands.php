<?php

/**
 * Logos de marca (taxonomía pa_marca) — autoadministrables desde
 * WooCommerce > Atributos > Marca.
 *
 * Antes el logo de cada marca era un array hardcodeado en tres archivos
 * Blade distintos (filter-sidebar, single-product, product-card), apuntando
 * a 6 SVGs sueltos por slug. Agregar una marca nueva no le ponía logo a
 * menos que alguien tocara código. Ahora el logo vive en term meta, con un
 * campo de imagen real en la pantalla de edición del término — mismo patrón
 * que usa WooCommerce core para su propia taxonomía product_brand.
 */

namespace App;

if (! defined('ABSPATH')) {
    exit;
}

const BRAND_TAXONOMY = 'pa_marca';
const BRAND_LOGO_META_KEY = '_rb_brand_logo_id';

/**
 * URL del logo de una marca, o null si el término no tiene uno asignado.
 *
 * @param  \WP_Term|int  $term
 */
function brand_logo_url($term, string $size = 'medium'): ?string
{
    $slug = '';
    $term_id = 0;

    if (is_object($term)) {
        $slug = $term->slug ?? '';
        $term_id = $term->term_id ?? 0;
    } elseif (is_numeric($term)) {
        $term_obj = get_term((int) $term, BRAND_TAXONOMY);
        if ($term_obj && ! is_wp_error($term_obj)) {
            $slug = $term_obj->slug;
            $term_id = $term_obj->term_id;
        }
    } elseif (is_string($term)) {
        $slug = sanitize_title($term);
    }

    if (! $slug) {
        return null;
    }

    // 1. Priorizar el SVG oficial incluido en el tema (public/images/brands/{slug}.svg).
    // Con esto, al desplegar el ZIP en cualquier servidor (producción/staging),
    // los logos actualizados (GW, Shimano, PRO, etc.) se muestran de inmediato
    // sin depender de la base de datos remota ni de archivos viejos en uploads/.
    $svg_path = get_theme_file_path("public/images/brands/{$slug}.svg");
    if (file_exists($svg_path)) {
        $v = (int) filemtime($svg_path);
        return get_theme_file_uri("public/images/brands/{$slug}.svg")."?v={$v}";
    }

    // 2. Si no hay SVG en el tema, verificar si hay imagen en term meta (WooCommerce > Atributos > Marca)
    if ($term_id) {
        $logo_id = (int) get_term_meta($term_id, BRAND_LOGO_META_KEY, true);
        if ($logo_id) {
            $url = wp_get_attachment_image_url($logo_id, $size);
            if ($url) {
                return $url;
            }
        }
    }

    // 3. Fallback: otras extensiones en public/images/brands/
    $extensions = ['png', 'webp', 'jpg'];
    foreach ($extensions as $ext) {
        $file_path = get_theme_file_path("public/images/brands/{$slug}.{$ext}");
        if (file_exists($file_path)) {
            $v = (int) filemtime($file_path);
            return get_theme_file_uri("public/images/brands/{$slug}.{$ext}")."?v={$v}";
        }
    }

    // 4. Fallback secundario: mapa histórico por slug a archivos numerados (1.svg a 6.svg)
    $legacy_map = [
        'orbea'       => '1.svg',
        'gw'          => '2.svg',
        'shimano'     => '3.svg',
        'pro'         => '4.svg',
        'cliff'       => '5.svg',
        'continental' => '6.svg',
    ];

    if (isset($legacy_map[$slug])) {
        $file_path = get_theme_file_path("public/images/brands/{$legacy_map[$slug]}");
        if (file_exists($file_path)) {
            $v = (int) filemtime($file_path);
            return get_theme_file_uri("public/images/brands/{$legacy_map[$slug]}")."?v={$v}";
        }
    }

    return null;
}

/**
 * Logo de la primera marca asignada a un producto.
 */
function product_brand_logo_url(int $product_id, string $size = 'medium'): ?string
{
    $terms = get_the_terms($product_id, BRAND_TAXONOMY);

    if (! $terms || is_wp_error($terms)) {
        return null;
    }

    return brand_logo_url(reset($terms), $size);
}

/**
 * Marcas con logo configurado, para tiras decorativas tipo "trabajamos con".
 *
 * @return \WP_Term[]
 */
function brands_with_logo(): array
{
    // Orden intencional para la sección de marcas aliadas en inicio y sobre nosotros:
    $order = ['cliff', 'continental', 'orbea', 'gw', 'shimano', 'pro', 'trek'];

    $names = [
        'cliff'       => 'Cliff',
        'continental' => 'Continental',
        'orbea'       => 'Orbea',
        'gw'          => 'GW',
        'shimano'     => 'Shimano',
        'pro'         => 'PRO',
        'trek'        => 'Trek',
    ];

    $result = [];

    foreach ($order as $slug) {
        $term = get_term_by('slug', $slug, BRAND_TAXONOMY);

        // Si el término no existe aún en la base de datos (ej. servidor remoto recién desplegado),
        // intentar crearlo en pa_marca para que también quede disponible en los filtros.
        if (! $term && function_exists('wp_insert_term') && taxonomy_exists(BRAND_TAXONOMY)) {
            $name = $names[$slug] ?? ucfirst($slug);
            $inserted = wp_insert_term($name, BRAND_TAXONOMY, ['slug' => $slug]);
            if (! is_wp_error($inserted) && isset($inserted['term_id'])) {
                $term = get_term($inserted['term_id'], BRAND_TAXONOMY);
            }
        }

        // Si aún no es término WP, crear objeto ligero compatible
        if (! $term || is_wp_error($term)) {
            $term = (object) [
                'term_id' => 0,
                'name'    => $names[$slug] ?? ucfirst($slug),
                'slug'    => $slug,
                'count'   => 0,
            ];
        }

        if (brand_logo_url($term)) {
            $result[] = $term;
        }
    }

    return $result;
}

// -----------------------------------------------------------------------
// Admin: campo de logo en la pantalla de edición de Marca
// -----------------------------------------------------------------------

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();

    if (! $screen || 'edit-'.BRAND_TAXONOMY !== $screen->id) {
        return;
    }

    wp_enqueue_media();

    // wp.media() reutilizado del mismo patrón ya usado en el plugin
    // skycode-maintenance (assets/js/admin.js): un solo frame reutilizable,
    // limitado a imágenes, sin el hack de namespace global que usa WC core.
    wp_add_inline_script('jquery', <<<'JS'
        jQuery(function ($) {
            var frame;

            $(document).on('click', '#rb-brand-logo-select', function (e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Selecciona el logo de la marca',
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#rb-brand-logo-id').val(attachment.id);
                    $('#rb-brand-logo-preview').attr('src', attachment.url).show();
                    $('#rb-brand-logo-remove').show();
                });

                frame.open();
            });

            $(document).on('click', '#rb-brand-logo-remove', function (e) {
                e.preventDefault();
                $('#rb-brand-logo-id').val('');
                $('#rb-brand-logo-preview').hide();
                $(this).hide();
            });
        });
        JS);
});

add_action(BRAND_TAXONOMY.'_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label><?php esc_html_e('Logo de la marca', 'sage'); ?></label>
        <p>
            <img id="rb-brand-logo-preview" src="" alt="" style="display:none;max-width:140px;max-height:90px;margin-bottom:8px;">
        </p>
        <input type="hidden" name="rb_brand_logo_id" id="rb-brand-logo-id" value="">
        <p>
            <button type="button" class="button" id="rb-brand-logo-select"><?php esc_html_e('Elegir imagen', 'sage'); ?></button>
            <button type="button" class="button" id="rb-brand-logo-remove" style="display:none;"><?php esc_html_e('Quitar', 'sage'); ?></button>
        </p>
        <p class="description">
            <?php esc_html_e('Se usa en el filtro de la tienda, la ficha de producto y la tarjeta de producto. Recomendado: SVG o PNG con fondo transparente.', 'sage'); ?>
        </p>
    </div>
    <?php
});

add_action(BRAND_TAXONOMY.'_edit_form_fields', function ($term) {
    $logo_id = (int) get_term_meta($term->term_id, BRAND_LOGO_META_KEY, true);
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : brand_logo_url($term);
    ?>
    <tr class="form-field">
        <th scope="row"><label><?php esc_html_e('Logo de la marca', 'sage'); ?></label></th>
        <td>
            <img
                id="rb-brand-logo-preview"
                src="<?php echo esc_url($logo_url); ?>"
                alt=""
                style="<?php echo $logo_url ? '' : 'display:none;'; ?>max-width:140px;max-height:90px;margin-bottom:8px;display:block;"
            >
            <input type="hidden" name="rb_brand_logo_id" id="rb-brand-logo-id" value="<?php echo esc_attr($logo_id); ?>">
            <p>
                <button type="button" class="button" id="rb-brand-logo-select"><?php esc_html_e('Elegir imagen', 'sage'); ?></button>
                <button type="button" class="button" id="rb-brand-logo-remove" style="<?php echo $logo_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Quitar', 'sage'); ?></button>
            </p>
            <p class="description">
                <?php esc_html_e('Se usa en el filtro de la tienda, la ficha de producto y la tarjeta de producto. Recomendado: SVG o PNG con fondo transparente.', 'sage'); ?>
            </p>
        </td>
    </tr>
    <?php
});

/**
 * Guarda el logo al crear o editar un término de Marca. Al enganchar el hook
 * específico de la taxonomía (created_pa_marca / edited_pa_marca, que WP sólo
 * dispara para pa_marca) no hace falta revalidar la taxonomía a mano.
 * edit-tags.php/term.php ya verifican su propio nonce y el cap
 * manage_product_terms antes de disparar estos hooks — mismo criterio que
 * usa WooCommerce core en su equivalente para product_brand.
 */
function save_brand_logo_meta($term_id)
{
    if (! isset($_POST['rb_brand_logo_id'])) {
        return;
    }

    $logo_id = absint($_POST['rb_brand_logo_id']);

    if ($logo_id) {
        update_term_meta($term_id, BRAND_LOGO_META_KEY, $logo_id);
    } else {
        delete_term_meta($term_id, BRAND_LOGO_META_KEY);
    }
}
add_action('created_'.BRAND_TAXONOMY, __NAMESPACE__.'\\save_brand_logo_meta');
add_action('edited_'.BRAND_TAXONOMY, __NAMESPACE__.'\\save_brand_logo_meta');
