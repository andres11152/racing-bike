<?php

/**
 * Quita la categoría "Uncategorized" de cualquier producto que ya tenga
 * al menos otra categoría real asignada — nunca deja un producto sin
 * categorías (si "Uncategorized" fuera la única, se deja intacta).
 *
 * Uso: wp --skip-themes eval-file scripts/remove-redundant-uncategorized.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$fixed = 0;

foreach ($ids as $id) {
    $terms = wp_get_post_terms($id, 'product_cat');

    if (is_wp_error($terms) || count($terms) <= 1) {
        continue;
    }

    $uncategorized = null;
    $others = [];

    foreach ($terms as $term) {
        if ($term->slug === 'uncategorized') {
            $uncategorized = $term;
        } else {
            $others[] = $term->term_id;
        }
    }

    if (! $uncategorized || empty($others)) {
        continue;
    }

    echo "  [{$id}] " . get_the_title($id) . ": quitar 'Uncategorized', conservar " . count($others) . " categoría(s) real(es)\n";

    if ($apply) {
        wp_set_object_terms($id, $others, 'product_cat', false);
    }

    $fixed++;
}

echo "\nProductos corregidos: {$fixed}\n";
echo "Listo.\n";
