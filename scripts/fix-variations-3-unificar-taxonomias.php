<?php

/**
 * FASE 3 del plan de corrección de variaciones.
 *
 * Problema: en 13 productos (bicicletas de ruta y montaña), los atributos
 * de "Talla" y "Color" / "Colores" existen simultáneamente como atributos
 * de texto libre (usados para generar las variaciones) y como taxonomías
 * pa_talla / pa_color (asignadas al producto pero NO usadas para variaciones).
 * Esto produce dos fuentes de verdad: el catálogo filtra por pa_*, pero
 * la selección de variaciones en la ficha de producto y el checkout usan
 * el texto libre. Además, en "Información adicional" aparecen duplicados.
 *
 * Arreglo:
 *  1. En cada variación:
 *     - Migra attribute_talla -> attribute_pa_talla (slug del término).
 *     - Migra attribute_color / attribute_colores -> attribute_pa_color (slug del término).
 *     - Borra las metaclaves de texto libre attribute_talla, attribute_color, attribute_colores.
 *     - En el producto 509: borra attribute_tipo-de-freno de las variaciones.
 *  2. En el producto padre (_product_attributes):
 *     - Marca pa_talla como usado para variaciones (is_variation = 1, is_visible = 1, is_taxonomy = 1).
 *     - Marca pa_color como usado para variaciones (is_variation = 1, is_visible = 1, is_taxonomy = 1).
 *     - Si existe pa_color-familia, mantiene is_variation = 0, is_visible = 0.
 *     - Elimina los atributos de texto libre (Talla, Color, Colores).
 *     - En el producto 509: mantiene Tipo de freno como atributo técnico visible
 *       (is_variation = 0, is_visible = 1) sin forzar dropdown en compra.
 *  3. Sincroniza términos en el padre y limpia transitorios de WooCommerce.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-variations-3-unificar-taxonomias.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_PRODUCTOS_FASE3 = [
    1713, // TREK ÉMONDA ALR 5 2026
    1692, // TREK DOMANE AL 2 2026
    1128, // ORBEA ALMA H20 2026
    882,  // ORBEA ALMA H30 2026
    852,  // ORBEA ALMA H30 2025
    741,  // Bicicleta Ruta Orbea Avant H50 2026
    677,  // Orbea Orca M30i 2025 OMR
    676,  // Orbea Orca M30 2025
    518,  // Bicicleta Zoncolan 700C / 105 2*12 Vel. Db Hidraulico
    515,  // Bicicleta Sprinter Ruta
    512,  // Bicicleta Letras+D Ruta
    509,  // Bicicleta Flamma Disco Ruta Tiagra 10 Velocidades
    506,  // Bicicleta Flamma 700C / Claris 2X8 Vel.
];

/**
 * Resuelve el slug de un término a partir del valor crudo de la variación.
 */
function rb_resolver_slug_termino(string $valorCrudo, string $taxonomia): ?string
{
    $slug = sanitize_title($valorCrudo);
    $term = get_term_by('slug', $slug, $taxonomia);

    if ($term) {
        return $term->slug;
    }

    $termByName = get_term_by('name', $valorCrudo, $taxonomia);
    if ($termByName) {
        return $termByName->slug;
    }

    return null;
}

$totalVariacionesModificadas = 0;
$totalPadresModificados = 0;

