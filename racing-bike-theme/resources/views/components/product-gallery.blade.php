@props([
  'product',
])

@php
  // Sólo las imágenes reales del producto
  $mainId = $product->get_image_id();
  $galleryIds = (array) $product->get_gallery_image_ids();

  // Si es un producto variable, incluir también las imágenes asignadas a sus variaciones
  if ($product->is_type('variable')) {
    $varImageIds = [];
    foreach ($product->get_children() as $cid) {
      $t = (int) get_post_meta($cid, '_thumbnail_id', true);
      if ($t && ! in_array($t, $varImageIds, true)) {
        $varImageIds[] = $t;
      }
    }
    $galleryIds = array_values(array_unique(array_merge($galleryIds, $varImageIds)));
  }

  $imageIds = array_values(array_filter(array_unique(array_merge([$mainId], $galleryIds))));

  $images = array_map(function($id) use ($product) {
    return [
      'id' => $id,
      'full' => wp_get_attachment_image_url($id, 'full'),
      'large' => wp_get_attachment_image_url($id, 'large') ?: wp_get_attachment_image_url($id, 'full'),
      'thumb' => wp_get_attachment_image_url($id, 'thumbnail') ?: wp_get_attachment_image_url($id, 'full'),
      'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: $product->get_name(),
    ];
  }, $imageIds);

  $images = array_values(array_filter($images, fn($img) => ! empty($img['full'])));
@endphp

<div
  class="flex flex-col md:flex-row gap-4 items-start w-full"
  data-product-gallery
  data-images="{{ json_encode(array_column($images, 'full'), JSON_UNESCAPED_SLASHES) }}"
