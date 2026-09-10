<?php

/**
 * One-off migration for products found in production on 2026-09-10 using
 * free-text (non-taxonomy) attributes instead of the shared pa_talla /
 * pa_color taxonomies. WooCommerce's built-in filter-by-attribute query
 * (WC_Query::get_layered_nav_chosen_attributes) only ever filters on real
 * registered attribute taxonomies — a free-text "talla" or "color"
 * attribute can be displayed on the product page, but a filter link built
 * from it would never actually narrow the product grid. This creates (or
 * reuses) the matching pa_talla / pa_color term for each free-text value
 * and assigns it to the product, additively — it does not remove the
 * original custom attribute, so the existing spec display is untouched.
 *
 * Deliberately NOT included: "cassette" and "bielas" (crank/cassette
 * ratios like "52-36", "11-34t") do not map to a Grupo/groupset name
 * (Shimano 105, SRAM Force...) — there is no reliable way to derive one
 * from the other, so migrating those would mean inventing product data.
 *
 * Usage: wp --skip-themes eval-file scripts/migrate-custom-attributes.php apply
 * Without "apply" it only prints what would change (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

/**
 * Encuentra o crea (si $apply) el término de destino para un valor de
 * texto libre, y lo asigna al producto — tanto la relación de taxonomía
 * (para que el filtro de WooCommerce funcione) como la entrada en
 * _product_attributes (para que se vea igual de consistente en el admin
 * que los productos que ya usaban la taxonomía real).
 */
function migrate_attribute_value(int $productId, string $sourceAttrName, string $targetTaxonomy, string $rawValue, bool $apply): array
{
    $slug = sanitize_title($rawValue);
    $term = get_term_by('slug', $slug, $targetTaxonomy);
    $wasNew = ! $term;
    $action = $wasNew ? 'crea' : 'reutiliza';

    echo "  [{$productId}] {$sourceAttrName}=\"{$rawValue}\" -> {$targetTaxonomy} ({$action} término \"{$slug}\")\n";

    if (! $apply) {
        return ['created' => $wasNew, 'slug' => $slug];
    }

    if (! $term) {
        $inserted = wp_insert_term($rawValue, $targetTaxonomy, ['slug' => $slug]);

        if (is_wp_error($inserted)) {
            echo "    ERROR creando término: {$inserted->get_error_message()}\n";

            return ['created' => false, 'slug' => $slug, 'error' => true];
        }

        $term = get_term($inserted['term_id'], $targetTaxonomy);
    }

    if (! has_term($term->term_id, $targetTaxonomy, $productId)) {
        wp_set_object_terms($productId, $term->term_id, $targetTaxonomy, true);
    }

    // Reflejar también en _product_attributes para que el admin lo muestre
    // igual que en los productos que ya usaban la taxonomía directamente.
    $rawAttrs = get_post_meta($productId, '_product_attributes', true);
    $rawAttrs = is_array($rawAttrs) ? $rawAttrs : [];

    if (! isset($rawAttrs[$targetTaxonomy])) {
        $rawAttrs[$targetTaxonomy] = [
            'name' => $targetTaxonomy,
            'value' => '',
            'position' => count($rawAttrs),
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 1,
        ];
        update_post_meta($productId, '_product_attributes', wp_slash($rawAttrs));
    }

    return ['created' => $wasNew, 'slug' => $slug];
}

$sources = [
    'talla' => 'pa_talla',
    'color' => 'pa_color',
    'colores' => 'pa_color',
];

$stats = ['assigned' => 0, 'terms_created' => 0];

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($ids as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        continue;
    }

    foreach ($product->get_attributes() as $attrName => $attribute) {
        if (! isset($sources[$attrName])) {
            continue;
        }

        $targetTaxonomy = $sources[$attrName];

        foreach ($attribute->get_options() as $rawValue) {
            $rawValue = trim((string) $rawValue);

            if ($rawValue === '') {
                continue;
            }

            $result = migrate_attribute_value($productId, $attrName, $targetTaxonomy, $rawValue, $apply);

            $stats['assigned']++;

            if ($result['created']) {
                $stats['terms_created']++;
            }
        }
    }
}

echo "\nAsignaciones procesadas: {$stats['assigned']}\n";
echo "Términos nuevos creados: {$stats['terms_created']}\n";
echo "Listo.\n";
