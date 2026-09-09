<?php
/**
 * Construye el menú principal con jerarquía (padre → hijos) para alimentar el mega menú.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-menu.php
 *
 * Idempotente: vacía y reconstruye el menú en cada ejecución.
 */

$menuName = 'Navegación principal';
$menu = wp_get_nav_menu_object($menuName);

if (! $menu) {
    $menuId = wp_create_nav_menu($menuName);
} else {
    $menuId = (int) $menu->term_id;

    // Vaciar para reconstruir sin duplicar.
    foreach (wp_get_nav_menu_items($menuId) ?: [] as $item) {
        wp_delete_post($item->ID, true);
    }
}

/** Añade un ítem que apunta a una categoría de producto. */
function rb_menu_category(int $menuId, string $categoryName, int $parentItemId = 0): int
{
    $term = get_term_by('name', $categoryName, 'product_cat');

    if (! $term) {
        WP_CLI::warning("Categoría no encontrada: {$categoryName}");

        return 0;
    }

    return (int) wp_update_nav_menu_item($menuId, 0, [
        'menu-item-title'     => $categoryName,
        'menu-item-object'    => 'product_cat',
        'menu-item-object-id' => $term->term_id,
        'menu-item-type'      => 'taxonomy',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parentItemId,
    ]);
}

/** Añade un ítem que apunta a una URL arbitraria. */
function rb_menu_link(int $menuId, string $title, string $url, int $parentItemId = 0): int
{
    return (int) wp_update_nav_menu_item($menuId, 0, [
        'menu-item-title'     => $title,
        'menu-item-url'       => $url,
        'menu-item-type'      => 'custom',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parentItemId,
    ]);
}

$bicicletas = rb_menu_category($menuId, 'Bicicletas');
rb_menu_category($menuId, 'Ruta', $bicicletas);
rb_menu_category($menuId, 'Gravel', $bicicletas);
rb_menu_category($menuId, 'Pista', $bicicletas);

$componentes = rb_menu_category($menuId, 'Componentes');
rb_menu_link($menuId, 'Ruedas', home_url('/shop/?filter_material=carbono'), $componentes);
rb_menu_link($menuId, 'Transmisión', home_url('/shop/?filter_grupo=shimano-105'), $componentes);

$accesorios = rb_menu_category($menuId, 'Accesorios');
rb_menu_link($menuId, 'Cascos', home_url('/shop/'), $accesorios);
rb_menu_link($menuId, 'Hidratación', home_url('/shop/'), $accesorios);

rb_menu_link($menuId, 'Ofertas', home_url('/shop/?on_sale=1'));

$locations = get_theme_mod('nav_menu_locations', []);
$locations['primary_navigation'] = $menuId;
set_theme_mod('nav_menu_locations', $locations);

WP_CLI::success('Menú principal construido con ' . count(wp_get_nav_menu_items($menuId)) . ' ítems.');
