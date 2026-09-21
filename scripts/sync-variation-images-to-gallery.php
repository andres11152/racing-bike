<?php

/**
 * Sincroniza las imágenes de las variaciones (_thumbnail_id) con la galería
 * del producto padre (_product_image_gallery) en WooCommerce.
 *
 * Modo por defecto: dry-run (solo informa qué cambios se harían).
 * Modo aplicar: pasar el argumento 'apply'.
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$isApply = in_array('apply', $args ?? [], true) || in_array('apply', $argv ?? [], true);

echo "=== SINCRONIZACIÓN DE IMÁGENES DE VARIACIONES A GALERÍA PADRE ===\n";
echo "Modo: " . ($isApply ? "APLICAR (escribiendo en base de datos)" : "DRY-RUN (solo lectura)") . "\n\n";

$products = wc_get_products(['type' => 'variable', 'limit' => -1]);
echo "Total productos variables analizados: " . count($products) . "\n\n";

$updatedCount = 0;
$totalImagesAdded = 0;

foreach ($products as $p) {
    $pid = $p->get_id();
    $name = $p->get_name();
    $parentImgId = (int) $p->get_image_id();
    $currentGalleryIds = array_values(array_filter(array_map('intval', (array) $p->get_gallery_image_ids())));

    $children = $p->get_children();
    $varImages = [];

    foreach ($children as $cid) {
        $t = (int) get_post_meta($cid, '_thumbnail_id', true);
        if ($t > 0 && wp_get_attachment_url($t)) {
            if (! in_array($t, $varImages, true)) {
                $varImages[] = $t;
            }
        }
    }

    // Unir galería actual con imágenes de variaciones, omitiendo la imagen principal y duplicados
    $newGalleryIds = $currentGalleryIds;
    $addedForThisProduct = [];

    foreach ($varImages as $vId) {
        if ($vId !== $parentImgId && ! in_array($vId, $newGalleryIds, true)) {
            $newGalleryIds[] = $vId;
            $addedForThisProduct[] = $vId;
        }
    }

    if (! empty($addedForThisProduct)) {
        $updatedCount++;
        $totalImagesAdded += count($addedForThisProduct);

        echo "[#{$pid}] {$name}\n";
        echo "  - Fotos actuales en galería: " . count($currentGalleryIds) . "\n";
        echo "  - Fotos de variaciones añadidas: " . count($addedForThisProduct) . " (IDs: " . implode(', ', $addedForThisProduct) . ")\n";
        echo "  - Total fotos en galería resultante: " . count($newGalleryIds) . "\n";

        if ($isApply) {
            $p->set_gallery_image_ids($newGalleryIds);
            $p->save();
            echo "  -> GUARDADO EN BD CORRECTAMENTE.\n";
        }
        echo "\n";
    }
}

echo "=== RESUMEN ===\n";
echo "Productos a actualizar: {$updatedCount}\n";
echo "Total imágenes añadidas a galerías: {$totalImagesAdded}\n";

if (! $isApply && $updatedCount > 0) {
    echo "\nPara aplicar los cambios en la base de datos, ejecuta:\n";
    echo "  ./deploy/run-prod-script.sh scripts/sync-variation-images-to-gallery.php apply\n";
}
