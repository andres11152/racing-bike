<?php
/**
 * Diagnóstico de taxonomías y atributos de marca en WooCommerce.
 */
require_once __DIR__ . '/wp-load.php';

echo "=== TAXONOMÍAS DE PRODUCTO ===\n";
$taxonomies = get_object_taxonomies('product', 'objects');
foreach ($taxonomies as $slug => $tax) {
    echo "Tax: {$slug} ({$tax->label})\n";
    if (str_contains($slug, 'marca') || str_contains($slug, 'brand') || str_contains($slug, 'pa_')) {
        $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
        if (!is_wp_error($terms)) {
            $tNames = array_map(fn($t) => "{$t->name} (count: {$t->count})", $terms);
            echo "   -> Términos: " . implode(', ', $tNames) . "\n";
        }
    }
}

echo "\n=== PRODUCT ATTRIBUTES REGISTRADOS (wc_get_attribute_taxonomies) ===\n";
if (function_exists('wc_get_attribute_taxonomies')) {
    $attrTaxes = wc_get_attribute_taxonomies();
    foreach ($attrTaxes as $at) {
        echo "Attr: id={$at->attribute_id}, name={$at->attribute_name}, label={$at->attribute_label}, type={$at->attribute_type}\n";
    }
}

echo "\n=== ESTADO DE MARCAS EN PRODUCTOS ===\n";
$products = wc_get_products(['limit' => -1, 'status' => ['publish', 'draft']]);
$withBrand = 0;
$withoutBrand = 0;

foreach ($products as $p) {
    if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) continue;
    $id = $p->get_id();
    $name = $p->get_name();

    // Revisar todas las posibles formas de marca
    $brandTaxs = ['pa_marca', 'pa_brand', 'product_brand', 'pwb-brand', 'brand', 'marca'];
    $foundBrands = [];
    foreach ($brandTaxs as $bt) {
        if (taxonomy_exists($bt)) {
            $terms = wp_get_post_terms($id, $bt, ['fields' => 'names']);
            if (!empty($terms)) {
                $foundBrands[$bt] = implode(', ', $terms);
            }
        }
    }

    // Revisar si está como atributo de producto
    $attrs = $p->get_attributes();
    foreach ($attrs as $k => $attr) {
        if (str_contains($k, 'marca') || str_contains($k, 'brand')) {
            if (is_a($attr, 'WC_Product_Attribute')) {
                $foundBrands['attr_' . $k] = implode(', ', (array) $attr->get_options());
            } else {
                $foundBrands['attr_' . $k] = (string) $attr;
            }
        }
    }

    if (!empty($foundBrands)) {
        $withBrand++;
        echo "[CON MARCA] ID {$id}: '{$name}' -> " . json_encode($foundBrands, JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $withoutBrand++;
        echo "[SIN MARCA] ID {$id}: '{$name}'\n";
    }
}

echo "\nTotal con marca: {$withBrand}\n";
echo "Total sin marca: {$withoutBrand}\n";
