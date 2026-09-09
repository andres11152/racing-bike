{{--
  Plantilla del Historial de Pedidos de Mi Cuenta.
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

@php do_action('woocommerce_before_account_orders', $has_orders); @endphp

<div class="space-y-6">
  {{-- Encabezado --}}
  <div class="border-b border-line pb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <span class="text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
        {{ __('Historial de Compras', 'sage') }}
      </span>
      <h2 class="mt-1 text-xl font-bold uppercase tracking-wider text-ink md:text-2xl">
        {{ __('Mis Pedidos', 'sage') }}
      </h2>
    </div>
    <p class="text-xs text-ink-subtle">
      {{ __('Consulta el estado, detalles y facturas de tus compras.', 'sage') }}
    </p>
  </div>

  @if ($has_orders)
    <div class="overflow-x-auto rounded-2xl border border-line bg-surface">
      <table class="w-full text-left text-xs text-ink-muted">
        <thead class="border-b border-line bg-surface-raised/80 text-[11px] font-bold uppercase tracking-wider text-ink">
          <tr>
            @foreach (wc_get_account_orders_columns() as $column_id => $column_name)
              <th scope="col" class="px-5 py-4 {{ $column_id === 'order-actions' ? 'text-right' : '' }}">
                {{ $column_name }}
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody class="divide-y divide-line/60">
          @foreach ($customer_orders->orders as $customer_order)
            @php
              $order      = wc_get_order($customer_order);
              $item_count = $order->get_item_count() - $order->get_item_count_refunded();
              $status     = $order->get_status();

              $statusStyles = [
                'completed'  => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                'on-hold'    => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                'pending'    => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                'cancelled'  => 'bg-red-500/10 text-red-400 border-red-500/30',
                'failed'     => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                'refunded'   => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
              ];
              $statusClass = $statusStyles[$status] ?? 'bg-surface-raised text-ink-subtle border-line';
            @endphp
            <tr class="hover:bg-surface-raised/40 transition-colors">
              @foreach (wc_get_account_orders_columns() as $column_id => $column_name)
                <td class="px-5 py-4 {{ $column_id === 'order-actions' ? 'text-right' : '' }}">
                  @if (has_action('woocommerce_my_account_my_orders_column_' . $column_id))
                    @php do_action('woocommerce_my_account_my_orders_column_' . $column_id, $order); @endphp

                  @elseif ('order-number' === $column_id)
                    <a href="{{ esc_url($order->get_view_order_url()) }}" class="font-bold text-ink hover:underline">
                      #{{ $order->get_order_number() }}
                    </a>

                  @elseif ('order-date' === $column_id)
                    <time datetime="{{ esc_attr($order->get_date_created()->date('c')) }}" class="text-ink-subtle">
                      {{ wc_format_datetime($order->get_date_created()) }}
                    </time>

                  @elseif ('order-status' === $column_id)
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                      {{ wc_get_order_status_name($status) }}
                    </span>

                  @elseif ('order-total' === $column_id)
                    <span class="font-bold text-ink">
                      {!! sprintf(_n('%1$s (%2$s artículo)', '%1$s (%2$s artículos)', $item_count, 'sage'), $order->get_formatted_order_total(), $item_count) !!}
                    </span>

                  @elseif ('order-actions' === $column_id)
                    @php
                      $actions = wc_get_account_orders_actions($order);
                    @endphp
                    @if (! empty($actions))
                      <div class="flex items-center justify-end gap-2">
                        @foreach ($actions as $key => $action)
                          <a
                            href="{{ esc_url($action['url']) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface-raised px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-ink hover:bg-ink hover:text-surface transition-all"
                          >
                            {{ $action['name'] }}
                          </a>
                        @endforeach
                      </div>
                    @endif
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @php do_action('woocommerce_before_account_orders_pagination'); @endphp

    @if (1 < $customer_orders->max_num_pages)
      <div class="flex items-center justify-between pt-4">
        @if (1 !== $current_page)
          <a href="{{ esc_url(wc_get_endpoint_url('orders', $current_page - 1)) }}" class="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2 text-xs font-bold uppercase text-ink hover:bg-surface-raised">
            &larr; {{ __('Anterior', 'sage') }}
          </a>
        @endif

        @if (intval($customer_orders->max_num_pages) !== $current_page)
          <a href="{{ esc_url(wc_get_endpoint_url('orders', $current_page + 1)) }}" class="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2 text-xs font-bold uppercase text-ink hover:bg-surface-raised ml-auto">
            {{ __('Siguiente', 'sage') }} &rarr;
          </a>
        @endif
      </div>
    @endif

  @else
    {{-- Estado Vacío --}}
    <div class="rounded-2xl border border-dashed border-line bg-surface/50 p-10 text-center">
      <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl border border-line bg-surface text-ink-subtle">
        <x-icon name="shopping-bag" class="size-8 text-emerald-400" />
      </div>
      <h3 class="text-sm font-bold uppercase tracking-wider text-ink">
        {{ __('Aún no tienes pedidos registrados', 'sage') }}
      </h3>
      <p class="mt-2 text-xs text-ink-subtle max-w-md mx-auto leading-relaxed">
        {{ __('Cuando realices una compra de bicicletas, componentes o equipamiento de ciclismo, podrás rastrear el estado del despacho y consultar tus comprobantes aquí.', 'sage') }}
      </p>
      <div class="mt-6">
        <a
          href="{{ esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) }}"
          class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-3 text-xs font-bold uppercase tracking-widest text-surface hover:bg-neutral-200 transition-all shadow-lg"
        >
          <span>{{ __('Explorar Catálogo', 'sage') }}</span>
          <x-icon name="chevron-right" class="size-4" />
        </a>
      </div>
    </div>
  @endif
</div>

@php do_action('woocommerce_after_account_orders', $has_orders); @endphp
