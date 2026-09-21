@php
  $query = new \WP_Query([
      'post_type' => 'rb_announcement',
      'posts_per_page' => 20,
      'orderby' => 'menu_order',
      'order' => 'ASC',
      'no_found_rows' => true,
  ]);

  $items = [];
  foreach ($query->posts as $post) {
      $icon = get_post_meta($post->ID, '_rb_announcement_icon', true) ?: 'star';
      $items[] = [
          'icon' => $icon,
          'text' => get_the_title($post),
      ];
  }

  // Fallback Enterprise con beneficios y garantías oficiales de Racing Bike 1998
  if (empty($items)) {
      $items = [
          ['icon' => 'truck', 'text' => __('ENVÍO SEGURO A TODA COLOMBIA — COTIZADO POR CIUDAD EN 2 A 5 DÍAS HÁBILES', 'sage')],
          ['icon' => 'shield-check', 'text' => __('GARANTÍA DE POR VIDA EN EL MARCO RESPALDADA DESDE 1998', 'sage')],
          ['icon' => 'user', 'text' => __('CADA BICICLETA SE ENTREGA 100% ARMADA Y AJUSTADA EN BOGOTÁ', 'sage')],
          ['icon' => 'star', 'text' => __('FINANCIACIÓN Y PAGOS SEGUROS CON PSE, TARJETAS Y ADDI', 'sage')],
      ];
  }
@endphp

<div
  class="relative overflow-hidden border-b border-white/[0.08] bg-[#090a0d] py-2 text-zinc-300 z-50"
  data-announcement-bar
>
  <div class="flex items-center justify-between w-full px-4 sm:px-6 md:px-8">
    {{-- Marquee Ticker continuo con estilo Enterprise --}}
    <a
      href="{{ function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tienda') }}"
      class="relative flex-1 overflow-hidden select-none hover:text-white transition-colors cursor-pointer block"
      aria-label="{{ __('Ir a la tienda', 'sage') }}"
    >
      <div class="animate-marquee items-center gap-12 whitespace-nowrap">
        @for ($i = 0; $i < 2; $i++)
          @foreach ($items as $item)
            <div class="flex items-center gap-2 text-[11px] font-bold tracking-widest uppercase text-zinc-300">
              <x-icon :name="$item['icon']" class="size-3.5 text-emerald-400 shrink-0" />
              <span>{{ $item['text'] }}</span>
              <span class="text-white/20 ml-6">•</span>
            </div>
          @endforeach
        @endfor
      </div>
    </a>

    {{-- Acciones y utilidades directas a la derecha --}}
    <div class="hidden xl:flex items-center gap-4 ml-6 shrink-0 text-xs">
      <a
        href="https://wa.me/573103233232"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-400 hover:text-emerald-300 transition-colors uppercase tracking-wider"
      >
        <x-icon name="phone" class="size-3 text-emerald-400" />
        <span>{{ __('Asesoría WhatsApp', 'sage') }}</span>
      </a>

      <span class="text-white/20">|</span>

      <span class="flex items-center gap-1.5 text-[11px] text-zinc-400 tracking-wider">
        <x-icon name="map-pin" class="size-3 text-zinc-400" />
        <span>{{ __('Bogotá, Colombia', 'sage') }}</span>
      </span>
    </div>

    {{-- Botón para cerrar la barra si el usuario lo desea --}}
    <button
      type="button"
      class="ml-4 shrink-0 text-zinc-400 transition-colors hover:text-white cursor-pointer"
      data-announcement-dismiss
      aria-label="{{ __('Ocultar anuncios', 'sage') }}"
    >
      <x-icon name="close" class="size-3.5" />
    </button>
  </div>
</div>
