{{--
  Confirmación de pedido (thank you). Sin este override, WooCommerce sirve su
  plantilla por defecto sin ningún envoltorio del theme: page.blade.php excluye
  a is_checkout() del contenedor rb-container porque asume que checkout trae
  el suyo propio (form-checkout.blade.php lo hace), pero order-received
  también es is_checkout() y no tenía ninguno — de ahí el contenido pegado
  al borde izquierdo sin estilos.
--}}

@php
  if (! defined('ABSPATH')) exit;

  $isFailed = $order && $order->has_status('failed');
  $isGuest = ! is_user_logged_in();

  if ($order && ! $isFailed) {
    $isCod = $order->get_payment_method() === 'cod';

    $city = $order->get_shipping_city() ?: $order->get_billing_city();
    $isBogota = $city && str_contains(mb_strtolower(remove_accents($city)), 'bogota');

    $hasBike = false;
    $orderedProductIds = [];

    foreach ($order->get_items() as $orderItem) {
      $itemProduct = $orderItem->get_product();
      if (! $itemProduct) {
        continue;
      }
      $orderedProductIds[] = $itemProduct->get_id();
      if (has_term('bicicletas', 'product_cat', $itemProduct->get_id())) {
        $hasBike = true;
      }
    }

    $crossSell = [];
    if ($hasBike && function_exists('wc_get_products')) {
      $crossSell = wc_get_products([
        'category' => ['accesorios', 'componentes'],
        'exclude' => $orderedProductIds,
        'limit' => 4,
        'status' => 'publish',
        'orderby' => 'popularity',
      ]);
    }
  }
@endphp

