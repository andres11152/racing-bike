{{--
  Plantilla del Carrito Completo optimizada para el mercado colombiano (2 columnas, centrado, dark mode).
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

<div class="max-w-6xl mx-auto px-4 py-8 md:py-12">
  <div class="mb-8 border-b border-line pb-6 flex items-center justify-between">
    <div>
      <span class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Tu Selección', 'sage') }}</span>
      <h1 class="mt-1 text-2xl font-bold uppercase tracking-wider text-ink md:text-3xl">
        {{ __('Carrito de Compras', 'sage') }}
      </h1>
    </div>

    <a href="{{ get_permalink(wc_get_page_id('shop')) }}" class="text-xs font-bold uppercase tracking-widest text-ink-subtle hover:text-ink transition-colors">
      {{ __('Seguir Comprando', 'sage') }} &rarr;
    </a>
  </div>

  @php do_action('woocommerce_before_cart'); @endphp

  <form class="woocommerce-cart-form grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" action="{{ esc_url(wc_get_cart_url()) }}" method="post">
    @php do_action('woocommerce_before_cart_table'); @endphp

    <div class="lg:col-span-8 bg-surface-raised p-6 md:p-8 rounded-xl border border-line shadow-2xl">
      <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents w-full" cellspacing="0">
        <thead>
          <tr class="border-b border-line text-left text-xs uppercase tracking-widest text-ink-subtle">
            <th class="product-remove pb-4">&nbsp;</th>
            <th class="product-thumbnail pb-4">&nbsp;</th>
            <th class="product-name pb-4">{{ __('Producto', 'sage') }}</th>
            <th class="product-price pb-4">{{ __('Precio', 'sage') }}</th>
            <th class="product-quantity pb-4">{{ __('Cantidad', 'sage') }}</th>
            <th class="product-subtotal pb-4 text-right">{{ __('Subtotal', 'sage') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          @php do_action('woocommerce_before_cart_contents'); @endphp

          @foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item)
            @php
              $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
              $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
            @endphp

            @if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key))
              @php
                $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
              @endphp
              <tr class="woocommerce-cart-form__cart-item {{ esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)) }}">
                <td class="product-remove py-4">
                  @php
                    echo apply_filters('woocommerce_cart_item_remove_link', sprintf(
                      '<a href="%s" class="remove text-ink-subtle hover:text-ink font-bold text-lg" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                      esc_url(wc_get_cart_remove_url($cart_item_key)),
                      esc_attr__('Eliminar producto', 'woocommerce'),
                      esc_attr($product_id),
                      esc_attr($_product->get_sku())
                    ), $cart_item_key);
                  @endphp
                </td>

                <td class="product-thumbnail py-4">
                  @php
                    $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail', ['class' => 'size-16 object-cover rounded bg-surface-muted']), $cart_item, $cart_item_key);
                    if (! $product_permalink) {
                      echo $thumbnail;
                    } else {
                      printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                    }
                  @endphp
                </td>

                <td class="product-name py-4 font-medium text-ink" data-title="{{ __('Producto', 'woocommerce') }}">
                  @php
                    if (! $product_permalink) {
                      echo wp_kses_post($_product->get_name());
                    } else {
                      echo wp_kses_post(sprintf('<a href="%s" class="hover:underline">%s</a>', esc_url($product_permalink), $_product->get_name()));
                    }
                    do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                    echo wc_get_formatted_cart_item_data($cart_item);
                  @endphp
                </td>

                <td class="product-price py-4 text-ink-muted" data-title="{{ __('Precio', 'woocommerce') }}">
                  @php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); @endphp
                </td>

                <td class="product-quantity py-4" data-title="{{ __('Cantidad', 'woocommerce') }}">
                  @php
                    if ($_product->is_sold_individually()) {
                      $min_quantity = 1;
                      $max_quantity = 1;
                    } else {
                      $min_quantity = 0;
                      $max_quantity = $_product->get_max_purchase_quantity();
                    }
                    $product_quantity = woocommerce_quantity_input([
                      'input_name'   => "cart[{$cart_item_key}][qty]",
                      'input_value'  => $cart_item['quantity'],
                      'max_value'    => $max_quantity,
                      'min_value'    => $min_quantity,
                      'product_name' => $_product->get_name(),
                    ], $_product, false);
                    echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                  @endphp
                </td>

                <td class="product-subtotal py-4 text-right font-bold text-ink" data-title="{{ __('Subtotal', 'woocommerce') }}">
                  @php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); @endphp
                </td>
              </tr>
            @endif
          @endforeach

          @php do_action('woocommerce_cart_contents'); @endphp

          <tr>
            <td colspan="6" class="actions pt-6">
              <div class="flex flex-wrap items-center justify-between gap-4">
                <button type="submit" class="button border border-line-strong px-6 py-3 text-xs uppercase tracking-widest font-medium text-ink hover:border-ink transition-colors" name="update_cart" value="{{ esc_attr__('Actualizar carrito', 'woocommerce') }}">
                  {{ __('Actualizar carrito', 'sage') }}
                </button>
                @php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); @endphp
              </div>
            </td>
          </tr>

          @php do_action('woocommerce_after_cart_contents'); @endphp
        </tbody>
      </table>
    </div>

    <div class="lg:col-span-4 bg-surface-raised p-6 md:p-8 rounded-xl border border-line shadow-2xl">
      @php woocommerce_cart_totals(); @endphp

      {{-- Sello Enterprise de Garantía & Respaldo Oficial Bogotá --}}
      <div class="mt-6">
        <x-trust-badges />
      </div>
    </div>
  </form>

  @php do_action('woocommerce_after_cart'); @endphp
</div>
