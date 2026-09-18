<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Permitir subida de archivos SVG en la biblioteca de medios de WordPress.
 *
 * Se necesita para los logos de marca (ver app/product-brands.php). El
 * archivo se sanea de verdad en wp_handle_upload más abajo — sin eso, un
 * SVG con <script> se serviría intacto desde el propio dominio, en la
 * sesión de quien lo abra.
 */
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
});

/**
 * Saneado real de SVG tras la subida (XSS almacenado).
 *
 * El filtro que existía antes aquí forzaba el tipo a image/svg+xml para
 * cualquier archivo cuyo *nombre* terminara en .svg, anulando la
 * verificación de contenido de WordPress — un SVG con <script> se subía y
 * se servía tal cual. Este filtro corre después, ya con el archivo en
 * disco: parsea el XML con DOMDocument (sin resolver entidades externas,
 * para evitar XXE) y elimina <script>, cualquier atributo on* (onload,
 * onclick...), <foreignObject> y hrefs con esquema javascript:. Si el
 * archivo no es XML válido, se rechaza en vez de subirlo tal cual.
 */
add_filter('wp_handle_upload', function ($upload) {
    if (($upload['type'] ?? '') !== 'image/svg+xml' || empty($upload['file'])) {
        return $upload;
    }

    $content = file_get_contents($upload['file']);

    if ($content === false) {
        return ['error' => __('No se pudo leer el archivo SVG subido.', 'sage')];
    }

    $previous = libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    // LIBXML_NONET: nunca resolver referencias externas por red.
    // Sin cargar un DTD externo (no se pasa LIBXML_DTDLOAD/LIBXML_NOENT):
    // php-src trata las entidades externas como no resueltas por defecto
    // desde PHP 8, así que esto ya viene protegido contra XXE.
    $loaded = $dom->loadXML($content, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        @unlink($upload['file']);

        return ['error' => __('El archivo SVG no es válido y no se pudo subir.', 'sage')];
    }

    $xpath = new \DOMXPath($dom);

    foreach (iterator_to_array($xpath->query('//*[local-name()="script"] | //*[local-name()="foreignObject"]')) as $node) {
        $node->parentNode->removeChild($node);
    }

    foreach (iterator_to_array($xpath->query('//@*')) as $attr) {
        $name = strtolower($attr->nodeName);
        $isEventHandler = str_starts_with($name, 'on');
        $isDangerousHref = in_array($name, ['href', 'xlink:href'], true)
            && preg_match('/^\s*javascript:/i', $attr->nodeValue);

        if ($isEventHandler || $isDangerousHref) {
            $attr->ownerElement->removeAttributeNode($attr);
        }
    }

    file_put_contents($upload['file'], $dom->saveXML());

    return $upload;
});

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Remplazar la terminología "cuadro" por "marco" (Español Colombia) dinámicamente.
 */
add_filter('woocommerce_attribute_label', function ($label) {
    return str_ireplace(['Talla de cuadro', 'cuadro'], ['Talla del marco', 'marco'], $label);
});

/**
 * Forzar la traducción de la política de privacidad en el Checkout al español de Colombia.
 */
add_filter('woocommerce_get_privacy_policy_text', function ($text, $type) {
    $policyUrl = get_privacy_policy_url();

    if (! $policyUrl) {
        return $text;
    }

    return sprintf(
        'Tus datos personales se utilizarán para procesar tu pedido, respaldar tu experiencia en este sitio web y para otros fines descritos en nuestra <a href="%s" class="woocommerce-privacy-policy-link" target="_blank">política de privacidad</a>.',
        esc_url($policyUrl)
    );
}, 20, 2);

/**
 * Traducciones puntuales de WooCommerce que su .po en español no cubre bien.
 *
 * Se registra tanto en `gettext` como en `gettext_with_context` porque
 * WooCommerce traduce algunas cadenas (como "Shipment") con `_x()`, que usa
 * el hook con contexto — con solo `gettext` esas cadenas quedan en inglés.
 */
