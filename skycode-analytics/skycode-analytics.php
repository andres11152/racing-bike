<?php
/**
 * Plugin Name: Skycode Analytics & Pixels
 * Description: Integración de alto rendimiento para Google Analytics 4 (GA4), Meta Pixel (Facebook) y Consent Mode v2. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Medición: GA4, Meta Pixel, Consent Mode v2 y datos estructurados de producto.
 *
 * Separado en un plugin personalizado e independiente del theme visual.
 */

/**
 * @return string ID de medición de GA4 (G-XXXXXXX), o cadena vacía si no está configurado.
 */
function rb_ga4_measurement_id(): string
{
    $db_val = get_option('rb_ga4_measurement_id', '');
    if (! empty($db_val)) {
        return trim((string) $db_val);
    }
    return defined('GA4_MEASUREMENT_ID') ? trim((string) GA4_MEASUREMENT_ID) : '';
}

/**
 * @return string ID de Meta Pixel, o cadena vacía si no está configurado.
 */
function rb_meta_pixel_id(): string
{
    $db_val = get_option('rb_meta_pixel_id', '');
    if (! empty($db_val)) {
        return trim((string) $db_val);
    }
    return defined('META_PIXEL_ID') ? trim((string) META_PIXEL_ID) : '';
}

/**
 * Configuración del Panel de Administración en WordPress
 */
add_action('admin_menu', function () {
    add_options_page(
        __('Analítica y Píxeles', 'skycode-analytics'),
        __('Analítica y Píxeles', 'skycode-analytics'),
        'manage_options',
        'rb-analytics-settings',
        'rb_analytics_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('rb_analytics_settings_group', 'rb_ga4_measurement_id', 'sanitize_text_field');
    register_setting('rb_analytics_settings_group', 'rb_meta_pixel_id', 'sanitize_text_field');
});

function rb_analytics_render_settings_page() {
    $ga4_env = defined('GA4_MEASUREMENT_ID') ? GA4_MEASUREMENT_ID : '';
    $meta_env = defined('META_PIXEL_ID') ? META_PIXEL_ID : '';
    ?>
    <div class="wrap">
        <h1>📊 <?php _e('Configuración de Analítica y Píxeles', 'skycode-analytics'); ?></h1>
        <p><?php _e('Administra las claves de medición y píxeles de conversión de tu tienda de forma autónoma sin depender de archivos del servidor.', 'skycode-analytics'); ?></p>
        
        <form method="post" action="options.php" style="background: white; border: 1px solid #ccd0d4; padding: 20px; max-width: 600px; margin-top: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <?php settings_fields('rb_analytics_settings_group'); ?>
            <?php do_settings_sections('rb_analytics_settings_group'); ?>
            
            <table class="form-table" style="width: 100%;">
                <tr valign="top">
                    <th scope="row" style="width: 35%;"><?php _e('Google Analytics 4 ID', 'skycode-analytics'); ?></th>
                    <td>
                        <input type="text" name="rb_ga4_measurement_id" value="<?php echo esc_attr(get_option('rb_ga4_measurement_id')); ?>" class="regular-text" placeholder="G-XXXXXXXXXX" />
                        <?php if ($ga4_env): ?>
                            <p class="description" style="color: #666; font-size: 11px;">
                                💡 <?php printf(__('Detectado en .env (Respaldo): %s', 'skycode-analytics'), esc_html($ga4_env)); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Meta Pixel ID (Facebook)', 'skycode-analytics'); ?></th>
                    <td>
                        <input type="text" name="rb_meta_pixel_id" value="<?php echo esc_attr(get_option('rb_meta_pixel_id')); ?>" class="regular-text" placeholder="1234567890" />
                        <?php if ($meta_env): ?>
                            <p class="description" style="color: #666; font-size: 11px;">
                                💡 <?php printf(__('Detectado en .env (Respaldo): %s', 'skycode-analytics'), esc_html($meta_env)); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Guardar Cambios', 'skycode-analytics')); ?>
        </form>
    </div>
    <?php
}

/**
 * Normaliza un producto a un "item" de GA4 / Meta reutilizable en todos los eventos de compra.
 *
 * @return array<string, mixed>
 */
function rb_ga4_item_from_product(\WC_Product $product, int $quantity = 1): array
{
    $terms = get_the_terms($product->get_id(), 'product_cat');
    $category = $terms && ! is_wp_error($terms) ? end($terms)->name : null;

    return array_filter([
        'item_id' => $product->get_sku() ?: (string) $product->get_id(),
        'item_name' => $product->get_name(),
        'item_category' => $category,
        'price' => (float) ($product->get_price() ?: 0),
        'quantity' => $quantity,
    ], fn ($value) => $value !== null && $value !== '');
}

/**
 * Consent Mode v2 + cargadores de GA4/Meta Pixel.
 */
add_action('wp_head', function () {
    $ga4 = rb_ga4_measurement_id();
    $meta = rb_meta_pixel_id();

    if (! $ga4 && ! $meta) {
        return;
    }
    ?>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() { dataLayer.push(arguments); }

      gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
      });

      window.rbMetaReady = false;

      window.rbLoadMetaPixel = <?php echo $meta ? 'function () {
        if (window.rbMetaReady) return;
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');
        fbq(\'init\', ' . wp_json_encode($meta) . ');
        fbq(\'track\', \'PageView\');
        window.rbMetaReady = true;
      }' : 'function () {}'; ?>;

      // Fuente única de eventos de ecommerce
      window.rbTrack = function (ga4Event, ga4Params, metaEvent, metaParams) {
        if (typeof gtag === 'function') gtag('event', ga4Event, ga4Params || {});
        if (window.rbMetaReady && typeof fbq === 'function' && metaEvent) fbq('track', metaEvent, metaParams || {});
      };

      (function () {
        var consent = null;
        try { consent = localStorage.getItem('rb_cookies_consent'); } catch (e) {}

        if (consent === 'accepted') {
          gtag('consent', 'update', {
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'granted',
            analytics_storage: 'granted',
          });
          window.rbLoadMetaPixel();
        }
      })();

      window.addEventListener('cookies-accepted', function () {
        gtag('consent', 'update', {
          ad_storage: 'granted',
          ad_user_data: 'granted',
          ad_personalization: 'granted',
          analytics_storage: 'granted',
        });
        window.rbLoadMetaPixel();
      });

      window.addEventListener('cookies-rejected', function () {
        gtag('consent', 'update', {
          ad_storage: 'denied',
          ad_user_data: 'denied',
          ad_personalization: 'denied',
          analytics_storage: 'denied',
        });
      });
    </script>
    <?php if ($ga4): ?>
      <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4); ?>"></script>
      <script>
        gtag('js', new Date());
        gtag('config', <?php echo wp_json_encode($ga4); ?>);
      </script>
    <?php endif; ?>
    <?php
}, 1);

