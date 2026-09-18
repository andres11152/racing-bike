<?php

/**
 * Corrige la separación entre fotos de variaciones y fotos de galería:
 *
 * 1. ASIGNA la foto real a cada variación de color para los productos donde
 *    las fotos existían en la galería del padre pero no estaban en la variación:
 *     - GW MONKEY 29 (835): asigna Fucsia (1511), Rojo (1536), Negro (1526),
 *       Morado (1521) y Gris (1516).
 *     - Bicicleta Letras+D Ruta (512): asigna Blanco Metálico (658),
 *       Azul Metálico (659) y Rojo Metálico (661).
 *     - Bicicleta Sprinter Ruta (515): asigna Rojo Rubí (663).
 *
 * 2. LIMPIA las fotos de variación de la galería del padre (_product_image_gallery),
 *    dejando en la galería ÚNICAMENTE las fotos generales o de detalle del producto,
 *    evitando que al ver la galería salgan bicicletas de diferentes colores.
 *     - GW Alligator 29 (841): remueve fotos de variaciones.
 *     - Zoncolan 105 (518): remueve fotos de variaciones.
 *     - Flamma Claris (506): remueve fotos de variaciones.
 *     - GW Zebra (1645): remueve fotos de variaciones.
 *     - GW Lynx (834): remueve fotos de variaciones.
 *     - GW Hawk (832): remueve fotos de variaciones, conserva fotos de detalle (1425, 1426, 1427).
 *     - Orbea Alma H20 2026 (1128): remueve foto de variación.
 *     - GW Monkey (835): remueve fotos de variaciones.
 *     - Letras+D (512): remueve fotos de variaciones.
 *     - Sprinter Ruta (515): remueve fotos de variaciones.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-bike-variation-images-and-galleries.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios en producción\n\n" : "MODO: dry-run (nada se escribe; agrega \"apply\" para ejecutar)\n\n";

// 1. Mapeo de fotos a asignar a variaciones por color:
// producto_id => [ slug_pa_color => attachment_id ]
const RB_NUEVAS_FOTOS_VARIACION = [
    // GW MONKEY 29 (835)
    835 => [
        'fucsia-fuego-negro-brillante' => 1511,
        'rojo-chile-negro-brillante'   => 1536,
        'negro-brillante-gris-plata'   => 1526,
        'morado-carmin-negro-brillante' => 1521,
        'gris-abedul-negro-brillante'  => 1516,
    ],
    // Bicicleta Letras+D Ruta (512)
    512 => [
        'blanco-metalico' => 658,
        'azul-metalico'   => 659,
        'rojo-metalico'   => 661,
        'gris-darkness'   => 660,
    ],
    // Bicicleta Sprinter Ruta (515)
    515 => [
        'rojo-rubi' => 663,
    ],
];

// 2. Fotos de variación que DEBEN SALIR de la galería del padre:
// producto_id => [ array de attachment_ids que son variaciones y no fotos generales ]
const RB_FOTOS_VARIACION_EN_GALERIA = [
    841  => [1452, 1453, 1454, 1455, 1456],                 // Alligator: todas son colores
    518  => [516, 665, 666, 667, 668],                      // Zoncolan: todas son colores
    506  => [504, 649, 650, 651, 652, 653],                 // Flamma Claris: todas son colores
    1645 => [1674, 1678, 1677, 1676, 1675],                 // Zebra: todas son colores
    834  => [1430, 1431, 1432],                             // Lynx: todas son colores
    832  => [1411, 1413],                                   // Hawk: se quitan Negro-Blanco y Negro-Azul, quedan 1425,1426,1427
    1128 => [1154],                                         // Alma H20 2026: Espace Green
    835  => [1511, 1536, 1526, 1521, 1516],                 // Monkey: se mueven a variaciones
    512  => [658, 659, 660, 661],                           // Letras+D: se mueven a variaciones
    515  => [663, 513, 514],                                // Sprinter: se mueven a variaciones
];

echo "=== PARTE 1: ASIGNAR FOTOS DE COLOR A VARIACIONES ===\n\n";

$totalVarsActualizadas = 0;

foreach (RB_NUEVAS_FOTOS_VARIACION as $productId => $colorMapping) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }

    printf("[%d] %s\n", $productId, $product->get_name());

    foreach ($product->get_children() as $variationId) {
        $colorSlug = get_post_meta($variationId, 'attribute_pa_color', true);

        if (! isset($colorMapping[$colorSlug])) {
            continue;
        }

        $targetImageId = $colorMapping[$colorSlug];
        $currentImageId = (int) get_post_meta($variationId, '_thumbnail_id', true);

        if ($currentImageId === $targetImageId) {
            continue;
        }

        printf(
            "  - Var %d (color '%s'): foto actual %s -> NUEVA FOTO ID %d (%s)\n",
            $variationId,
            $colorSlug,
            $currentImageId ? "ID $currentImageId" : 'heredada',
            $targetImageId,
            basename(get_attached_file($targetImageId))
        );

        if ($apply) {
            update_post_meta($variationId, '_thumbnail_id', $targetImageId);
            clean_post_cache($variationId);
        }

        $totalVarsActualizadas++;
    }

    if ($apply) {
        clean_post_cache($productId);
        wc_delete_product_transients($productId);
    }

    echo "\n";
}

printf("Total de variaciones con nueva foto asignada: %d\n\n", $totalVarsActualizadas);

echo "=== PARTE 2: LIMPIAR FOTOS DE VARIACIÓN DE LA GALERÍA DEL PADRE ===\n\n";

$totalGaleriasModificadas = 0;

foreach (RB_FOTOS_VARIACION_EN_GALERIA as $productId => $idsToRemove) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }

    $currentGallery = $product->get_gallery_image_ids();
    $nuevaGaleria = array_values(array_diff($currentGallery, $idsToRemove));

    printf("[%d] %s\n", $productId, $product->get_name());
    printf("  Galería antes (%d fotos): %s\n", count($currentGallery), $currentGallery ? implode(', ', $currentGallery) : 'ninguna');
    printf("  Fotos de variación a retirar (%d fotos): %s\n", count($idsToRemove), implode(', ', $idsToRemove));
    printf("  Galería resultante (%d fotos generales): %s\n", count($nuevaGaleria), $nuevaGaleria ? implode(', ', $nuevaGaleria) : '(quedará vacía sin fotos de otros colores)');

    if ($apply) {
        $product->set_gallery_image_ids($nuevaGaleria);
        $product->save();
        clean_post_cache($productId);
        wc_delete_product_transients($productId);
    }

    $totalGaleriasModificadas++;
    echo "\n";
}

printf("Total de galerías limpiadas: %d\n\n", $totalGaleriasModificadas);

echo $apply ? "¡Cambios aplicados exitosamente!\n" : "Dry-run completado. Agrega 'apply' para ejecutar en producción.\n";
