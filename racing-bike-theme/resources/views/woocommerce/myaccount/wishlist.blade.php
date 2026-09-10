{{--
  Plantilla de Lista de Deseos de Mi Cuenta.
  Los productos vienen ya resueltos (y filtrados a publicados) desde
  fiveam_wishlist_get() en el plugin Skycode Wishlist.
--}}

@php
  if (! defined('ABSPATH')) exit;
  $products = $products ?? [];
@endphp

<div class="space-y-6">
  {{-- Encabezado --}}
  <div class="border-b border-line pb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <span class="text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
        {{ __('Guardados', 'sage') }}
      </span>
      <h2 class="mt-1 text-xl font-bold uppercase tracking-wider text-ink md:text-2xl">
        {{ __('Mi Lista de Deseos', 'sage') }}
      </h2>
    </div>
    <p class="text-xs text-ink-subtle">
      {{ __('Los productos que guardaste para revisar más tarde.', 'sage') }}
    </p>
  </div>

  @if (! empty($products))
    <div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-6 lg:gap-x-8">
      @foreach ($products as $product)
        <x-product-card :product="$product" list-id="my_account_wishlist" list-name="Mi Lista de Deseos" :position="$loop->index" />
      @endforeach
    </div>
  @else
    {{-- Estado Vacío --}}
    <div class="rounded-2xl border border-dashed border-line bg-surface/50 p-10 text-center">
      <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl border border-line bg-surface text-ink-subtle">
        <x-icon name="heart" class="size-8 text-emerald-400" />
      </div>
      <h3 class="text-sm font-bold uppercase tracking-wider text-ink">
        {{ __('Tu lista de deseos está vacía', 'sage') }}
      </h3>
      <p class="mt-2 text-xs text-ink-subtle max-w-md mx-auto leading-relaxed">
        {{ __('Toca el corazón en cualquier producto del catálogo para guardarlo aquí.', 'sage') }}
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
