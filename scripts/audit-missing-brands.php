<?php
/**
 * Análisis exhaustivo de marcas faltantes o incorrectas en todos los productos.
 */
require_once __DIR__ . '/wp-load.php';

$brandExpected = [
    // Orbea (11 productos)
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

    // Magene (14 productos)
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

echo "=== AUDITORÍA DE ESTADO DE MARCAS EN 58 PRODUCTOS ===\n\n";

$missingPaMarca = [];
$missingProductBrand = [];
$mismatchedBrand = [];
$okCount = 0;

foreach ($brandExpected as $id => $expected) {
    $p = wc_get_product($id);
    if (!$p) {
        echo "[-] ID {$id} no encontrado en WooCommerce.\n";
        continue;
    }

    $paTerms = wp_get_post_terms($id, 'pa_marca', ['fields' => 'names']);
    $pbTerms = wp_get_post_terms($id, 'product_brand', ['fields' => 'names']);

    $currentPa = !empty($paTerms) ? $paTerms[0] : null;
    $currentPb = !empty($pbTerms) ? $pbTerms[0] : null;

    $needsFix = false;
    $issues = [];

    if (!$currentPa) {
        $needsFix = true;
        $missingPaMarca[] = $id;
        $issues[] = "Falta pa_marca";
    } elseif (strcasecmp($currentPa, $expected) !== 0) {
        $needsFix = true;
        $mismatchedBrand[] = $id;
        $issues[] = "pa_marca incorrecta (tiene '{$currentPa}', debe ser '{$expected}')";
    }

    if (!$currentPb) {
        $needsFix = true;
        $missingProductBrand[] = $id;
        $issues[] = "Falta product_brand";
    } elseif (strcasecmp($currentPb, $expected) !== 0) {
        $needsFix = true;
        $mismatchedBrand[] = $id;
        $issues[] = "product_brand incorrecta (tiene '{$currentPb}', debe ser '{$expected}')";
    }

    // Verificar si está registrado en los atributos del producto (_product_attributes)
    $attrs = $p->get_attributes();
    if (!isset($attrs['pa_marca'])) {
        $issues[] = "pa_marca no registrada en atributos del producto";
        $needsFix = true;
    }

    if ($needsFix) {
        echo "[!] ID {$id}: '{$p->get_name()}'\n";
        echo "    Marca esperada: {$expected}\n";
        echo "    Problemas: " . implode(' | ', $issues) . "\n\n";
    } else {
        $okCount++;
    }
}

echo "=== RESUMEN ===\n";
echo "Productos 100% correctos: {$okCount}\n";
echo "Productos que necesitan asignación o corrección de marca: " . (count($brandExpected) - $okCount) . "\n";
echo "Total productos: " . count($brandExpected) . "\n";