<div class="rb-order-received mx-auto max-w-4xl px-4 pb-24 pt-10 md:pb-32 md:pt-16">

  @if ($order)
    @php
      do_action('woocommerce_before_thankyou', $order->get_id());
    @endphp
  @endif

  @if ($isFailed)
    {{-- Rama de fallo, preservada de la plantilla del core --}}
    <div class="rounded-xl border border-red-800/40 bg-red-950/40 p-6 text-center">
      <p class="text-sm text-red-200">
        {{ __('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce') }}
      </p>
      <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
        <x-button variant="primary" :href="$order->get_checkout_payment_url()">
          {{ __('Pagar de nuevo', 'sage') }}
        </x-button>
        @if (is_user_logged_in())
          <x-button variant="secondary" :href="wc_get_page_permalink('myaccount')">
            {{ __('Mi cuenta', 'sage') }}
          </x-button>
        @endif
      </div>
    </div>
  @elseif ($order)
    {{-- Confirmación --}}
    <div class="text-center">
      <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
        <x-icon name="shield-check" class="size-7" />
      </span>

      <h1 class="mt-5 text-2xl font-bold uppercase tracking-tight text-ink md:text-3xl">
        {{ __('Pedido confirmado', 'sage') }}
      </h1>

      <p class="mt-3 text-sm text-ink-muted">
        {!! apply_filters(
          'woocommerce_thankyou_order_received_text',
          sprintf(
            esc_html__('Gracias. Recibimos tu pedido #%s.', 'sage'),
            $order->get_order_number()
          ),
          $order
        ) !!}
        @if ($order->get_billing_email())
          <br>
          {{ sprintf(__('Te enviamos una copia a %s.', 'sage'), $order->get_billing_email()) }}
        @endif
      </p>
    </div>

    {{-- Resumen en tarjetas --}}
    <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line md:grid-cols-4">
      <div class="bg-surface-raised px-5 py-5 text-center">
        <dt class="text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Pedido', 'sage') }}</dt>
        <dd class="mt-1 text-sm font-bold text-ink">#{{ $order->get_order_number() }}</dd>
      </div>
      <div class="bg-surface-raised px-5 py-5 text-center">
        <dt class="text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Fecha', 'sage') }}</dt>
        <dd class="mt-1 text-sm font-bold text-ink">{{ wc_format_datetime($order->get_date_created()) }}</dd>
      </div>
      <div class="bg-surface-raised px-5 py-5 text-center">
        <dt class="text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Total', 'sage') }}</dt>
        <dd class="mt-1 text-sm font-bold text-ink">{!! $order->get_formatted_order_total() !!}</dd>
      </div>
      <div class="bg-surface-raised px-5 py-5 text-center">
        <dt class="text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Pago', 'sage') }}</dt>
        <dd class="mt-1 text-sm font-bold text-ink">{{ wp_strip_all_tags($order->get_payment_method_title()) ?: __('—', 'sage') }}</dd>
      </div>
    </dl>

    {{-- Aviso de pago contra entrega: hoy es texto plano invisible en la plantilla por defecto --}}
    @if ($isCod)
      <div class="mt-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-5 text-center">
        <p class="text-sm font-semibold text-amber-200">
          {{ sprintf(__('Ten listo %s en efectivo o tarjeta al momento de la entrega.', 'sage'), html_entity_decode(wp_strip_all_tags($order->get_formatted_order_total()))) }}
        </p>
      </div>
    @elseif ($order->is_paid())
      <div class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 text-center">
        <p class="text-sm font-semibold text-emerald-300">
          {{ __('Pago confirmado. No necesitas hacer nada más.', 'sage') }}
        </p>
      </div>
    @endif

    {{-- Qué sigue ahora --}}
    <div class="mt-10 border-t border-line pt-10">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Qué sigue ahora', 'sage') }}</h2>

      @php
        $steps = [
          ['label' => __('Confirmado', 'sage'), 'active' => true],
        ];

        if ($hasBike) {
          $steps[] = ['label' => __('Armado y ajuste', 'sage'), 'active' => false];
        }

        $steps[] = ['label' => __('Despacho', 'sage'), 'active' => false];
        $steps[] = ['label' => __('Entrega', 'sage'), 'active' => false];
      @endphp

      <ol class="mt-6 grid gap-6 sm:grid-cols-{{ count($steps) }}">
        @foreach ($steps as $index => $step)
          <li class="relative pl-8 sm:pl-0 sm:text-center">
            <div class="flex items-center gap-3 sm:flex-col sm:gap-2">
              <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $step['active'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-surface-muted text-ink-subtle' }}">
                {{ $index + 1 }}
              </span>
              <span class="text-xs font-semibold uppercase tracking-wide {{ $step['active'] ? 'text-ink' : 'text-ink-subtle' }}">
                {{ $step['label'] }}
              </span>
            </div>
          </li>
        @endforeach
      </ol>

      <div class="mt-6 space-y-2 text-sm leading-relaxed text-ink-muted">
        @if ($hasBike)
          <p>
            {{ __('El armado y ajuste en Bogotá toma 48 horas antes del despacho.', 'sage') }}
          </p>
        @endif

        @if ($isBogota)
          <p>{{ __('En Bogotá entregamos tu bicicleta 100% armada, calibrada y lista para rodar.', 'sage') }}</p>
        @else
          <p>{{ __('Tu pedido viaja protegido en caja reforzada, pre-ensamblado al 90%, y llega en 2 a 5 días hábiles.', 'sage') }}</p>
        @endif
      </div>
    </div>

    {{-- Detalle del pedido --}}
    <div class="mt-10 border-t border-line pt-10">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Detalle del pedido', 'sage') }}</h2>

      {{-- Móvil: tarjetas --}}
      <ul class="mt-5 space-y-4 md:hidden">
        @foreach ($order->get_items() as $orderItem)
          @php
            $lineProduct = $orderItem->get_product();
            $lineImageId = $lineProduct ? $lineProduct->get_image_id() : null;
          @endphp
          <li class="flex items-center gap-4 border-b border-line pb-4">
            <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
              @if ($lineImageId)
                <img src="{{ wp_get_attachment_image_url($lineImageId, 'thumbnail') }}" alt="" class="size-full object-cover">
              @endif
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-ink">{{ $orderItem->get_name() }}</p>
              <p class="text-xs text-ink-subtle">{{ __('Cantidad', 'sage') }}: {{ $orderItem->get_quantity() }}</p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-ink">{!! $order->get_formatted_line_subtotal($orderItem) !!}</p>
          </li>
        @endforeach
      </ul>

      {{-- Escritorio: tabla con miniatura --}}
      <table class="mt-5 hidden w-full text-sm md:table">
        <thead>
          <tr class="border-b border-line text-left text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">
            <th class="pb-3 font-semibold">{{ __('Producto', 'sage') }}</th>
            <th class="pb-3 text-right font-semibold">{{ __('Total', 'sage') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($order->get_items() as $orderItem)
            @php
              $lineProduct = $orderItem->get_product();
              $lineImageId = $lineProduct ? $lineProduct->get_image_id() : null;
            @endphp
            <tr class="border-b border-line">
              <td class="py-4">
                <div class="flex items-center gap-4">
                  <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
                    @if ($lineImageId)
                      <img src="{{ wp_get_attachment_image_url($lineImageId, 'thumbnail') }}" alt="" class="size-full object-cover">
                    @endif
                  </div>
                  <div>
                    <p class="font-semibold text-ink">{{ $orderItem->get_name() }}</p>
                    <p class="text-xs text-ink-subtle">{{ __('Cantidad', 'sage') }}: {{ $orderItem->get_quantity() }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 text-right font-semibold text-ink">{!! $order->get_formatted_line_subtotal($orderItem) !!}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Totales --}}
      <div class="mt-4 ml-auto max-w-xs space-y-1.5 text-sm">
        <div class="flex justify-between text-ink-subtle">
          <span>{{ __('Subtotal', 'sage') }}</span>
          <span>{!! wc_price($order->get_subtotal()) !!}</span>
        </div>
        <div class="flex justify-between text-ink-subtle">
          <span>{{ __('Envío', 'sage') }}</span>
          <span>{{ $order->get_shipping_total() > 0 ? wp_kses_post($order->get_shipping_to_display()) : __('A coordinar', 'sage') }}</span>
        </div>
        <div class="flex justify-between border-t border-line pt-1.5 text-base font-bold text-ink">
          <span>{{ __('Total', 'sage') }}</span>
          <span>{!! $order->get_formatted_order_total() !!}</span>
        </div>
      </div>

      {{-- Direcciones --}}
      <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-line p-5 text-sm not-italic leading-relaxed text-ink-muted">
          <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Facturación', 'sage') }}</p>
          {!! $order->get_formatted_billing_address() ?: __('No especificada.', 'woocommerce') !!}
        </div>
        <div class="rounded-xl border border-line p-5 text-sm not-italic leading-relaxed text-ink-muted">
          <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Envío', 'sage') }}</p>
          {!! $order->get_formatted_shipping_address() ?: __('Igual a la dirección de facturación.', 'woocommerce') !!}
        </div>
      </div>
    </div>

    {{-- Acciones --}}
    <div class="mt-10 flex flex-wrap items-center justify-center gap-3 border-t border-line pt-10 print:hidden">
      @if (function_exists('rb_cro_get_smart_whatsapp_url'))
        <x-button variant="primary" :href="rb_cro_get_smart_whatsapp_url('order', $order)" target="_blank" rel="noopener noreferrer">
          {{ __('Escribir por WhatsApp', 'sage') }}
        </x-button>
      @endif

      @if (is_user_logged_in())
        <x-button variant="secondary" :href="$order->get_view_order_url()">
          {{ __('Ver mi pedido', 'sage') }}
        </x-button>
      @endif

      <x-button variant="secondary" :href="get_permalink(wc_get_page_id('shop'))">
        {{ __('Seguir comprando', 'sage') }}
      </x-button>
    </div>

    @if ($isGuest && get_option('woocommerce_enable_myaccount_registration') === 'yes')
      <p class="mt-6 text-center text-xs text-ink-subtle print:hidden">
        {{ __('¿Quieres hacer seguimiento a este pedido la próxima vez?', 'sage') }}
        <a href="{{ wc_get_page_permalink('myaccount') }}" class="font-semibold text-ink underline underline-offset-2">
          {{ __('Crea una cuenta', 'sage') }}
        </a>
      </p>
    @endif

    {{-- Cross-sell: sólo tras comprar una bicicleta, es el único momento en que no estorba --}}
    @if (! empty($crossSell))
      <div class="mt-14 border-t border-line pt-10 print:hidden">
        <h2 class="mb-6 text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Completa tu equipo', 'sage') }}
        </h2>

        <x-carousel-shelf aria-label="{{ __('Accesorios recomendados', 'sage') }}" tracking-id="order_received_cross_sell">
          @foreach ($crossSell as $index => $crossSellProduct)
            <div class="w-[75%] shrink-0 snap-start sm:w-[46%] lg:w-[31%]" data-carousel-slide>
              <x-product-card :product="$crossSellProduct" list-id="order_received_cross_sell" list-name="Completa tu equipo" :position="$index" />
            </div>
          @endforeach
        </x-carousel-shelf>
      </div>
    @endif
  @else
    {{-- $order es false: WooCommerce no pudo resolver el pedido --}}
    <p class="text-center text-sm text-ink-muted">
      {!! apply_filters('woocommerce_thankyou_order_received_text', esc_html__('Thank you. Your order has been received.', 'woocommerce'), false) !!}
    </p>
  @endif

  @if ($order)
    @php
      do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id());
      do_action('woocommerce_thankyou', $order->get_id());
    @endphp
  @endif
</div>
