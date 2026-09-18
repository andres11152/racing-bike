<?php
/**
 * Asignación y estandarización completa de marcas en WooCommerce (pa_marca y product_brand).
 */
require_once __DIR__ . '/wp-load.php';

$brandExpected = [
    // Orbea (7 productos)
    741 => 'Orbea',
    676 => 'Orbea',
    677 => 'Orbea',
    1128 => 'Orbea',
    882 => 'Orbea',
    861 => 'Orbea',
    852 => 'Orbea',

    // Trek (7 productos)
    1736 => 'Trek',
    1713 => 'Trek',
    1692 => 'Trek',
    1133 => 'Trek',
    973 => 'Trek',
    954 => 'Trek',
    911 => 'Trek',

    // GW (12 productos)
    2056 => 'GW',
    1645 => 'GW',
    841 => 'GW',
    832 => 'GW',
    834 => 'GW',
    835 => 'GW',
    518 => 'GW',
    515 => 'GW',
    509 => 'GW',
    512 => 'GW',
    506 => 'GW',
    359 => 'GW',

    // Poseidon (1 producto)
    1117 => 'Poseidon',

    // Shimano (12 productos)
    2008 => 'Shimano',
    1993 => 'Shimano',
    1977 => 'Shimano',
    1950 => 'Shimano',
    1934 => 'Shimano',
    1923 => 'Shimano',
    1808 => 'Shimano',
    1799 => 'Shimano',
    1784 => 'Shimano',
    1775 => 'Shimano',
    1765 => 'Shimano',
    1760 => 'Shimano',

    // Magene (15 productos)
    1867 => 'Magene',
    1862 => 'Magene',
    1855 => 'Magene',
    1845 => 'Magene',
    1843 => 'Magene',
    1828 => 'Magene',
    1824 => 'Magene',
    1819 => 'Magene',
    818 => 'Magene',
    821 => 'Magene',
    395 => 'Magene',
    396 => 'Magene',
    397 => 'Magene',
    399 => 'Magene',
    400 => 'Magene',

    // Racing Bike (4 productos)
    40 => 'Racing Bike',
    42 => 'Racing Bike',
    44 => 'Racing Bike',
    46 => 'Racing Bike',
];

echo "=== ASIGNACIÓN DE MARCAS WOOCOMMERCE A PRODUCCIÓN ===" . PHP_EOL . PHP_EOL;

// 1. Asegurar existencia de términos en ambas taxonomías
$distinctBrands = array_unique(array_values($brandExpected));
$brandTermIds = []; // ['Orbea' => ['pa_marca' => id, 'product_brand' => id]]

foreach ($distinctBrands as $brandName) {
    $slug = sanitize_title($brandName);

    // En pa_marca
    $paTerm = get_term_by('slug', $slug, 'pa_marca');
    if (!$paTerm) {
        $paTerm = get_term_by('name', $brandName, 'pa_marca');
    }
    if (!$paTerm) {
        $ins = wp_insert_term($brandName, 'pa_marca', ['slug' => $slug]);
        if (!is_wp_error($ins)) {
            $paTerm = get_term($ins['term_id'], 'pa_marca');
            echo "[+] Creado término pa_marca: {$brandName} (ID {$ins['term_id']})\n";
        }
    }

    // En product_brand
    $pbTerm = get_term_by('slug', $slug, 'product_brand');
    if (!$pbTerm) {
        $pbTerm = get_term_by('name', $brandName, 'product_brand');
    }
    if (!$pbTerm) {
        $ins = wp_insert_term($brandName, 'product_brand', ['slug' => $slug]);
        if (!is_wp_error($ins)) {
            $pbTerm = get_term($ins['term_id'], 'product_brand');
            echo "[+] Creado término product_brand: {$brandName} (ID {$ins['term_id']})\n";
        }
    }

    $brandTermIds[$brandName] = [
        'pa_marca' => $paTerm ? $paTerm->term_id : 0,
        'product_brand' => $pbTerm ? $pbTerm->term_id : 0,
    ];
}

$marcaTaxId = function_exists('wc_attribute_taxonomy_id_by_name') ? wc_attribute_taxonomy_id_by_name('marca') : 5;

// 2. Asignar a cada producto
$updated = 0;
$errors = 0;

foreach ($brandExpected as $id => $expected) {
    $p = wc_get_product($id);
    if (!$p) {
        echo "[-] ID {$id} no encontrado.\n";
        $errors++;
        continue;
    }

    $paId = $brandTermIds[$expected]['pa_marca'] ?? 0;
    $pbId = $brandTermIds[$expected]['product_brand'] ?? 0;

    if (!$paId || !$pbId) {
        echo "[!] Error obteniendo IDs para {$expected}\n";
        $errors++;
        continue;
    }

    // Asignar en pa_marca (reemplazando cualquier marca errónea anterior)
    wp_set_object_terms($id, [$paId], 'pa_marca', false);

    // Asignar en product_brand (reemplazando cualquier marca errónea anterior)
    wp_set_object_terms($id, [$pbId], 'product_brand', false);

    // Registrar en los atributos oficiales del producto WooCommerce
    $attributes = $p->get_attributes();
    $attrObj = new \WC_Product_Attribute();
    $attrObj->set_id($marcaTaxId);
    $attrObj->set_name('pa_marca');
    $attrObj->set_options([$paId]);
    $attrObj->set_position(0);
    $attrObj->set_visible(true);
    $attrObj->set_variation(false);

    $attributes['pa_marca'] = $attrObj;
    $p->set_attributes($attributes);
    $p->save();

    clean_post_cache($id);
    $updated++;
    echo "[✓] ID {$id}: Marca asignada -> {$expected} ('{$p->get_name()}')\n";
}

echo PHP_EOL;
echo "=== RESUMEN ===" . PHP_EOL;
echo "Productos actualizados con éxito: {$updated}\n";
echo "Errores: {$errors}\n";
echo "Total productos: " . count($brandExpected) . PHP_EOL;
