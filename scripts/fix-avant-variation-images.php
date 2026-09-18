<?php

/**
 * Corrige las imágenes de las variaciones y la galería para:
 * 1. Orbea Avant H50 2026 (ID 741):
 *    - Asigna foto ID 729 (Slate Blue) a sus 7 variaciones.
 *    - Asigna foto ID 730 (Ivory White) a sus 7 variaciones.
 *    - Asigna foto ID 750 (Magnetic Bronze) a sus 7 variaciones.
 *    - Limpia la foto de variación 729 de la galería del padre (deja solo 732).
 * 2. Orbea Alma H20 2025 (ID 861):
 *    - Limpia la foto de variación 2100 de la galería del padre.
 * 3. Trek Marlin 5 2026 (ID 911):
 *    - Limpia las fotos de variación 1157 y 1158 de la galería del padre.
 * 4. GW Hawk (ID 832):
 *    - Asigna foto 1409 a la variación huérfana de Gris Brillante - Rojo.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-avant-variation-images.php [apply]
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "=== MODO: APLICANDO CAMBIOS EN PRODUCCIÓN ===\n\n" : "=== MODO: DRY-RUN (no se escribe nada) ===\n\n";

// 1. Avant H50 (741)
$avant = wc_get_product(741);
if ($avant) {
    echo "--- [741] {$avant->get_name()} ---\n";
    $avantColorMap = [
        'slate-blue-matt-halo-silver-gloss'       => 729,
        'ivory-white-titan-bronze-gloss'          => 730,
        'magnetic-bronze-matt-cosmic-bronze-gloss' => 750,
    ];

    $updatedVars = 0;
    foreach ($avant->get_children() as $varId) {
        $color = get_post_meta($varId, 'attribute_pa_color', true);
        if (isset($avantColorMap[$color])) {
            $target = $avantColorMap[$color];
            $current = (int) get_post_meta($varId, '_thumbnail_id', true);
            if ($current !== $target) {
                printf("  Var %d (%s): foto actual %d -> NUEVA FOTO %d\n", $varId, $color, $current, $target);
                if ($apply) {
                    update_post_meta($varId, '_thumbnail_id', $target);
                    clean_post_cache($varId);
                }
                $updatedVars++;
            }
        }
    }
    printf("  Variaciones actualizadas en 741: %d\n", $updatedVars);

    $currentGallery = $avant->get_gallery_image_ids();
    $newGallery = array_values(array_diff($currentGallery, [729]));
    printf("  Galería 741: antes [%s] -> ahora [%s]\n", implode(', ', $currentGallery), implode(', ', $newGallery));
    if ($apply) {
        $avant->set_gallery_image_ids($newGallery);
        $avant->save();
        clean_post_cache(741);
        wc_delete_product_transients(741);
    }
    echo "\n";
}

// 2. Orbea Alma H20 2025 (861) - Quitar foto de variación 2100 de la galería
$alma = wc_get_product(861);
if ($alma) {
    echo "--- [861] {$alma->get_name()} ---\n";
    $currentGallery = $alma->get_gallery_image_ids();
    $newGallery = array_values(array_diff($currentGallery, [2100]));
    printf("  Galería 861: antes [%s] -> ahora [%s]\n", implode(', ', $currentGallery), implode(', ', $newGallery));
    if ($apply) {
        $alma->set_gallery_image_ids($newGallery);
        $alma->save();
        clean_post_cache(861);
        wc_delete_product_transients(861);
    }
    echo "\n";
}

// 3. Trek Marlin 5 2026 (911) - Quitar fotos de variación 1157, 1158 de la galería
$trek5 = wc_get_product(911);
if ($trek5) {
    echo "--- [911] {$trek5->get_name()} ---\n";
    $currentGallery = $trek5->get_gallery_image_ids();
    $newGallery = array_values(array_diff($currentGallery, [1157, 1158]));
    printf("  Galería 911: antes [%s] -> ahora [%s]\n", implode(', ', $currentGallery), implode(', ', $newGallery));
    if ($apply) {
        $trek5->set_gallery_image_ids($newGallery);
        $trek5->save();
        clean_post_cache(911);
        wc_delete_product_transients(911);
    }
    echo "\n";
}

// 4. GW Hawk (832) - Asignar 1409 a Gris Brillante - Rojo faltante
$hawk = wc_get_product(832);
if ($hawk) {
    echo "--- [832] {$hawk->get_name()} ---\n";
    $updatedHawk = 0;
    foreach ($hawk->get_children() as $varId) {
        $color = get_post_meta($varId, 'attribute_pa_color', true);
        if ($color === 'gris-brillante-rojo') {
            $current = (int) get_post_meta($varId, '_thumbnail_id', true);
            if ($current !== 1409) {
                printf("  Var %d (gris-brillante-rojo): foto actual %d -> NUEVA FOTO 1409\n", $varId, $current);
                if ($apply) {
                    update_post_meta($varId, '_thumbnail_id', 1409);
                    clean_post_cache($varId);
                }
                $updatedHawk++;
            }
        }
    }
    printf("  Variaciones actualizadas en 832: %d\n", $updatedHawk);
    if ($apply && $updatedHawk > 0) {
        clean_post_cache(832);
        wc_delete_product_transients(832);
    }
    echo "\n";
}

echo "=== FIN ===\n";