/**
 * Reemplaza "cuadro"→"marco" solo en los nodos de texto de un fragmento
 * HTML, nunca dentro de una etiqueta. `str_ireplace` sobre HTML crudo
 * reescribía también `href`, `src` y `class` de cualquier elemento cuyo
 * contenido tuviera "cuadro" — un enlace a /producto/cuadro-carbono se
 * volvía /producto/marco-carbono (404) y su imagen dejaba de cargar.
 *
 * @return string
 */
function rb_replace_cuadro_outside_tags($html)
{
    $segments = preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

    foreach ($segments as $i => $segment) {
        if ($segment !== '' && $segment[0] !== '<') {
            $segments[$i] = preg_replace_callback('/cuadros?/i', function ($match) {
                $replacement = strlen($match[0]) > 6 ? 'marcos' : 'marco'; // "cuadros" vs "cuadro"
                // Conserva mayúscula inicial si el texto original la traía.
                return ctype_upper($match[0][0]) ? ucfirst($replacement) : $replacement;
            }, $segment);
        }
    }

    return implode('', $segments);
}

function rb_translate_gettext($translation, $text, $domain)
{
    $translations = [
        'Add to cart' => 'Añadir al carrito',
        'View cart' => 'Ver carrito',
        'Checkout' => 'Finalizar compra',
        'Place order' => 'Realizar el pedido',
        'Billing details' => 'Detalles de facturación',
        'Shipping details' => 'Detalles de envío',
        'Shipping' => 'Envío',
        'Shipment' => 'Envío',
        'Your order' => 'Resumen del pedido',
        'Subtotal' => 'Subtotal',
        'Total' => 'Total',
        'Product' => 'Producto',
        'Price' => 'Precio',
        'Quantity' => 'Cantidad',
        'Description' => 'Descripción',
        'Reviews' => 'Reseñas',
        'Additional information' => 'Especificaciones técnicas',
        'Related products' => 'Productos relacionados',
        'Out of stock' => 'Agotado',
        'In stock' => 'Disponible en tienda',
        'Select options' => 'Elegir opciones',
        'Cart' => 'Carrito de compras',
        'Search' => 'Buscar',
        'My account' => 'Mi cuenta',
        'Login' => 'Ingresar',
        'Register' => 'Registrarse',
        'Lost your password?' => '¿Olvidaste tu contraseña?',
        'Remember me' => 'Recordarme',

        // Verificación de correo de "Mi cuenta" (función nueva de WooCommerce,
        // aún sin traducción oficial al español en esta versión).
        'Confirm email address' => 'Confirmar correo electrónico',
        'Confirm your email address' => 'Confirma tu correo electrónico',
        'Confirm your email address for {site_title}' => 'Confirma tu correo electrónico en {site_title}',
        'Confirm your email address to check for past orders and link them to your account.' => 'Confirma tu correo electrónico para ver tus pedidos anteriores y vincularlos a tu cuenta.',
        'Confirm your email address to check for past orders. A confirmation link was sent recently — please check your inbox.' => 'Confirma tu correo electrónico para ver tus pedidos anteriores. Te enviamos un enlace de confirmación hace poco — revisa tu bandeja de entrada.',
        'Confirm email address is a required field.' => 'Confirmar correo electrónico es un campo obligatorio.',
        'You need to be logged in to confirm your email address.' => 'Debes iniciar sesión para confirmar tu correo electrónico.',
        'A confirmation link has been sent to your email address. Please check your inbox.' => 'Te enviamos un enlace de confirmación a tu correo electrónico. Revisa tu bandeja de entrada.',
        'A confirmation link was sent recently. Please check your inbox, or wait a moment before requesting a new one.' => 'Te enviamos un enlace de confirmación hace poco. Revisa tu bandeja de entrada o espera un momento antes de pedir uno nuevo.',
        'Your email address has been confirmed.' => 'Tu correo electrónico ha sido confirmado.',
        'This confirmation link is invalid or has expired. Please request a new one.' => 'Este enlace de confirmación no es válido o ha expirado. Solicita uno nuevo.',
        'Unable to confirm this email while you are logged in to a different account. Please log out and open the link again.' => 'No se puede confirmar este correo mientras tienes la sesión iniciada en otra cuenta. Cierra sesión y abre el enlace de nuevo.',
        'Invalid request. Please try again.' => 'Solicitud no válida. Inténtalo de nuevo.',
        'Cuadro' => 'Marco',
        'Talla de cuadro' => 'Talla del marco',
        'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.' => 'Tus datos personales se utilizarán para procesar tu pedido, mejorar tu experiencia en este sitio web y para otros fines descritos en nuestra %s.',
    ];

    if (isset($translations[$text])) {
        return $translations[$text];
    }

    if (str_contains(strtolower($text), 'cuadro') || str_contains(strtolower($translation), 'cuadro')) {
        $translation = str_ireplace('Talla de cuadro', 'Talla del marco', $translation);

        return rb_replace_cuadro_outside_tags($translation);
    }

    return $translation;
}
add_filter('gettext', __NAMESPACE__ . '\rb_translate_gettext', 20, 3);
add_filter('gettext_with_context', __NAMESPACE__ . '\rb_translate_gettext', 20, 3);

