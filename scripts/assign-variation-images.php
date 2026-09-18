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
 * El mapeo foto->color se hizo mirando las fotos una por una; los nombres
 * de archivo casi nunca dicen el color ("83490_3.jpg", "442578.jpg").
 *
 * IMPORTANTE — esto NO resuelve todos los productos con variaciones sin
 * imagen propia, solo aquellos donde la foto de ESE color en particular
 * existe en algún lado (imagen principal o galería del padre). Auditoría
 * del 2026-09-17 encontró varios productos (Trek Marlin 6, Trek
 * ProCaliber 6, Trek Marlin 7, Orbea Alma H30 2025/2026) donde solo 1 de
 * 2-3 colores tiene foto real — el resto de sus variaciones se queda
 * heredando la del padre a propósito, porque asignarles cualquier otra
 * foto sería mostrar un color que no es. Esos casos quedan documentados
 * abajo, producto por producto, para que quede registro de qué falta
 * pedirle al proveedor/cliente en vez de perderse en el código.
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
    // Nota aparte sobre este mismo producto: la variación 1167 (talla M)
    // no tiene NINGÚN color asignado (atributo vacío) — no es un problema
    // de foto, es una variación huérfana de la importación. No se toca
    // aquí; necesita revisión manual de a qué color pertenece o si debe
    // borrarse.

    // ORBEA ALMA H20 2026 — imagen principal y galería son, cada una, una
    // foto completa y sin ambigüedad de cada uno de sus 2 colores.
    1128 => [
        'halo-silver-tanzanite-gloss' => 1153,          // imagen principal
        'espace-green-matt-ivory-white-gloss' => 1154,  // única foto de galería
    ],

    // TREK MARLIN 5 2026 — imagen principal + 2 fotos de galería, una
    // para cada uno de sus 3 colores. Cobertura completa.
    911 => [
        'fury-red' => 1156,                    // imagen principal
        'mulsanne-blue' => 1157,
        'miami-green-dark-aquatic-fade' => 1158,
    ],

    // TREK MARLIN 6 2026 — sus 4 fotos (principal + 3 de galería) son la
    // MISMA bicicleta Gloss Lavender Haze en 4 ángulos distintos (lo
    // confirma el propio nombre de archivo, todas terminan en
    // "-0-", "-1-", "-2-", "-4-" del mismo lote). No existe ninguna foto
    // de "Lava" ni de "Matte Lichen/Keswick Green Fade" — sus variaciones
    // de esos 2 colores se quedan heredando la del padre a propósito.
    // Falta pedir esas 2 fotos al proveedor/cliente.
    1133 => [
        'gloss-lavender-haze' => 1129,
    ],

    // ORBEA ALMA H20 2025 — de las 3 fotos disponibles (principal +
    // galería), solo la de galería 2100 corresponde a uno de los 2
    // colores reales del producto (Espace Green). La imagen principal
    // (2101, bronce/naranja) y la otra de galería (2102, borgoña) NO son
    // ni Espace Green ni Halo Silver — son fotos de otras versiones de
    // color de la Alma H20 que Orbea no vende en esta tienda. No existe
    // ninguna foto real de "Halo Silver - Tanzanite" para asignar; esa
    // variación se queda heredando la del padre (que además es una foto
    // de un color que no es — ver nota al cliente).
    861 => [
        'espace-green-matt-ivory-white-gloss' => 2100,
    ],

    // TREK PROCALIBER 6 2026 — sin galería, solo imagen principal, y
    // corresponde a uno solo de sus 2 colores. Falta la foto de
    // "Satin Trek Black/Lithium Grey".
    973 => [
        'lavender-haze' => 972,
    ],

    // TREK MARLIN 7 2026 — sin galería, solo imagen principal,
    // corresponde a uno solo de sus 3 colores. Faltan las fotos de
    // "Magic Mint" y "Matte Dark Web/Clear Gloss".
    954 => [
        'fury-red-lithium-grey-fade' => 953,
    ],

    // ORBEA ALMA H30 2026 — sin galería, solo imagen principal,
    // corresponde a uno solo de sus 2 colores. Falta la foto de
    // "Halo Silver - Tanzanite".
    882 => [
        'espace-green-matt-ivory-white-gloss' => 881,
    ],

    // ORBEA ALMA H30 2025 — mismo caso que el H30 2026: sin galería,
    // solo imagen principal, corresponde a uno solo de sus 2 colores.
    // Falta la foto de "Halo Silver - Tanzanite".
    852 => [
        'espace-green-matt-ivory-white-gloss' => 851,
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
