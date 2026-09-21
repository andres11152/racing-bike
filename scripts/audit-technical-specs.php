<?php
/**
 * Auditoría de ficha técnica y especificaciones de todos los productos.
 */
if (! defined('ABSPATH')) { exit; }

$products = wc_get_products([
    'limit' => -1,
    'status' => 'publish',
]);

echo "Total productos publicados: " . count($products) . "\n\n";

$hasSpecs = [];
$missingSpecs = [];

foreach ($products as $p) {
    $id = $p->get_id();
    $name = $p->get_name();
    $sku = $p->get_sku();
    $cats = wc_get_product_category_list($id);
    $desc = $p->get_description();
    
    // Atributos no usados para variación
    $nonVarAttrs = [];
    foreach ($p->get_attributes() as $attrKey => $attr) {
        if (!$attr->get_variation() && $attr->get_visible()) {
            if ($attr->is_taxonomy()) {
                $terms = wc_get_product_terms($id, $attr->get_name(), ['fields' => 'names']);
                $nonVarAttrs[$attr->get_name()] = implode(', ', $terms);
            } else {
                $nonVarAttrs[$attr->get_name()] = implode(', ', $attr->get_options());
            }
        }
    }

    // Detectar si la descripción contiene tabla o lista de especificaciones
    $hasSpecsInDesc = (
        stripos($desc, 'especificaciones') !== false ||
        stripos($desc, 'ficha técnica') !== false ||
        stripos($desc, 'características técnicas') !== false ||
        stripos($desc, '<table') !== false ||
        (substr_count($desc, '<li>') >= 4 && (stripos($desc, 'marco') !== false || stripos($desc, 'cuadro') !== false || stripos($desc, 'peso') !== false || stripos($desc, 'material') !== false || stripos($desc, 'frenos') !== false || stripos($desc, 'velocidades') !== false))
    );

    $item = [
        'id' => $id,
        'name' => $name,
        'sku' => $sku,
        'category' => strip_tags($cats),
        'desc_words' => str_word_count(strip_tags($desc)),
        'non_var_attrs' => $nonVarAttrs,
        'has_specs_in_desc' => $hasSpecsInDesc,
        'desc_snippet' => substr(strip_tags($desc), 0, 150),
    ];

    if ($hasSpecsInDesc && count($nonVarAttrs) >= 1) {
        $hasSpecs[] = $item;
    } else {
        $missingSpecs[] = $item;
    }
}

echo "========================================\n";
echo "RESUMEN:\n";
echo "Con especificaciones / atributos: " . count($hasSpecs) . "\n";
echo "Sin ficha técnica o incompletos: " . count($missingSpecs) . "\n";
echo "========================================\n\n";

echo "LISTA DETALLADA DE PRODUCTOS SIN FICHA O INCOMPLETOS (" . count($missingSpecs) . "):\n";
foreach ($missingSpecs as $item) {
    echo "ID: {$item['id']} | {$item['name']} | Cat: {$item['category']}\n";
    echo "  - Palabras desc: {$item['desc_words']} | Has specs en desc: " . ($item['has_specs_in_desc'] ? 'SI' : 'NO') . "\n";
    echo "  - Atributos visibles: " . json_encode($item['non_var_attrs']) . "\n";
    echo "  - Snippet: {$item['desc_snippet']}...\n\n";
}

echo "MUESTRA DE PRODUCTOS CON FICHA (" . count($hasSpecs) . "):\n";
foreach (array_slice($hasSpecs, 0, 5) as $item) {
    echo "ID: {$item['id']} | {$item['name']} | Atributos: " . json_encode($item['non_var_attrs']) . "\n";
    echo "  - Snippet: {$item['desc_snippet']}...\n\n";
}