>
  {{-- Tira Vertical de Miniaturas en ESCRITORIO (A la izquierda) --}}
  @if (count($images) > 1)
    <div class="hidden md:block relative w-20 lg:w-24 shrink-0">
      <div
        class="flex flex-col gap-3 max-h-[500px] lg:max-h-[580px] overflow-y-auto pr-1 pb-10 scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        data-gallery-thumbs-track
        style="mask-image: linear-gradient(to bottom, black 0%, black calc(100% - 64px), transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 0%, black calc(100% - 64px), transparent 100%);"
      >
        @foreach ($images as $index => $img)
          <button
            type="button"
            class="group relative aspect-square w-full shrink-0 rounded-2xl overflow-hidden border-2 transition-all duration-300 cursor-pointer bg-surface-muted hover:bg-surface-raised data-[active=true]:border-primary data-[active=true]:ring-2 data-[active=true]:ring-primary/30 border-line hover:border-line-strong hover:scale-[1.02]"
            data-gallery-thumb
            data-index="{{ $index }}"
            data-active="{{ $index === 0 ? 'true' : 'false' }}"
            data-full="{{ $img['full'] }}"
            aria-label="{{ sprintf(__('Ver ángulo %d', 'sage'), $index + 1) }}"
          >
            <img
              src="{{ $img['thumb'] }}"
              alt="{{ $img['alt'] }}"
              loading="lazy"
              class="size-full object-contain p-1.5 transition-transform duration-300 group-hover:scale-105"
            >
          </button>
        @endforeach
      </div>

      {{-- Gradiente / Blur inferior con micro-indicador de scroll si hay muchas imágenes --}}
      @if (count($images) > 4)
        <div
          class="pointer-events-none absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-surface via-surface/80 to-transparent flex items-end justify-center pb-1.5 transition-opacity duration-300"
          data-gallery-fade-indicator
        >
          <span class="inline-flex items-center justify-center size-6 rounded-full bg-white/10 text-white/80 border border-white/15 backdrop-blur-md shadow-lg animate-bounce">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </span>
        </div>
      @endif
    </div>
  @endif

  {{-- Visor Principal con Zoom al Hover --}}
  <div class="flex-1 w-full relative">
    <div
      class="relative aspect-4/5 md:aspect-square overflow-hidden group cursor-zoom-in"
      data-gallery-main-container
    >
      {{--
        object-contain, no object-cover: las fotos del catálogo no tienen
        una proporción uniforme (de 48 imágenes principales, 19 no son
        cuadradas — hay apaisadas de 2.07:1 y verticales de 0.67:1). Con
        object-cover, una foto apaisada dentro de este contenedor cuadrado
        se recortaba tanto que la bicicleta aparecía ampliada y cortada; y
        al cambiar de talla o color, la foto nueva saltaba a otro recorte
        distinto. La caja de luz ya usaba object-contain, así que además
        eran dos encuadres distintos para la misma foto.
      --}}
      @if (! empty($images))
        <img
          src="{{ $images[0]['full'] }}"
          alt="{{ $images[0]['alt'] }}"
          class="size-full object-contain transition-transform duration-200 ease-out origin-center"
          fetchpriority="high"
          decoding="async"
          data-gallery-main-img
          data-full="{{ $images[0]['full'] }}"
        >
      @endif

      {{-- Insignias de estado --}}
      @if ($product->is_on_sale() || ! $product->is_in_stock())
        <div class="absolute left-4 top-4 flex flex-col gap-1.5 z-10 pointer-events-none">
          @if (! $product->is_in_stock())
            <x-badge variant="outline">{{ __('Agotado', 'sage') }}</x-badge>
          @elseif ($product->is_on_sale())
            <x-badge variant="sale">{{ __('Oferta Especial', 'sage') }}</x-badge>
          @endif
        </div>
      @endif

      {{-- Botón Lupa de Caja de Luz: sin imagen no hay nada que ampliar --}}
      @if (! empty($images))
        <button
          type="button"
          class="absolute right-4 bottom-4 z-10 flex size-10 items-center justify-center rounded-full bg-surface/80 text-ink backdrop-blur-md transition-all hover:bg-surface hover:scale-110 shadow-lg border border-line"
          data-open-lightbox
          aria-label="{{ __('Ampliar fotografía', 'sage') }}"
        >
          <x-icon name="search" class="size-4" />
        </button>
      @endif
    </div>

    {{-- Tira Horizontal de Miniaturas en MÓVIL (Al pie de la imagen principal) --}}
    @if (count($images) > 1)
      <div class="flex md:hidden gap-3 overflow-x-auto pt-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden scroll-smooth">
        @foreach ($images as $index => $img)
          <button
            type="button"
            class="relative aspect-square size-16 rounded-2xl overflow-hidden border-2 transition-all duration-200 shrink-0 cursor-pointer bg-surface-muted data-[active=true]:border-primary data-[active=true]:ring-2 data-[active=true]:ring-primary/30 border-line hover:border-line-strong"
            data-gallery-thumb
            data-index="{{ $index }}"
            data-active="{{ $index === 0 ? 'true' : 'false' }}"
            data-full="{{ $img['full'] }}"
            aria-label="{{ sprintf(__('Ver ángulo %d', 'sage'), $index + 1) }}"
          >
            <img
              src="{{ $img['thumb'] }}"
              alt="{{ $img['alt'] }}"
              loading="lazy"
              class="size-full object-contain p-1.5"
            >
          </button>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Modal de Caja de Luz Enterprise (Fullscreen Lightbox Modal) --}}
  <div
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-surface/95 backdrop-blur-xl transition-opacity duration-300"
    data-lightbox-modal
    role="dialog"
    aria-modal="true"
  >
    <div class="relative flex size-full flex-col items-center justify-between p-4 md:p-8">
      {{-- Barra Superior de la Caja de Luz --}}
      <div class="flex w-full items-center justify-between z-20">
        <span class="text-xs font-bold uppercase tracking-widest text-ink-muted" data-lightbox-counter>
          1 / {{ count($images) }}
        </span>

        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-full bg-surface-raised text-ink border border-line transition-all hover:bg-surface hover:scale-105 cursor-pointer"
          data-close-lightbox
          aria-label="{{ __('Cerrar vista ampliada', 'sage') }}"
        >
          <x-icon name="close" class="size-5" />
        </button>
      </div>

      {{-- Imagen Ampliada en Fullscreen --}}
      <div class="relative flex flex-1 w-full items-center justify-center overflow-hidden py-4">
        <img
          src=""
          alt=""
          class="max-h-[85vh] max-w-[90vw] object-contain transition-transform duration-300 shadow-2xl"
          data-lightbox-img
        >
      </div>

      {{-- Controles de Navegación Flechas --}}
      @if (count($images) > 1)
        <button
          type="button"
          class="absolute left-4 top-1/2 -translate-y-1/2 flex size-12 items-center justify-center rounded-full bg-surface-raised/80 text-ink border border-line backdrop-blur transition-all hover:bg-surface hover:scale-110 z-20 cursor-pointer"
          data-lightbox-prev
          aria-label="{{ __('Anterior', 'sage') }}"
        >
          <x-icon name="chevron-right" class="size-6 rotate-180" />
        </button>

        <button
          type="button"
          class="absolute right-4 top-1/2 -translate-y-1/2 flex size-12 items-center justify-center rounded-full bg-surface-raised/80 text-ink border border-line backdrop-blur transition-all hover:bg-surface hover:scale-110 z-20 cursor-pointer"
          data-lightbox-next
          aria-label="{{ __('Siguiente', 'sage') }}"
        >
          <x-icon name="chevron-right" class="size-6" />
        </button>
      @endif
    </div>
  </div>
</div>