add_filter('the_content', __NAMESPACE__ . '\rb_replace_cuadro_outside_tags', 20);

/**
 * Permitir que WooCommerce busque automáticamente plantillas en resources/views/woocommerce.
 */
add_filter('woocommerce_locate_template', function ($template, $template_name, $template_path) {
    $php_path = get_theme_file_path("resources/views/woocommerce/{$template_name}");

    if (file_exists($php_path)) {
        return $php_path;
    }

    return $template;
}, 100, 3);

/**
 * La página de gracias (thankyou.blade.php) ya construye su propio detalle de
 * pedido con miniaturas y su propio aviso de contra-entrega, mejor integrados
 * visualmente que los de WooCommerce por defecto. Ambos se disparan sobre el
 * mismo woocommerce_thankyou que el override preserva a propósito para no
 * romper el evento `purchase` de GA4/Meta ni la integración de Mercado Pago
 * — así que en vez de omitir el hook, se retiran sólo estos dos callbacks
 * puntuales antes de que se disparen, para no duplicar la tabla de items ni
 * el mensaje de "ten listo el pago".
 */
add_action('woocommerce_before_thankyou', function ($orderId) {
    remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);

    $order = wc_get_order($orderId);

    if ($order && function_exists('WC') && WC()->payment_gateways) {
        $gateways = WC()->payment_gateways->payment_gateways();
        $gateway = $gateways[$order->get_payment_method()] ?? null;

        if ($gateway && method_exists($gateway, 'thankyou_page')) {
            remove_action('woocommerce_thankyou_' . $gateway->id, [$gateway, 'thankyou_page']);
        }
    }
});

/**
 * Fragmentos AJAX de WooCommerce para actualizar el mini-carrito en tiempo real.
 */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['[data-cart-drawer-body]'] = \Roots\view('components.cart-drawer-content')->render();
    $fragments['[data-cart-count]'] = sprintf(
        '<span class="absolute -right-2 -top-2 flex size-4 items-center justify-center bg-action text-[10px] font-semibold text-on-action" data-cart-count>%d</span>',
        function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0
    );
    return $fragments;
});

/**
 * WooCommerce engancha su propio manejador clásico de "add to cart"
 * (WC_Form_Handler::add_to_cart_action) en wp_loaded con prioridad 20, sin
 * comprobar si es una petición AJAX. Si el formulario envía add-to-cart (lo
 * hace siempre en productos con variaciones, vía un input oculto que WC
 * renderiza) o si nuestro propio JS lo copia del botón submit, WooCommerce
 * agrega el producto UNA vez ahí y nuestro handler rb_quick_add lo agrega
 * OTRA vez — mismo click, dos unidades en el carrito.
 *
 * Se desactiva ese manejador clásico específicamente cuando la petición es
 * nuestro propio endpoint AJAX, antes de que WooCommerce lo registre.
 */
add_action('wp_loaded', function () {
    if (wp_doing_ajax() && ($_REQUEST['action'] ?? '') === 'rb_quick_add') {
        remove_action('wp_loaded', ['WC_Form_Handler', 'add_to_cart_action'], 20);
    }
}, 5);

/**
 * Handler AJAX para Quick-Add y Cross-Selling Addons en 1-Clic.
 */
