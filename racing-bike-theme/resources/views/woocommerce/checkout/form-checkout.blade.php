{{--
  Plantilla optimizada de Checkout para Colombia (One-Page Checkout de Alta Conversión).

  El pago vive en su propia columna a ancho completo (no en el sidebar de
  resumen) porque el formulario de tarjeta de Mercado Pago necesita ~450px
  para mostrar número + vencimiento/CVV en dos columnas y las cuotas sin
  amontonarse. `woocommerce_order_review()` y `woocommerce_checkout_payment()`
  se llaman por separado en vez de `do_action('woocommerce_checkout_order_review')`
  (que dispara ambas): WooCommerce ya apunta sus fragments de AJAX a
  `.woocommerce-checkout-review-order-table` y `.woocommerce-checkout-payment`
  por clase, no por contenedor padre, así que separarlas en el layout no
  rompe el refresco de totales/pasarelas al cambiar de ciudad o método de pago.
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

<div class="max-w-6xl mx-auto px-4 py-8 md:py-12">
  {{-- Encabezado con Trust Triggers --}}
  <div class="mb-8 border-b border-line pb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <span class="text-xs font-semibold uppercase tracking-widest text-emerald-400">{{ __('Finalizar Compra Segura', 'sage') }}</span>
      <h1 class="mt-1 text-2xl font-bold uppercase tracking-wider text-ink md:text-3xl">
        {{ __('Checkout Racing Bike 1998', 'sage') }}
      </h1>
      <p class="mt-1 text-xs text-ink-muted flex items-center gap-2">
        <x-icon name="shield-check" class="size-4 text-emerald-400 shrink-0" />
        <span>{{ __('Transacción protegida con cifrado SSL de 256 bits. Envíos nacionales con seguro.', 'sage') }}</span>
      </p>
    </div>

    <div class="flex items-center gap-2 text-xs text-ink-subtle bg-surface-raised px-4 py-2 rounded-lg border border-line">
      <x-icon name="truck" class="size-4 text-emerald-400 shrink-0" />
      <span>{{ __('Despacho desde Bogotá a toda Colombia', 'sage') }}</span>
    </div>
  </div>

  {{-- Cupones y Avisos Previos (Full Width) --}}
  <div class="mb-6 space-y-4">
    @php
      do_action('woocommerce_before_checkout_form', $checkout);
    @endphp
  </div>

  @if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in())
    <div class="rounded-xl border border-line bg-surface-raised p-8 text-center text-sm text-ink">
      <p class="font-bold text-base mb-2">{{ __('Debes iniciar sesión para realizar la compra.', 'sage') }}</p>
      <a href="{{ wc_get_page_permalink('myaccount') }}" class="mt-4 inline-flex items-center rounded-full bg-white text-black px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-neutral-200">
        {{ __('Iniciar sesión', 'sage') }}
      </a>
    </div>
  @else
    <form name="checkout" method="post" class="checkout woocommerce-checkout space-y-6" action="{{ esc_url(wc_get_checkout_url()) }}" enctype="multipart/form-data">

      {{-- Resumen del pedido, colapsable en móvil para no empujar el pago hacia abajo --}}
      <details class="group rounded-2xl border border-line bg-surface-raised lg:hidden" open>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 select-none">
          <span class="flex items-center gap-3">
            <x-icon name="shopping-bag" class="size-4 text-emerald-400 shrink-0" />
            <span class="text-sm font-bold uppercase tracking-wider text-ink">{{ __('Ver resumen del pedido', 'sage') }}</span>
          </span>
          <x-icon name="chevron-down" class="size-4 text-ink-subtle transition-transform group-open:rotate-180" />
        </summary>

        <div class="border-t border-line px-5 pb-5 pt-4 text-sm">
          @php do_action('woocommerce_checkout_before_order_review'); @endphp

          <div id="order_review_mobile" class="woocommerce-checkout-review-order">
            @php woocommerce_order_review(); @endphp
          </div>

          @php do_action('woocommerce_checkout_after_order_review'); @endphp
        </div>
      </details>

      {{-- Contenedor en 2 Columnas --}}
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Columna Izquierda: Datos + Pago, a ancho completo (7 columnas) --}}
        <div class="lg:col-span-7 min-w-0 space-y-6">
          @if ($checkout->get_checkout_fields())
            @php do_action('woocommerce_checkout_before_customer_details'); @endphp

            <div class="bg-surface-raised p-6 md:p-8 rounded-2xl border border-line shadow-2xl space-y-6" id="customer_details">
              <div class="flex items-center gap-3 border-b border-line pb-4">
                <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-emerald-500/40 text-xs font-bold text-emerald-400">1</span>
                <h3 class="text-sm font-bold uppercase tracking-wider text-ink">
                  {{ __('Datos de Envío & Facturación', 'sage') }}
                </h3>
              </div>

              <div class="col-1">
                @php do_action('woocommerce_checkout_billing'); @endphp
              </div>

              <div class="col-2">
                @php do_action('woocommerce_checkout_shipping'); @endphp
              </div>
            </div>

            @php do_action('woocommerce_checkout_after_customer_details'); @endphp
          @endif

          {{-- Paso 2: Pago, con el ancho completo de la columna para que el
               formulario de tarjeta de Mercado Pago (número + vencimiento/CVV
               en dos columnas + cuotas) tenga espacio real. --}}
          <div class="rb-payment-step bg-surface-raised p-6 md:p-8 rounded-2xl border border-line shadow-2xl space-y-6">
            <div class="flex items-center justify-between border-b border-line pb-4">
              <div class="flex items-center gap-3">
                <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-emerald-500/40 text-xs font-bold text-emerald-400">2</span>
                <h3 class="text-sm font-bold uppercase tracking-wider text-ink">
                  {{ __('Método de Pago', 'sage') }}
                </h3>
              </div>
              <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
                <x-icon name="lock" class="size-3.5 shrink-0" />
                {{ __('Pago seguro', 'sage') }}
              </span>
            </div>

            @php woocommerce_checkout_payment(); @endphp
          </div>

          {{-- Sellos de confianza para pasarelas en Colombia --}}
          <div class="flex flex-wrap items-center justify-center gap-2 text-[10px] text-ink-muted">
            <span class="px-2.5 py-1 rounded bg-surface border border-line font-bold text-white">PSE</span>
            <span class="px-2.5 py-1 rounded bg-surface border border-line font-bold text-white">Tarjetas Crédito/Débito</span>
            <span class="px-2.5 py-1 rounded bg-surface border border-line font-bold text-white">Mercado Pago</span>
            <span class="px-2.5 py-1 rounded bg-surface border border-line font-bold text-white">ADDI / Sistecrédito</span>
          </div>
        </div>

        {{-- Columna Derecha: Resumen de Pedido, sticky (5 columnas, oculta en móvil) --}}
        <div class="hidden lg:block lg:col-span-5 min-w-0 bg-surface-raised p-6 md:p-8 rounded-2xl border border-line space-y-6 lg:sticky lg:top-24 shadow-2xl">
          @php do_action('woocommerce_checkout_before_order_review_heading'); @endphp

          <div class="flex items-center justify-between border-b border-line pb-4">
            <div class="flex items-center gap-3">
              <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-emerald-500/40 text-xs font-bold text-emerald-400">
                <x-icon name="shopping-bag" class="size-3.5" />
              </span>
              <h3 id="order_review_heading" class="text-sm font-bold uppercase tracking-wider text-ink m-0 p-0 border-none">
                {{ __('Tu Pedido', 'sage') }}
              </h3>
            </div>
            <span class="text-[11px] font-semibold text-emerald-400">100% Seguro</span>
          </div>

          @php do_action('woocommerce_checkout_before_order_review'); @endphp

          <div id="order_review" class="woocommerce-checkout-review-order text-sm">
            @php woocommerce_order_review(); @endphp
          </div>

          @php do_action('woocommerce_checkout_after_order_review'); @endphp

          {{-- Sello de Garantía y Taller Oficial Bogotá --}}
          <div class="border-t border-line/40 pt-4 text-center">
            <p class="text-[11px] text-ink-subtle">
              ¿Dudas con tu pago? Escríbenos a nuestro <a href="{{ \App\whatsapp_url(__('Hola Racing Bike 1998, estoy en el checkout y necesito ayuda para finalizar mi compra.', 'sage')) }}" target="_blank" rel="noopener noreferrer" class="text-emerald-400 font-semibold hover:underline">WhatsApp ({{ $contact['whatsapp_display'] }})</a>.
            </p>
          </div>
        </div>
      </div>
    </form>

    @php do_action('woocommerce_after_checkout_form', $checkout); @endphp
  @endif
</div>
