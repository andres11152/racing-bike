{{--
  Plantilla del formulario para editar direcciones de Facturación y Envío.
--}}

@php
  if (! defined('ABSPATH')) exit;

  $isBilling = ($load_address === 'billing');
  $pageTitle = $isBilling ? __('Dirección de Facturación', 'sage') : __('Dirección de Envío', 'sage');
  $pageDesc  = $isBilling
    ? __('Actualiza tus datos para la emisión de facturas electrónicas y recibos de pago en Colombia.', 'sage')
    : __('Indica la dirección exacta y el departamento donde deseas recibir tus paquetes y bicicletas.', 'sage');
@endphp

@php do_action('woocommerce_before_edit_account_address_form'); @endphp

@if (! $load_address)
  @include('woocommerce.myaccount.my-address')
@else
  <div class="space-y-6">
    {{-- Botón de regreso --}}
    <div>
      <a
        href="{{ esc_url(wc_get_account_endpoint_url('edit-address')) }}"
        class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-ink-subtle hover:text-ink transition-colors"
      >
        <x-icon name="arrow-left" class="size-4" />
        <span>{{ __('Volver a Libreta de Direcciones', 'sage') }}</span>
      </a>
    </div>

    {{-- Encabezado del Formulario --}}
    <div class="border-b border-line pb-5">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-xl bg-surface border border-line text-emerald-400">
          @if ($isBilling)
            <x-icon name="document-text" class="size-5" />
          @else
            <x-icon name="truck" class="size-5" />
          @endif
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-400">
            {{ $isBilling ? __('Datos Tributarios', 'sage') : __('Logística de Entrega', 'sage') }}
          </span>
          <h2 class="text-xl font-bold uppercase tracking-wider text-ink">
            {{ sprintf(__('Editar %s', 'sage'), $pageTitle) }}
          </h2>
        </div>
      </div>
      <p class="mt-2 text-xs text-ink-muted leading-relaxed">
        {{ $pageDesc }}
      </p>
    </div>

    {{-- Formulario nativo de WooCommerce adaptado --}}
    <form method="post" novalidate class="space-y-6 rb-edit-address-form">
      <div class="woocommerce-address-fields">
        @php do_action("woocommerce_before_edit_address_form_{$load_address}"); @endphp

        <div class="woocommerce-address-fields__field-wrapper grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1">
          @foreach ($address as $key => $field)
            @php
              woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value'] ?? ''));
            @endphp
          @endforeach
        </div>

        @php do_action("woocommerce_after_edit_address_form_{$load_address}"); @endphp

        <div class="mt-8 pt-6 border-t border-line flex flex-wrap items-center justify-between gap-4">
          <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-ink px-8 py-3.5 text-xs font-bold uppercase tracking-widest text-surface hover:bg-neutral-200 transition-all cursor-pointer shadow-xl"
            name="save_address"
            value="{{ esc_attr__('Guardar dirección', 'woocommerce') }}"
          >
            <x-icon name="check" class="size-4 text-surface" />
            <span>{{ __('Guardar Dirección', 'sage') }}</span>
          </button>

          <a
            href="{{ esc_url(wc_get_account_endpoint_url('edit-address')) }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-line bg-surface px-6 py-3.5 text-xs font-bold uppercase tracking-wider text-ink-subtle hover:text-ink hover:border-line-strong transition-all"
          >
            {{ __('Cancelar', 'sage') }}
          </a>

          @php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); @endphp
          <input type="hidden" name="action" value="edit_address" />
        </div>
      </div>
    </form>
  </div>
@endif

@php do_action('woocommerce_after_edit_account_address_form'); @endphp
