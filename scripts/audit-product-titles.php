<?php
/**
 * Auditoría de títulos de productos en producción.
 * Solo lectura.
 */
require_once __DIR__ . '/wp-load.php';

$products = wc_get_products([
    'limit' => -1,
    'status' => ['publish', 'draft'],
]);

echo "TOTAL PRODUCTOS: " . count($products) . PHP_EOL . PHP_EOL;

$list = [];
foreach ($products as $p) {
    if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) {
        continue;
    }
    $id = $p->get_id();
    $status = $p->get_status();
    $title = $p->get_name();
    $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
    $marca = $p->get_attribute('pa_marca') ?: $p->get_attribute('marca') ?: $p->get_attribute('pa_brand') ?: '';
    
    // Obtener taxonomías o atributos para ver el modelo
    $modelo = $p->get_attribute('pa_modelo') ?: $p->get_attribute('modelo') ?: '';

    $list[] = [
        'id' => $id,
        'status' => $status,
        'title' => $title,
        'cats' => implode(', ', $cats),
        'marca' => $marca,
        'modelo' => $modelo,
    ];
}

foreach ($list as $item) {
    echo "ID: {$item['id']} [{$item['status']}]\n";
    echo "  Actual: {$item['title']}\n";
    echo "  Categorías: {$item['cats']}\n";
    echo "  Marca: " . ($item['marca'] ?: '(sin marca)') . "\n";
    if ($item['modelo']) echo "  Modelo: {$item['modelo']}\n";
    echo "\n";
}
