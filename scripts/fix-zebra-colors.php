<?php

/**
 * Realinea los colores de [1645] BICICLETA ZEBRA MTB 29 con las fotos que
 * realmente existen en su galería.
 *
 * El producto declaraba 6 colores (Negro Perlado, Negro Mate, Azul
 * Special, Blanco, Turqueza, Gris) que no corresponden a ninguna de sus
 * fotos: la galería muestra 5 combinaciones distintas (negro con acentos
 * rosados / naranjas / rojos, y azul noche con acentos turquesa / verde
 * neón), más una foto repetida byte a byte. Filtrar por Blanco devolvía
 * una bici negra.
 *
 * Decisión del cliente: renombrar los colores según las fotos y ajustar
 * los SKUs para que sigan coincidiendo (los SKUs codifican el color:
 * GW-ZEBRA-29-15-BLANCO). Se le advirtió que si esos SKUs se usan en el
 * sistema del proveedor quedan desincronizados; aun así pidió proceder.
 *
 * No se tocan los términos compartidos (blanco, negro-perlado y
 * negro-mate los usan otros productos): se crean términos nuevos y se
 * reapunta solo este producto.
 *
 * "Blanco" se queda como está: no existe ninguna foto blanca que
 * asignarle, y borrar sus 3 variaciones (con stock y SKU propios) no se
 * autorizó. Queda reportado para que el cliente suba la foto o retire ese
 * color.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-zebra-colors.php apply
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_ZEBRA_ID = 1645;

// slug de color actual => [nombre nuevo, slug nuevo, familia, foto, sufijo de SKU]
const RB_ZEBRA_REMAP = [
    'turqueza' => ['Azul Noche - Turquesa', 'azul-noche-turquesa', 'azul', 1673, 'AZUL-NOCHE-TURQUESA'],
    'azul-special' => ['Azul Noche - Verde Neón', 'azul-noche-verde-neon', 'azul', 1678, 'AZUL-NOCHE-VERDE-NEON'],
    'negro-perlado' => ['Negro - Rosado', 'negro-rosado', 'negro', 1674, 'NEGRO-ROSADO'],
    'negro-mate' => ['Negro - Naranja', 'negro-naranja', 'negro', 1676, 'NEGRO-NARANJA'],
    'gris' => ['Negro - Rojo', 'negro-rojo-zebra', 'negro', 1675, 'NEGRO-ROJO'],
];

$product = wc_get_product(RB_ZEBRA_ID);

if (! $product) {
    echo "Producto " . RB_ZEBRA_ID . " no encontrado.\n";
    exit(1);
}

// 1. Crear los términos nuevos y guardarles su familia de color.
$newTermIds = [];

foreach (RB_ZEBRA_REMAP as $oldSlug => [$newName, $newSlug, $family, $imageId, $skuSuffix]) {
    $term = get_term_by('slug', $newSlug, 'pa_color');

    if (! $term) {
        echo "Crear término pa_color: {$newName} ({$newSlug}) — familia {$family}\n";

        if ($apply) {
            $inserted = wp_insert_term($newName, 'pa_color', ['slug' => $newSlug]);

            if (is_wp_error($inserted)) {
                echo '  ERROR: ' . $inserted->get_error_message() . "\n";
                continue;
            }

            $newTermIds[$newSlug] = $inserted['term_id'];
            update_term_meta($inserted['term_id'], '_rb_color_family', $family);
        }
    } else {
        $newTermIds[$newSlug] = $term->term_id;

        if ($apply) {
            update_term_meta($term->term_id, '_rb_color_family', $family);
        }
    }
}

// 2. Reapuntar cada variación: color, foto y SKU.
$actualizadas = 0;
$sinCambio = 0;

foreach ($product->get_children() as $variationId) {
    $currentSlug = get_post_meta($variationId, 'attribute_pa_color', true);

    if (! isset(RB_ZEBRA_REMAP[$currentSlug])) {
        $sinCambio++;
        continue; // "blanco" u otro que no se remapea
    }

    [$newName, $newSlug, $family, $imageId, $skuSuffix] = RB_ZEBRA_REMAP[$currentSlug];

    $variation = wc_get_product($variationId);
    $talla = get_post_meta($variationId, 'attribute_pa_talla', true);
    $newSku = "GW-ZEBRA-29-{$talla}-{$skuSuffix}";

    echo "  variación {$variationId} (talla {$talla}): {$currentSlug} -> {$newSlug}"
        . " | foto {$imageId} | SKU {$variation->get_sku()} -> {$newSku}\n";

    if ($apply) {
        update_post_meta($variationId, 'attribute_pa_color', $newSlug);
        update_post_meta($variationId, '_thumbnail_id', $imageId);

        $variation->set_sku($newSku);
        $variation->save();
    }

    $actualizadas++;
}

// 3. Rehacer las relaciones de término del producto: los colores nuevos
//    entran, los viejos que ya no usa ninguna variación salen (solo la
//    relación con ESTE producto; el término sigue existiendo para los
//    demás).
if ($apply) {
    $finalSlugs = array_column(array_values(RB_ZEBRA_REMAP), 1);

    // Lo que quede sin remapear (blanco) conserva su término.
    foreach ($product->get_children() as $variationId) {
        $slug = get_post_meta($variationId, 'attribute_pa_color', true);

        if ($slug && ! in_array($slug, $finalSlugs, true)) {
            $finalSlugs[] = $slug;
        }
    }

    $finalTermIds = [];

    foreach (array_unique($finalSlugs) as $slug) {
        $term = get_term_by('slug', $slug, 'pa_color');

        if ($term) {
            $finalTermIds[] = $term->term_id;
        }
    }

    wp_set_object_terms(RB_ZEBRA_ID, $finalTermIds, 'pa_color', false);

    // 4. Recalcular las familias de color del producto a partir de los
    //    colores que le quedaron.
    $familyTermIds = [];

    foreach ($finalTermIds as $colorTermId) {
        $family = get_term_meta($colorTermId, '_rb_color_family', true);

        if (! $family) {
            continue;
        }

        $familyTerm = get_term_by('slug', $family, 'pa_color-familia');

        if ($familyTerm) {
            $familyTermIds[$familyTerm->term_id] = true;
        }
    }

    wp_set_object_terms(RB_ZEBRA_ID, array_keys($familyTermIds), 'pa_color-familia', false);

    WC_Product_Variable::sync(RB_ZEBRA_ID);
    wc_delete_product_transients(RB_ZEBRA_ID);
}

echo "\nVariaciones actualizadas: {$actualizadas}\n";
echo "Variaciones sin remapear (Blanco, sin foto disponible): {$sinCambio}\n";
echo "Listo.\n";
