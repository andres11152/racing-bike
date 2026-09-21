@props([
  'activeFilters' => [],
  'showTitle' => true,
])

@php
  // Lista de taxonomías centralizada en app/catalog-filters.php: la
  // usan tanto este sidebar como las chips de filtros activos en
  // archive-product.blade.php.
  $taxonomies = \App\rb_filter_taxonomies();

  // WooCommerce resetea la navegación por capas a la página 1 al cambiar
  // de filtro; nuestras propias URLs no lo hacían porque strtok() solo
  // quitaba el query string y dejaba intacto el /page/N/ del path. El
  // resultado: filtrar desde /tienda/page/3/ generaba una URL con filtro
  // que apuntaba a una página 3 que ya no existe con ese resultset.
  $currentUrl = preg_replace('#/page/\d+/?$#', '/', strtok($_SERVER['REQUEST_URI'] ?? '', '?'));
  $hasFilters = ! empty($_GET['min_price']) || ! empty($_GET['max_price']);

  foreach (array_keys($taxonomies) as $tax) {
    if (! empty($_GET['filter_' . str_replace('pa_', '', $tax)])) {
      $hasFilters = true;
      break;
    }
  }

  $priceBounds = \App\rb_catalog_price_bounds();
  $priceHistogram = \App\rb_catalog_price_histogram();
  $pricePresets = \App\rb_catalog_price_presets();
  $priceBoundMin = (int) $priceBounds['min'];
  $priceBoundMax = (int) $priceBounds['max'];
  $minPrice = isset($_GET['min_price']) ? max($priceBoundMin, (int) $_GET['min_price']) : $priceBoundMin;
  $maxPrice = isset($_GET['max_price']) ? min($priceBoundMax, (int) $_GET['max_price']) : $priceBoundMax;
  // ~150 pasos a lo largo de todo el rango, redondeado a un múltiplo de
  // 1.000 legible — con un catálogo de $45.000 a $13M, arrastrar el
  // slider en pasos de a $1 se sentiría infinito y en pasos de $100.000
  // se sentiría tosco.
  $priceStep = max(1000, (int) round((($priceBoundMax - $priceBoundMin) / 150) / 1000) * 1000);
@endphp

