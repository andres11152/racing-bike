@props([
  'product' => null,
])

@if ($product)
  @php
    $productId = $product->get_id();
    $imageId = $product->get_image_id();
    $mainImage = $imageId ? wp_get_attachment_image_url($imageId, 'large') : null;
    $mainImageAlt = $imageId ? get_post_meta($imageId, '_wp_attachment_image_alt', true) : $product->get_name();

    // Galería completa (imagen principal + miniaturas adicionales)
    $galleryIds = $product->get_gallery_image_ids();
    $allImages = [];
    if ($imageId) {
        $allImages[] = [
            'thumb' => wp_get_attachment_image_url($imageId, 'thumbnail'),
            'full' => $mainImage,
            'alt' => $mainImageAlt,
        ];
    }
    if (! empty($galleryIds)) {
        foreach ($galleryIds as $gId) {
            $allImages[] = [
                'thumb' => wp_get_attachment_image_url($gId, 'thumbnail'),
                'full' => wp_get_attachment_image_url($gId, 'large'),
                'alt' => get_post_meta($gId, '_wp_attachment_image_alt', true) ?: $product->get_name(),
            ];
        }
    }

    $terms = get_the_terms($productId, 'product_cat');
    $primaryCategory = $terms && ! is_wp_error($terms) ? end($terms) : null;
    $permalink = get_permalink($productId);
    $inStock = $product->is_in_stock();
    $onSale = $product->is_on_sale();
    $ratingCount = (int) $product->get_rating_count();
    $averageRating = (float) $product->get_average_rating();

    // Cálculo del porcentaje de descuento si está en oferta
    $discountPercent = 0;
    if ($onSale && (float) $product->get_regular_price() > 0) {
        $reg = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();
        if ($reg > $sale) {
            $discountPercent = round((($reg - $sale) / $reg) * 100);
        }
    }
  @endphp

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 items-start pb-4 sm:pb-0">
    {{-- Columna Izquierda: Galería y Media (5 columnas) --}}
    <div class="lg:col-span-5 flex flex-col gap-3.5 sm:gap-4">
      {{-- Contenedor de Imagen Principal --}}
      <div class="relative aspect-4/3 sm:aspect-square max-h-60 sm:max-h-none overflow-hidden rounded-2xl border border-line bg-surface-raised/40 group shadow-inner">
        @if ($mainImage)
          <img
            id="qv-main-image"
            src="{{ $mainImage }}"
            alt="{{ $mainImageAlt }}"
            class="size-full object-cover object-center transition-all duration-500 group-hover:scale-105"
          />
        @else
          <div class="flex size-full items-center justify-center text-ink-subtle">
            <x-icon name="image" class="size-16 stroke-1" />
          </div>
        @endif

        {{-- Insignias Flotantes --}}
        <div class="absolute left-3 top-3 flex flex-col gap-1.5 z-10">
          @if ($discountPercent > 0)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-black shadow-md">
              -{{ $discountPercent }}% OFF
            </span>
          @elseif ($onSale)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-white text-black shadow-md">
              {{ __('Oferta', 'sage') }}
            </span>
          @endif

          @if (! $inStock)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-black/80 text-neutral-400 border border-white/20 backdrop-blur-md">
              {{ __('Agotado', 'sage') }}
            </span>
          @endif
        </div>
      </div>

      {{-- Tira de Miniaturas si hay más de 1 imagen --}}
      @if (count($allImages) > 1)
        <div class="flex items-center gap-2.5 overflow-x-auto pb-1.5 custom-scrollbar" data-qv-thumbnails>
          @foreach ($allImages as $index => $img)
            <button
              type="button"
              class="relative size-16 shrink-0 rounded-xl overflow-hidden border {{ $index === 0 ? 'border-white ring-2 ring-white/20' : 'border-line/60 opacity-60 hover:opacity-100' }} transition-all cursor-pointer"
              data-qv-thumb="{{ $img['full'] }}"
            >
              <img src="{{ $img['thumb'] }}" alt="{{ $img['alt'] }}" class="size-full object-cover" />
            </button>
          @endforeach
        </div>
      @endif

      {{-- Enlace a la Ficha Completa --}}
      <div class="pt-2 border-t border-line/30">
        <a
          href="{{ $permalink }}"
          class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-ink-subtle hover:text-emerald-400 transition-colors group"
        >
          <span>{{ __('Ver ficha técnica completa', 'sage') }}</span>
          <x-icon name="chevron-right" class="size-3.5 transition-transform group-hover:translate-x-1" />
        </a>
      </div>
    </div>

    {{-- Columna Derecha: Información y Formulario de Compra (7 columnas) --}}
    <div class="lg:col-span-7 flex flex-col justify-between space-y-5">
      <div>
        {{-- Categoría y Fabricante --}}
        @if ($primaryCategory)
          <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">
            {{ $primaryCategory->name }}
          </p>
        @endif

        {{-- Título del Producto --}}
        <h3 class="mt-1 text-xl sm:text-2xl font-bold uppercase tracking-tight text-white leading-tight">
          {{ $product->get_name() }}
        </h3>

        {{-- Reseñas / Calificación --}}
        @if ($ratingCount > 0)
          <div class="mt-2.5 flex items-center gap-2">
            <x-rating :value="$averageRating" :count="$ratingCount" />
            <span class="text-xs text-ink-subtle">({{ $ratingCount }} {{ _n('reseña', 'reseñas', $ratingCount, 'sage') }})</span>
          </div>
        @endif

        {{-- Bloque de Precio --}}
        <div class="rb-woo-price mt-3 text-xl sm:text-2xl">
          {!! $product->get_price_html() !!}
        </div>

        {{-- Descripción Corta --}}
        @if ($excerpt = $product->get_short_description())
          <div class="mt-3.5 text-xs sm:text-sm leading-relaxed text-ink-muted line-clamp-3">
            {!! wpautop($excerpt) !!}
          </div>
        @endif
      </div>

      {{-- Formulario WooCommerce con Wrapper Adecuado --}}
      <div class="border-t border-line pt-4">
        @if (function_exists('rb_cro_get_stock_badge'))
          <div class="mb-3">
            {!! rb_cro_get_stock_badge($product) !!}
          </div>
        @endif

        <div class="rb-woo-add-to-cart woocommerce">
          @php
            woocommerce_template_single_add_to_cart();
          @endphp
        </div>
      </div>

      {{-- Insignias de Confianza en el Punto de Compra --}}
      <div class="grid grid-cols-2 gap-3 pt-3 border-t border-line/40 text-[11px] text-ink-subtle">
        <div class="flex items-center gap-2">
          <x-icon name="truck" class="size-4 text-emerald-400 shrink-0" />
          <span>{{ __('Envío asegurado nacional', 'sage') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <x-icon name="shield-check" class="size-4 text-emerald-400 shrink-0" />
          <span>{{ __('Garantía oficial y taller', 'sage') }}</span>
        </div>
      </div>
    </div>
  </div>
@endif
