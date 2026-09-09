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

  // Fallback si no hay anuncios creados en la administración de WordPress
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
  class="relative overflow-hidden border-b border-line bg-white py-2 text-black"
  data-announcement-bar
>
  <div class="flex items-center justify-between w-full px-4 sm:px-6 md:px-8">
    {{-- Marquee Ticker continuo enlazado a la tienda --}}
    <a
      href="{{ function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tienda') }}"
      class="relative flex-1 overflow-hidden select-none hover:text-emerald-500 transition-colors cursor-pointer block"
      aria-label="{{ __('Ir a la tienda', 'sage') }}"
    >
      <div class="animate-marquee items-center gap-12 whitespace-nowrap">
        @for ($i = 0; $i < 2; $i++)
          @foreach ($items as $item)
            <div class="flex items-center gap-2.5 text-[11px] font-bold tracking-widest uppercase text-black">
              <x-icon :name="$item['icon']" class="size-3.5 text-emerald-500 shrink-0" />
              <span>{{ $item['text'] }}</span>
              <span class="text-black/30 ml-6">•</span>
            </div>
          @endforeach
        @endfor
      </div>
    </a>

    <button
      type="button"
      class="ml-4 shrink-0 text-black/50 transition-colors hover:text-black"
      data-announcement-dismiss
      aria-label="{{ __('Ocultar anuncios', 'sage') }}"
    >
      <x-icon name="close" class="size-3.5" />
    </button>
  </div>
</div>
