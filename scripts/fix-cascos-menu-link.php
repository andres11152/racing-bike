<?php

/**
 * El menú principal tiene dos elementos "Cascos" en submenús distintos
 * (bajo "Para Ti" y bajo "Para tu bici"). El de "Para Ti" (ID 56) es un
 * enlace tipo "custom" con la URL completamente vacía — clic y no pasa
 * nada. El de "Para tu bici" (ID 2098) sí apunta a la categoría real.
 *
 * Corrige el ID 56 para que apunte a la misma categoría (product_cat 461,
 * /product-category/para-ti/cascos/) en vez de moverlo o borrarlo — sigue
 * teniendo más sentido bajo "Para Ti" (junto a Gafas, Guantes, Ropa,
 * Zapatillas) que bajo "Para tu bici".
 *
 * Uso: wp --skip-themes eval-file scripts/fix-cascos-menu-link.php apply
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_BROKEN_MENU_ITEM_ID = 56;
const RB_CASCOS_CATEGORY_ID = 461;

$item = get_post(RB_BROKEN_MENU_ITEM_ID);

if (! $item || $item->post_type !== 'nav_menu_item') {
    echo "El elemento de menú " . RB_BROKEN_MENU_ITEM_ID . " no existe o no es un ítem de menú.\n";
    exit(1);
}

$currentUrl = get_post_meta(RB_BROKEN_MENU_ITEM_ID, '_menu_item_url', true);
$correctUrl = get_term_link(RB_CASCOS_CATEGORY_ID, 'product_cat');

if (is_wp_error($correctUrl)) {
    echo 'ERROR obteniendo el link de la categoría: ' . $correctUrl->get_error_message() . "\n";
    exit(1);
}

echo "Elemento de menú {$item->ID} (\"{$item->post_title}\"): URL actual = \"" . ($currentUrl ?: '(vacía)') . "\" -> \"{$correctUrl}\"\n";

if ($apply) {
    update_post_meta(RB_BROKEN_MENU_ITEM_ID, '_menu_item_url', $correctUrl);
    // Reapunta el ítem a la taxonomía real en vez de dejarlo como enlace
    // "custom" suelto, para que si el slug de la categoría cambia algún
    // día, este enlace se actualice solo (igual que su gemelo, ID 2098).
    update_post_meta(RB_BROKEN_MENU_ITEM_ID, '_menu_item_type', 'taxonomy');
    update_post_meta(RB_BROKEN_MENU_ITEM_ID, '_menu_item_object', 'product_cat');
    update_post_meta(RB_BROKEN_MENU_ITEM_ID, '_menu_item_object_id', RB_CASCOS_CATEGORY_ID);
    wp_update_post([
        'ID' => RB_BROKEN_MENU_ITEM_ID,
        'post_title' => $item->post_title ?: 'Cascos',
    ]);
}

echo "\nListo.\n";
