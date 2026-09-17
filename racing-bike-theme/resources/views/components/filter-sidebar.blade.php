@props([
  'activeFilters' => [],
  'showTitle' => true,
])

@php
  // pa_grupo y pa_talla-cuadro se quitaron de aquí: existen como vocabulario
  // en WooCommerce > Atributos pero ningún producto los tiene asignados —
  // mostrarlos solo agregaría secciones vacías. La talla real vive en
  // pa_talla (antes repartida entre esa taxonomía y un atributo de texto
  // libre "talla"; ver scripts/migrate-custom-attributes.php).
  //
  // pa_disciplina y pa_material se quitaron por el mismo motivo el
  // 2026-09-17: auditoría de catálogo mostró que solo 1 de 63 productos
  // publicados tiene cada uno asignado (2%). Mostrar un filtro que vacía
  // la tienda en el 98% de sus opciones es peor que no mostrarlo. Se
  // reactivan cuando el catálogo tenga cobertura real (ver
  // scripts/audit-catalog.php para medirla).
  //
  // El filtro de Color usa pa_color-familia (9 familias: Azul, Rojo...),
  // no pa_color directo — pa_color tiene ~76 tonos exactos de fabricante
  // ("Halo Silver - Tanzanite (Gloss)"), correctos para la ficha de
  // producto pero inservibles como filtro (abruma, la mayoría con 1 solo
  // producto). Ver scripts/migrate-color-families.php para el mapeo.
  $taxonomies = [
    'pa_marca' => __('Marca', 'sage'),
    'pa_talla' => __('Talla', 'sage'),
    'pa_color-familia' => __('Color', 'sage'),
  ];

  // WooCommerce resetea la navegación por capas a la página 1 al cambiar
  // de filtro; nuestras propias URLs no lo hacían porque strtok() solo
  // quitaba el query string y dejaba intacto el /page/N/ del path. El
  // resultado: filtrar desde /tienda/page/3/ generaba una URL con filtro
  // que apuntaba a una página 3 que ya no existe con ese resultset.
  $currentUrl = preg_replace('#/page/\d+/?$#', '/', strtok($_SERVER['REQUEST_URI'] ?? '', '?'));
  $hasFilters = false;

  foreach (array_keys($taxonomies) as $tax) {
    if (! empty($_GET['filter_' . str_replace('pa_', '', $tax)])) {
      $hasFilters = true;
      break;
    }
  }
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

  @foreach ($taxonomies as $taxonomy => $label)
    @php
      // hide_empty siempre en true: antes pa_marca se mostraba completa
      // (Continental, PRO...) aunque 0 productos la tuvieran asignada —
      // clic en una marca fantasma y la tienda quedaba vacía sin motivo
      // visible para quien compra.
      $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
      if (is_wp_error($terms) || empty($terms)) continue;
      $paramKey = 'filter_' . str_replace('pa_', '', $taxonomy);
      $queryTypeKey = 'query_type_' . str_replace('pa_', '', $taxonomy);
      $selectedValues = isset($_GET[$paramKey]) ? explode(',', $_GET[$paramKey]) : [];
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
              href="{{ $filterUrl }}"
              role="checkbox"
              aria-checked="{{ $isChecked ? 'true' : 'false' }}"
              class="relative flex aspect-video items-center justify-center rounded-xl border px-2 py-1 transition-all {{ $isChecked ? 'border-emerald-400 bg-surface-raised/40 ring-1 ring-emerald-400/30' : 'border-line hover:border-white/20 bg-surface/20' }}"
              title="{{ $term->name }} ({{ $term->count }})"
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
              href="{{ $filterUrl }}"
              role="checkbox"
              aria-checked="{{ $isChecked ? 'true' : 'false' }}"
              class="flex items-center justify-between text-xs transition-colors {{ $isChecked ? 'font-bold text-ink' : 'text-ink-muted hover:text-ink' }}"
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
              <span class="text-[10px] text-ink-subtle">({{ $term->count }})</span>
            </a>
          @endforeach
        </div>
      @endif
    </fieldset>
  @endforeach
</div>
