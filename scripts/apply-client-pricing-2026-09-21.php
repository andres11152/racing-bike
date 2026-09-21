<?php

/**
 * Aplica los precios y variaciones reales que el cliente confirmó el
 * 2026-09-21 para los productos que estaban en borrador por falta de
 * datos. No toca imágenes ni descripciones — solo precio/variaciones, y
 * publica el producto una vez queda completo (excepto donde el cliente
 * pidió explícitamente dejarlo así).
 *
 * Uso: wp --skip-themes eval-file scripts/apply-client-pricing-2026-09-21.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

function rb_set_uniform_variation_price(int $productId, string $price, bool $apply, bool $publish = true): void {
    $product = wc_get_product($productId);

    if (! $product) {
        echo "  [{$productId}] producto no encontrado.\n";
        return;
    }

    echo "=== [{$productId}] {$product->get_name()} -> \${$price} en todas las variaciones ===\n";

    foreach ($product->get_children() as $variationId) {
        $variation = wc_get_product($variationId);

        if (! $variation) {
            continue;
        }

        echo "  variación {$variationId}: " . var_export($variation->get_regular_price(), true) . " -> {$price}\n";

        if ($apply) {
            $variation->set_regular_price($price);
            $variation->set_price($price);
            $variation->save();
        }
    }

    if ($apply && $publish && $product->get_status() !== 'publish') {
        wp_update_post(['ID' => $productId, 'post_status' => 'publish']);
        echo "  -> publicado\n";
    }

    echo "\n";
}

// -----------------------------------------------------------------------
// 1. [2181] Zapatillas Shimano XC1 MTB — $379.000, SOLO blanco, tallas 40-44.
//    Las 5 variaciones existentes están en "negro"; el cliente dice que
//    ese color no existe. Se recolorean a blanco en vez de crear/borrar.
// -----------------------------------------------------------------------
echo "=== [2181] Zapatillas Shimano XC1 MTB — recolorear a blanco + precio \$379.000 ===\n";

$xc1 = wc_get_product(2181);

if ($xc1) {
    $blancoTerm = get_term_by('slug', 'blanco', 'pa_color');

    if (! $blancoTerm) {
        echo "  ERROR: no existe el término 'blanco' en pa_color.\n";
    } else {
        foreach ($xc1->get_children() as $variationId) {
            $variation = wc_get_product($variationId);
            $attrs = $variation->get_attributes();
            echo "  variación {$variationId}: color '{$attrs['pa_color']}' -> 'blanco' | precio -> 379000\n";

            if ($apply) {
                update_post_meta($variationId, 'attribute_pa_color', 'blanco');
                $variation->set_regular_price('379000');
                $variation->set_price('379000');
                $variation->save();
            }
        }

        if ($apply) {
            // El producto (no solo las variaciones) también debe quedar
            // asociado únicamente al término "blanco" en pa_color.
            wp_set_object_terms(2181, [(int) $blancoTerm->term_id], 'pa_color', false);
            wc_delete_product_transients(2181);
            wp_update_post(['ID' => 2181, 'post_status' => 'publish']);
            echo "  -> publicado\n";
        }
    }
}
echo "\n";

// -----------------------------------------------------------------------
// 2. [2153] Zapatillas Shimano RC1 Ruta — $395.000, blanco y negro,
//    tallas 40 a 44. El producto no tiene ninguna variación creada, y a
//    pa_talla le falta la talla 40. Se crean las 10 combinaciones.
// -----------------------------------------------------------------------
echo "=== [2153] Zapatillas Shimano RC1 Ruta — crear 10 variaciones (2 colores x 5 tallas) a \$395.000 ===\n";

$rc1 = wc_get_product(2153);

if ($rc1) {
    $colorSlugs = ['blanco', 'negro'];
    $tallaSlugs = ['40', '41', '42', '43', '44'];

    // Asegurar que el término talla=40 existe y queda entre las opciones
    // del atributo pa_talla de este producto (los otros 4 ya lo estaban).
    $talla40 = get_term_by('slug', '40', 'pa_talla');
    if (! $talla40 && $apply) {
        $inserted = wp_insert_term('40', 'pa_talla', ['slug' => '40']);
        $talla40 = is_wp_error($inserted) ? null : get_term($inserted['term_id'], 'pa_talla');
    }

    if ($apply) {
        $tallaTermIds = [];
        foreach ($tallaSlugs as $slug) {
            $t = get_term_by('slug', $slug, 'pa_talla');
            if ($t) {
                $tallaTermIds[] = $t->term_id;
            }
        }
        wp_set_object_terms(2153, $tallaTermIds, 'pa_talla', false);
    }

    foreach ($colorSlugs as $color) {
        foreach ($tallaSlugs as $talla) {
            echo "  crear variación: color={$color} talla={$talla} precio=395000\n";

            if ($apply) {
                $variation = new WC_Product_Variation();
                $variation->set_parent_id(2153);
                $variation->set_attributes([
                    'pa_color' => $color,
                    'pa_talla' => $talla,
                ]);
                $variation->set_regular_price('395000');
                $variation->set_price('395000');
                $variation->set_stock_status('instock');
                $variation->set_manage_stock(false);
                $variation->save();
            }
        }
    }

    if ($apply) {
        $rc1->save();
        wc_delete_product_transients(2153);
        wp_update_post(['ID' => 2153, 'post_status' => 'publish']);
        echo "  -> publicado\n";
    }
}
echo "\n";

// -----------------------------------------------------------------------
// 3-5. Precio uniforme en todas las variaciones (el cliente confirmó que
//      no cambia entre ellas) + publicar.
// -----------------------------------------------------------------------
rb_set_uniform_variation_price(1828, '999000', $apply);   // Bielas Magene P515
rb_set_uniform_variation_price(1765, '3449000', $apply);  // Grupo Shimano 105 R7120
rb_set_uniform_variation_price(973, '4990000', $apply);   // Trek Procaliber 6 2026

// -----------------------------------------------------------------------
// 6. [1855] Simulador Magene T110 — simple, $1.295.000.
// -----------------------------------------------------------------------
echo "=== [1855] Simulador Magene T110 — precio \$1.295.000 (simple) ===\n";

$t110 = wc_get_product(1855);

if ($t110) {
    echo "  precio actual: " . var_export($t110->get_regular_price(), true) . " -> 1295000\n";

    if ($apply) {
        $t110->set_regular_price('1295000');
        $t110->set_price('1295000');
        $t110->save();
        wp_update_post(['ID' => 1855, 'post_status' => 'publish']);
        echo "  -> publicado\n";
    }
}
echo "\n";

// -----------------------------------------------------------------------
// 7. [2142] Simulador Magene T300 — el cliente pidió dejarlo desactivado
//    por ahora. No se toca nada; solo se confirma su estado actual.
// -----------------------------------------------------------------------
$t300 = wc_get_product(2142);
echo "=== [2142] Simulador Magene T300 — sin cambios (cliente pidió dejarlo en borrador) ===\n";
echo "  status actual: " . ($t300 ? $t300->get_status() : 'no encontrado') . "\n\n";

echo "Listo.\n";
