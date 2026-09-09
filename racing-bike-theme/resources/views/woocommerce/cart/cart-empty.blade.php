{{--
  Página de carrito vacío de WooCommerce.
--}}

<div class="py-20 md:py-32 text-center w-full flex flex-col items-center justify-center">
  <div class="flex size-20 items-center justify-center rounded-full bg-surface-raised border border-line mb-6 shadow-xl">
    <x-icon name="cart" class="size-8 text-ink-subtle opacity-50" />
  </div>

  <h1 class="text-2xl font-bold uppercase tracking-widest text-ink md:text-3xl">
    {{ __('Tu carrito está vacío', 'sage') }}
  </h1>

  <p class="mt-3 max-w-md text-sm text-ink-muted">
    {{ __('Aún no has agregado ninguna bicicleta o componente a tu compra. Explora nuestro catálogo de alto rendimiento.', 'sage') }}
  </p>

  <div class="mt-8">
    <x-button variant="primary" size="lg" :href="get_permalink(wc_get_page_id('shop'))">
      {{ __('Ir a la tienda', 'sage') }}
    </x-button>
  </div>
</div>
