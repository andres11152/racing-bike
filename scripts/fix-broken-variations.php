<?php

/**
 * One-off fix for 4 products found broken in production on 2026-09-10:
 *
 *  - [832] BICICLETA GW HAWK 1X12 MTB R29: 27 of 28 variations have no
 *    price, so WooCommerce hides them from the buyer. Fills in the price
 *    the client confirmed ($2.490.000, same as the parent) — does not
 *    touch the one variation that already has a price, and does not
 *    delete the duplicate combinations already in the database.
 *
 *  - [841] BICICLETA ALLIGATOR MTB 29P: pa_talla/pa_color exist on the
 *    product but aren't flagged "used for variations", and its 3
 *    variations carry no attribute values at all. Flags both attributes,
 *    reuses those 3 empty variation posts for 3 of the 18 real
 *    combinations (3 tallas x 6 colores, confirmed with the client), and
 *    creates the remaining 15. All 18 get the parent's price ($2.390.000).
 *
 *  - [399] Magene H603 / [400] Magene H613: marked "variable" with zero
 *    variations and no price. Converted to "simple" and set to draft
 *    (client will add a real price before publishing).
 *
 * Usage: wp --skip-themes eval-file scripts/fix-broken-variations.php apply
 * Without the "apply" argument it only prints what would change (dry run).
 * (Positional arg, not a --flag: wp-cli's eval-file rejects unknown flags.)
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

// -----------------------------------------------------------------------
// 1. [832] GW HAWK — completar precio en variaciones sin precio.
// -----------------------------------------------------------------------
echo "=== [832] BICICLETA GW HAWK 1X12 MTB R29 ===\n";

$hawkPrice = '2490000';
$hawk = wc_get_product(832);

if (! $hawk) {
    echo "  Producto 832 no encontrado, se omite.\n\n";
} else {
    $fixed = 0;

    foreach ($hawk->get_children() as $variationId) {
        $variation = wc_get_product($variationId);

        if (! $variation) {
            continue;
        }

        if ($variation->get_price() !== '' && $variation->get_price() !== null) {
            continue; // ya tiene precio, no tocar
        }

        echo "  variación {$variationId}: precio vacío -> {$hawkPrice}\n";

        if ($apply) {
            $variation->set_regular_price($hawkPrice);
            $variation->set_price($hawkPrice);
            $variation->save();
        }

        $fixed++;
    }

    echo "  Total variaciones corregidas: {$fixed}\n\n";
}

// -----------------------------------------------------------------------
// 2. [841] Alligator — marcar atributos para variación y completar las
//    18 combinaciones talla x color.
// -----------------------------------------------------------------------
echo "=== [841] BICICLETA ALLIGATOR MTB 29P ===\n";

$alligator = wc_get_product(841);

if (! $alligator) {
    echo "  Producto 841 no encontrado, se omite.\n\n";
} else {
    $tallaSlugs = ['15', '17', '19'];
    $colorSlugs = ['azul-humo', 'azul-real', 'blanco-mate', 'negro-perlado', 'rojo-marron', 'verde-pino'];

    // Precio confirmado con el cliente. OJO: NO usar
    // $alligator->get_regular_price() aquí — en un producto variable el
    // padre normalmente no tiene su propio _regular_price (WooCommerce lo
    // calcula desde las variaciones), así que esa llamada devuelve '' y
    // dejaría las 18 variaciones sin precio.
    $alligatorPrice = '2390000';

    // 2a. Marcar pa_talla y pa_color como usados para variación.
    //
    // No usar $attribute->set_variation(true) + $product->set_attributes()
    // + save(): get_attributes() devuelve los mismos objetos por
    // referencia que ya están en el producto, así que al mutarlos antes de
    // reasignarlos, WC_Data ve "new value === current value" y no marca
    // 'attributes' como cambiado — update_attributes() en el data store
    // solo escribe si array_key_exists('attributes', $product->get_changes()),
    // así que el save() no escribe nada. Se actualiza el meta directamente.
    $rawAttrs = get_post_meta($alligator->get_id(), '_product_attributes', true);
    $changedAttrs = false;

    foreach (['pa_talla', 'pa_color'] as $attrName) {
        if (isset($rawAttrs[$attrName]) && empty($rawAttrs[$attrName]['is_variation'])) {
            echo "  atributo {$attrName}: marcar como usado para variaciones\n";
            $rawAttrs[$attrName]['is_variation'] = 1;
            $changedAttrs = true;
        }
    }

    if ($apply && $changedAttrs) {
        update_post_meta($alligator->get_id(), '_product_attributes', wp_slash($rawAttrs));
    }

    // 2b. Combinaciones existentes (por si ya hay alguna con atributos reales).
    $existingCombos = [];
    $reusableEmptyIds = [];

    foreach ($alligator->get_children() as $variationId) {
        $variation = wc_get_product($variationId);

        if (! $variation) {
            continue;
        }

        $attrs = $variation->get_attributes();
        $talla = $attrs['pa_talla'] ?? '';
        $color = $attrs['pa_color'] ?? '';

        if ($talla === '' && $color === '') {
            $reusableEmptyIds[] = $variationId;
            continue;
        }

        $existingCombos[$talla . '|' . $color] = $variationId;
    }

    $created = 0;
    $reused = 0;

    foreach ($tallaSlugs as $talla) {
        foreach ($colorSlugs as $color) {
            $comboKey = $talla . '|' . $color;

            if (isset($existingCombos[$comboKey])) {
                continue; // ya existe con estos atributos, no duplicar
            }

            $variationId = array_shift($reusableEmptyIds);
            $mode = $variationId ? 'reutiliza vacía' : 'crea nueva';

            echo "  {$mode}: talla={$talla} color={$color}" . ($variationId ? " (ID {$variationId})" : '') . "\n";

            if ($apply) {
                if (! $variationId) {
                    $variation = new WC_Product_Variation();
                    $variation->set_parent_id(841);
                } else {
                    $variation = wc_get_product($variationId);
                }

                $variation->set_attributes([
                    'pa_talla' => $talla,
                    'pa_color' => $color,
                ]);
                $variation->set_regular_price($alligatorPrice);
                $variation->set_price($alligatorPrice);
                $variation->set_stock_status('instock');
                $variation->set_manage_stock(false);
                $variation->save();
            }

            $variationId ? $reused++ : $created++;
        }
    }

    if ($apply) {
        $alligator->save(); // recalcula rangos de precio/stock del padre
    }

    echo "  Variaciones reutilizadas: {$reused} | creadas: {$created}\n\n";
}

// -----------------------------------------------------------------------
// 3. Magene H603 [399] / H613 [400] — pasar a simple + borrador.
// -----------------------------------------------------------------------
echo "=== Magene H603 [399] / H613 [400] ===\n";

foreach ([399, 400] as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        echo "  Producto {$productId} no encontrado, se omite.\n";
        continue;
    }

    echo "  [{$productId}] {$product->get_name()}: variable (sin variaciones) -> simple, status draft\n";

    if ($apply) {
        wp_set_object_terms($productId, 'simple', 'product_type');
        wp_update_post([
            'ID'          => $productId,
            'post_status' => 'draft',
        ]);
    }
}

echo "\nListo.\n";
