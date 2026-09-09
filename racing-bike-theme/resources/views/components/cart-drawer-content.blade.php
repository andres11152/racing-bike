@php
  $cart = function_exists('WC') && WC()->cart ? WC()->cart : null;
  $items = $cart ? $cart->get_cart() : [];
  $subtotal = $cart ? $cart->get_cart_subtotal() : 0;

  // Obtener productos adicionales para recomendación de Cross-Selling en 1 Clic
  $addonProducts = function_exists('wc_get_products') ? wc_get_products([
    'limit' => 2,
    'status' => 'publish',
    'orderby' => 'rand',
  ]) : [];
@endphp

<div data-cart-drawer-body class="flex flex-col h-full justify-between">
  {{-- Barra Dinámica de Envío Gratis (CRO Booster) --}}
  @if (function_exists('rb_cro_render_shipping_bar'))
    {!! rb_cro_render_shipping_bar() !!}
  @else
    <div class="px-6 py-4 border-b border-line bg-surface-raised">
      <div class="flex items-center gap-2 text-xs font-bold text-ink-muted">
        <x-icon name="truck" class="size-4 shrink-0 text-ink-subtle" />
        <span>{{ __('Envío a todo Colombia con Interrapidísimo. Te confirmamos el costo por WhatsApp según tu ciudad.', 'sage') }}</span>
      </div>
    </div>
  @endif

  @if (! empty($items))
    <div class="flex-1 overflow-y-auto px-6 py-5 divide-y divide-line">
      {{-- Lista de Productos en Carrito --}}
      <div class="space-y-4 pb-4">
        @foreach ($items as $cartItemKey => $cartItem)
          @php
            $_product = apply_filters('woocommerce_cart_item_product', $cartItem['data'], $cartItem, $cartItemKey);
            $product_id = apply_filters('woocommerce_cart_item_product_id', $cartItem['product_id'], $cartItem, $cartItemKey);

            if (! $_product || ! $_product->exists() || $cartItem['quantity'] < 1 || ! apply_filters('woocommerce_cart_item_visible', true, $cartItem, $cartItemKey)) {
              continue;
            }

            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cartItem) : '', $cartItem, $cartItemKey);
            $thumbnail = $_product->get_image('thumbnail', ['class' => 'size-16 rounded-xl object-cover border border-line shrink-0 bg-surface-muted']);
            $price = WC()->cart->get_product_subtotal($_product, $cartItem['quantity']);
          @endphp

          <div class="flex items-start gap-4 py-2">
            {!! $thumbnail !!}

            <div class="flex-1 min-w-0">
              <a href="{{ $product_permalink }}" class="text-xs font-bold text-ink hover:underline truncate block">
                {{ $_product->get_name() }}
              </a>

              @if (! empty($cartItem['variation']))
                <p class="mt-1 text-[11px] text-ink-subtle">
                  {{ wc_get_formatted_variation($cartItem['variation'], true) }}
                </p>
              @endif

              <div class="mt-2 flex items-center justify-between gap-2">
                {{-- Selector Interactivo de Cantidad (- / +) --}}
                <div class="inline-flex items-center rounded-full border border-line bg-surface p-0.5">
                  <button
                    type="button"
                    class="size-5 rounded-full flex items-center justify-center text-ink-subtle hover:text-white hover:bg-surface-raised transition-colors cursor-pointer text-xs font-bold"
                    data-change-cart-qty="{{ $cartItemKey }}"
                    data-qty="{{ $cartItem['quantity'] - 1 }}"
                    aria-label="{{ __('Disminuir cantidad', 'sage') }}"
                  >
                    -
                  </button>

                  <span class="text-xs font-bold text-white px-2 min-w-[18px] text-center">{{ $cartItem['quantity'] }}</span>

                  <button
                    type="button"
                    class="size-5 rounded-full flex items-center justify-center text-ink-subtle hover:text-white hover:bg-surface-raised transition-colors cursor-pointer text-xs font-bold"
                    data-change-cart-qty="{{ $cartItemKey }}"
                    data-qty="{{ $cartItem['quantity'] + 1 }}"
                    aria-label="{{ __('Aumentar cantidad', 'sage') }}"
                  >
                    +
                  </button>
                </div>

                <span class="text-xs font-bold text-white">{!! $price !!}</span>
              </div>
            </div>

            <button
              type="button"
              class="text-ink-subtle hover:text-white transition-colors p-1 cursor-pointer"
              data-remove-cart-item="{{ $cartItemKey }}"
              aria-label="{{ __('Eliminar del carrito', 'sage') }}"
            >
              <x-icon name="close" class="size-4" />
            </button>
          </div>
        @endforeach
      </div>

      {{-- Módulo de Cross-Selling en 1 Clic --}}
      @if (! empty($addonProducts))
        <div class="pt-6">
          <h4 class="text-xs font-bold uppercase tracking-wider text-ink-muted mb-3 flex items-center justify-between">
            <span>⚡ {{ __('Completa tu equipamiento:', 'sage') }}</span>
            <span class="text-[10px] text-emerald-400 font-semibold">+ 1-Clic</span>
          </h4>

          <div class="space-y-3">
            @foreach ($addonProducts as $addon)
              <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-surface-raised border border-line">
                <div class="flex items-center gap-3 min-w-0">
                  {!! $addon->get_image('thumbnail', ['class' => 'size-11 rounded-lg object-cover shrink-0 bg-surface-muted']) !!}
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ $addon->get_name() }}</p>
                    <p class="text-[11px] font-semibold text-emerald-400">{!! $addon->get_price_html() !!}</p>
                  </div>
                </div>

                <button
                  type="button"
                  class="px-3 py-1.5 rounded-full bg-white text-black text-[11px] font-bold uppercase tracking-wider hover:bg-neutral-200 transition-all shrink-0 cursor-pointer shadow active:scale-95"
                  data-add-addon="{{ $addon->get_id() }}"
                >
                  + {{ __('Agregar', 'sage') }}
                </button>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    {{-- Footer del Carrito con Subtotal y Finalizar Compra --}}
    <div class="border-t border-line px-6 py-5 bg-surface-raised space-y-3">
      <div class="flex items-center justify-between text-sm">
        <span class="text-ink-muted uppercase tracking-wider text-xs font-semibold">{{ __('Subtotal', 'sage') }}</span>
        <span class="text-base font-bold text-white">{!! $subtotal !!}</span>
      </div>

      <a
        href="{{ wc_get_checkout_url() }}"
        class="flex w-full items-center justify-center rounded-full bg-white text-black px-6 py-3.5 text-xs font-bold uppercase tracking-widest hover:bg-neutral-200 transition-all shadow-lg active:scale-95"
      >
        {{ __('Finalizar compra', 'sage') }} &rarr;
      </a>

      @if (function_exists('rb_cro_get_smart_whatsapp_url'))
        <a
          href="{{ rb_cro_get_smart_whatsapp_url('cart') }}"
          target="_blank"
          rel="noopener noreferrer"
          class="flex w-full items-center justify-center gap-2 rounded-full border border-line bg-surface px-4 py-2.5 text-[11px] font-semibold text-ink-muted hover:text-white hover:border-emerald-500/50 transition-colors"
        >
          <x-icon name="phone" class="size-3.5 text-emerald-400" />
          <span>{{ __('Pedir asesoría o pagar por WhatsApp', 'sage') }}</span>
        </a>
      @endif

      <div class="flex items-center justify-center gap-3 pt-1 text-[10px] text-ink-subtle">
        <span>🔒 {{ __('Pago 100% Seguro') }}</span>
        <span>•</span>
        <span>PSE / Tarjetas / Addi</span>
      </div>
    </div>
  @else
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 text-center">
      <x-icon name="cart" class="size-12 text-ink-subtle opacity-40 mb-4" />
      <h3 class="text-sm font-bold text-ink uppercase tracking-wider">{{ __('Tu carrito está vacío', 'sage') }}</h3>
      <p class="mt-1 text-xs text-ink-muted">{{ __('Agrega bicicletas o accesorios para comenzar.', 'sage') }}</p>

      <a
        href="{{ get_permalink(wc_get_page_id('shop')) }}"
        class="mt-6 inline-flex items-center rounded-full border border-line px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-ink hover:bg-surface-raised transition-colors"
        data-cart-close
      >
        {{ __('Explorar tienda', 'sage') }}
      </a>
    </div>
  @endif
</div>
