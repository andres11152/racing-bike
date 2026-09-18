<?php
/**
 * Extraer todos los valores de pa_color de las variaciones.
 */
require_once __DIR__ . '/wp-load.php';

$products = wc_get_products([
    'limit' => -1,
    'status' => ['publish', 'draft'],
]);

$allColors = [];
foreach ($products as $p) {
    if (!$p->is_type('variable')) continue;
    foreach ($p->get_children() as $varId) {
        $var = wc_get_product($varId);
        if (!$var) continue;
        $attrs = $var->get_attributes();
        $col = $attrs['pa_color'] ?? $attrs['color'] ?? null;
        if ($col) {
            $allColors[$col] = ($allColors[$col] ?? 0) + 1;
        }
    }
}

ksort($allColors);
echo "TOTAL COLORES ÚNICOS: " . count($allColors) . PHP_EOL;
foreach ($allColors as $c => $count) {
    echo " - '{$c}' => {$count} variaciones\n";
}
