{{--
  Plantilla de Descargas de Mi Cuenta.
--}}

@php
  if (! defined('ABSPATH')) exit;
  $downloads     = $downloads ?? (function_exists('WC') && WC()->customer ? WC()->customer->get_downloadable_products() : []);
  $has_downloads = $has_downloads ?? (bool) $downloads;
@endphp

@php do_action('woocommerce_before_account_downloads', $has_downloads); @endphp

<div class="space-y-6">
  {{-- Encabezado --}}
  <div class="border-b border-line pb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <span class="text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
        {{ __('Material Digital', 'sage') }}
      </span>
      <h2 class="mt-1 text-xl font-bold uppercase tracking-wider text-ink md:text-2xl">
        {{ __('Mis Descargas', 'sage') }}
      </h2>
    </div>
    <p class="text-xs text-ink-subtle">
      {{ __('Accede a tus manuales, certificados y productos digitales.', 'sage') }}
    </p>
  </div>

  @if ($has_downloads)
    @php do_action('woocommerce_before_available_downloads'); @endphp
    @php do_action('woocommerce_available_downloads', $downloads); @endphp
    @php do_action('woocommerce_after_available_downloads'); @endphp
  @else
    {{-- Estado Vacío --}}
    <div class="rounded-2xl border border-dashed border-line bg-surface/50 p-10 text-center">
      <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl border border-line bg-surface text-ink-subtle">
        <x-icon name="sparkles" class="size-8 text-emerald-400" />
      </div>
      <h3 class="text-sm font-bold uppercase tracking-wider text-ink">
        {{ __('No tienes descargas disponibles', 'sage') }}
      </h3>
      <p class="mt-2 text-xs text-ink-subtle max-w-md mx-auto leading-relaxed">
        {{ __('Aquí aparecerán los manuales de ensamble, garantías digitales o contenidos descargables de tus bicicletas adquiridas.', 'sage') }}
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

@php do_action('woocommerce_after_account_downloads', $has_downloads); @endphp
