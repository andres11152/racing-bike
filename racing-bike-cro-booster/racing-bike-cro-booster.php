<?php
/**
 * Plugin Name: Racing Bike CRO Booster
 * Description: Motor de Optimización de Tasa de Conversión (CRO), barra dinámica de envío gratis para Colombia, alertas de escasez de stock y generación de leads inteligentes por WhatsApp.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Obtener la meta de envío gratis en COP (por defecto $500.000 COP).
 *
 * El envío gratis solo aplica para pedidos con destino Bogotá. Para el
 * resto del país el costo se calcula con la transportadora y se le
 * notifica al cliente por correo/WhatsApp — ver rb_cro_get_shipping_data().
 */
function rb_cro_get_shipping_threshold() {
    $threshold = (float) get_option('rb_cro_shipping_threshold', 500000);
    return apply_filters('rb_cro_shipping_threshold', $threshold);
}

/**
 * Calcular el progreso actual del envío gratis en el carrito.
 */
function rb_cro_get_shipping_data() {
    $threshold = rb_cro_get_shipping_threshold();
    $subtotal = 0;

    if (function_exists('WC') && WC()->cart) {
        $subtotal = (float) WC()->cart->get_displayed_subtotal();
    }

    $remaining = max(0, $threshold - $subtotal);
    $percentage = $threshold > 0 ? min(100, round(($subtotal / $threshold) * 100)) : 100;
    $is_unlocked = $subtotal >= $threshold;

    $formatted_remaining = function_exists('wc_price') ? wc_price($remaining) : '$' . number_format($remaining, 0, ',', '.');
    $formatted_threshold = function_exists('wc_price') ? wc_price($threshold) : '$' . number_format($threshold, 0, ',', '.');

    if ($subtotal <= 0) {
        $message = sprintf(__('Agrega productos para obtener <strong>ENVÍO GRATIS en Bogotá</strong> (compras desde %s). Para otras ciudades el envío se calcula con la transportadora.', 'sage'), $formatted_threshold);
    } elseif (! $is_unlocked) {
        $message = sprintf(__('¡Estás a solo <strong>%s</strong> de obtener <strong>ENVÍO GRATIS en Bogotá</strong>! Para otras ciudades el envío se calcula con la transportadora y te lo confirmamos por WhatsApp/correo.', 'sage'), $formatted_remaining);
    } else {
        $message = __('🎉 ¡Felicidades! Tienes <strong>ENVÍO GRATIS ASEGURADO si tu pedido es para Bogotá</strong>. Para otras ciudades el costo se calcula con la transportadora y te lo confirmamos por WhatsApp/correo.', 'sage');
    }

    return [
        'threshold' => $threshold,
        'subtotal' => $subtotal,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'is_unlocked' => $is_unlocked,
        'message' => $message,
    ];
}

/**
 * Renderizar la barra de progreso de envío gratis (HTML).
 */
function rb_cro_render_shipping_bar() {
    $data = rb_cro_get_shipping_data();
    $is_unlocked = $data['is_unlocked'];
    $percentage = $data['percentage'];

    ob_start();
    ?>
    <div data-cro-shipping-bar class="px-5 py-3.5 border-b border-line bg-surface-raised/90 backdrop-blur-md transition-all duration-300">
      <div class="flex items-center justify-between gap-2 text-xs mb-2">
        <div class="flex items-center gap-2 font-medium text-ink">
          <?php if ($is_unlocked) : ?>
            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 text-xs">✓</span>
          <?php else : ?>
            <svg class="size-4 shrink-0 text-emerald-400 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2.25 5.25h9.75v10.5H2.25V5.25Zm9.75 3.75h4.5l3 3v3.75h-7.5V9ZM6 18.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11.25 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            </svg>
          <?php endif; ?>
          <span class="text-[11px] leading-snug text-ink-muted"><?php echo wp_kses_post($data['message']); ?></span>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider <?php echo $is_unlocked ? 'text-emerald-400 font-black' : 'text-ink-subtle'; ?>">
          <?php echo esc_html($percentage); ?>%
        </span>
      </div>

      <!-- Barra visual interactiva -->
      <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-muted border border-line/40 relative">
        <div
          class="h-full rounded-full transition-all duration-500 ease-out <?php echo $is_unlocked ? 'bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-emerald-400'; ?>"
          style="width: <?php echo esc_attr($percentage); ?>%;"
        ></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Agregar la barra de envío gratis a los fragmentos de WooCommerce para actualización en tiempo real.
 */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['div[data-cro-shipping-bar]'] = rb_cro_render_shipping_bar();
    return $fragments;
}, 30);

/**
 * Obtener badge de escasez / stock bajo para un producto.
 */
function rb_cro_get_stock_badge($product) {
    if (! $product instanceof WC_Product) {
        return '';
    }

    if (! $product->is_in_stock()) {
        return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-950/80 text-red-300 border border-red-800/40">
                  <span class="size-1.5 rounded-full bg-red-400"></span>' . __('Agotado temporalmente', 'sage') . '</span>';
    }

    if ($product->managing_stock()) {
        $stock_qty = $product->get_stock_quantity();
        if ($stock_qty !== null && $stock_qty > 0 && $stock_qty <= 4) {
            return '<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30 animate-pulse">
                      <span class="size-2 rounded-full bg-amber-400"></span>' .
                      sprintf(__('⚡ Solo %d unidades disponibles para despacho inmediato', 'sage'), $stock_qty) .
                   '</div>';
        }
    }

    return '';
}

/**
 * Generar enlace inteligente de WhatsApp con contexto específico.
 */
function rb_cro_get_smart_whatsapp_url($context = 'general', $data = null) {
    $base_phone = '573118485643';
    $message = 'Hola Racing Bike 1998, quisiera asesoría personalizada para elegir mi bicicleta.';

    if ($context === 'product' && $data instanceof WC_Product) {
        $product_name = $data->get_name();
        $sku = $data->get_sku() ? ' (Ref: ' . $data->get_sku() . ')' : '';
        $permalink = $data->get_permalink();
        $message = sprintf(
            'Hola Racing Bike 1998, estoy viendo el producto "%s"%s en su tienda online (%s) y quisiera confirmar disponibilidad y opciones de pago/envío.',
            $product_name,
            $sku,
            $permalink
        );
    } elseif ($context === 'cart' && function_exists('WC') && WC()->cart) {
        $count = WC()->cart->get_cart_contents_count();
        $total = strip_tags(WC()->cart->get_cart_total());
        $message = sprintf(
            'Hola Racing Bike 1998, tengo %d producto(s) en mi carrito por un total de %s y quisiera asesoría para finalizar mi pedido por WhatsApp.',
            $count,
            $total
        );
    } elseif ($context === 'order' && $data instanceof WC_Order) {
        $message = sprintf(
            'Hola Racing Bike 1998, tengo una consulta sobre mi pedido #%s (total %s).',
            $data->get_order_number(),
            strip_tags($data->get_formatted_order_total())
        );
    }

    return 'https://wa.me/' . $base_phone . '?text=' . urlencode($message);
}
