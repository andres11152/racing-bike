<div class="drawer" data-size-guide-drawer data-open="false" role="dialog" aria-modal="true" aria-label="{{ __('Guía de tallas de marco', 'sage') }}">
  <div class="absolute inset-0 bg-surface/70" data-size-guide-close></div>

  <div class="drawer-panel absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-surface-raised transition-transform duration-300 ease-out">
    <div class="flex items-center justify-between border-b border-line px-6 py-5">
      <div class="flex items-center gap-2">
        <x-icon name="ruler" class="size-5 text-ink-subtle" />
        <h2 class="text-xs font-semibold uppercase tracking-widest text-ink">{{ __('Guía de Tallas de Marco', 'sage') }}</h2>
      </div>
      <button type="button" class="text-ink-muted transition-colors hover:text-ink" data-size-guide-close aria-label="{{ __('Cerrar guía de tallas', 'sage') }}">
        <x-icon name="close" class="size-5" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-6 text-sm">
      <p class="text-ink-muted">
        {{ __('Encontrar la talla correcta de marco garantiza comodidad, rendimiento y evita molestias en salidas largas. Usa esta tabla de referencia según tu estatura:', 'sage') }}
      </p>

      <div class="mt-6 overflow-hidden rounded-md border border-line">
        <table class="w-full text-left">
          <thead class="bg-surface-subtle text-xs font-semibold uppercase tracking-wider text-ink">
            <tr>
              <th class="px-4 py-3 border-b border-line">{{ __('Talla', 'sage') }}</th>
              <th class="px-4 py-3 border-b border-line">{{ __('Estatura (cm)', 'sage') }}</th>
              <th class="px-4 py-3 border-b border-line">{{ __('Saber Más', 'sage') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line text-ink-muted">
            <tr class="hover:bg-surface">
              <td class="px-4 py-3 font-bold text-ink">XS</td>
              <td class="px-4 py-3 font-medium text-ink">160 – 168 cm</td>
              <td class="px-4 py-3 text-xs">{{ __('Entrepierna ~72-76 cm', 'sage') }}</td>
            </tr>
            <tr class="hover:bg-surface">
              <td class="px-4 py-3 font-bold text-ink">S</td>
              <td class="px-4 py-3 font-medium text-ink">168 – 175 cm</td>
              <td class="px-4 py-3 text-xs">{{ __('Entrepierna ~76-80 cm', 'sage') }}</td>
            </tr>
            <tr class="hover:bg-surface">
              <td class="px-4 py-3 font-bold text-ink">M</td>
              <td class="px-4 py-3 font-medium text-ink">175 – 183 cm</td>
              <td class="px-4 py-3 text-xs">{{ __('Entrepierna ~80-85 cm', 'sage') }}</td>
            </tr>
            <tr class="hover:bg-surface">
              <td class="px-4 py-3 font-bold text-ink">L</td>
              <td class="px-4 py-3 font-medium text-ink">183 – 192 cm</td>
              <td class="px-4 py-3 text-xs">{{ __('Entrepierna ~85-90 cm', 'sage') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 rounded-md bg-surface-subtle p-4 text-xs text-ink-muted">
        <strong class="font-semibold text-ink">{{ __('¿Dudas entre dos tallas?', 'sage') }}</strong>
        <p class="mt-1">
          {{ __('Si estás justo en el límite, elige la talla menor para mayor agilidad y maniobrabilidad, o la mayor para mayor estabilidad en ruta. Cada bicicleta que entregamos incluye servicio de ajuste personalizado.', 'sage') }}
        </p>
      </div>
    </div>

    <div class="border-t border-line px-6 py-4">
      <button type="button" data-size-guide-close class="w-full rounded-md border border-line py-2.5 text-xs font-semibold uppercase tracking-widest text-ink hover:bg-surface transition-colors">
        {{ __('Entendido', 'sage') }}
      </button>
    </div>
  </div>
</div>
