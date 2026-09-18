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
  $minPrice = isset($_GET['min_price']) ? max(0, (int) $_GET['min_price']) : null;
  $maxPrice = isset($_GET['max_price']) ? max(0, (int) $_GET['max_price']) : null;
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
    precio nativo) — solo faltaba un formulario que los mande. Los demás
    filtros activos van como campos ocultos para no perderse al enviar
    este formulario, que solo trae sus propios dos campos en el GET.
  --}}
  @if ($priceBounds['max'] > 0)
    <fieldset class="space-y-3 border-0 p-0 m-0">
      <legend class="text-xs font-bold uppercase tracking-wider text-ink p-0">{{ __('Precio', 'sage') }}</legend>

      <form method="get" action="{{ $currentUrl }}" class="flex items-center gap-2">
        @foreach ($_GET as $key => $value)
          @continue(in_array($key, ['min_price', 'max_price', 'paged'], true) || is_array($value))
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <label class="sr-only" for="rb-min-price">{{ __('Precio mínimo', 'sage') }}</label>
        <input
          id="rb-min-price"
          type="number"
          name="min_price"
          min="0"
          step="1000"
          inputmode="numeric"
          placeholder="{{ number_format_i18n((int) $priceBounds['min']) }}"
          value="{{ $minPrice }}"
          class="w-full min-w-0 rounded border border-line-strong bg-surface px-2 py-1.5 text-xs text-ink"
        >
        <span class="text-ink-subtle text-xs" aria-hidden="true">–</span>
        <label class="sr-only" for="rb-max-price">{{ __('Precio máximo', 'sage') }}</label>
        <input
          id="rb-max-price"
          type="number"
          name="max_price"
          min="0"
          step="1000"
          inputmode="numeric"
          placeholder="{{ number_format_i18n((int) $priceBounds['max']) }}"
          value="{{ $maxPrice }}"
          class="w-full min-w-0 rounded border border-line-strong bg-surface px-2 py-1.5 text-xs text-ink"
        >
        <button
          type="submit"
          class="shrink-0 rounded border border-line-strong px-2.5 py-1.5 text-ink hover:bg-surface-muted transition-colors"
          aria-label="{{ __('Aplicar filtro de precio', 'sage') }}"
        >
          <x-icon name="chevron-right" class="size-3.5" />
        </button>
      </form>
    </fieldset>
  @endif

  @foreach ($taxonomies as $taxonomy => $label)
    @php
      // hide_empty siempre en true: antes pa_marca se mostraba completa
      // (Continental, PRO...) aunque 0 productos la tuvieran asignada —
      // clic en una marca fantasma y la tienda quedaba vacía sin motivo
      // visible para quien compra.
      $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
      if (is_wp_error($terms) || empty($terms)) continue;

      if ($taxonomy === 'pa_talla') {
        $terms = \App\rb_sort_size_terms($terms);
      }

      $paramKey = 'filter_' . str_replace('pa_', '', $taxonomy);
      $queryTypeKey = 'query_type_' . str_replace('pa_', '', $taxonomy);
      $selectedValues = isset($_GET[$paramKey]) ? explode(',', $_GET[$paramKey]) : [];

      // Conteo contextual: cuántos productos del resultado ACTUAL tendría
      // cada término si se marcara, no el conteo global de todo el
      // catálogo (ver app/catalog-filters.php — $term->count es global y
      // llevaba a marcar opciones que vaciaban la tienda).
      $termCounts = \App\rb_layered_nav_term_counts($taxonomy);
    @endphp

    {{--
      <fieldset> en vez de un <div> + <h3>: cada grupo de filtro es
      semánticamente un conjunto de casillas relacionadas, y sin agrupar
      un lector de pantalla anunciaba una lista plana de enlaces sin decir
      a qué pertenecía cada uno.
    --}}
    <fieldset class="space-y-3 border-0 p-0 m-0">
      <legend class="text-xs font-bold uppercase tracking-wider text-ink p-0">{{ $label }}</legend>

      @if ($taxonomy === 'pa_marca')
        {{-- Cuadrícula visual de logotipos de marcas --}}
        <div class="grid grid-cols-3 gap-2">
          @foreach ($terms as $term)
            @php
              $isChecked = in_array($term->slug, $selectedValues);
              $count = $termCounts[$term->slug] ?? 0;
              // Deshabilitada (no oculta) si marcarla dejaría la tienda
              // vacía: oculta, el visitante no entiende por qué la opción
              // desapareció; deshabilitada y visible, entiende que existe
              // pero no combina con lo que ya tiene marcado.
              $isDisabled = ! $isChecked && $count === 0;

              $newValues = $isChecked
                ? array_diff($selectedValues, [$term->slug])
                : array_merge($selectedValues, [$term->slug]);

              $queryParams = $_GET;
              unset($queryParams['paged']); // volver a la página 1 al cambiar de filtro

              if (! empty($newValues)) {
                $queryParams[$paramKey] = implode(',', $newValues);
                // WooCommerce filtra en AND entre valores de un mismo
                // atributo por defecto: marcar "Shimano" + "GW" devolvía 0
                // productos porque ninguno tiene las dos marcas a la vez.
                // query_type=or hace que baste con cualquiera de los
                // valores marcados, que es el comportamiento esperado de
                // una casilla de selección múltiple.
                $queryParams[$queryTypeKey] = 'or';
              } else {
                unset($queryParams[$paramKey], $queryParams[$queryTypeKey]);
              }

              $filterUrl = $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : '');

              // Logo real de la marca, editable en WooCommerce > Atributos >
              // Marca (term meta, ver app/product-brands.php).
              $logoUrl = \App\brand_logo_url($term);
            @endphp

            <a
              @if (! $isDisabled) href="{{ $filterUrl }}" @endif
              role="checkbox"
              aria-checked="{{ $isChecked ? 'true' : 'false' }}"
              @if ($isDisabled) aria-disabled="true" @endif
              class="relative flex aspect-video items-center justify-center rounded-xl border px-2 py-1 transition-all {{ $isChecked ? 'border-emerald-400 bg-surface-raised/40 ring-1 ring-emerald-400/30' : 'border-line hover:border-white/20 bg-surface/20' }} {{ $isDisabled ? 'opacity-30 pointer-events-none cursor-not-allowed' : '' }}"
              title="{{ $term->name }} ({{ $count }})"
            >
              @if ($logoUrl)
                <img
                  src="{{ $logoUrl }}"
                  alt="{{ $term->name }}"
                  class="h-full max-h-10 w-auto object-contain transition-all {{ $isChecked ? 'filter-none opacity-100 scale-105' : 'brand-logo-white-green' }}"
                >
              @else
                <span class="text-[10px] font-bold uppercase tracking-wider {{ $isChecked ? 'text-emerald-400' : 'text-ink-muted' }}">
                  {{ $term->name }}
                </span>
              @endif

              @if ($isChecked)
                <span class="absolute top-1 right-1 flex size-2.5 items-center justify-center rounded-full bg-emerald-400 text-[8px] text-[#0A0A0B] font-bold">
                  ✓
                </span>
              @endif
            </a>
          @endforeach
        </div>
      @else
        {{-- Lista de Checkbox Tradicional para otras taxonomías --}}
        <div class="space-y-2">
          @foreach ($terms as $term)
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
                // WooCommerce filtra en AND entre valores de un mismo
                // atributo por defecto: marcar "Shimano" + "GW" devolvía 0
                // productos porque ninguno tiene las dos marcas a la vez.
                // query_type=or hace que baste con cualquiera de los
                // valores marcados, que es el comportamiento esperado de
                // una casilla de selección múltiple.
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
      @endif
    </fieldset>
  @endforeach
</div>
