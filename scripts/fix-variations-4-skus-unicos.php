<?php

/**
 * FASE 4 del plan de corrección de variaciones.
 *
 * Problema: en 2 productos (BICICLETA ALLIGATOR 841 y BICICLETA MONKEY 835),
 * todas sus variaciones comparten exactamente el mismo SKU que el producto padre
 * (GW-29P-1X11 y GW-MONKEY-29 respectivamente), generando 45 hallazgos de SKU
 * duplicado en el catálogo. Esto impide la correcta gestión de stock,
 * sincronización contable y control de pedidos.
 *
 * Arreglo:
 *  Genera un SKU determinista y único para cada variación combinando:
 *    {SKU_PADRE}-{TALLA}-{COLOR}
 *  siguiendo la convención ya establecida en el catálogo (GW LYNX, GW ZEBRA, GW HAWK).
 *
 * Uso: wp --skip-themes eval-file scripts/fix-variations-4-skus-unicos.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_PRODUCTOS_FASE4 = [
    841, // BICICLETA ALLIGATOR MTB 29P 1X11 VELOCIDADES
    835, // BICICLETA MTB 29 MONKEY 3X7 VELOCIDADES
];

$totalSkusActualizados = 0;
$skusUsadosEnLote = [];

foreach (RB_PRODUCTOS_FASE4 as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        printf("[%d] no es producto variable o no existe, se omite.\n\n", $productId);
        continue;
    }

    $skuPadre = trim((string) $product->get_sku());
    if ($skuPadre === '') {
        printf("[%d] no tiene SKU en el padre, se omite.\n\n", $productId);
        continue;
    }

    printf("=== [%d] %s (SKU Base: %s) ===\n", $productId, $product->get_name(), $skuPadre);

    $children = $product->get_children();

    foreach ($children as $variationId) {
        $v = wc_get_product($variationId);
        if (! $v) {
            continue;
        }

        $skuActual = (string) $v->get_sku();
        $attrs = $v->get_attributes();

        // Obtener talla y color
        $talla = $attrs['pa_talla'] ?? get_post_meta($variationId, 'attribute_pa_talla', true);
        $color = $attrs['pa_color'] ?? get_post_meta($variationId, 'attribute_pa_color', true);

        $partes = [$skuPadre];
        if ($talla !== '') {
            $partes[] = strtoupper($talla);
        }
        if ($color !== '') {
            // Reemplazar guiones múltiples y poner en mayúsculas
            $partes[] = strtoupper(str_replace('---', '-', $color));
        }

        $nuevoSku = implode('-', $partes);

        // Validar que no colisione con otro producto diferente en la tienda
        $existenteId = wc_get_product_id_by_sku($nuevoSku);
        if ($existenteId && $existenteId !== $variationId) {
            printf("  [ALERTA] SKU '%s' ya está en uso por el producto/variación %d! No se puede asignar a %d.\n", $nuevoSku, $existenteId, $variationId);
            continue;
        }

        if (isset($skusUsadosEnLote[$nuevoSku])) {
            printf("  [ALERTA] SKU '%s' duplicado dentro del mismo lote para la variación %d!\n", $nuevoSku, $variationId);
            continue;
        }

        $skusUsadosEnLote[$nuevoSku] = $variationId;

        printf(
            "  - Var %d (%s / %s): SKU '%s' -> '%s'\n",
            $variationId,
            $talla ?: 'sin-talla',
            $color ?: 'sin-color',
            $skuActual ?: '(vacio)',
            $nuevoSku
        );

        if ($apply) {
            update_post_meta($variationId, '_sku', $nuevoSku);
            clean_post_cache($variationId);
        }

        $totalSkusActualizados++;
    }

    if ($apply) {
        clean_post_cache($productId);
        wc_delete_product_transients($productId);
    }

    echo "\n";
}

printf(
    "\n%s: %d SKU(s) de variaciones actualizados.\n",
    $apply ? 'Completado' : 'Se procesarían',
    $totalSkusActualizados
);
