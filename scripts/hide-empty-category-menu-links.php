<?php

/**
 * Quita del menú principal cualquier enlace a una categoría de producto
 * con 0 productos — encontrado en la auditoría del 2026-09-22: 18 de
 * ~38 elementos del menú (casi la mitad) apuntaban a categorías vacías,
 * el mismo tipo de bug que "Cascos" (URL vacía) pero en la otra
 * dirección: la URL funciona, pero la página siempre dice "no
 * encontramos productos".
 *
 * Solo toca elementos de tipo taxonomy/product_cat cuyo término tiene
 * count=0 — no toca enlaces custom, a páginas, ni categorías con
 * productos reales aunque tengan pocos.
 *
 * Uso: wp --skip-themes eval-file scripts/hide-empty-category-menu-links.php apply
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

$menuId = get_nav_menu_locations()['primary_navigation'] ?? null;

if (! $menuId) {
    echo "No se encontró la ubicación de menú 'primary_navigation'.\n";
    exit(1);
}

$items = wp_get_nav_menu_items($menuId);
$emptyCategoryIds = [];

foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $cat) {
    if ($cat->count === 0) {
        $emptyCategoryIds[$cat->term_id] = $cat;
    }
}

$removed = 0;

foreach ($items as $item) {
    if ($item->object !== 'product_cat' || ! isset($emptyCategoryIds[(int) $item->object_id])) {
        continue;
    }

    echo "  quitar del menú: [{$item->ID}] \"{$item->title}\" -> {$item->url} (0 productos)\n";

    if ($apply) {
        wp_delete_post($item->ID, true);
    }

    $removed++;
}

echo "\nElementos quitados del menú: {$removed}\n";
echo "Listo.\n";
