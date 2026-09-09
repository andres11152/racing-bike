<div class="drawer z-50" data-cart-drawer data-open="false" role="dialog" aria-modal="true" aria-label="{{ __('Carrito de compras', 'sage') }}">
  <div class="absolute inset-0 bg-surface/70" data-cart-close></div>

  <div class="drawer-panel absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-surface-raised transition-transform duration-300 ease-out shadow-2xl">
    <div class="flex items-center justify-between border-b border-line px-6 py-5">
      <div class="flex items-center gap-2">
        <x-icon name="cart" class="size-5 text-ink-subtle" />
        <h2 class="text-xs font-semibold uppercase tracking-widest text-ink">{{ __('Tu carrito', 'sage') }}</h2>
      </div>
      <button type="button" class="text-ink-muted transition-colors hover:text-ink" data-cart-close aria-label="{{ __('Cerrar carrito', 'sage') }}">
        <x-icon name="close" class="size-5" />
      </button>
    </div>

    <div class="flex-1 min-h-0">
      <x-cart-drawer-content />
    </div>
  </div>
</div>
