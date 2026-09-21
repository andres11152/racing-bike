<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$products = wc_get_products(['type' => 'variable', 'limit' => -1]);
echo "=== AUDITORÍA DE PRODUCTOS VARIABLES, GALERÍAS Y UMBRAL AJAX ===\n";
echo "Total productos variables: " . count($products) . "\n\n";

$threshold = apply_filters('woocommerce_ajax_variation_threshold', 30, wc_get_product(506));
echo "Umbral actual woocommerce_ajax_variation_threshold: {$threshold}\n\n";

$exceedingThreshold = [];
$missingInGallery = [];

foreach ($products as $p) {
    $pid = $p->get_id();
    $name = $p->get_name();
    $children = $p->get_children();
    $childCount = count($children);
    $parentImgId = (int) $p->get_image_id();
    $galleryIds = array_map('intval', (array) $p->get_gallery_image_ids());

    if ($childCount > $threshold) {
        $exceedingThreshold[] = [
            'id' => $pid,
            'name' => $name,
            'count' => $childCount,
        ];
    }

    // Recolectar _thumbnail_id únicos de variaciones
    $varImages = [];
    foreach ($children as $cid) {
        $t = (int) get_post_meta($cid, '_thumbnail_id', true);
        if ($t > 0 && ! in_array($t, $varImages, true)) {
            $varImages[] = $t;
        }
    }

    // Ver cuántas imágenes de variaciones no están en la galería del padre
    $diff = array_diff($varImages, array_merge([$parentImgId], $galleryIds));

    if (! empty($diff)) {
        $missingInGallery[] = [
            'id' => $pid,
            'name' => $name,
            'child_count' => $childCount,
            'parent_img' => $parentImgId,
            'gallery_count' => count($galleryIds),
            'var_unique_images' => count($varImages),
            'missing_ids' => array_values($diff),
        ];
    }
}

echo "1. PRODUCTOS QUE SUPERAN EL UMBRAL AJAX (data-product_variations = false):\n";
if (empty($exceedingThreshold)) {
    echo "  -> Ninguno supera el umbral ({$threshold}).\n\n";
} else {
    foreach ($exceedingThreshold as $ex) {
        echo "  [#{$ex['id']}] {$ex['name']} -> {$ex['count']} variaciones (Límite: {$threshold})\n";
    }
    echo "\n";
}

echo "2. PRODUCTOS CUYAS IMÁGENES DE VARIACIÓN NO ESTÁN EN LA GALERÍA DEL PADRE:\n";
echo "Total productos con imágenes desincronizadas en galería: " . count($missingInGallery) . "\n\n";
foreach ($missingInGallery as $m) {
    echo "  [#{$m['id']}] {$m['name']}\n";
    echo "    - Variaciones: {$m['child_count']} | Fotos en variaciones: {$m['var_unique_images']} | Fotos en galería padre: {$m['gallery_count']}\n";
    echo "    - IDs de imágenes que faltan en la galería: " . implode(', ', $m['missing_ids']) . "\n";
}

echo "\n--- DETALLE ESPECÍFICO GW FLAMMA (#506) ---\n";
$flamma = wc_get_product(506);
if ($flamma) {
    echo "Nombre: " . $flamma->get_name() . "\n";
    echo "Total variaciones: " . count($flamma->get_children()) . "\n";
    echo "Imagen principal: " . $flamma->get_image_id() . " (" . wp_get_attachment_url($flamma->get_image_id()) . ")\n";
    echo "Galería actual: " . implode(', ', $flamma->get_gallery_image_ids()) . "\n";
    echo "Variaciones por color:\n";
    $flammaColors = [];
    foreach ($flamma->get_children() as $cid) {
        $color = get_post_meta($cid, 'attribute_pa_color', true);
        $talla = get_post_meta($cid, 'attribute_pa_talla', true);
        $thumb = (int) get_post_meta($cid, '_thumbnail_id', true);
        $url = $thumb ? wp_get_attachment_url($thumb) : 'SIN FOTO';
        if (! isset($flammaColors[$color])) {
            $flammaColors[$color] = [];
        }
        $flammaColors[$color][] = "Var #$cid ($talla): Thumb #$thumb ($url)";
    }
    foreach ($flammaColors as $col => $lines) {
        echo "  Color '{$col}':\n";
        foreach (array_slice($lines, 0, 2) as $l) {
            echo "    $l\n";
        }
    }
}
