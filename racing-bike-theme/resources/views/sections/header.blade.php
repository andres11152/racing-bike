<x-announcement-bar />

<header class="sticky top-0 z-40 border-b border-line bg-surface/95 backdrop-blur transition-all duration-500" data-header>
  <div class="rb-container">
    {{--
      Tres columnas de igual peso mantienen la marca ópticamente centrada
      sin importar cuántos ítems tenga el menú ni cuántos iconos haya.
    --}}
    <div class="grid h-auto py-0.5 md:py-0 grid-cols-[1fr_auto_1fr] items-center gap-4">
      <div class="flex items-center justify-start">
        <button
          type="button"
          class="text-ink md:hidden"
          data-nav-open
          aria-label="{{ __('Abrir menú', 'sage') }}"
          aria-expanded="false"
          aria-controls="mobile-nav"
        >
          <x-icon name="menu" class="size-6" />
        </button>

        <x-mega-menu />
      </div>

      <a
        class="flex items-center justify-center py-2"
        href="{{ home_url('/') }}"
        aria-label="{{ __('Ir al inicio', 'sage') }}"
      >
        <div class="rb-logo-wrapper">
          <img
            src="{{ get_theme_file_uri('public/images/racing-bike-horizontal.png') }}"
            alt="{!! $siteName !!}"
            width="320"
            height="160"
            class="h-14 md:h-24 w-auto object-contain"
          >
        </div>
      </a>

      <div class="flex items-center justify-end gap-5 text-ink">
        <button type="button" class="flex items-center justify-center text-ink hover:text-white transition-colors cursor-pointer" aria-label="{{ __('Buscar', 'sage') }}" data-search-trigger>
          <x-icon name="search" class="size-5" />
        </button>

        <a href="{{ wc_get_page_permalink('myaccount') }}" class="hidden md:block" aria-label="{{ __('Mi cuenta', 'sage') }}">
          <x-icon name="user" class="size-5" />
        </a>

        <button type="button" class="relative" data-cart-open aria-label="{{ __('Ver carrito', 'sage') }}">
          <x-icon name="cart" class="size-5" />
          <span
            class="absolute -right-2 -top-2 flex size-4 items-center justify-center bg-action text-[10px] font-semibold text-on-action"
            data-cart-count
          >
            {{ function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0 }}
          </span>
        </button>
      </div>
    </div>
  </div>
</header>

<x-mobile-nav />