$quickAddHandler = function () {
    // Asegurar que la sesión y el carrito de WooCommerce estén listos en peticiones AJAX
    if (function_exists('WC')) {
        if (null === WC()->session) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
            WC()->session = new $session_class();
            WC()->session->init();
        }
        if (null === WC()->customer) {
            WC()->customer = new \WC_Customer(get_current_user_id(), true);
        }
        if (null === WC()->cart) {
            WC()->cart = new \WC_Cart();
        }
    }

    // Validación segura de nonce que no rompa compras de páginas cacheadas por LiteSpeed
    $nonce = $_REQUEST['nonce'] ?? $_REQUEST['_ajax_nonce'] ?? '';
    $valid_nonce = $nonce && wp_verify_nonce($nonce, 'rb_cart_nonce');

    if (! $valid_nonce) {
        $referer = wp_get_raw_referer();
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $referer_host = $referer ? wp_parse_url($referer, PHP_URL_HOST) : '';

        // Bloquear únicamente peticiones externas sospechosas (CSRF real)
        if (! $referer || ($referer_host && $referer_host !== $site_host)) {
            wp_send_json_error([
                'message' => __('Sesión no válida o petición externa bloqueada. Por favor recarga la página.', 'sage'),
            ], 403);
        }
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : (isset($_POST['add-to-cart']) ? absint($_POST['add-to-cart']) : 0);
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }
    $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
    $variations = [];

    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'attribute_')) {
            $variations[sanitize_text_field($key)] = wp_unslash($value);
        }
    }

    if ($product_id > 0 && function_exists('WC') && WC()->cart) {
        $product = wc_get_product($product_id);
        if ($product) {
            // Si enviaron una variación directamente como product_id, normalizar
            if ($product->is_type('variation')) {
                $variation_id = $product->get_id();
                $product_id = $product->get_parent_id();
                $product = wc_get_product($product_id);
            }

            if ($product && $product->is_type('variable')) {
                if ($variation_id === 0 && ! empty($variations)) {
                    $data_store = \WC_Data_Store::load('product');
                    $variation_id = (int) $data_store->find_matching_product_variation($product, $variations);
                }

                if ($variation_id === 0) {
                    $children = $product->get_children();
                    $variation_id = ! empty($children) ? (int) current($children) : 0;
                }

                if ($variation_id > 0) {
                    $variation_product = wc_get_product($variation_id);
                    if ($variation_product) {
                        $expected_attrs = $variation_product->get_variation_attributes();
                        foreach ($expected_attrs as $k => $v) {
                            if (! isset($variations[$k]) || empty($variations[$k])) {
                                $variations[$k] = $v;
                            }
                        }
                    }
                }

                $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);
            } else {
                $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
            }

            if ($cart_item_key) {
                $ga4_item = null;
                if (function_exists('rb_ga4_item_from_product')) {
                    $ga4_item = rb_ga4_item_from_product($product, $quantity);
                } elseif (function_exists('\rb_ga4_item_from_product')) {
                    $ga4_item = \rb_ga4_item_from_product($product, $quantity);
                }

                wp_send_json([
                    'success' => true,
                    'fragments' => apply_filters('woocommerce_add_to_cart_fragments', []),
                    'cart_hash' => WC()->cart->get_cart_hash(),
                    'ga4_item' => $ga4_item,
                ]);
            } else {
                // Si falló por validación de stock o atributos
                $notices = wc_get_notices('error');
                $error_msg = ! empty($notices) ? wp_strip_all_tags(current($notices)['notice']) : __('Error al agregar al carrito.', 'sage');
                wc_clear_notices();
                wp_send_json_error(['message' => $error_msg]);
            }
        }
    }

    wp_send_json_error(['message' => __('Error al añadir el producto al carrito.', 'sage')]);
};

add_action('wp_ajax_rb_quick_add', $quickAddHandler);
add_action('wp_ajax_nopriv_rb_quick_add', $quickAddHandler);

/**
 * Handler AJAX para eliminar items del mini-carrito.
 */
$removeCartItemHandler = function () {
    check_ajax_referer('rb_cart_nonce', 'nonce');

    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

    if (! empty($cart_item_key) && function_exists('WC') && WC()->cart) {
        WC()->cart->remove_cart_item($cart_item_key);

        if (class_exists('WC_AJAX')) {
            \WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_success(['message' => 'Eliminado']);
        }
    }

    wp_send_json_error(['message' => 'Error al eliminar']);
};

