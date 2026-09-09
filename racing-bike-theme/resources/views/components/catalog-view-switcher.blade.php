<div {{ $attributes->merge(['class' => 'flex items-center gap-1 rounded-xl bg-surface-raised border border-line p-1', 'data-catalog-view-switcher' => '']) }}>
  {{-- Vista 1: Cuadrícula Estándar --}}
  <button
    type="button"
    class="flex size-8 items-center justify-center rounded-lg text-ink-subtle hover:text-white transition-colors cursor-pointer data-[active=true]:bg-white data-[active=true]:text-black shadow"
    data-view-btn="grid"
    data-active="false"
    aria-label="{{ __('Vista cuadrícula', 'sage') }}"
  >
    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
    </svg>
  </button>

  {{-- Vista 2: Cuadrícula Compacta (Segunda vista - predeterminada en móvil) --}}
  <button
    type="button"
    class="flex size-8 items-center justify-center rounded-lg text-ink-subtle hover:text-white transition-colors cursor-pointer data-[active=true]:bg-white data-[active=true]:text-black shadow"
    data-view-btn="compact"
    data-active="true"
    aria-label="{{ __('Vista compacta', 'sage') }}"
  >
    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h3v3H4V4zm5 0h3v3H9V4zm5 0h3v3h-3V4zm-10 5h3v3H4V9zm5 0h3v3H9V9zm5 0h3v3h-3V9zm-10 5h3v3H4v-3zm5 0h3v3H9v-3zm5 0h3v3h-3v-3z" />
    </svg>
  </button>
</div>
