<?php
/**
 * Auditoría de SKUs actuales en producción (padres y variaciones).
 * Solo lectura.
 */
require_once __DIR__ . '/wp-load.php';

$products = wc_get_products([
    'limit' => -1,
    'status' => ['publish', 'draft'],
]);

$totalProducts = 0;
$totalVariations = 0;
$withSkuParent = 0;
$withSkuVar = 0;

$report = [];

foreach ($products as $p) {
    if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) {
        continue;
    }
    $totalProducts++;
    $id = $p->get_id();
    $sku = $p->get_sku();
    if (!empty($sku)) $withSkuParent++;

    $isVariable = $p->is_type('variable');
    $vars = [];

    if ($isVariable) {
        $variation_ids = $p->get_children();
        foreach ($variation_ids as $var_id) {
            $totalVariations++;
            $var = wc_get_product($var_id);
            if (!$var) continue;
            $vsku = $var->get_sku();
            if (!empty($vsku)) $withSkuVar++;
            $vars[] = [
                'id' => $var_id,
                'sku' => $vsku,
                'attributes' => $var->get_attributes(),
            ];
        }
    }

    $report[] = [
        'id' => $id,
        'title' => $p->get_name(),
        'type' => $p->get_type(),
        'sku' => $sku,
        'variations' => $vars,
    ];
}

echo "TOTAL PRODUCTOS ANALIZADOS: {$totalProducts}\n";
echo "  - Con SKU: {$withSkuParent}\n";
echo "  - Sin SKU: " . ($totalProducts - $withSkuParent) . "\n\n";

echo "TOTAL VARIACIONES ANALIZADAS: {$totalVariations}\n";
echo "  - Con SKU: {$withSkuVar}\n";
echo "  - Sin SKU: " . ($totalVariations - $withSkuVar) . "\n\n";

echo "=== MUESTRA DE SKUS EXISTENTES ===\n";
foreach ($report as $r) {
    if (!empty($r['sku']) || !empty($r['variations'])) {
        echo "ID {$r['id']} ({$r['type']}): '{$r['title']}' | SKU: " . ($r['sku'] ?: '[VACÍO]') . "\n";
        foreach ($r['variations'] as $v) {
            $attrStr = [];
            foreach ($v['attributes'] as $k => $val) {
                $attrStr[] = "{$k}: {$val}";
            }
            echo "   -> Var {$v['id']} | SKU: " . ($v['sku'] ?: '[VACÍO]') . " | " . implode(', ', $attrStr) . "\n";
        }
    }
}