/**
 * Evento `view_item` al cargar una ficha de producto.
 */
add_action('wp_footer', function () {
    if (! function_exists('is_product') || ! is_product()) {
        return;
    }

    $product = wc_get_product(get_queried_object_id());

    if (! $product) {
        return;
    }

    $item = rb_ga4_item_from_product($product);
    $price = (float) ($product->get_price() ?: 0);
    ?>
    <script>
      window.rbTrack && window.rbTrack('view_item', {
        currency: 'COP',
        value: <?php echo wp_json_encode($price); ?>,
        items: [<?php echo wp_json_encode($item); ?>],
      }, 'ViewContent', {
        content_ids: [<?php echo wp_json_encode((string) $item['item_id']); ?>],
        content_type: 'product',
        value: <?php echo wp_json_encode($price); ?>,
        currency: 'COP',
      });
    </script>
    <?php
});

/**
 * Evento `add_to_cart` cuando WooCommerce añade por el flujo clásico.
 */
add_action('wp_footer', function () {
    if (empty($_GET['added-to-cart'])) {
        return;
    }

    $ids = array_filter(array_map('absint', explode(',', wp_unslash($_GET['added-to-cart']))));

    if (! $ids) {
        return;
    }

    $items = [];
    $value = 0.0;

    foreach ($ids as $id) {
        $product = wc_get_product($id);

        if (! $product) {
            continue;
        }

        $items[] = rb_ga4_item_from_product($product);
        $value += (float) ($product->get_price() ?: 0);
    }

    if (! $items) {
        return;
    }
    ?>
    <script>
      window.rbTrack && window.rbTrack('add_to_cart', {
        currency: 'COP',
        value: <?php echo wp_json_encode($value); ?>,
        items: <?php echo wp_json_encode($items); ?>,
      }, 'AddToCart', {
        content_ids: <?php echo wp_json_encode(array_map('strval', $ids)); ?>,
        content_type: 'product',
        value: <?php echo wp_json_encode($value); ?>,
        currency: 'COP',
      });
    </script>
    <?php
}, 20);

