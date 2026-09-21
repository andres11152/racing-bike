<x-announcement-bar />

<header
  class="sticky top-0 z-40 border-b border-white/[0.08] bg-surface/95 backdrop-blur-md transition-all duration-300 shadow-lg"
  data-header
>
  <div class="rb-container">
    <div class="flex items-center justify-between h-16 sm:h-20 md:h-24 gap-2 sm:gap-3 lg:gap-6 xl:gap-8 transition-[height] duration-300">
      
      {{-- ZONA 1 (IZQUIERDA): Menú Móvil + Logotipo Ancla --}}
      <div class="flex items-center gap-2 sm:gap-3 md:gap-4 shrink min-w-0">
        <button
          type="button"
          class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-white/[0.04] text-ink border border-white/[0.08] hover:bg-white/[0.08] hover:text-emerald-400 transition-colors lg:hidden cursor-pointer"
          data-nav-open
          aria-label="{{ __('Abrir menú', 'sage') }}"
          aria-expanded="false"
          aria-controls="mobile-nav"
        >
          <x-icon name="menu" class="size-4.5 sm:size-5" />
        </button>

        <a
          class="flex items-center py-1 group shrink min-w-0"
          href="{{ home_url('/') }}"
          aria-label="{{ __('Ir al inicio', 'sage') }}"
        >
          <div class="rb-logo-wrapper">
            <picture>
              <source srcset="{{ get_theme_file_uri('public/images/racing-bike-horizontal.webp') }}" type="image/webp">
              <img
                src="{{ get_theme_file_uri('public/images/racing-bike-horizontal.png') }}"
                alt="{!! $siteName !!}"
                width="200"
                height="35"
                class="h-7 sm:h-9 md:h-12 lg:h-[54px] xl:h-[60px] max-w-[150px] sm:max-w-[200px] md:max-w-none w-auto object-contain transition-all duration-300"
              >
            </picture>
          </div>
        </a>
      </div>

      {{-- ZONA 2 (CENTRO): Mega-Menú de Navegación Principal --}}
      <div class="hidden lg:flex items-center justify-center flex-1 min-w-0">
        <x-mega-menu />
      </div>

      {{-- ZONA 3 (DERECHA): Herramientas de Conversión, Búsqueda y Acciones de Compra --}}
      <div class="flex items-center justify-end gap-2 sm:gap-2.5 xl:gap-3 shrink-0 text-ink">
        

        {{-- Botón de Utilidad: Guía de Tallas --}}
        <a
          href="{{ $links['sizeGuide'] }}"
          class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider text-ink-muted hover:text-ink bg-white/[0.04] hover:bg-white/[0.08] border border-white/[0.08] transition-all cursor-pointer"
          title="{{ __('Calcula tu talla exacta de marco', 'sage') }}"
        >
          <x-icon name="ruler" class="size-3.5 text-ink-muted shrink-0" />
          <span>{{ __('Guía de Tallas', 'sage') }}</span>
        </a>

        {{-- Barra / Botón de Búsqueda Instantánea estilo Enterprise --}}
        <button
          type="button"
          class="hidden md:flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/[0.08] hover:border-white/[0.2] text-ink-muted hover:text-ink text-xs transition-all cursor-pointer group shadow-inner"
          data-search-trigger
          aria-label="{{ __('Buscar bicicletas y repuestos', 'sage') }}"
        >
          <x-icon name="search" class="size-3.5 text-ink-muted group-hover:text-emerald-400 transition-colors" />
          <span class="font-medium text-ink-subtle group-hover:text-ink transition-colors">{{ __('Buscar...', 'sage') }}</span>
          <kbd class="hidden xl:inline-block px-1.5 py-0.5 text-[9px] uppercase font-mono font-semibold bg-white/10 rounded text-ink-subtle">⌘K</kbd>
        </button>

        {{-- Icono de búsqueda compacto para pantallas móviles (< md) --}}
        <button
          type="button"
          class="flex md:hidden size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-white/[0.04] text-ink border border-white/[0.08] hover:bg-white/[0.08] hover:text-emerald-400 transition-colors cursor-pointer"
          data-search-trigger
          aria-label="{{ __('Buscar', 'sage') }}"
        >
          <x-icon name="search" class="size-4 sm:size-4.5" />
        </button>

        {{-- Mi Cuenta --}}
        <a
          href="{{ wc_get_page_permalink('myaccount') }}"
          class="hidden sm:flex size-10 items-center justify-center rounded-xl bg-white/[0.04] text-ink border border-white/[0.08] hover:bg-white/[0.08] hover:text-emerald-400 transition-colors"
          aria-label="{{ __('Mi cuenta', 'sage') }}"
          title="{{ __('Mi cuenta', 'sage') }}"
        >
          <x-icon name="user" class="size-4.5" />
        </a>

        {{-- Lista de Deseos (Wishlist) --}}
        <a
          href="{{ function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('wishlist') : wc_get_page_permalink('myaccount') }}"
          class="relative hidden sm:flex size-10 items-center justify-center rounded-xl bg-white/[0.04] text-ink border border-white/[0.08] hover:bg-white/[0.08] hover:text-emerald-400 transition-colors"
          aria-label="{{ __('Ver mi lista de deseos', 'sage') }}"
          title="{{ __('Lista de deseos', 'sage') }}"
        >
          <x-icon name="heart" class="size-4.5" />
          <span
            class="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full bg-emerald-500 text-[10px] font-bold text-black shadow-sm"
            data-wishlist-count
          >0</span>
        </a>

        {{-- Carrito de Compras --}}
        @php
          $cartCount = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
        @endphp
        <button
          type="button"
          class="relative flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-white/[0.04] text-ink border border-white/[0.08] hover:bg-white/[0.08] hover:text-emerald-400 hover:border-emerald-500/30 transition-all cursor-pointer group"
          data-cart-open
          aria-label="{{ __('Ver carrito', 'sage') }}"
          title="{{ __('Ver carrito de compras', 'sage') }}"
        >
          <x-icon name="cart" class="size-4.5 sm:size-5 text-emerald-400 group-hover:scale-110 transition-transform" />
          <span
            class="absolute -top-1.5 -right-1.5 flex size-5 min-w-5 items-center justify-center rounded-full bg-emerald-500 text-[10px] sm:text-[11px] font-black text-black shadow-md ring-2 ring-[#0a0a0b] pointer-events-none transition-all duration-300 {{ $cartCount > 0 ? 'scale-100 opacity-100' : 'hidden opacity-0 scale-75' }}"
            data-cart-count
            @if ($cartCount <= 0) style="display: none;" @endif
          >
            {{ $cartCount }}
          </span>
        </button>
      </div>

    </div>
  </div>
</header>

<x-mobile-nav />