add_action('wp_ajax_rb_remove_cart_item', $removeCartItemHandler);
add_action('wp_ajax_nopriv_rb_remove_cart_item', $removeCartItemHandler);

/**
 * Handler AJAX para modificar la cantidad (+ / -) de un ítem en el mini-carrito.
 */
$changeCartQtyHandler = function () {
    check_ajax_referer('rb_cart_nonce', 'nonce');

    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

    if (! empty($cart_item_key) && function_exists('WC') && WC()->cart) {
        if ($quantity <= 0) {
            WC()->cart->remove_cart_item($cart_item_key);
        } else {
            WC()->cart->set_quantity($cart_item_key, $quantity);
        }

        if (class_exists('WC_AJAX')) {
            \WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_success(['message' => 'Cantidad actualizada']);
        }
    }

    wp_send_json_error(['message' => 'Error al actualizar cantidad']);
};

add_action('wp_ajax_rb_change_cart_qty', $changeCartQtyHandler);
add_action('wp_ajax_nopriv_rb_change_cart_qty', $changeCartQtyHandler);

/**
 * AJAX Handler para Quick-View de producto.
 */
$quickViewHandler = function () {
    check_ajax_referer('rb_cart_nonce', 'nonce');

    $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
    if ($product_id > 0 && get_post_status($product_id) === 'publish') {
        $product = wc_get_product($product_id);
        if ($product) {
            global $product, $post;
            $post = get_post($product_id);
            setup_postdata($post);
            $product = wc_get_product($product_id);

            echo \Roots\view('components.quick-view-content', ['product' => $product])->render();
            
            wp_reset_postdata();
            wp_die();
        }
    }
    wp_die('Producto no encontrado');
};
add_action('wp_ajax_rb_quick_view', $quickViewHandler);
add_action('wp_ajax_nopriv_rb_quick_view', $quickViewHandler);

/**
 * Priorizar coincidencias en el título en búsquedas de WooCommerce (Enterprise Search Relevance).
 */
add_filter('posts_orderby', function ($orderby, \WP_Query $query) {
    if (! is_admin() && $query->is_main_query() && $query->is_search() && ($query->get('post_type') === 'product' || is_woocommerce() || (isset($_GET['post_type']) && $_GET['post_type'] === 'product'))) {
        global $wpdb;
        $search_term = $query->get('s');
        if (! empty($search_term)) {
            $escaped = esc_sql($wpdb->esc_like(trim($search_term)));
            $relevance = "CASE 
                WHEN {$wpdb->posts}.post_title LIKE '{$escaped}%' THEN 1
                WHEN {$wpdb->posts}.post_title LIKE '% {$escaped}%' THEN 2
                WHEN {$wpdb->posts}.post_title LIKE '%{$escaped}%' THEN 3
                ELSE 4
            END ASC";
            
            return empty($orderby) ? "{$relevance}, {$wpdb->posts}.post_date DESC" : "{$relevance}, {$orderby}";
        }
    }
    return $orderby;
}, 10, 2);

/**
 * AJAX Handler para búsqueda predictiva en vivo (Enterprise Live Search).
 */
