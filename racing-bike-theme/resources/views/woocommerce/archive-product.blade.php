{{--
  Archivo de productos (tienda y categorías).

  Reemplaza el bucle por hooks de WooCommerce por un render directo con el
  componente `product-card`. Se conservan los hooks que otros plugins usan
  para inyectar contenido, pero la maquetación es nuestra.
--}}

@extends('layouts.app')

@php
  $breadcrumbs = [['label' => __('Inicio', 'sage'), 'href' => home_url('/')]];

  if (is_product_category()) {
    $breadcrumbs[] = ['label' => __('Tienda', 'sage'), 'href' => get_permalink(wc_get_page_id('shop'))];
  }

  $breadcrumbs[] = ['label' => wp_strip_all_tags(woocommerce_page_title(false))];

  $total = (int) wc_get_loop_prop('total');
@endphp

@section('content')
  @php(do_action('woocommerce_before_main_content'))

  <div class="rb-container py-8 md:py-12">
    <x-breadcrumbs :items="$breadcrumbs" class="mb-6" />

    @php(woocommerce_output_all_notices())

    <header class="border-b border-line pb-6">
      @if (apply_filters('woocommerce_show_page_title', true))
        <h1 class="text-2xl font-bold uppercase tracking-widest text-ink md:text-3xl">
          {!! woocommerce_page_title(false) !!}
        </h1>
      @endif

      @if ($description = get_the_archive_description())
        <div class="mt-3 max-w-2xl text-sm text-ink-muted">{!! $description !!}</div>
      @endif
    </header>

    @if (woocommerce_product_loop() && $total)
      {{-- Barra de herramientas --}}
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-4 md:py-6 border-b border-line mb-8">
        <div class="flex items-center justify-between sm:justify-start gap-4 w-full sm:w-auto">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded border border-line px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-ink lg:hidden hover:bg-surface transition-colors"
            data-filter-open
          >
            <x-icon name="menu" class="size-4" />
            <span>{{ __('Filtrar', 'sage') }}</span>
          </button>

          <p class="text-xs uppercase tracking-widest text-ink-subtle">
            {{ sprintf(_n('%s producto', '%s productos', $total, 'sage'), number_format_i18n($total)) }}
          </p>
        </div>

        <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
          <div class="rb-woo-ordering flex-1 sm:flex-initial">
            @php(woocommerce_catalog_ordering())
          </div>

          <x-catalog-view-switcher />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-10 items-start">
        {{-- Sidebar de filtros en Escritorio --}}
        <aside class="hidden lg:block sticky top-24">
          <x-filter-sidebar />
        </aside>

        {{-- Rejilla de productos --}}
        <div>
          <div
            id="catalog-grid-container"
            class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-6 lg:gap-x-8 transition-all duration-300"
          >
            @while (have_posts())
              @php(the_post())
              <x-product-card :product="wc_get_product()" />
            @endwhile
          </div>

          <div class="rb-woo-pagination mt-14">
            @php(do_action('woocommerce_after_shop_loop'))
          </div>
        </div>
      </div>
    @else
      <div class="py-20 text-center">
        <p class="text-sm text-ink-muted">
          {{ __('No encontramos productos que coincidan con tus filtros.', 'sage') }}
        </p>

        <x-button variant="secondary" size="md" :href="get_permalink(wc_get_page_id('shop'))" class="mt-6">
          {{ __('Ver todo el catálogo', 'sage') }}
        </x-button>
      </div>
    @endif
  </div>

  {{-- Drawer de filtros para móvil --}}
  <div class="drawer z-50 lg:hidden" data-filter-drawer data-open="false" role="dialog" aria-modal="true" aria-label="{{ __('Filtros de catálogo', 'sage') }}">
    <div class="absolute inset-0 bg-surface/70" data-filter-close></div>

    <div class="drawer-panel absolute inset-y-0 left-0 flex w-full max-w-xs -translate-x-full flex-col bg-surface-raised transition-transform duration-300 ease-out p-6 overflow-y-auto">
      <div class="flex items-center justify-between border-b border-line pb-4 mb-6">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-ink">{{ __('Filtros', 'sage') }}</h2>
        <button type="button" class="text-ink-muted transition-colors hover:text-ink" data-filter-close aria-label="{{ __('Cerrar filtros', 'sage') }}">
          <x-icon name="close" class="size-5" />
        </button>
      </div>

      <x-filter-sidebar :show-title="false" />
    </div>
  </div>


  @php(do_action('woocommerce_after_main_content'))

  {{-- Script de Alternancia de Vista (Cuadrícula vs Compacta vs Lista) --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const container = document.getElementById('catalog-grid-container');
      const switcher = document.querySelector('[data-catalog-view-switcher]');
      if (!container || !switcher) return;

      const gridBtn = switcher.querySelector('[data-view-btn="grid"]');
      const compactBtn = switcher.querySelector('[data-view-btn="compact"]');

      const setViewMode = (mode) => {
        if (gridBtn) {
          gridBtn.setAttribute('data-active', mode === 'grid' ? 'true' : 'false');
          gridBtn.dataset.active = mode === 'grid' ? 'true' : 'false';
        }
        if (compactBtn) {
          compactBtn.setAttribute('data-active', mode === 'compact' ? 'true' : 'false');
          compactBtn.dataset.active = mode === 'compact' ? 'true' : 'false';
        }

        container.classList.remove('view-mode-grid', 'view-mode-compact', 'view-mode-list');
        if (mode === 'compact') {
          container.classList.add('view-mode-compact');
        } else {
          container.classList.add('view-mode-grid');
        }
        localStorage.setItem('rb_catalog_view_mode', mode);
      };

      // En mobile (< 768px), la 2da vista (compact) es la predeterminada obligatoria
      const isMobile = window.innerWidth < 768;
      const defaultMode = isMobile ? 'compact' : 'grid';
      const savedMode = localStorage.getItem('rb_catalog_view_mode') || defaultMode;
      setViewMode(savedMode);

      if (gridBtn) gridBtn.addEventListener('click', () => setViewMode('grid'));
      if (compactBtn) compactBtn.addEventListener('click', () => setViewMode('compact'));
    });
  </script>
@endsection
