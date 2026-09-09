<?php
/**
 * Plugin Name: Racing Bike Bike Builder
 * Description: Módulo interactivo de personalización de bicicletas en tiempo real integrado con WooCommerce.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Obtener o crear el producto base de Bicicleta Personalizada
 */
function rb_bb_get_custom_bike_product_id() {
    $slug = 'bicicleta-personalizada';
    $post = get_page_by_path($slug, OBJECT, 'product');
    
    if ($post) {
        return $post->ID;
    }

    if (class_exists('WC_Product_Simple')) {
        $product = new WC_Product_Simple();
        $product->set_name('Bicicleta Personalizada (Configurada)');
        $product->set_slug($slug);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_price(1500000); // Precio base de la bicicleta $1.500.000 COP
        $product->set_regular_price(1500000);
        $product->set_manage_stock(false);
        $product_id = $product->save();

        return $product_id;
    }

    return 0;
}

/**
 * Crear automáticamente la página de Bike Builder si no existe
 */
add_action('init', function() {
    $slug = 'armar-bicicleta';
    $page = get_page_by_path($slug);
    if (!$page) {
        $page_id = wp_insert_post([
            'post_title'   => 'Arma tu Bicicleta',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $slug,
        ]);
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'template-bike-builder.blade.php');
        }
    }
});

/**
 * Guardar datos de configuración personalizados al añadir al carrito
 */
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id, $variation_id) {
    $is_bike_builder_request = isset($_POST['is_bike_builder']) && $_POST['is_bike_builder'] === '1';
    $is_custom_bike_product = $product_id === rb_bb_get_custom_bike_product_id();

    // Solo adjuntar la configuración de la bicicleta cuando el producto que
    // realmente se está agregando es el producto base de "Bicicleta
    // Personalizada". Sin esta verificación, un flag `is_bike_builder=1`
    // residual en el POST podía "contaminar" cualquier otro producto simple
    // con metadatos de Marco/Grupo/Ruedas/Talla que no le pertenecen.
    if ($is_bike_builder_request && $is_custom_bike_product) {
        $cart_item_data['bike_builder'] = [
            'marco' => isset($_POST['bb_marco']) ? sanitize_text_field($_POST['bb_marco']) : '',
            'grupo' => isset($_POST['bb_grupo']) ? sanitize_text_field($_POST['bb_grupo']) : '',
            'ruedas' => isset($_POST['bb_ruedas']) ? sanitize_text_field($_POST['bb_ruedas']) : '',
            'contacto' => isset($_POST['bb_contacto']) ? sanitize_text_field($_POST['bb_contacto']) : '',
            'talla' => isset($_POST['bb_talla']) ? sanitize_text_field($_POST['bb_talla']) : '',
            'price_extra' => isset($_POST['bb_price_extra']) ? floatval($_POST['bb_price_extra']) : 0,
        ];
        $cart_item_data['unique_key'] = md5(microtime() . rand());
    }
    return $cart_item_data;
}, 10, 3);

/**
 * Ajustar el precio del producto en el carrito sumando componentes adicionales
 */
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['bike_builder'])) {
            $base_price = floatval($cart_item['data']->get_price());
            $extra = floatval($cart_item['bike_builder']['price_extra']);
            $cart_item['data']->set_price($base_price + $extra);
        }
    }
}, 10, 1);

/**
 * Mostrar los componentes elegidos en el carrito y checkout
 */
add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    if (isset($cart_item['bike_builder'])) {
        $item_data[] = ['name' => __('Marco', 'sage'), 'value' => $cart_item['bike_builder']['marco']];
        $item_data[] = ['name' => __('Grupo', 'sage'), 'value' => $cart_item['bike_builder']['grupo']];
        $item_data[] = ['name' => __('Ruedas', 'sage'), 'value' => $cart_item['bike_builder']['ruedas']];
        $item_data[] = ['name' => __('Contacto', 'sage'), 'value' => $cart_item['bike_builder']['contacto']];
        $item_data[] = ['name' => __('Talla de Marco', 'sage'), 'value' => $cart_item['bike_builder']['talla']];
    }
    return $item_data;
}, 10, 2);

/**
 * Guardar los metadatos personalizados en el pedido de WooCommerce
 */
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    if (isset($values['bike_builder'])) {
        $item->add_meta_data(__('Marco de Bicicleta', 'sage'), $values['bike_builder']['marco']);
        $item->add_meta_data(__('Grupo de Cambios', 'sage'), $values['bike_builder']['grupo']);
        $item->add_meta_data(__('Ruedas de Bicicleta', 'sage'), $values['bike_builder']['ruedas']);
        $item->add_meta_data(__('Componente de Contacto', 'sage'), $values['bike_builder']['contacto']);
        $item->add_meta_data(__('Talla de Bicicleta', 'sage'), $values['bike_builder']['talla']);
    }
}, 10, 4);

/**
 * Endpoint de AJAX para añadir la configuración al carrito
 */
add_action('wp_ajax_rb_add_bike_to_cart', 'rb_bb_ajax_add_bike_to_cart');
add_action('wp_ajax_nopriv_rb_add_bike_to_cart', 'rb_bb_ajax_add_bike_to_cart');

function rb_bb_ajax_add_bike_to_cart() {
    // Este endpoint tiene un único propósito: agregar la bicicleta
    // personalizada configurada. Se ignora cualquier `product_id` enviado
    // por el cliente para evitar que la configuración (marco, talla, etc.)
    // termine adjunta a un producto distinto.
    $product_id = rb_bb_get_custom_bike_product_id();

    if (!$product_id) {
        wp_send_json_error(['message' => 'No se pudo crear o encontrar el producto base de la bicicleta.']);
    }

    $cart_item_data = [
        'bike_builder' => [
            'marco' => sanitize_text_field($_POST['bb_marco']),
            'grupo' => sanitize_text_field($_POST['bb_grupo']),
            'ruedas' => sanitize_text_field($_POST['bb_ruedas']),
            'contacto' => sanitize_text_field($_POST['bb_contacto']),
            'talla' => sanitize_text_field($_POST['bb_talla']),
            'price_extra' => floatval($_POST['bb_price_extra']),
        ],
        'unique_key' => md5(microtime() . rand())
    ];

    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1);
    
    if ($passed_validation && WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data)) {
        wp_send_json_success([
            'message' => '¡Bicicleta personalizada añadida al carrito!',
            'cart_url' => wc_get_cart_url()
        ]);
    } else {
        wp_send_json_error(['message' => 'No fue posible agregar la bicicleta configurada al carrito de compras.']);
    }
}