/**
 * Evento `begin_checkout` al cargar la página de checkout.
 */
add_action('wp_footer', function () {
    if (! function_exists('is_checkout') || ! is_checkout() || is_wc_endpoint_url()) {
        return;
    }

    if (! function_exists('WC') || ! WC()->cart) {
        return;
    }

    $items = [];

    foreach (WC()->cart->get_cart() as $cartItem) {
        if (! $cartItem['data']) {
            continue;
        }

        $items[] = rb_ga4_item_from_product($cartItem['data'], (int) $cartItem['quantity']);
    }

    if (! $items) {
        return;
    }

    $value = (float) WC()->cart->get_total('edit');
    ?>
    <script>
      window.rbTrack && window.rbTrack('begin_checkout', {
        currency: 'COP',
        value: <?php echo wp_json_encode($value); ?>,
        items: <?php echo wp_json_encode($items); ?>,
      }, 'InitiateCheckout', {
        content_ids: <?php echo wp_json_encode(array_column($items, 'item_id')); ?>,
        value: <?php echo wp_json_encode($value); ?>,
        currency: 'COP',
      });
    </script>
    <?php
});

/**
 * Evento `purchase` en la página de gracias.
 */
add_action('woocommerce_thankyou', function ($orderId) {
    if (! $orderId) {
        return;
    }

    $order = wc_get_order($orderId);

    if (! $order || $order->get_meta('_rb_ga4_purchase_tracked')) {
        return;
    }

    $items = [];

    foreach ($order->get_items() as $orderItem) {
        $product = $orderItem->get_product();

        if (! $product) {
            continue;
        }

        $line = rb_ga4_item_from_product($product, (int) $orderItem->get_quantity());
        $line['price'] = (float) $order->get_item_total($orderItem, false, false);
        $items[] = $line;
    }
    ?>
    <script>
      window.rbTrack && window.rbTrack('purchase', {
        transaction_id: <?php echo wp_json_encode($order->get_order_number()); ?>,
        currency: <?php echo wp_json_encode($order->get_currency()); ?>,
        value: <?php echo wp_json_encode((float) $order->get_total()); ?>,
        shipping: <?php echo wp_json_encode((float) $order->get_shipping_total()); ?>,
        items: <?php echo wp_json_encode($items); ?>,
      }, 'Purchase', {
        value: <?php echo wp_json_encode((float) $order->get_total()); ?>,
        currency: <?php echo wp_json_encode($order->get_currency()); ?>,
        content_ids: <?php echo wp_json_encode(array_column($items, 'item_id')); ?>,
      });
    </script>
    <?php
    $order->update_meta_data('_rb_ga4_purchase_tracked', '1');
    $order->save();
}, 20);

/**
 * JSON-LD de Product + BreadcrumbList en la ficha.
 */
add_action('wp_head', function () {
    if (! function_exists('is_product') || ! is_product()) {
        return;
    }

    $product = wc_get_product(get_queried_object_id());

    if (! $product) {
        return;
    }

    $isVariable = $product->is_type('variable');
    $price = $isVariable ? $product->get_variation_price('min') : $product->get_price();

    $schema = array_filter([
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'sku' => $product->get_sku() ?: (string) $product->get_id(),
        'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
        'image' => array_values(array_filter([wp_get_attachment_image_url($product->get_image_id(), 'full')])),
        'offers' => [
            '@type' => 'Offer',
            'url' => $product->get_permalink(),
            'priceCurrency' => get_woocommerce_currency(),
            'price' => (string) $price,
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ]);

    if ($product->get_rating_count()) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (float) $product->get_average_rating(),
            'reviewCount' => (int) $product->get_rating_count(),
        ];
    }

    $breadcrumbList = [
        '@context' => 'https://schema.org/',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('Inicio', 'sage'), 'item' => home_url('/')],
        ],
    ];

    $terms = get_the_terms($product->get_id(), 'product_cat');
    $primaryCategory = $terms && ! is_wp_error($terms) ? end($terms) : null;
    $position = 2;

    if ($primaryCategory) {
        $link = get_term_link($primaryCategory);

        if (! is_wp_error($link)) {
            $breadcrumbList['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $primaryCategory->name,
                'item' => $link,
            ];
        }
    }

    $breadcrumbList['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $product->get_name(),
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 5);