$liveSearchHandler = function () {
    $term = sanitize_text_field($_GET['q'] ?? $_POST['q'] ?? '');
    $term = trim($term);

    if (mb_strlen($term) < 2) {
        wp_send_json_success([
            'products' => [],
            'categories' => [],
            'brands' => [],
            'total' => 0,
            'term' => $term,
            'all_url' => home_url('/?s=' . urlencode($term) . '&post_type=product'),
        ]);
    }

    global $wpdb;
    $escaped = esc_sql($wpdb->esc_like($term));

    // Buscar productos ordenados por relevancia de título
    $query_args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 8,
        's' => $term,
    ];

    $orderFilter = function ($orderby) use ($wpdb, $escaped) {
        return "CASE 
            WHEN {$wpdb->posts}.post_title LIKE '{$escaped}%' THEN 1
            WHEN {$wpdb->posts}.post_title LIKE '% {$escaped}%' THEN 2
            WHEN {$wpdb->posts}.post_title LIKE '%{$escaped}%' THEN 3
            ELSE 4
        END ASC, {$wpdb->posts}.post_date DESC";
    };

    add_filter('posts_orderby', $orderFilter);
    $query = new \WP_Query($query_args);
    remove_filter('posts_orderby', $orderFilter);

    $products = [];

    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if (! $product || ! $product->is_visible()) {
            continue;
        }
        if (str_contains(strtolower($product->get_name()), 'personalizada') || str_contains($product->get_slug(), 'personalizada')) {
            continue;
        }

        $id = $product->get_id();
        $thumb_id = $product->get_image_id();
        $img_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'woocommerce_thumbnail') : '';
        if (! $img_url) {
            $img_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
        }
        if (! $img_url) {
            $img_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'full') : wc_placeholder_img_src();
        }

        // Categoría principal
        $terms = get_the_terms($id, 'product_cat');
        $cat_name = '';
        if ($terms && ! is_wp_error($terms)) {
            $valid_terms = array_values(array_filter($terms, fn($t) => $t->slug !== 'sin-categorizar'));
            if (! empty($valid_terms)) {
                $cat_name = $valid_terms[0]->name;
            } elseif (! empty($terms)) {
                $cat_name = $terms[0]->name;
            }
        }

        $products[] = [
            'id' => $id,
            'title' => $product->get_name(),
            'url' => get_permalink($id),
            'image' => $img_url,
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'category' => $cat_name,
            'in_stock' => $product->is_in_stock(),
        ];
    }

    // Buscar categorías que coincidan
    $categories = [];
    $cat_terms = get_terms([
        'taxonomy' => 'product_cat',
        'name__like' => $term,
        'hide_empty' => true,
        'number' => 3,
    ]);
    if (! is_wp_error($cat_terms) && ! empty($cat_terms)) {
        foreach ($cat_terms as $ct) {
            if ($ct->slug === 'sin-categorizar') continue;
            $categories[] = [
                'name' => $ct->name,
                'url' => get_term_link($ct),
                'count' => (int) $ct->count,
            ];
        }
    }

    // Buscar marcas que coincidan
    $brands = [];
    foreach (['pa_marca', 'pa_brand'] as $brand_tax) {
        if (taxonomy_exists($brand_tax)) {
            $brand_terms = get_terms([
                'taxonomy' => $brand_tax,
                'name__like' => $term,
                'hide_empty' => true,
                'number' => 3,
            ]);
            if (! is_wp_error($brand_terms) && ! empty($brand_terms)) {
                foreach ($brand_terms as $bt) {
                    $brands[] = [
                        'name' => $bt->name,
                        'url' => home_url('/?s=' . urlencode($bt->name) . '&post_type=product'),
                    ];
                }
            }
        }
    }

    wp_send_json_success([
        'products' => $products,
        'categories' => $categories,
        'brands' => $brands,
        'total' => (int) $query->found_posts,
        'term' => $term,
        'all_url' => home_url('/?s=' . urlencode($term) . '&post_type=product'),
    ]);
};
add_action('wp_ajax_rb_live_search', $liveSearchHandler);
add_action('wp_ajax_nopriv_rb_live_search', $liveSearchHandler);

/**
 * Optimización de campos de Checkout para Colombia:
 * - Código postal no requerido.
 * - Etiquetas claras y orden ergonómico.
 * - Placeholders colombianos para evitar errores de validación.
 */
