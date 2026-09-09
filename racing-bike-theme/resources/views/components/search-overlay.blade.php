{{-- Componente de Búsqueda Rápida de Alta Conversión --}}
<div
  class="fixed inset-0 z-[250] hidden items-start justify-center bg-black/85 backdrop-blur-2xl p-4 md:p-10 transition-all duration-300"
  data-search-overlay
  role="dialog"
  aria-modal="true"
  aria-label="{{ __('Buscar productos', 'sage') }}"
>
  <div class="relative w-full max-w-2xl rounded-3xl bg-[#0A0A0B] border border-white/10 p-6 md:p-8 shadow-[0_25px_70px_rgba(0,0,0,0.95)] overflow-hidden text-ink animate-fade-in mt-10 md:mt-20">
    
    {{-- Brillo ambiental esmeralda --}}
    <div class="absolute -right-12 -top-12 -z-10 size-40 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

    <div class="flex items-center justify-between border-b border-line pb-4 mb-6">
      <h3 class="text-xs font-bold uppercase tracking-widest text-emerald-400">
        {{ __('¿Qué estás buscando?', 'sage') }}
      </h3>
      <button
        type="button"
        class="flex size-9 items-center justify-center rounded-full bg-surface-raised text-ink-subtle hover:text-white border border-line hover:border-white/30 transition-all cursor-pointer"
        data-search-close
        aria-label="{{ __('Cerrar búsqueda', 'sage') }}"
      >
        <x-icon name="close" class="size-4" />
      </button>
    </div>

    {{-- Formulario de Búsqueda WooCommerce --}}
    <form role="search" method="get" class="relative" action="{{ home_url('/') }}">
      <input
        type="search"
        name="s"
        placeholder="{{ __('Escribe para buscar bicicletas, marcos...', 'sage') }}"
        class="w-full bg-[#121316] border border-line-strong rounded-2xl py-4 pl-12 pr-4 text-white text-base placeholder-ink-subtle outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
        data-search-input
        autocomplete="off"
        required
      >
      <input type="hidden" name="post_type" value="product">
      
      <div class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-subtle">
        <x-icon name="search" class="size-5" />
      </div>
    </form>

    {{-- Sugerencias de Búsqueda Rápida --}}
    <div class="mt-6">
      <p class="text-[10px] font-bold uppercase tracking-widest text-ink-subtle mb-3">{{ __('Sugerencias populares', 'sage') }}</p>
      <div class="flex flex-wrap gap-2">
        <a href="{{ home_url('/') }}?s=Gravel&post_type=product" class="px-3.5 py-1.5 rounded-xl bg-[#121316] border border-line text-xs font-medium text-ink hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">
          🚲 Bicicletas Gravel
        </a>
        <a href="{{ home_url('/') }}?s=Carbono&post_type=product" class="px-3.5 py-1.5 rounded-xl bg-[#121316] border border-line text-xs font-medium text-ink hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">
          📐 Marcos de Carbono
        </a>
        <a href="{{ home_url('/') }}?s=Ruta&post_type=product" class="px-3.5 py-1.5 rounded-xl bg-[#121316] border border-line text-xs font-medium text-ink hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">
          🏁 Bicicletas de Ruta
        </a>
        <a href="{{ home_url('/') }}?s=Shimano&post_type=product" class="px-3.5 py-1.5 rounded-xl bg-[#121316] border border-line text-xs font-medium text-ink hover:text-emerald-400 hover:border-emerald-500/40 transition-colors">
          ⚙️ Shimano
        </a>
      </div>
    </div>
  </div>
</div>
