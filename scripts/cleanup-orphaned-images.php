<?php

/**
 * Borra las imágenes "huérfanas" que audit-orphaned-images-safety-check.php
 * confirmó como seguras: sin rol de uso conocido (no son imagen de
 * producto/variación/galería, slide, categoría, marca ni reseña), sin
 * post_parent vivo, y sin ninguna referencia a su nombre de archivo en
 * post_content ni en wp_options. Recalcula ese mismo criterio en el
 * momento (no una lista fija) para no borrar algo que cambió de estado
 * entre la auditoría y esta corrida.
 *
 * Antes de borrar cualquier archivo, hace un respaldo físico en
 * wp-content/rb-orphan-backup-<fecha>.tar.gz en el propio servidor — el
 * backup de base de datos de deploy/backup-prod-db.sh NO cubre archivos
 * borrados del disco, así que sin esto un error acá no tendría vuelta
 * atrás. wp_delete_attachment() se encarga de borrar el post Y todos los
 * tamaños generados del archivo.
 *
 * Usage: wp --skip-themes eval-file scripts/cleanup-orphaned-images.php apply
 * Sin "apply" solo imprime qué borraría (dry run) — no toca nada.
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

const RB_BRAND_TAXONOMY = 'pa_marca';

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios (esto BORRA archivos)\n\n" : "MODO: dry-run (no se borra nada; agrega \"apply\" para ejecutar)\n\n";

// ---- Recalcular claimedIds (idéntico a audit-orphaned-images-safety-check.php) ----

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
// (_rb_slide_image_mobile, ver Home.php), no como ID de adjunto — no hay
// forma de "reclamar" eso por ID. Se guarda el basename para comparar por
// nombre de archivo más abajo. `post_status => 'any'` a propósito: un
// slide en borrador puede volver a publicarse.
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

// Mismo blindaje que audit-orphaned-images-safety-check.php: hay
// referencias de imagen que WordPress/WooCommerce guardan como ID
// numérico puro en una opción (woocommerce_placeholder_image, site_icon,
// custom_logo), no como nombre de archivo — la búsqueda por filename de
// más abajo nunca las encuentra. Detectado porque el placeholder de
// producto de TODO el sitio (attachment 366) casi queda en la lista de
// "seguras para borrar".
foreach (['woocommerce_placeholder_image', 'site_icon'] as $optionName) {
    $value = (int) get_option($optionName);
    if ($value) {
        $claimedIds[$value] = true;
    }
}

$customLogoId = (int) get_theme_mod('custom_logo');
if ($customLogoId) {
    $claimedIds[$customLogoId] = true;
}

$allThumbnailIds = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value != ''");
foreach ($allThumbnailIds as $thumbId) {
    $claimedIds[(int) $thumbId] = true;
}

$allImageIds = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids']);
$orphanIds = array_values(array_diff($allImageIds, array_keys($claimedIds)));

// ---- Filtrar sólo las realmente seguras (mismo criterio que el safety-check) ----

$toDelete = [];

foreach ($orphanIds as $id) {
    $file = get_attached_file($id);
    $filename = $file ? basename($file) : null;
    $parentId = (int) get_post_field('post_parent', $id);

    if ($parentId) {
        $parentStatus = get_post_status($parentId);
        if ($parentStatus && $parentStatus !== 'trash') {
            continue; // cuelga de un post vivo: no se toca
        }
    }

    if ($filename && isset($claimedFilenames[$filename])) {
        continue; // URL cruda en postmeta de un slide (imagen/video móvil): no se toca
    }

    if ($filename) {
        $contentHits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_status NOT IN ('trash', 'auto-draft') AND ID != %d AND post_content LIKE %s",
            $id,
            '%' . $wpdb->esc_like($filename) . '%'
        ));

        if ($contentHits > 0) {
            continue; // referenciada a mano en algún contenido: no se toca
        }

        $optionHits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_value LIKE %s",
            '%' . $wpdb->esc_like($filename) . '%'
        ));

        if ($optionHits > 0) {
            continue; // referenciada en una opción del sitio (logo, customizer, etc.): no se toca
        }
    }

    if (! $file || ! file_exists($file)) {
        continue; // ya no hay archivo físico que borrar
    }

    $toDelete[] = ['id' => $id, 'file' => $file, 'filename' => $filename];
}

echo 'Huérfanas totales: ' . count($orphanIds) . "\n";
echo 'Confirmadas seguras para borrar: ' . count($toDelete) . "\n\n";

if (! $toDelete) {
    echo "Nada que borrar.\n";
    return;
}

$totalBytes = 0;
foreach ($toDelete as $item) {
    $totalBytes += @filesize($item['file']) ?: 0;
    echo "  [{$item['id']}] {$item['filename']}\n";
}

printf("\nEspacio a liberar: %.1f MB\n", $totalBytes / 1024 / 1024);

if (! $apply) {
    echo "\nDry-run: no se borró ni respaldó nada.\n";
    return;
}

// ---- Respaldo físico antes de borrar ----

$backupDir = WP_CONTENT_DIR . '/rb-orphan-backup-' . date('Y-m-d_His');
if (! mkdir($backupDir, 0755, true) && ! is_dir($backupDir)) {
    echo "ERROR: no se pudo crear el directorio de respaldo {$backupDir}. Se aborta sin borrar nada.\n";
    return;
}

foreach ($toDelete as $item) {
    if (! copy($item['file'], $backupDir . '/' . $item['id'] . '__' . $item['filename'])) {
        echo "ERROR: no se pudo respaldar {$item['filename']} (ID {$item['id']}). Se aborta sin borrar nada.\n";
        return;
    }
}

echo "Respaldo físico creado en: {$backupDir}\n\n";

// ---- Borrado real ----

$deleted = 0;
foreach ($toDelete as $item) {
    if (wp_delete_attachment($item['id'], true)) {
        $deleted++;
    } else {
        echo "  ERROR al borrar [{$item['id']}] {$item['filename']}\n";
    }
}

printf("\nBorradas %d de %d imágenes huérfanas. Respaldo físico en %s.\n", $deleted, count($toDelete), $backupDir);