add_filter("woocommerce_checkout_fields", function ($fields) {
    // Solo Colombia: quitar localidad (address_2), mantener dirección + ciudad + depto
    unset($fields["billing"]["billing_address_2"]);
    unset($fields["shipping"]["shipping_address_2"]);

    // País fijo a Colombia
    if (isset($fields["billing"]["billing_country"])) {
        $fields["billing"]["billing_country"]["default"] = "CO";
    }
    if (isset($fields["shipping"]["shipping_country"])) {
        $fields["shipping"]["shipping_country"]["default"] = "CO";
    }
    if (isset($fields['billing']['billing_postcode'])) {
        $fields['billing']['billing_postcode']['required'] = false;
        $fields['billing']['billing_postcode']['label'] = __('Código postal (opcional)', 'sage');
    }
    if (isset($fields['shipping']['shipping_postcode'])) {
        $fields['shipping']['shipping_postcode']['required'] = false;
    }

    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = __('Correo Electrónico (Facturación y Guía de Envío)', 'sage');
        $fields['billing']['billing_email']['placeholder'] = 'ejemplo@correo.com';
    }

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = __('Celular / WhatsApp (Para Coordinar la Entrega)', 'sage');
        $fields['billing']['billing_phone']['placeholder'] = 'Ej: 310 123 4567';
    }

    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = __('Dirección de Residencia o Trabajo', 'sage');
        $fields['billing']['billing_address_1']['placeholder'] = 'Calle / Carrera / Avenida # - Barrio';
    }

    return $fields;
}, 50);

/**
 * Traducir mensajes de error y éxito de cupones de WooCommerce al español.
 */
add_filter('woocommerce_coupon_error', function ($err, $err_code, $coupon) {
    if (str_contains($err, 'does not exist')) {
        return sprintf('El cupón "%s" no existe o no es válido.', $coupon->get_code());
    }
    if (str_contains($err, 'already been applied')) {
        return sprintf('El cupón "%s" ya ha sido aplicado a tu carrito.', $coupon->get_code());
    }
    return $err;
}, 20, 3);

add_filter('woocommerce_add_error', function ($message) {
    if (str_contains($message, 'cannot be applied because it does not exist')) {
        $coupon_code = preg_replace('/.*Coupon "([^"]+)".*/', '$1', $message);
        if ($coupon_code === $message) {
            return 'El cupón ingresado no existe o no es válido.';
        }
        return sprintf('El cupón "%s" no existe o no es válido.', $coupon_code);
    }
    if (str_contains($message, 'already been applied')) {
        $coupon_code = preg_replace('/.*Coupon "([^"]+)".*/', '$1', $message);
        if ($coupon_code === $message) {
            return 'Este cupón ya ha sido aplicado a tu carrito.';
        }
        return sprintf('El cupón "%s" ya ha sido aplicado a tu carrito.', $coupon_code);
    }
    return $message;
}, 20);

add_filter('woocommerce_coupon_message', function ($msg, $msg_code, $coupon) {
    if ($msg_code === \WC_Coupon::WC_COUPON_SUCCESS) {
        return sprintf('¡El cupón "%s" se ha aplicado correctamente!', $coupon->get_code());
    }
    return $msg;
}, 20, 3);

/**
 * Redirección 301 permanente de /shop/ a /tienda/ para evitar 404s y consolidar SEO.
 */
add_action('template_redirect', function () {
    if (! empty($_SERVER['REQUEST_URI']) && preg_match('#^/shop(/|\?|$)#i', $_SERVER['REQUEST_URI'])) {
        $target = preg_replace('#^/shop#i', '/tienda', $_SERVER['REQUEST_URI']);
        wp_safe_redirect(home_url($target), 301);
        exit;
    }
});

/**
 * Renderiza el shortcode del feed de Instagram sin el bloque de cabecera
 * (avatar + nombre de cuenta). Se pidió con showheader=false, pero el
 * plugin (Instagram Feed by Smash Balloon) igual imprime ese bloque en
 * el HTML —incluyendo un <img> del avatar sin atributo alt— y antes solo
 * se ocultaba con CSS, así que seguía ahí para cualquier rastreador
 * (Rank Math lo marcaba como imagen sin alt en el SEO Analyzer). Se
 * quita el nodo del DOM en vez de solo ocultarlo.
 */
function rb_instagram_feed_shortcode(string $shortcode): string
{
    $html = do_shortcode($shortcode);

    if ($html === '' || ! str_contains($html, 'sb_instagram_header')) {
        return $html;
    }

    $previousLibxmlState = libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="rb-instagram-feed-wrap">' . $html . '</div>');
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlState);

    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sb_instagram_header ")]') as $node) {
        $node->parentNode?->removeChild($node);
    }

    $wrap = $dom->getElementById('rb-instagram-feed-wrap');

    if (! $wrap) {
        return $html;
    }

    $result = '';
    foreach ($wrap->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}

