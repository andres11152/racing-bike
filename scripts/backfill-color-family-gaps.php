<?php

/**
 * Encontrado en la auditoría de filtros por categoría del 2026-09-22: 5
 * productos publicados tienen pa_color asignado pero NUNCA recibieron su
 * pa_color-familia (la taxonomía que de verdad usa el filtro de Color,
 * ver scripts/migrate-color-families.php) — todos estaban en borrador el
 * día que corrió esa migración (2026-09-10), así que no había nada que
 * migrar en ese momento; se publicaron después sin volver a pasar por
 * ese paso.
 *
 * Este script es el mismo mapeo, aplicado solo a los productos que se
 * quedaron atrás: para cada pa_color ya asignado, busca su familia
 * (guardada como term meta _rb_color_family por la migración original) y
 * asigna esa familia al producto si aún no la tiene.
 *
 * Uso: wp --skip-themes eval-file scripts/backfill-color-family-gaps.php apply
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$fixed = 0;
$skippedNoFamily = [];

foreach ($ids as $productId) {
    $colorTerms = get_the_terms($productId, 'pa_color');
    $familyTerms = get_the_terms($productId, 'pa_color-familia');

    $hasColor = $colorTerms && ! is_wp_error($colorTerms) && count($colorTerms) > 0;
    $hasFamily = $familyTerms && ! is_wp_error($familyTerms) && count($familyTerms) > 0;

    if (! $hasColor || $hasFamily) {
        continue;
    }

    $familyTermIds = [];
    $familyNames = [];

    foreach ($colorTerms as $colorTerm) {
        $familySlug = get_term_meta($colorTerm->term_id, '_rb_color_family', true);

        if (! $familySlug) {
            $skippedNoFamily[] = "{$colorTerm->name} (producto {$productId})";
            continue;
        }

        $familyTerm = get_term_by('slug', $familySlug, 'pa_color-familia');

        if ($familyTerm) {
            $familyTermIds[$familyTerm->term_id] = true;
            $familyNames[] = $familyTerm->name;
        }
    }

    if (empty($familyTermIds)) {
        continue;
    }

    echo "  [{$productId}] " . get_the_title($productId) . ": pa_color-familia -> " . implode(', ', $familyNames) . "\n";

    if ($apply) {
        wp_set_object_terms($productId, array_keys($familyTermIds), 'pa_color-familia', false);

        $rawAttrs = get_post_meta($productId, '_product_attributes', true);
        $rawAttrs = is_array($rawAttrs) ? $rawAttrs : [];

        if (! isset($rawAttrs['pa_color-familia'])) {
            $rawAttrs['pa_color-familia'] = [
                'name' => 'pa_color-familia',
                'value' => '',
                'position' => count($rawAttrs),
                'is_visible' => 0,
                'is_variation' => 0,
                'is_taxonomy' => 1,
            ];
            update_post_meta($productId, '_product_attributes', wp_slash($rawAttrs));
        }
    }

    $fixed++;
}

if ($apply) {
    $familyTaxTerms = get_terms(['taxonomy' => 'pa_color-familia', 'hide_empty' => false, 'fields' => 'id=>parent']);
    _wc_term_recount($familyTaxTerms, get_taxonomy('pa_color-familia'), false, false);
}

echo "\nProductos corregidos: {$fixed}\n";

if (! empty($skippedNoFamily)) {
    echo "Colores sin familia mapeada (revisar a mano): " . implode(', ', array_unique($skippedNoFamily)) . "\n";
}

echo "Listo.\n";
