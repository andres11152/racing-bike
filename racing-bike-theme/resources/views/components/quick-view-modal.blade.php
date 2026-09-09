<div
  class="fixed inset-0 z-[130] hidden items-center justify-center bg-black/80 backdrop-blur-xl transition-all duration-300 p-2.5 sm:p-4 md:p-6"
  data-quick-view-modal
  role="dialog"
  aria-modal="true"
  aria-label="{{ __('Vista rápida de producto', 'sage') }}"
>
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" data-quick-view-close></div>

  <div class="relative w-full max-w-4xl max-h-[92dvh] sm:max-h-[90vh] rounded-2xl sm:rounded-3xl bg-[#0A0A0B]/98 border border-white/10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.95)] overflow-hidden text-ink flex flex-col z-10 my-auto">
    {{-- Brillo ambiental decorativo --}}
    <div class="absolute -top-24 -right-24 size-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 size-72 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>

    {{-- Encabezado Modal --}}
    <div class="flex items-center justify-between border-b border-white/10 px-4 sm:px-6 py-3 sm:py-4 shrink-0 z-20 bg-surface/80 backdrop-blur-md">
      <div class="flex items-center gap-2.5">
        <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <h2 class="text-xs font-bold uppercase tracking-widest text-ink">{{ __('Vista rápida del producto', 'sage') }}</h2>
      </div>

      <button
        type="button"
        class="flex size-9 sm:size-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 border border-white/15 transition-all hover:scale-105 active:scale-95 cursor-pointer touch-manipulation z-30"
        data-quick-view-close
        aria-label="{{ __('Cerrar vista rápida', 'sage') }}"
      >
        <x-icon name="close" class="size-4 pointer-events-none" />
      </button>
    </div>

    {{-- Contenedor del contenido del producto con scroll suave --}}
    <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 md:p-8 z-10 custom-scrollbar" data-quick-view-target style="-webkit-overflow-scrolling: touch;">
      <div class="flex flex-col items-center justify-center py-20 text-ink-subtle gap-4">
        <svg class="size-8 animate-spin text-emerald-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs uppercase tracking-widest text-ink-muted">{{ __('Cargando especificaciones...', 'sage') }}</span>
      </div>
    </div>
  </div>
</div>
