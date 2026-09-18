<?php
/**
 * Dump detallado de todos los productos de producción.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/wp-load.php';

$products = wc_get_products([
    'limit' => -1,
    'status' => ['publish', 'draft'],
]);

$data = [];
foreach ($products as $p) {
    if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) {
        continue;
    }
    $id = $p->get_id();
    $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
    $post = get_post($id);

    $attributes = [];
    foreach ($p->get_attributes() as $tax => $attr) {
        if (is_a($attr, 'WC_Product_Attribute')) {
            $attributes[$tax] = $attr->get_options();
        }
    }

    $data[] = [
        'id' => $id,
        'status' => $p->get_status(),
        'title' => $p->get_name(),
        'slug' => $p->get_slug(),
        'categories' => $cats,
        'sku' => $p->get_sku(),
        'attributes' => $attributes,
        'excerpt' => wp_strip_all_tags(substr($post->post_excerpt, 0, 150)),
        'content_snippet' => wp_strip_all_tags(substr($post->post_content, 0, 200)),
    ];
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
