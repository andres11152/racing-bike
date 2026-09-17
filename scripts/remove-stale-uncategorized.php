<?php

/**
 * Quita el término "uncategorized" de cualquier producto que YA tenga al
 * menos otra categoría real asignada. Es un residuo típico de WordPress:
 * al crear el producto se le puso "uncategorized" por defecto, y cuando
 * después se le asignó su categoría real nadie quitó la de por defecto.
 *
 * Auditoría del 2026-09-17: 29 productos publicados con "uncategorized" +
 * su categoría real al mismo tiempo — ensucia breadcrumbs, el menú de
 * categorías y la taxonomía primaria que usa Rank Math para el SEO.
 *
 * No toca productos donde "uncategorized" es la ÚNICA categoría — esos
 * genuinamente no tienen categoría asignada; quitarles su única
 * categoría los dejaría sin ninguna, que es peor.
 *
 * Usage: wp --skip-themes eval-file scripts/remove-stale-uncategorized.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

$uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');

if (! $uncategorized) {
    echo "No existe el término 'uncategorized' en product_cat. Nada que hacer.\n";
    return;
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => [[
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => [$uncategorized->term_id],
    ]],
]);

$fixed = 0;
$skippedOnlyCat = 0;

foreach ($ids as $productId) {
    $terms = wp_get_post_terms($productId, 'product_cat', ['fields' => 'all']);

    if (is_wp_error($terms)) {
        continue;
    }

    $otherTermIds = array_values(array_filter(
        wp_list_pluck($terms, 'term_id'),
        fn ($termId) => (int) $termId !== (int) $uncategorized->term_id
    ));

    $name = get_the_title($productId);

    if (empty($otherTermIds)) {
        echo "[{$productId}] {$name} -> se deja igual ('uncategorized' es su única categoría)\n";
        $skippedOnlyCat++;
        continue;
    }

    $otherNames = wp_list_pluck(array_filter($terms, fn ($t) => (int) $t->term_id !== (int) $uncategorized->term_id), 'name');
    echo "[{$productId}] {$name} -> quita 'uncategorized' (conserva: " . implode(', ', $otherNames) . ")\n";

    if ($apply) {
        wp_remove_object_terms($productId, $uncategorized->term_id, 'product_cat');
    }

    $fixed++;
}

echo "\n";
printf(
    "%s: %d producto(s) limpiados. %d dejados igual (sin otra categoría que asignarles).\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $fixed,
    $skippedOnlyCat
);
