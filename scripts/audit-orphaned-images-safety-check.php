<?php

/**
 * Antes de borrar cualquiera de las 207 imágenes que audit-image-seo.php
 * marcó como "sin rol de uso detectado", hay que confirmar que de verdad
 * no las usa nada — esa auditoría sólo miraba imagen principal/galería/
 * variación de producto, slides, categorías, marcas y reseñas con foto.
 * No miraba:
 *
 *  - El HTML de post_content de CUALQUIER post/página/producto (una foto
 *    insertada a mano en una descripción larga, un <img> suelto en una
 *    página, etc.)
 *  - post_parent: si el adjunto cuelga de un post real, borrarlo rompe
 *    esa relación aunque WooCommerce no lo use como imagen destacada.
 *  - Cualquier opción de personalización del tema (logo del Customizer,
 *    imágenes de widgets, ACF, etc.) que guarde la URL como texto plano.
 *
 * Esto SÓLO lee. Clasifica cada huérfana en:
 *   - "segura para borrar": no aparece en ningún post_content, ninguna
 *     opción, y no tiene post_parent con un post que siga existiendo.
 *   - "revisar a mano": aparece en algún lado — se imprime dónde.
 *
 * Usage: wp --skip-themes eval-file scripts/audit-orphaned-images-safety-check.php
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

const RB_BRAND_TAXONOMY = 'pa_marca';

// ---- Recalcular el mismo set de "huérfanas" que audit-image-seo.php ----
// (roles conocidos: producto principal/galería/variación, slide de hero,
// thumbnail de categoría, logo de marca, foto de reseña)

$claimedIds = [];

$productIds = get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => -1, 'fields' => 'ids']);
foreach ($productIds as $productId) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }
    if ($product->get_image_id()) {
        $claimedIds[(int) $product->get_image_id()] = true;
    }
    foreach ($product->get_gallery_image_ids() as $gid) {
        $claimedIds[(int) $gid] = true;
    }
    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variationId) {
            $thumbId = get_post_meta($variationId, '_thumbnail_id', true);
            if ($thumbId) {
                $claimedIds[(int) $thumbId] = true;
            }
        }
    }
}

// Los slides referencian su imagen/video móvil como URL CRUDA en postmeta
// (_rb_slide_image_mobile, ver Home.php), no como ID de adjunto. Buscar
// esa URL en wp_options (vía el transient de caché del home) es poco
// fiable: el transient expira cada 15 minutos y sólo contiene lo que
// haya en caché en ese instante — se confirmó comparando dos corridas de
// este mismo script con pocos minutos de diferencia, donde
// "hero-1-mobile.webp" (activamente usado por el slide 123) aparecía en
// una corrida y no en la otra. La fuente real es el postmeta.
$claimedFilenames = [];
foreach (get_posts(['post_type' => 'rb_slide', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']) as $slideId) {
    $thumbId = get_post_thumbnail_id($slideId);
    if ($thumbId) {
        $claimedIds[(int) $thumbId] = true;
    }

    foreach (['_rb_slide_image_mobile', '_rb_slide_video_desktop', '_rb_slide_video_mobile'] as $rawUrlMetaKey) {
        $rawUrl = get_post_meta($slideId, $rawUrlMetaKey, true);
        if ($rawUrl) {
            $claimedFilenames[basename(parse_url($rawUrl, PHP_URL_PATH) ?: $rawUrl)] = true;
        }
    }
}

$categoryTerms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
if (! is_wp_error($categoryTerms)) {
    foreach ($categoryTerms as $term) {
        $thumbId = get_term_meta($term->term_id, 'thumbnail_id', true);
        if ($thumbId) {
            $claimedIds[(int) $thumbId] = true;
        }
    }
}

if (taxonomy_exists(RB_BRAND_TAXONOMY)) {
    $brandTerms = get_terms(['taxonomy' => RB_BRAND_TAXONOMY, 'hide_empty' => false]);
    if (! is_wp_error($brandTerms)) {
        foreach ($brandTerms as $term) {
            $logoId = get_term_meta($term->term_id, '_rb_brand_logo_id', true);
            if ($logoId) {
                $claimedIds[(int) $logoId] = true;
            }
        }
    }
}

global $wpdb;
$reviewPhotoLists = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->commentmeta} WHERE meta_key = 'rb_photo_ids' AND meta_value != ''");
foreach ($reviewPhotoLists as $list) {
    foreach (array_filter(array_map('absint', explode(',', $list))) as $photoId) {
        $claimedIds[$photoId] = true;
    }
}

// Casi se cuela un error grave sin esto: WooCommerce y WordPress guardan
// varias referencias de imagen como el ID NUMÉRICO puro en una opción
// (woocommerce_placeholder_image, site_icon, custom_logo del Customizer),
// no como el nombre de archivo — así que la búsqueda por filename en
// wp_options de más abajo nunca las iba a encontrar. Se detectó porque
// 366 (woocommerce-placeholder.webp, el placeholder de TODO producto sin
// foto en el sitio) apareció en la lista de "seguras para borrar" en la
// primera corrida de este script.
$knownSingletonOptions = ['woocommerce_placeholder_image', 'site_icon'];
foreach ($knownSingletonOptions as $optionName) {
    $value = (int) get_option($optionName);
    if ($value) {
        $claimedIds[$value] = true;
    }
}

$customLogoId = (int) get_theme_mod('custom_logo');
if ($customLogoId) {
    $claimedIds[$customLogoId] = true;
}

// Imagen destacada de CUALQUIER post/página (no sólo rb_slide): una
// página institucional o una entrada de blog puede tener featured image
// sin que ningún rol de los de arriba lo sepa.
$allThumbnailIds = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value != ''");
foreach ($allThumbnailIds as $thumbId) {
    $claimedIds[(int) $thumbId] = true;
}

$allImageIds = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids']);
$orphanIds = array_values(array_diff($allImageIds, array_keys($claimedIds)));

echo "Total huérfanas a revisar: " . count($orphanIds) . "\n\n";

// ---- Verificación real contra el resto del sitio ----

$safe = [];
$unsafe = [];

foreach ($orphanIds as $id) {
    $file = get_attached_file($id);
    $filename = $file ? basename($file) : null;
    $url = wp_get_attachment_url($id);
    $parentId = (int) get_post_field('post_parent', $id);

    $reasons = [];

    if ($filename && isset($claimedFilenames[$filename])) {
        $reasons[] = 'URL cruda en postmeta de un slide (imagen/video móvil)';
    }

    // 1) ¿Cuelga de un post que todavía existe (y no es basura/papelera)?
    if ($parentId) {
        $parentStatus = get_post_status($parentId);
        if ($parentStatus && $parentStatus !== 'trash') {
            $reasons[] = "post_parent {$parentId} ({$parentStatus}: " . get_the_title($parentId) . ')';
        }
    }

    // 2) ¿Su nombre de archivo aparece en el contenido de algún
    // post/página/producto publicado? (imagen insertada a mano en una
    // descripción larga vía el editor, o referenciada por URL cruda como
    // hace la portada con la imagen móvil del hero — ver Home.php).
    //
    // Sólo por NOMBRE DE ARCHIVO, no por el ID numérico crudo: buscar
    // "233" como substring hace falsos positivos constantes contra
    // precios, tallas o cualquier número de una ficha de producto que no
    // tiene nada que ver con este adjunto.
    if ($filename) {
        $hits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_status NOT IN ('trash', 'auto-draft')
             AND ID != %d
             AND post_content LIKE %s",
            $id,
            '%' . $wpdb->esc_like($filename) . '%'
        ));

        if ($hits > 0) {
            $reasons[] = "referenciada en post_content de {$hits} post(s)";
        }
    }

    // 3) ¿Aparece en alguna opción del sitio (Customizer, widgets, ACF a
    // nivel de opción, configuración de plugins que guarde una URL)?
    if ($filename) {
        $optionHits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_value LIKE %s",
            '%' . $wpdb->esc_like($filename) . '%'
        ));

        if ($optionHits > 0) {
            $reasons[] = "referenciada en {$optionHits} option(s) del sitio";
        }
    }

    if ($reasons) {
        $unsafe[] = ['id' => $id, 'filename' => $filename, 'reasons' => $reasons];
    } else {
        $safe[] = ['id' => $id, 'filename' => $filename, 'url' => $url];
    }
}

echo "== SEGURAS PARA BORRAR (sin ninguna referencia encontrada): " . count($safe) . " ==\n";
foreach ($safe as $s) {
    printf("  [%d] %s\n", $s['id'], $s['filename']);
}

echo "\n== REVISAR A MANO (sí aparecen en algún lado): " . count($unsafe) . " ==\n";
foreach ($unsafe as $u) {
    printf("  [%d] %s -> %s\n", $u['id'], $u['filename'], implode(' | ', $u['reasons']));
}

echo "\nResumen: " . count($safe) . " seguras, " . count($unsafe) . " necesitan revisión manual, de " . count($orphanIds) . " huérfanas totales.\n";
