<?php

/**
 * Asigna a cada variación la foto de SU color, para los productos donde
 * las fotos existían pero estaban solo en la galería del padre.
 *
 * Contexto: el cliente dejó la misma foto (la verde) como imagen de todas
 * las variaciones de color de varios productos, así que al filtrar la
 * tienda por Azul salían bicicletas verdes. La tarjeta ya sabe elegir la
 * foto de la variación que coincide con el filtro activo (ver
 * product-card.blade.php), pero solo puede hacerlo si la variación tiene
 * imagen propia — heredar la del padre no sirve, es justamente la foto
 * equivocada.
 *
 * El mapeo foto->color se hizo mirando las fotos una por una y fue
 * aprobado por el cliente antes de escribir nada; los nombres de archivo
 * no dicen el color ("83490_3.jpg").
 *
 * Uso: wp --skip-themes eval-file scripts/assign-variation-images.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

// producto => [ slug de color en pa_color => ID del adjunto ]
const RB_VARIATION_IMAGES = [
    // BICICLETA ALLIGATOR MTB 29P — 6 fotos en la galería, una por color.
    841 => [
        'verde-pino' => 1451,    // la que estaba como principal
        'azul-real' => 1452,
        'azul-humo' => 1453,
        'blanco-mate' => 1454,
        'negro-perlado' => 1455,
        'rojo-marron' => 1456,
    ],

    // BICICLETA GW HAWK — el cliente ya había asignado la foto correcta a
    // una variación de cada color, pero este producto tiene combinaciones
    // duplicadas (dos variaciones para el mismo talla+color), y las
    // duplicadas quedaron heredando la foto del padre. Se completa cada
    // color con la foto que el propio cliente ya había elegido para él.
    832 => [
        'azul-petroleo-verde-neon' => 831,
        'gris-brillante-rojo' => 1409,
        'negro-brillante-blanco-brillante' => 1411,
        'negro-mate-azul-claro' => 1413,
        'negro-mate-gris-mate' => 1424,
    ],
];

$asignadas = 0;
$yaCorrectas = 0;

foreach (RB_VARIATION_IMAGES as $productId => $colorToImage) {
    $product = wc_get_product($productId);

    if (! $product) {
        echo "  [{$productId}] producto no encontrado, se omite.\n";
        continue;
    }

    echo "=== [{$productId}] {$product->get_name()} ===\n";

    foreach ($product->get_children() as $variationId) {
        // Conviven dos esquemas según cómo se cargó cada producto.
        $rawColor = get_post_meta($variationId, 'attribute_pa_color', true)
            ?: get_post_meta($variationId, 'attribute_color', true);

        if (! $rawColor) {
            continue;
        }

        $slug = sanitize_title($rawColor);

        if (! isset($colorToImage[$slug])) {
            continue;
        }

        $targetImageId = $colorToImage[$slug];
        $currentOwn = (int) get_post_meta($variationId, '_thumbnail_id', true);

        if ($currentOwn === $targetImageId) {
            $yaCorrectas++;
            continue;
        }

        echo "  variación {$variationId} ({$slug}): imagen "
            . ($currentOwn ?: 'heredada del padre')
            . " -> {$targetImageId}\n";

        if ($apply) {
            update_post_meta($variationId, '_thumbnail_id', $targetImageId);
        }

        $asignadas++;
    }
}

echo "\nVariaciones actualizadas: {$asignadas}\n";
echo "Variaciones que ya tenían la foto correcta: {$yaCorrectas}\n";
echo "Listo.\n";