<div class="space-y-8">
  @if ($showTitle || $hasFilters)
    <div class="flex items-center justify-between border-b border-line pb-4">
      @if ($showTitle)
        <h2 class="text-xs font-semibold uppercase tracking-widest text-ink">{{ __('Filtros', 'sage') }}</h2>
      @endif
      @if ($hasFilters)
        <a href="{{ $currentUrl }}" class="text-xs font-medium text-ink-subtle hover:text-ink underline transition-colors">
          {{ __('Limpiar todo', 'sage') }}
        </a>
      @endif
    </div>
  @endif

  {{--
    Filtro de precio: WooCommerce ya sabe leer min_price/max_price en la
    consulta principal de la tienda (es como funciona su propio widget de
    precio nativo) — solo hacía falta un control mejor que dos campos de
    texto sueltos. Slider doble con el histograma real de precios detrás
    (para ver dónde se concentra el catálogo antes de mover nada) y 3
    tramos de un clic calculados sobre esos mismos precios. Los demás
    filtros activos van como campos ocultos para no perderse al enviar
    este formulario, que solo trae sus propios dos campos en el GET.
  --}}
  @if ($priceBoundMax > 0 && $priceBoundMax > $priceBoundMin)
    <fieldset class="space-y-3 border-0 p-0 m-0">
      <legend class="text-xs font-bold uppercase tracking-wider text-ink p-0">{{ __('Precio', 'sage') }}</legend>

      <form
        method="get"
        action="{{ $currentUrl }}"
        class="space-y-3"
        data-price-filter-form
      >
        @foreach ($_GET as $key => $value)
          @continue(in_array($key, ['min_price', 'max_price', 'paged'], true) || is_array($value))
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        {{-- Valores actuales: lo primero que se lee, grande y en vivo. --}}
        <div class="flex items-center justify-between text-sm font-bold text-ink tabular-nums">
          <span data-price-min-label>{{ \App\rb_format_cop($minPrice) }}</span>
          <span class="text-[10px] font-normal text-ink-subtle" aria-hidden="true">—</span>
          <span data-price-max-label>{{ \App\rb_format_cop($maxPrice) }}</span>
        </div>

        <div class="pt-1" data-price-slider>
          {{-- Histograma: dónde se concentra el catálogo, antes de arrastrar nada. --}}
          <div class="mb-1.5 flex h-7 items-end gap-px" aria-hidden="true">
            @foreach ($priceHistogram as $barHeight)
              <div class="min-h-[3px] flex-1 rounded-[1px] bg-line-strong/70" style="height: {{ max((int) $barHeight, 6) }}%"></div>
            @endforeach
          </div>

          {{-- Track + relleno + los dos tiradores, superpuestos. --}}
          <div class="relative h-5">
            <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-line-strong"></div>
            <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-emerald-400" data-price-fill></div>

            <input
              type="range"
              name="min_price"
              class="rb-range"
              min="{{ $priceBoundMin }}"
              max="{{ $priceBoundMax }}"
              step="{{ $priceStep }}"
              value="{{ $minPrice }}"
              data-price-min-range
              aria-label="{{ __('Precio mínimo', 'sage') }}"
            >
            <input
              type="range"
              name="max_price"
              class="rb-range"
              min="{{ $priceBoundMin }}"
              max="{{ $priceBoundMax }}"
              step="{{ $priceStep }}"
              value="{{ $maxPrice }}"
              data-price-max-range
              aria-label="{{ __('Precio máximo', 'sage') }}"
            >
          </div>
        </div>

        {{-- Tramos de un clic, calculados sobre los precios reales del catálogo. --}}
        @if ($pricePresets)
          <div class="flex flex-wrap gap-1.5">
            @foreach ($pricePresets as $preset)
              <button
                type="button"
                class="rounded-full border border-line-strong px-2.5 py-1 text-[10px] font-semibold text-ink-muted transition-colors hover:border-ink hover:text-ink cursor-pointer"
                data-price-preset
                data-preset-min="{{ $preset['min'] }}"
                data-preset-max="{{ $preset['max'] }}"
              >
                {{ $preset['label'] }}
              </button>
            @endforeach
          </div>
        @endif

        {{--
          Envío normal como respaldo sin JS (el slider sigue funcionando
          con las flechas del teclado y este botón aplica el valor). Con
          JS, initPriceSlider() ya manda el formulario solo al soltar
          cada tirador o al tocar un tramo — este botón rara vez hace
          falta, pero se deja visible en vez de ocultarlo con JS para no
          depender de que el script cargue a tiempo.
        --}}
        <button
          type="submit"
          class="w-full rounded-full border border-line-strong py-1.5 text-[10px] font-bold uppercase tracking-widest text-ink-muted transition-colors hover:border-ink hover:text-ink cursor-pointer"
        >
          {{ __('Aplicar', 'sage') }}
        </button>
      </form>
    </fieldset>
  @endif

  @foreach ($taxonomies as $taxonomy => $label)
    @php
      // hide_empty siempre en true: antes pa_marca se mostraba completa
      // aunque 0 productos la tuvieran asignada.
      $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
      if (is_wp_error($terms) || empty($terms)) continue;

      if ($taxonomy === 'pa_talla') {
        $terms = \App\rb_sort_size_terms($terms);
      } elseif ($taxonomy === 'pa_longitud-de-biela') {
        $terms = \App\rb_sort_crank_length_terms($terms);
      }

      $paramKey = 'filter_' . str_replace('pa_', '', $taxonomy);
      $queryTypeKey = 'query_type_' . str_replace('pa_', '', $taxonomy);
      $selectedValues = isset($_GET[$paramKey]) ? array_filter(explode(',', (string) $_GET[$paramKey])) : [];

      // Conteo contextual: cuántos productos del resultado ACTUAL tendría
      // cada término si se marcara, no el conteo global de todo el catálogo.
      $termCounts = \App\rb_layered_nav_term_counts($taxonomy);

      // Si no hay ningún producto en el contexto actual con esta taxonomía y no hay nada seleccionado,
      // no mostrar este grupo de filtro (ej: tallas de bicicleta en grupos/simuladores, longitud de biela en bicicletas/cascos).
      if (empty($selectedValues) && array_sum($termCounts) === 0) {
        continue;
      }

      // Filtrar términos visibles: solo mostrar aquellos que tienen productos disponibles (> 0)
      // o que ya están seleccionados por el usuario para poder verlos y desmarcarlos.
      // Esto elimina tallas de bicicletas en cascos, tallas de ruta en MTB, etc.
      $visibleTerms = array_filter($terms, function ($term) use ($selectedValues, $termCounts) {
        $isChecked = in_array($term->slug, $selectedValues, true);
        $count = $termCounts[$term->slug] ?? 0;
        return $isChecked || $count > 0;
      });

      if (empty($visibleTerms)) {
        continue;
      }
    @endphp

    {{--
      <fieldset> en vez de un <div> + <h3>: cada grupo de filtro es
      semánticamente un conjunto de casillas relacionadas, y sin agrupar
      un lector de pantalla anunciaba una lista plana de enlaces sin decir
      a qué pertenecía cada uno.
    --}}
    <fieldset class="space-y-3 border-0 p-0 m-0">
      <legend class="text-xs font-bold uppercase tracking-wider text-ink p-0">{{ $label }}</legend>

      {{-- Lista de Checkbox Tradicional para todas las taxonomías (Marca, Talla, Longitud de biela, Color) --}}
      <div class="space-y-2">
        @foreach ($visibleTerms as $term)
          @php
            $isChecked = in_array($term->slug, $selectedValues);
            $count = $termCounts[$term->slug] ?? 0;
            $isDisabled = ! $isChecked && $count === 0;

            $newValues = $isChecked
              ? array_diff($selectedValues, [$term->slug])
              : array_merge($selectedValues, [$term->slug]);

            $queryParams = $_GET;
            unset($queryParams['paged']); // volver a la página 1 al cambiar de filtro

            if (! empty($newValues)) {
              $queryParams[$paramKey] = implode(',', $newValues);
              // query_type=or hace que baste con cualquiera de los valores marcados
              $queryParams[$queryTypeKey] = 'or';
            } else {
              unset($queryParams[$paramKey], $queryParams[$queryTypeKey]);
            }

            $filterUrl = $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : '');
          @endphp

          <a
            @if (! $isDisabled) href="{{ $filterUrl }}" @endif
            role="checkbox"
            aria-checked="{{ $isChecked ? 'true' : 'false' }}"
            @if ($isDisabled) aria-disabled="true" @endif
            class="flex items-center justify-between text-xs transition-colors {{ $isChecked ? 'font-bold text-ink' : 'text-ink-muted hover:text-ink' }} {{ $isDisabled ? 'opacity-30 pointer-events-none cursor-not-allowed' : '' }}"
          >
            <span class="flex items-center gap-2">
              <span class="size-3.5 rounded border flex items-center justify-center transition-colors {{ $isChecked ? 'border-ink bg-ink text-surface' : 'border-line-strong bg-surface' }}">
                @if ($isChecked)
                  <svg class="size-2.5 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                @endif
              </span>
              <span>{{ $term->name }}</span>
            </span>
            <span class="text-[10px] text-ink-subtle">({{ $count }})</span>
          </a>
        @endforeach
      </div>
    </fieldset>
  @endforeach
</div>
