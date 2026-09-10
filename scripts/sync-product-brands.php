<?php

/**
 * Backfill único: espeja hacia pa_marca cualquier producto cuya marca fue
 * asignada desde la taxonomía nativa de WooCommerce (Productos > Marcas,
 * product_brand) antes de que existiera el hook en tiempo real de
 * app/product-brands.php (set_object_terms). Ese hook solo cubre
 * asignaciones nuevas; este script cubre lo que ya estaba en la base de
 * datos cuando se desplegó.
 *
 * Uso: wp eval-file scripts/sync-product-brands.php
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

if (! taxonomy_exists('product_brand') || ! taxonomy_exists('pa_marca')) {
    echo "Falta la taxonomía product_brand o pa_marca. Abortando.\n";
    exit(1);
}

$productIds = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'tax_query'      => [
        [
            'taxonomy' => 'product_brand',
            'operator' => 'EXISTS',
        ],
    ],
]);

echo sprintf("Productos con marca nativa (product_brand): %d\n", count($productIds));

$synced = 0;
$created = 0;

foreach ($productIds as $productId) {
    $brandTerms = get_the_terms($productId, 'product_brand');

    if (! $brandTerms || is_wp_error($brandTerms)) {
        continue;
    }

    foreach ($brandTerms as $brandTerm) {
        $marcaTerm = get_term_by('slug', $brandTerm->slug, 'pa_marca');

        if (! $marcaTerm) {
            $inserted = wp_insert_term($brandTerm->name, 'pa_marca', ['slug' => $brandTerm->slug]);

            if (is_wp_error($inserted)) {
                echo sprintf("  Error creando término pa_marca '%s': %s\n", $brandTerm->name, $inserted->get_error_message());
                continue;
            }

            $marcaTerm = get_term($inserted['term_id'], 'pa_marca');
            $created++;
            echo sprintf("  Creado término pa_marca: %s (%s)\n", $brandTerm->name, $brandTerm->slug);
        }

        if ($marcaTerm && ! is_wp_error($marcaTerm) && ! has_term($marcaTerm->term_id, 'pa_marca', $productId)) {
            wp_set_object_terms($productId, $marcaTerm->term_id, 'pa_marca', true);
            $synced++;
        }
    }
}

echo sprintf("Asignaciones sincronizadas a pa_marca: %d\n", $synced);
echo sprintf("Términos pa_marca creados: %d\n", $created);
echo "Listo.\n";
