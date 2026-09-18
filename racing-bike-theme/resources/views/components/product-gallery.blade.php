@props([
  'product',
])

@php
  // Sólo las imágenes reales del producto: mostrar la foto de otro producto
  // (aunque sea "de relleno") es engañoso para el comprador.
  $mainId = $product->get_image_id();
  $galleryIds = $product->get_gallery_image_ids();
  $imageIds = array_values(array_filter(array_merge([$mainId], $galleryIds)));

  $images = array_map(function($id) use ($product) {
    return [
      'id' => $id,
      'full' => wp_get_attachment_image_url($id, 'full'),
      'large' => wp_get_attachment_image_url($id, 'large') ?: wp_get_attachment_image_url($id, 'full'),
      'thumb' => wp_get_attachment_image_url($id, 'thumbnail') ?: wp_get_attachment_image_url($id, 'full'),
      'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: $product->get_name(),
    ];
  }, $imageIds);
@endphp

<div
  class="flex flex-col md:flex-row gap-4 items-start w-full"
  data-product-gallery
  data-images="{{ json_encode(array_column($images, 'full'), JSON_UNESCAPED_SLASHES) }}"
>
  {{-- Tira Vertical de Miniaturas en ESCRITORIO (A la izquierda) --}}
  @if (count($images) > 1)
    <div class="hidden md:flex flex-col gap-3 w-20 shrink-0 max-h-[580px] overflow-y-auto pr-1">
      @foreach ($images as $index => $img)
        <button
          type="button"
          class="relative aspect-square w-full rounded-xl overflow-hidden border-2 transition-all duration-200 cursor-pointer bg-surface-muted data-[active=true]:border-ink data-[active=true]:ring-2 data-[active=true]:ring-ink/20 border-line hover:border-line-strong"
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
            class="size-full object-contain p-1"
          >
        </button>
      @endforeach
    </div>
  @endif

  {{-- Visor Principal con Zoom al Hover --}}
  <div class="flex-1 w-full relative">
    <div
      class="relative aspect-4/5 md:aspect-square overflow-hidden rounded-2xl bg-surface-muted border border-line group cursor-zoom-in shadow-2xl"
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
          class="size-full object-contain p-4 md:p-6 transition-transform duration-200 ease-out origin-center"
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
      <div class="flex md:hidden gap-3 overflow-x-auto pt-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach ($images as $index => $img)
          <button
            type="button"
            class="relative aspect-square size-16 rounded-xl overflow-hidden border-2 transition-all duration-200 shrink-0 cursor-pointer bg-surface-muted data-[active=true]:border-ink border-line"
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
              class="size-full object-contain p-1"
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