foreach (RB_PRODUCTOS_FASE3 as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        printf("[%d] no es producto variable o no existe, se omite.\n\n", $productId);
        continue;
    }

    printf("=== [%d] %s ===\n", $productId, $product->get_name());

    $rawAttrs = get_post_meta($productId, '_product_attributes', true) ?: [];
    $tieneTalla = false;
    $tieneColor = false;

    // Detectar qué taxonomías deben quedar activas para variaciones
    foreach ($rawAttrs as $k => $attrData) {
        $nombre = strtolower(str_replace(['pa_', '-', '_'], '', $attrData['name'] ?? $k));
        if ($nombre === 'talla') {
            $tieneTalla = true;
        }
        if ($nombre === 'color' || $nombre === 'colores') {
            $tieneColor = true;
        }
    }

    $children = $product->get_children();
    $tallaSlugsUsados = [];
    $colorSlugsUsados = [];
    $variacionesOk = 0;

    echo "  Procesando " . count($children) . " variaciones:\n";

    foreach ($children as $variationId) {
        // Leer metadatos actuales de la variación
        $tallaRaw = get_post_meta($variationId, 'attribute_talla', true);
        if ($tallaRaw === '') {
            $tallaRaw = get_post_meta($variationId, 'attribute_pa_talla', true);
        }

        $colorRaw = get_post_meta($variationId, 'attribute_color', true);
        if ($colorRaw === '') {
            $colorRaw = get_post_meta($variationId, 'attribute_colores', true);
        }
        if ($colorRaw === '') {
            $colorRaw = get_post_meta($variationId, 'attribute_pa_color', true);
        }

        $nuevoAttrTalla = null;
        if ($tieneTalla && $tallaRaw !== '') {
            $nuevoAttrTalla = rb_resolver_slug_termino($tallaRaw, 'pa_talla');
            if (! $nuevoAttrTalla) {
                printf("    [ERROR] var %d: valor de talla '%s' no tiene término en pa_talla!\n", $variationId, $tallaRaw);
            } else {
                $tallaSlugsUsados[] = $nuevoAttrTalla;
            }
        }

        $nuevoAttrColor = null;
        if ($tieneColor && $colorRaw !== '') {
            $nuevoAttrColor = rb_resolver_slug_termino($colorRaw, 'pa_color');
            if (! $nuevoAttrColor) {
                printf("    [ERROR] var %d: valor de color '%s' no tiene término en pa_color!\n", $variationId, $colorRaw);
            } else {
                $colorSlugsUsados[] = $nuevoAttrColor;
            }
        }

        printf(
            "    - Var %d: talla='%s' -> pa_talla='%s' | color='%s' -> pa_color='%s'\n",
            $variationId,
            $tallaRaw,
            $nuevoAttrTalla ?: '-',
            $colorRaw,
            $nuevoAttrColor ?: '-'
        );

        if ($apply) {
            if ($nuevoAttrTalla) {
                update_post_meta($variationId, 'attribute_pa_talla', $nuevoAttrTalla);
            }
            if ($nuevoAttrColor) {
                update_post_meta($variationId, 'attribute_pa_color', $nuevoAttrColor);
            }

            // Limpiar metaclaves obsoletas
            delete_post_meta($variationId, 'attribute_talla');
            delete_post_meta($variationId, 'attribute_color');
            delete_post_meta($variationId, 'attribute_colores');
            delete_post_meta($variationId, 'attribute_pa_talla-cuadro');

            if ($productId === 509) {
                delete_post_meta($variationId, 'attribute_tipo-de-freno');
            }

            clean_post_cache($variationId);
        }

        $variacionesOk++;
        $totalVariacionesModificadas++;
    }

    // Configurar _product_attributes en el producto padre
    $tallaSlugsUsados = array_values(array_unique($tallaSlugsUsados));
    $colorSlugsUsados = array_values(array_unique($colorSlugsUsados));

    $nuevosAttrs = [];
    $pos = 0;

    // 1. pa_talla como taxonomía de variación
    if ($tieneTalla) {
        $nuevosAttrs['pa_talla'] = [
            'name' => 'pa_talla',
            'value' => '',
            'position' => $pos++,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 1,
        ];
        echo "  Padre: pa_talla activado para variaciones (" . implode(', ', $tallaSlugsUsados) . ")\n";
    }

    // 2. pa_color como taxonomía de variación
    if ($tieneColor) {
        $nuevosAttrs['pa_color'] = [
            'name' => 'pa_color',
            'value' => '',
            'position' => $pos++,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 1,
        ];
        echo "  Padre: pa_color activado para variaciones (" . implode(', ', $colorSlugsUsados) . ")\n";
    }

    // 3. Conservar otros atributos (ej. pa_color-familia, tipo-de-freno) sin variación
    foreach ($rawAttrs as $k => $attrData) {
        if ($k === 'pa_talla' || $k === 'pa_color') {
            continue; // Ya incluidos arriba
        }

        $isTax = ! empty($attrData['is_taxonomy']);
        $nombreNorm = strtolower(str_replace(['pa_', '-', '_'], '', $attrData['name'] ?? $k));

        if (! $isTax && in_array($nombreNorm, ['talla', 'color', 'colores'], true)) {
            // Se descartan las versiones de texto libre redundantes
            echo "  Padre: eliminando atributo redundante de texto libre '{$attrData['name']}'\n";
            continue;
        }

        $attrData['position'] = $pos++;
        if ($productId === 509 && ($k === 'tipo-de-freno' || ($attrData['name'] ?? '') === 'Tipo de freno')) {
            $attrData['is_variation'] = 0;
            $attrData['is_visible'] = 1;
            echo "  Padre [509]: 'Tipo de freno' configurado como especificación visible sin variación\n";
        }

        $nuevosAttrs[$k] = $attrData;
    }

    if ($apply) {
        // Asignar términos al padre si hiciera falta alguno
        if ($tieneTalla && ! empty($tallaSlugsUsados)) {
            wp_set_object_terms($productId, $tallaSlugsUsados, 'pa_talla', false);
        }
        if ($tieneColor && ! empty($colorSlugsUsados)) {
            wp_set_object_terms($productId, $colorSlugsUsados, 'pa_color', false);
        }

        update_post_meta($productId, '_product_attributes', wp_slash($nuevosAttrs));
        clean_post_cache($productId);
        WC_Product_Variable::sync($productId);
        wc_delete_product_transients($productId);
        echo "  [OK] Guardado y sincronizado.\n";
    }

    $totalPadresModificados++;
    echo "\n";
}

printf(
    "\n%s: %d productos padre y %d variaciones procesadas.\n",
    $apply ? 'Completado' : 'Se procesaría',
    $totalPadresModificados,
    $totalVariacionesModificadas
);
