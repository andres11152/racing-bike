<?php

/**
 * Introduce una taxonomía "Color" curada de solo 9 familias (pa_color-familia)
 * para el filtro de la tienda, separada de pa_color (el nombre de pintura
 * exacto del fabricante — "Halo Silver - Tanzanite (Gloss)", "Espace Green
 * (Matt) - Ivory White (Gloss)"...). Migrar pa_color completo al filtro
 * (ver scripts/migrate-custom-attributes.php) resultó en ~74 opciones de
 * filtro, la mayoría con 1 solo producto — abruma al cliente en vez de
 * ayudarlo. pa_color sigue existiendo tal cual (ficha de producto,
 * selector de variación); esto solo agrega una capa de agrupación para
 * el sidebar de filtros.
 *
 * El mapeo de cada uno de los 76 tonos existentes a una de las 9 familias
 * (Negro, Blanco, Gris, Azul, Rojo, Verde, Morado, Dorado/Bronce,
 * Multicolor) se revisó a mano, uno por uno — no por coincidencia de
 * palabras clave, que falla con nombres bicolor como "Fury Red/Lithium
 * Grey Fade" (¿Rojo o Gris?). Confirmado con el cliente:
 *  - El término huérfano (0 productos) que juntaba 5 colores en un solo
 *    nombre va a Multicolor sin más.
 *  - El resto de la clasificación se aplica tal como quedó propuesta.
 *
 * Usage: wp --skip-themes eval-file scripts/migrate-color-families.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_COLOR_FAMILIES = [
    'negro' => 'Negro',
    'blanco' => 'Blanco',
    'gris' => 'Gris',
    'azul' => 'Azul',
    'rojo' => 'Rojo',
    'verde' => 'Verde',
    'morado' => 'Morado / Rosado',
    'dorado-bronce' => 'Dorado / Bronce',
    'multicolor' => 'Multicolor',
];

// term_id de pa_color => slug de familia (RB_COLOR_FAMILIES). Revisado
// término por término el 2026-09-10; ver el razonamiento en el mensaje al
// cliente, no repetido aquí para no desincronizarse del código.
const RB_COLOR_TERM_TO_FAMILY = [
    432 => 'azul', 436 => 'azul', 425 => 'azul', 443 => 'azul', 427 => 'azul',
    424 => 'blanco', 435 => 'blanco',
    423 => 'azul',
    389 => 'rojo',
    421 => 'gris',
    381 => 'blanco',
    405 => 'multicolor',
    412 => 'rojo', 409 => 'rojo',
    401 => 'morado',
    430 => 'gris', 437 => 'gris', 428 => 'gris', 441 => 'gris', 442 => 'gris', 440 => 'gris',
    404 => 'multicolor',
    380 => 'azul',
    422 => 'multicolor',
    420 => 'multicolor',
    402 => 'rojo',
    406 => 'morado',
    408 => 'verde',
    419 => 'dorado-bronce',
    410 => 'negro',
    403 => 'verde',
    395 => 'gris',
    413 => 'verde',
    411 => 'azul',
    445 => 'negro', 429 => 'negro', 444 => 'negro',
    439 => 'gris',
    426 => 'rojo', 438 => 'rojo', 431 => 'rojo',
    407 => 'negro',
    418 => 'azul',
    390 => 'negro',
    434 => 'verde', 433 => 'verde',
    235 => 'azul', 245 => 'azul', 244 => 'azul', 260 => 'azul',
    227 => 'multicolor',
    250 => 'azul',
    237 => 'blanco', 246 => 'blanco',
    251 => 'multicolor',
    252 => 'morado',
    262 => 'gris', 253 => 'gris',
    228 => 'multicolor',
    254 => 'morado',
    236 => 'negro', 259 => 'negro', 231 => 'negro', 229 => 'negro', 247 => 'negro', 255 => 'negro',
    230 => 'multicolor',
    327 => 'multicolor', // huérfano, 5 colores en un solo término — confirmado con el cliente
    263 => 'multicolor', // "RD/GY", abreviatura ambigua
    234 => 'rojo', 256 => 'rojo', 248 => 'rojo',
    261 => 'verde', // Turquesa: se agrupó con Verde, es la familia más cercana de las 9
    238 => 'verde', 257 => 'verde', 243 => 'verde',
];

// 1. Crear la taxonomía pa_color-familia como atributo de WooCommerce
//    (necesario para que el filtro de la tienda la reconozca — ver
//    WC_Query::get_layered_nav_chosen_attributes, que exige
//    wc_attribute_taxonomy_id_by_name()).
$attributeId = wc_attribute_taxonomy_id_by_name('color-familia');

if (! $attributeId) {
    echo "Crear atributo WooCommerce 'color-familia' (Color)\n";

    if ($apply) {
        $attributeId = wc_create_attribute([
            'name' => 'Color',
            'slug' => 'color-familia',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($attributeId)) {
            echo '  ERROR: ' . $attributeId->get_error_message() . "\n";
            exit(1);
        }
    }
}

if (! taxonomy_exists('pa_color-familia')) {
    register_taxonomy('pa_color-familia', ['product'], [
        'hierarchical' => false,
        'show_ui' => false,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

// 2. Crear los 9 términos de familia si faltan.
$familyTermIds = [];

foreach (RB_COLOR_FAMILIES as $slug => $name) {
    $term = get_term_by('slug', $slug, 'pa_color-familia');

    if (! $term) {
        echo "Crear término de familia: {$name} ({$slug})\n";

        if ($apply) {
            $inserted = wp_insert_term($name, 'pa_color-familia', ['slug' => $slug]);

            if (is_wp_error($inserted)) {
                echo '  ERROR: ' . $inserted->get_error_message() . "\n";
                continue;
            }

            $familyTermIds[$slug] = $inserted['term_id'];
        }
    } else {
        $familyTermIds[$slug] = $term->term_id;
    }
}

// 2b. Guardar la familia como term meta en cada término de pa_color.
//
//     El array RB_COLOR_TERM_TO_FAMILY vive en este script, que solo corre
//     a mano; el theme necesita resolver "¿de qué familia es este tono?" en
//     cada render de tarjeta (para elegir la imagen de la variación que
//     coincide con el filtro activo). Guardarlo como meta lo hace
//     consultable sin duplicar la tabla en el theme.
$metaEscritos = 0;

foreach (RB_COLOR_TERM_TO_FAMILY as $colorTermId => $familySlug) {
    $actual = get_term_meta($colorTermId, '_rb_color_family', true);

    if ($actual === $familySlug) {
        continue;
    }

    $metaEscritos++;

    if ($apply) {
        update_term_meta($colorTermId, '_rb_color_family', $familySlug);
    }
}

echo "Términos de pa_color a los que se les guarda su familia: {$metaEscritos}\n\n";

// 3. Para cada producto con un color de pa_color mapeado, asignar su
//    familia correspondiente en pa_color-familia.
$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$assigned = 0;
$skippedNoMapping = [];

foreach ($ids as $productId) {
    $colorTerms = get_the_terms($productId, 'pa_color');

    if (! $colorTerms || is_wp_error($colorTerms)) {
        continue;
    }

    $families = [];

    foreach ($colorTerms as $colorTerm) {
        $familySlug = RB_COLOR_TERM_TO_FAMILY[$colorTerm->term_id] ?? null;

        if ($familySlug === null) {
            $skippedNoMapping[$colorTerm->term_id] = $colorTerm->name;
            continue;
        }

        $families[$familySlug] = true;
    }

    foreach (array_keys($families) as $familySlug) {
        $familyName = RB_COLOR_FAMILIES[$familySlug];
        echo "  [{$productId}] color -> familia \"{$familyName}\"\n";

        if ($apply && isset($familyTermIds[$familySlug])) {
            if (! has_term($familyTermIds[$familySlug], 'pa_color-familia', $productId)) {
                wp_set_object_terms($productId, $familyTermIds[$familySlug], 'pa_color-familia', true);
            }

            $rawAttrs = get_post_meta($productId, '_product_attributes', true);
            $rawAttrs = is_array($rawAttrs) ? $rawAttrs : [];

            if (! isset($rawAttrs['pa_color-familia'])) {
                $rawAttrs['pa_color-familia'] = [
                    'name' => 'pa_color-familia',
                    'value' => '',
                    'position' => count($rawAttrs),
                    'is_visible' => 0, // ya se ve el tono exacto vía pa_color; esto es solo para filtrar
                    'is_variation' => 0,
                    'is_taxonomy' => 1,
                ];
                update_post_meta($productId, '_product_attributes', wp_slash($rawAttrs));
            }
        }

        $assigned++;
    }
}

if (! empty($skippedNoMapping)) {
    echo "\nADVERTENCIA: términos de pa_color sin mapeo a familia (revisar):\n";
    foreach ($skippedNoMapping as $id => $name) {
        echo "  [{$id}] {$name}\n";
    }
}

echo "\nAsignaciones de familia procesadas: {$assigned}\n";
echo "Listo.\n";
