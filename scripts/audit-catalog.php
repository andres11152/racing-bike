<?php
/**
 * AUDITORÍA DE CATÁLOGO — SOLO LECTURA.
 * No escribe absolutamente nada en la base de datos. No acepta "apply".
 * Vuelca un JSON con el estado de cada producto: atributos, marcas,
 * imágenes, precios, stock, SEO, variaciones y taxonomías de filtro.
 */

if (! defined('ABSPATH')) { exit; }

$out = [];

/* ---------- 1. Taxonomías de atributo globales ---------- */
$attr_taxes = wc_get_attribute_taxonomies();
$tax_report = [];
foreach ($attr_taxes as $t) {
    $tax = wc_attribute_taxonomy_name($t->attribute_name);
    $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
    $terms_out = [];
    if (! is_wp_error($terms)) {
        foreach ($terms as $term) {
            $terms_out[] = [
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => (int) $term->count,
            ];
        }
    }
    $tax_report[$tax] = [
        'label' => $t->attribute_label,
        'type' => $t->attribute_type,
        'orderby' => $t->attribute_orderby,
        'public' => (int) $t->attribute_public,
        'term_count' => count($terms_out),
        'terms' => $terms_out,
    ];
}
$out['attribute_taxonomies'] = $tax_report;

/* ---------- 2. Categorías de producto ---------- */
$cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
$cat_out = [];
if (! is_wp_error($cats)) {
    foreach ($cats as $c) {
        $cat_out[] = [
            'name' => $c->name, 'slug' => $c->slug, 'count' => (int) $c->count,
            'parent' => $c->parent, 'desc_len' => strlen($c->description),
            'thumb' => (bool) get_term_meta($c->term_id, 'thumbnail_id', true),
        ];
    }
}
$out['product_cats'] = $cat_out;

/* ---------- 3. Productos ---------- */
$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields' => 'ids',
]);

$products = [];
foreach ($ids as $pid) {
    $p = wc_get_product($pid);
    if (! $p) { continue; }

    $attrs = [];
    foreach ($p->get_attributes() as $key => $a) {
        $attrs[$key] = [
            'name' => $a->get_name(),
            'taxonomy' => $a->is_taxonomy(),
            'visible' => $a->get_visible(),
            'variation' => $a->get_variation(),
            'position' => $a->get_position(),
            'options' => $a->is_taxonomy()
                ? wp_list_pluck(wc_get_product_terms($pid, $key, ['fields' => 'all']), 'name')
                : $a->get_options(),
        ];
    }

    $variations = [];
    if ($p->is_type('variable')) {
        foreach ($p->get_children() as $vid) {
            $v = wc_get_product($vid);
            if (! $v) { continue; }
            $variations[] = [
                'id' => $vid,
                'status' => get_post_status($vid),
                'sku' => $v->get_sku(),
                'price' => $v->get_price(),
                'regular' => $v->get_regular_price(),
                'sale' => $v->get_sale_price(),
                'stock_status' => $v->get_stock_status(),
                'stock_qty' => $v->get_stock_quantity(),
                'manage_stock' => $v->get_manage_stock(),
                'image' => (bool) $v->get_image_id(),
                'attrs' => $v->get_variation_attributes(),
                'weight' => $v->get_weight(),
                'purchasable' => $v->is_purchasable(),
            ];
        }
    }

    $products[] = [
        'id' => $pid,
        'name' => $p->get_name(),
        'slug' => $p->get_slug(),
        'status' => $p->get_status(),
        'type' => $p->get_type(),
        'sku' => $p->get_sku(),
        'catalog_visibility' => $p->get_catalog_visibility(),
        'featured' => $p->get_featured(),
        'price' => $p->get_price(),
        'regular_price' => $p->get_regular_price(),
        'sale_price' => $p->get_sale_price(),
        'on_sale' => $p->is_on_sale(),
        'purchasable' => $p->is_purchasable(),
        'in_stock' => $p->is_in_stock(),
        'stock_status' => $p->get_stock_status(),
        'stock_qty' => $p->get_stock_quantity(),
        'manage_stock' => $p->get_manage_stock(),
        'backorders' => $p->get_backorders(),
        'desc_len' => strlen(wp_strip_all_tags($p->get_description())),
        'short_desc_len' => strlen(wp_strip_all_tags($p->get_short_description())),
        'image_id' => $p->get_image_id(),
        'gallery_count' => count($p->get_gallery_image_ids()),
        'cats' => wp_list_pluck(get_the_terms($pid, 'product_cat') ?: [], 'slug'),
        'tags' => wp_list_pluck(get_the_terms($pid, 'product_tag') ?: [], 'slug'),
        'attributes' => $attrs,
        'variation_count' => count($variations),
        'variations' => $variations,
        'weight' => $p->get_weight(),
        'dims' => [$p->get_length(), $p->get_width(), $p->get_height()],
        'shipping_class' => $p->get_shipping_class(),
        'reviews_allowed' => $p->get_reviews_allowed(),
        'rating_count' => $p->get_rating_count(),
        'avg_rating' => $p->get_average_rating(),
        'total_sales' => (int) get_post_meta($pid, 'total_sales', true),
        'rankmath_desc' => (bool) get_post_meta($pid, 'rank_math_description', true),
        'rankmath_title' => (bool) get_post_meta($pid, 'rank_math_title', true),
        'rankmath_focus' => get_post_meta($pid, 'rank_math_focus_keyword', true),
        'brand_terms' => wp_list_pluck(get_the_terms($pid, 'pa_marca') ?: [], 'slug'),
        'alt_missing' => $p->get_image_id()
            ? (trim((string) get_post_meta($p->get_image_id(), '_wp_attachment_image_alt', true)) === '')
            : null,
    ];
}
$out['products'] = $products;

/* ---------- 4. Ajustes de tienda relevantes a filtros ---------- */
$out['settings'] = [
    'shop_page_id' => wc_get_page_id('shop'),
    'per_page' => (int) apply_filters('loop_shop_per_page', get_option('posts_per_page')),
    'default_catalog_orderby' => get_option('woocommerce_default_catalog_orderby'),
    'hide_out_of_stock' => get_option('woocommerce_hide_out_of_stock_items'),
    'catalog_columns' => get_option('woocommerce_catalog_columns'),
    'catalog_rows' => get_option('woocommerce_catalog_rows'),
    'permalink_product_base' => get_option('woocommerce_permalinks'),
    'currency' => get_option('woocommerce_currency'),
    'price_decimals' => get_option('woocommerce_price_num_decimals'),
    'thousand_sep' => get_option('woocommerce_price_thousand_sep'),
    'active_plugins' => get_option('active_plugins'),
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
