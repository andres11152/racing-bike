{{-- Componente de Búsqueda Predictiva Enterprise de Alta Conversión --}}
@php
  $popularProducts = [];
  if (function_exists('wc_get_products')) {
      $queryProducts = wc_get_products([
          'status' => 'publish',
          'limit' => 6,
          'visibility' => 'visible',
          'orderby' => 'date',
          'order' => 'DESC',
      ]);
      foreach ($queryProducts as $p) {
          if (! $p || ! $p->is_visible()) continue;
          if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) continue;
          $popularProducts[] = $p;
          if (count($popularProducts) >= 4) break;
      }
  }
@endphp

<div
  class="fixed inset-0 z-[250] hidden items-start justify-center bg-black/85 backdrop-blur-2xl p-3 sm:p-6 md:p-10 transition-all duration-300"
  data-search-overlay
  role="dialog"
  aria-modal="true"
  aria-label="{{ __('Buscador inteligente de productos', 'sage') }}"
>
  <div class="relative w-full max-w-3xl rounded-3xl bg-[#0B0C10] border border-white/10 shadow-[0_30px_90px_rgba(0,0,0,0.95)] overflow-hidden text-ink animate-fade-in mt-4 md:mt-12 max-h-[90vh] flex flex-col">
    
    {{-- Brillos ambientales de marca esmeralda --}}
    <div class="absolute -right-20 -top-20 -z-10 size-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -left-20 -bottom-20 -z-10 size-64 rounded-full bg-emerald-500/5 blur-3xl pointer-events-none"></div>

    {{-- Top Bar / Header --}}
    <div class="flex items-center justify-between px-5 md:px-7 pt-5 pb-3 border-b border-white/10 shrink-0">
      <div class="flex items-center gap-2.5">
        <span class="flex size-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
          {{ __('Búsqueda Inteligente', 'sage') }}
        </span>
        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/5 border border-white/10 text-ink-subtle">
          Catálogo Racing Bike
        </span>
      </div>
      <div class="flex items-center gap-2">
        <kbd class="hidden md:inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono rounded bg-white/5 border border-white/10 text-ink-subtle">
          <span>ESC</span> {{ __('para salir', 'sage') }}
        </kbd>
        <button
          type="button"
          class="flex size-8 items-center justify-center rounded-full bg-surface-raised text-ink-subtle hover:text-white border border-line hover:border-white/30 transition-all cursor-pointer"
          data-search-close
          aria-label="{{ __('Cerrar búsqueda', 'sage') }}"
        >
          <x-icon name="close" class="size-4" />
        </button>
      </div>
    </div>

    {{-- Input Bar Principal --}}
    <div class="p-4 md:px-7 pb-3 border-b border-white/10 shrink-0 bg-[#0B0C10]/95 sticky top-0 z-10">
      <form role="search" method="get" class="relative" action="{{ home_url('/') }}" data-search-form>
        <input type="hidden" name="post_type" value="product">
        
        {{-- Icono Lupa --}}
        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-subtle pointer-events-none flex items-center justify-center">
          <x-icon name="search" class="size-5 text-emerald-400/90" />
        </div>

        {{-- Input text con estética enterprise --}}
        <input
          type="search"
          name="s"
          placeholder="{{ __('Buscar bicicletas, marcas, grupos, cascos... (ej. Orbea, Trek, Shimano)', 'sage') }}"
          class="w-full bg-[#13151C] border border-white/15 focus:border-emerald-500 rounded-2xl py-3.5 md:py-4 pl-12 pr-28 text-white text-base md:text-lg placeholder-ink-subtle outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-inner"
          data-search-input
          autocomplete="off"
          spellcheck="false"
          required
        >

        {{-- Controles laterales dentro del input --}}
        <div class="absolute right-3.5 top-1/2 -translate-y-1/2 flex items-center gap-2">
          {{-- Spinner de carga AJAX --}}
          <div data-search-spinner class="hidden">
            <svg class="animate-spin size-5 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          {{-- Botón Limpiar (X) --}}
          <button
            type="button"
            class="hidden size-7 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-all cursor-pointer"
            data-search-clear
            aria-label="{{ __('Limpiar búsqueda', 'sage') }}"
          >
            <x-icon name="close" class="size-3.5" />
          </button>

          {{-- Badge Enter --}}
          <kbd class="hidden sm:inline-flex items-center px-2 py-1 text-[10px] font-mono rounded bg-white/5 border border-white/10 text-ink-subtle">
            ↵ Enter
          </kbd>
        </div>
      </form>
    </div>

    {{-- Contenedor scrolleable con altura adaptativa --}}
    <div class="overflow-y-auto px-5 md:px-7 py-5 space-y-6 flex-1 custom-scrollbar" data-search-scroll-container>

      {{-- =========================================================================
           VISTA POR DEFECTO: Historial Reciente + Tendencias Populares + Destacados
           ========================================================================= --}}
      <div data-search-default-view class="space-y-6">

        {{-- Búsquedas Recientes del Usuario (se muestra dinámicamente si hay en localStorage) --}}
        <div data-search-recent-container class="hidden">
          <div class="flex items-center justify-between mb-2.5">
            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ink-subtle">
              <svg class="size-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ __('Tus Búsquedas Recientes', 'sage') }}</span>
            </div>
            <button
              type="button"
              class="text-[11px] text-ink-subtle hover:text-emerald-400 transition-colors cursor-pointer"
              data-search-clear-recent
            >
              {{ __('Borrar historial', 'sage') }}
            </button>
          </div>
          <div class="flex flex-wrap gap-2" data-search-recent-list>
            {{-- Inyectado dinámicamente por JS --}}
          </div>
        </div>

        {{-- Búsquedas Más Populares de Clientes (Tendencias de Alta Conversión) --}}
        <div data-search-popular-container>
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="text-sm">🔥</span>
              <h4 class="text-xs font-bold uppercase tracking-widest text-ink">
                {{ __('Búsquedas Más Populares', 'sage') }}
              </h4>
            </div>
            <span class="text-[11px] text-ink-subtle hidden sm:inline">
              {{ __('Tendencias de la semana', 'sage') }}
            </span>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Orbea Avant"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">⚡</span>
              <span>Orbea Avant H50</span>
              <span class="text-[10px] text-ink-subtle px-1.5 py-0.5 rounded bg-white/5 group-hover:text-emerald-300">Ruta</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Trek Domane"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🚵</span>
              <span>Trek Domane</span>
              <span class="text-[10px] text-ink-subtle px-1.5 py-0.5 rounded bg-white/5 group-hover:text-emerald-300">Endurance</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Ruta"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🏁</span>
              <span>Bicicletas de Ruta</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="MTB"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🌲</span>
              <span>MTB Rin 29</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Gravel"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🍂</span>
              <span>Bicicletas Gravel</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="GW Zebra"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🦓</span>
              <span>GW Zebra 29</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Shimano Deore"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">⚙️</span>
              <span>Grupo Shimano Deore</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Shimano GRX"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">⚡</span>
              <span>Grupo Gravel GRX</span>
            </button>

            <button
              type="button"
              class="group flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#14161E] border border-white/10 hover:border-emerald-500/50 hover:bg-emerald-500/10 text-xs font-medium text-white transition-all cursor-pointer shadow-sm active:scale-95"
              data-search-popular-tag="Casco"
            >
              <span class="text-emerald-400 group-hover:scale-110 transition-transform">🪖</span>
              <span>Cascos y Protección</span>
            </button>
          </div>
        </div>

        {{-- Productos Destacados del Catálogo (cuando no se ha escrito nada) --}}
        @if (! empty($popularProducts))
          <div class="pt-2 border-t border-white/10">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-bold uppercase tracking-widest text-ink flex items-center gap-1.5">
                <x-icon name="star" class="size-3.5 text-amber-400" />
                <span>{{ __('Productos Destacados', 'sage') }}</span>
              </h4>
              <a href="{{ wc_get_page_permalink('shop') }}" class="text-[11px] text-emerald-400 hover:underline flex items-center gap-1">
                <span>{{ __('Ver catálogo completo', 'sage') }}</span>
                <x-icon name="chevron-right" class="size-3" />
              </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              @foreach ($popularProducts as $p)
                @php
                  $pId = $p->get_id();
                  $pName = $p->get_name();
                  $pUrl = $p->get_permalink();
                  $pImgId = $p->get_image_id();
                  $pImgUrl = $pImgId ? wp_get_attachment_image_url($pImgId, 'woocommerce_thumbnail') : wc_placeholder_img_src();
                  $pCat = '';
                  $pTerms = get_the_terms($pId, 'product_cat');
                  if ($pTerms && ! is_wp_error($pTerms)) {
                      $validCats = array_values(array_filter($pTerms, fn($t) => $t->slug !== 'sin-categorizar'));
                      if (! empty($validCats)) $pCat = $validCats[0]->name;
                  }
                @endphp
                <a
                  href="{{ $pUrl }}"
                  class="flex items-center gap-3.5 p-2.5 rounded-2xl bg-[#14161F]/60 hover:bg-white/[0.08] border border-white/5 hover:border-emerald-500/40 transition-all group cursor-pointer"
                >
                  <img
                    src="{{ $pImgUrl }}"
                    alt="{{ esc_attr($pName) }}"
                    class="size-14 rounded-xl object-cover bg-black/40 border border-white/10 shrink-0 group-hover:scale-105 transition-transform"
                    loading="lazy"
                  >
                  <div class="min-w-0 flex-1">
                    @if ($pCat)
                      <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-400 truncate">
                        {{ $pCat }}
                      </span>
                    @endif
                    <h5 class="text-xs font-semibold text-white truncate group-hover:text-emerald-300 transition-colors">
                      {{ $pName }}
                    </h5>
                    <div class="mt-0.5 text-xs text-ink-subtle font-medium">
                      {!! $p->get_price_html() !!}
                    </div>
                  </div>
                  <div class="text-ink-subtle group-hover:text-emerald-400 group-hover:translate-x-1 transition-all pr-1">
                    <x-icon name="chevron-right" class="size-4" />
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif

      </div>

      {{-- =========================================================================
           VISTA DE RESULTADOS EN TIEMPO REAL (AJAX PREDICTIVE SEARCH)
           ========================================================================= --}}
      <div data-search-results-view class="hidden space-y-5">

        {{-- Categorías y Marcas Sugeridas --}}
        <div data-search-taxonomies class="hidden flex-wrap items-center gap-2 pb-3 border-b border-white/10">
          <span class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
            {{ __('Explorar categorías:', 'sage') }}
          </span>
          <div data-search-categories-list class="flex flex-wrap gap-1.5">
            {{-- Inyectado por JS --}}
          </div>
        </div>

        {{-- Lista de Productos Encontrados --}}
        <div>
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-bold uppercase tracking-widest text-ink flex items-center gap-2">
              <span class="text-emerald-400">●</span>
              <span data-search-count-label>{{ __('Resultados de productos', 'sage') }}</span>
            </h4>
            <span class="text-[11px] text-ink-subtle" data-search-total-count></span>
          </div>

          <div class="space-y-2" data-search-products-list role="listbox">
            {{-- Inyectado por JS con tarjetas interactivas accesibles --}}
          </div>
        </div>

        {{-- Botón / Enlace "Ver todos los resultados" --}}
        <div data-search-all-container class="pt-2">
          <a
            href="#"
            class="flex items-center justify-between w-full px-5 py-3.5 rounded-2xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/40 text-emerald-300 hover:text-emerald-200 font-semibold text-xs md:text-sm transition-all group"
            data-search-all-link
          >
            <span class="flex items-center gap-2">
              <x-icon name="search" class="size-4 text-emerald-400" />
              <span data-search-all-text>{{ __('Ver todos los resultados', 'sage') }}</span>
            </span>
            <span class="flex items-center gap-1 text-xs group-hover:translate-x-1 transition-transform">
              <span>{{ __('Ir a la página de resultados', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-4" />
            </span>
          </a>
        </div>

        {{-- Estado Vacío (Sin Resultados) --}}
        <div data-search-empty-state class="hidden py-8 text-center space-y-4">
          <div class="inline-flex size-14 items-center justify-center rounded-full bg-white/5 border border-white/10 text-ink-subtle">
            <x-icon name="search" class="size-7 text-ink-subtle" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-white" data-search-empty-title>
              {{ __('No encontramos resultados para tu búsqueda', 'sage') }}
            </h4>
            <p class="text-xs text-ink-subtle max-w-md mx-auto">
              {{ __('Verifica la ortografía o intenta buscar con términos más generales como "Orbea", "Trek", "Shimano", "Ruta" o "Gravel".', 'sage') }}
            </p>
          </div>
          <div class="pt-2 flex flex-wrap justify-center gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-ink transition-colors cursor-pointer"
              data-search-popular-tag="Orbea"
            >
              Bicicletas Orbea
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-ink transition-colors cursor-pointer"
              data-search-popular-tag="Trek"
            >
              Bicicletas Trek
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-ink transition-colors cursor-pointer"
              data-search-popular-tag="Shimano"
            >
              Componentes Shimano
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-ink transition-colors cursor-pointer"
              data-search-popular-tag="Ruta"
            >
              Bicicletas de Ruta
            </button>
          </div>
        </div>

      </div>

    </div>

    {{-- Footer de Navegación Rápida con Teclado (Enterprise UX) --}}
    <div class="hidden sm:flex items-center justify-between px-6 py-2.5 border-t border-white/10 bg-[#08090C] text-[11px] text-ink-subtle shrink-0">
      <div class="flex items-center gap-4">
        <span class="flex items-center gap-1.5">
          <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px] text-white">↑</kbd>
          <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px] text-white">↓</kbd>
          <span>{{ __('Navegar', 'sage') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px] text-white">↵</kbd>
          <span>{{ __('Abrir producto', 'sage') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px] text-white">ESC</kbd>
          <span>{{ __('Cerrar', 'sage') }}</span>
        </span>
      </div>
      <div class="flex items-center gap-1.5 text-emerald-400 font-medium">
        <span>Racing Bike 1998</span>
        <span class="text-ink-subtle">• Tienda Oficial</span>
      </div>
    </div>

  </div>
</div>
