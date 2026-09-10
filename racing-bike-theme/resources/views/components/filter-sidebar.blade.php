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
  // El filtro de Color usa pa_color-familia (9 familias: Azul, Rojo...),
  // no pa_color directo — pa_color tiene ~76 tonos exactos de fabricante
  // ("Halo Silver - Tanzanite (Gloss)"), correctos para la ficha de
  // producto pero inservibles como filtro (abruma, la mayoría con 1 solo
  // producto). Ver scripts/migrate-color-families.php para el mapeo.
  $taxonomies = [
    'pa_marca' => __('Marca', 'sage'),
    'pa_disciplina' => __('Disciplina', 'sage'),
    'pa_material' => __('Material', 'sage'),
    'pa_talla' => __('Talla', 'sage'),
    'pa_color-familia' => __('Color', 'sage'),
  ];

  $currentUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
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
      $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => ($taxonomy !== 'pa_marca')]);
      if (is_wp_error($terms) || empty($terms)) continue;
      $paramKey = 'filter_' . str_replace('pa_', '', $taxonomy);
      $selectedValues = isset($_GET[$paramKey]) ? explode(',', $_GET[$paramKey]) : [];
    @endphp

    <div class="space-y-3">
      <h3 class="text-xs font-bold uppercase tracking-wider text-ink">{{ $label }}</h3>

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
              if (! empty($newValues)) {
                $queryParams[$paramKey] = implode(',', $newValues);
              } else {
                unset($queryParams[$paramKey]);
              }

              $filterUrl = $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : '');

              // Logo real de la marca, editable en WooCommerce > Atributos >
              // Marca (term meta, ver app/product-brands.php).
              $logoUrl = \App\brand_logo_url($term);
            @endphp

            <a
              href="{{ $filterUrl }}"
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
              if (! empty($newValues)) {
                $queryParams[$paramKey] = implode(',', $newValues);
              } else {
                unset($queryParams[$paramKey]);
              }

              $filterUrl = $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : '');
            @endphp

            <a
              href="{{ $filterUrl }}"
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
    </div>
  @endforeach
</div>
