<?php
/**
 * Migra la talla de marco de centímetros a la nomenclatura por letras
 * (XS–L) que se usa en Colombia, en vez de medidas en cm (48/51/54/57).
 *
 * Renombra los términos globales en su sitio (mismo term_id, así que los
 * atributos ya guardados en cada producto padre siguen siendo válidos) y
 * reescribe el slug guardado en cada variación para que siga apuntando al
 * término correcto.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/fix-sizes-to-letters.php
 *
 * Idempotente.
 */

if (! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce no está activo.');
}

$map = ['48' => 'XS', '51' => 'S', '54' => 'M', '57' => 'L'];
$taxonomy = wc_attribute_taxonomy_name('talla-cuadro');

if (! taxonomy_exists($taxonomy)) {
    WP_CLI::error("La taxonomía {$taxonomy} no existe.");
}

/* --- 1. Renombrar los términos globales (mismo term_id) --- */

$slugMap = [];

foreach ($map as $oldName => $newName) {
    $term = get_term_by('name', $oldName, $taxonomy);

    if (! $term) {
        WP_CLI::log("· Término '{$oldName}' no encontrado (¿ya migrado?), se omite.");
        continue;
    }

    $oldSlug = $term->slug;
    $newSlug = sanitize_title($newName);

    $result = wp_update_term($term->term_id, $taxonomy, [
        'name' => $newName,
        'slug' => $newSlug,
    ]);

    if (is_wp_error($result)) {
        WP_CLI::warning("No se pudo renombrar '{$oldName}' → '{$newName}': " . $result->get_error_message());
        continue;
    }

    $slugMap[$oldSlug] = $newSlug;
    WP_CLI::log("✓ Término '{$oldName}' → '{$newName}' (slug: {$newSlug})");
}

delete_transient('wc_attribute_taxonomies');

/* --- 2. Reescribir el slug guardado en cada variación --- */

$variationIds = get_posts([
    'post_type' => 'product_variation',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$metaKey = 'attribute_' . $taxonomy;
$updated = 0;
$parentIds = [];

foreach ($variationIds as $variationId) {
    $currentSlug = get_post_meta($variationId, $metaKey, true);

    if ($currentSlug === '' || ! isset($slugMap[$currentSlug])) {
        continue;
    }

    update_post_meta($variationId, $metaKey, $slugMap[$currentSlug]);
    $updated++;

    $parentId = wp_get_post_parent_id($variationId);

    if ($parentId) {
        $parentIds[$parentId] = true;
    }
}

WP_CLI::log("Variaciones actualizadas: {$updated}");

/* --- 3. Resincronizar y limpiar caché de cada bicicleta afectada --- */

foreach (array_keys($parentIds) as $parentId) {
    WC_Product_Variable::sync($parentId);
    wc_delete_product_transients($parentId);
}

WP_CLI::success('Tallas migradas de centímetros a letras (XS–L).');
