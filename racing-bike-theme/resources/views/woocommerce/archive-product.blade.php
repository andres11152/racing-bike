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
  $activeFilterChips = \App\rb_active_filter_chips(\App\rb_filter_taxonomies());
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

    {{--
      La barra de herramientas y el sidebar de filtros vivían dentro de
      este @if: en cuanto un filtro devolvía 0 productos (algo frecuente,
      ver los bugs de filter-sidebar.blade.php), ambos desaparecían junto
      con el botón "Filtrar" de móvil. El usuario quedaba en un callejón
      sin salida — sin forma de ver qué había marcado ni de quitar un solo
      filtro, solo "Ver todo el catálogo" que los borra todos de un golpe.
      Ahora la barra y el sidebar siempre se muestran; solo la rejilla de
      productos cambia por el mensaje de "sin resultados".
    --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-4 md:py-6 border-b border-line mb-8" data-catalog-toolbar>
      <div class="flex items-center justify-between sm:justify-start gap-4 w-full sm:w-auto">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded border border-line px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-ink lg:hidden hover:bg-surface transition-colors"
          data-filter-open
        >
          <x-icon name="menu" class="size-4" />
          <span>{{ __('Filtrar', 'sage') }}</span>
        </button>

        <p class="text-xs uppercase tracking-widest text-ink-subtle" data-catalog-count tabindex="-1" aria-live="polite">
          {{ $total ? sprintf(_n('%s producto', '%s productos', $total, 'sage'), number_format_i18n($total)) : __('Sin resultados', 'sage') }}
        </p>
      </div>

      {{--
        Siempre se renderiza este contenedor, incluso vacío con $total=0:
        el filtro AJAX (ver initCatalogAjaxFilters en app.js) necesita un
        nodo estable que encontrar y reemplazar en cada respuesta — que
        aparezca o no según $total no puede depender de si existe en el
        HTML previo.
      --}}
      <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto" data-catalog-toolbar-actions>
        @if ($total)
          <div class="rb-woo-ordering flex-1 sm:flex-initial">
            @php(woocommerce_catalog_ordering())
          </div>

          <x-catalog-view-switcher />
        @endif
      </div>
    </div>

    {{--
      Chips de filtros activos: antes la única forma de quitar UN filtro
      era volver a abrir el sidebar (o el drawer completo en móvil) y
      desmarcarlo ahí, o usar "Limpiar todo" y perder también las demás.
      El wrapper exterior siempre se renderiza (vacío si no hay chips),
      mismo motivo que arriba: un nodo estable para el AJAX.
    --}}
    <div data-catalog-chips>
      @if (! empty($activeFilterChips))
        <div class="flex flex-wrap items-center gap-2 -mt-4 mb-8">
          @foreach ($activeFilterChips as $chip)
            <a
              href="{{ $chip['url'] }}"
              class="inline-flex items-center gap-1.5 rounded-full border border-line-strong bg-surface px-3 py-1 text-[11px] font-medium text-ink hover:border-ink transition-colors"
            >
              {{ $chip['label'] }}
              <x-icon name="close" class="size-3" />
            </a>
          @endforeach
        </div>
      @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-10 items-start">
      {{-- Sidebar de filtros en Escritorio --}}
      <aside class="hidden lg:block sticky top-24" id="catalog-sidebar-desktop">
        <x-filter-sidebar />
      </aside>

      {{-- Rejilla de productos --}}
      <div>
        @if (woocommerce_product_loop() && $total)
          <div
            id="catalog-grid-container"
            class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 md:gap-x-6 lg:gap-x-8 transition-all duration-300"
            aria-busy="false"
          >
            @while (have_posts())
              @php(the_post())
              <x-product-card :product="wc_get_product()" />
            @endwhile
          </div>
        @else
          <div id="catalog-grid-container" class="py-20 text-center" aria-busy="false">
            <p class="text-sm text-ink-muted">
              {{ __('No encontramos productos que coincidan con tus filtros.', 'sage') }}
            </p>

            <x-button variant="secondary" size="md" :href="get_permalink(wc_get_page_id('shop'))" class="mt-6">
              {{ __('Ver todo el catálogo', 'sage') }}
            </x-button>
          </div>
        @endif

        {{--
          Wrapper de paginación siempre presente (vacío si no hay
          resultados): mismo motivo que los demás nodos "estables" de esta
          vista — el AJAX necesita encontrarlo exista o no haya páginas.
        --}}
        <div id="catalog-pagination" class="mt-14">
          @if ($total)
            <div class="rb-woo-pagination">
              @php(do_action('woocommerce_after_shop_loop'))
            </div>
          @endif
        </div>
      </div>
    </div>
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

      <div id="catalog-sidebar-mobile">
        <x-filter-sidebar :show-title="false" />
      </div>
    </div>
  </div>


  @php(do_action('woocommerce_after_main_content'))
@endsection
