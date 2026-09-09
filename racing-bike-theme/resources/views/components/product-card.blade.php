@props([
  'product' => null,
  'name' => null,
  'href' => '#',
  'image' => null,
  'imageAlt' => '',
  'regularPrice' => null,
  'salePrice' => null,
  'rating' => null,
  'ratingCount' => null,
  'badge' => null,
  'inStock' => true,
  'sizes' => [],
  'listId' => null,
  'listName' => null,
  'position' => null,
])

@php
  // Dos formas de uso: pasar un WC_Product (tienda real) o props sueltas
  // (catálogo demo y previsualizaciones de diseño).
  if ($product instanceof \WC_Product) {
    $name = $product->get_name();
    $href = $product->get_permalink();
    $imageId = $product->get_image_id();
    $image = $imageId ? wp_get_attachment_image_url($imageId, 'full') : null;
    $imageAlt = $imageId ? get_post_meta($imageId, '_wp_attachment_image_alt', true) : '';
    $inStock = $product->is_in_stock();
    $ratingCount = $product->get_rating_count() ?: null;
    $rating = $ratingCount ? (float) $product->get_average_rating() : null;

    $regularPrice = $product->get_regular_price();
    $salePrice = $product->get_sale_price() ?: null;

    // Los productos variables no exponen un precio único: se toma el rango.
    if ($product->is_type('variable')) {
      $regularPrice = $product->get_variation_regular_price('min');
      $sale = $product->get_variation_sale_price('min');
      $salePrice = ($sale && $sale !== $regularPrice) ? $sale : null;
    }

    if (! $badge) {
      $badge = $product->is_on_sale() ? 'sale' : null;
    }

    // Tallas del marco disponibles, para el swatch de la tarjeta.
    if (! $sizes && $product->is_type('variable')) {
      $sizeTaxonomy = 'pa_talla-cuadro';
      $available = $product->get_available_variations();
      $seen = [];

      foreach ($available as $variation) {
        $value = $variation['attributes']['attribute_' . $sizeTaxonomy] ?? null;

        if ($value && ! isset($seen[$value])) {
          $seen[$value] = true;
          $sizes[] = [
            'label' => strtoupper($value),
            'inStock' => (bool) $variation['is_in_stock'],
          ];
        }
      }
    }

    $excerpt = $product->get_short_description() ? wp_strip_all_tags($product->get_short_description()) : null;

    $galleryImageIds = $product->get_gallery_image_ids();
    $galleryImages = [];

    if ($imageId) {
      $galleryImages[] = [
        'url' => wp_get_attachment_image_url($imageId, 'full'),
        'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: $name,
      ];
    }

    foreach ($galleryImageIds as $gId) {
      $gUrl = wp_get_attachment_image_url($gId, 'full');
      if ($gUrl && count($galleryImages) < 5) {
        $galleryImages[] = [
          'url' => $gUrl,
          'alt' => get_post_meta($gId, '_wp_attachment_image_alt', true) ?: $name,
        ];
      }
    }

    // Logo real de la marca, editable en WooCommerce > Atributos > Marca
    // (term meta, ver app/product-brands.php).
    $brandLogoUrl = \App\product_brand_logo_url($product->get_id());
  } else {
    $excerpt = null;
    $galleryImages = $image ? [['url' => $image, 'alt' => $imageAlt]] : [];
    $brandLogoUrl = null;
  }
@endphp

<article
  @if ($listId)
    data-ga4-item
    data-ga4-list-id="{{ $listId }}"
    data-ga4-list-name="{{ $listName ?? $listId }}"
    data-ga4-item-id="{{ $product instanceof \WC_Product ? ($product->get_sku() ?: $product->get_id()) : '' }}"
    data-ga4-item-name="{{ $name }}"
    data-ga4-item-price="{{ $regularPrice }}"
    @if ($position !== null)
      data-ga4-index="{{ $position }}"
    @endif
  @endif
  {{ $attributes->merge(['class' => 'group relative flex flex-col justify-between overflow-hidden rounded-lg border border-transparent p-3 card-glass-glow']) }}
>
  <a href="{{ $href }}" class="block">
    <div class="relative aspect-4/5 overflow-hidden rounded bg-surface-muted" data-card-gallery data-active-index="0">
      @if (! empty($galleryImages))
        @foreach ($galleryImages as $index => $img)
          <img
            src="{{ $img['url'] }}"
            alt="{{ $img['alt'] }}"
            loading="lazy"
            decoding="async"
            class="size-full object-cover transition-all duration-500 ease-out {{ $index === 0 ? 'opacity-100 group-hover:scale-105' : 'absolute inset-0 opacity-0 group-hover:scale-105' }} {{ ! $inStock ? 'grayscale opacity-40' : '' }}"
            data-gallery-img="{{ $index }}"
          >
        @endforeach
      @elseif ($image)
        <img
          src="{{ $image }}"
          alt="{{ $imageAlt }}"
          loading="lazy"
          decoding="async"
          class="size-full object-cover transition-transform duration-700 ease-out group-hover:scale-110 {{ ! $inStock ? 'opacity-40 grayscale' : '' }}"
        >
      @endif

      {{-- Gradiente decorativo inferior --}}
      <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

      {{-- Flechas laterales de navegación de imágenes --}}
      @if (count($galleryImages) > 1)
        <div class="absolute inset-y-0 left-1.5 right-1.5 z-20 flex items-center justify-between pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-300">
          <button
            type="button"
            class="flex size-7 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur hover:bg-black transition-colors pointer-events-auto cursor-pointer shadow-md"
            data-gallery-prev
            aria-label="{{ __('Imagen anterior', 'sage') }}"
          >
            <x-icon name="chevron-right" class="size-3.5 rotate-180" />
          </button>

          <button
            type="button"
            class="flex size-7 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur hover:bg-black transition-colors pointer-events-auto cursor-pointer shadow-md"
            data-gallery-next
            aria-label="{{ __('Imagen siguiente', 'sage') }}"
          >
            <x-icon name="chevron-right" class="size-3.5" />
          </button>
        </div>

        {{-- Mini-indicadores (dashes) de imágenes - Centrados para no tapar el carrito --}}
        <div class="absolute bottom-2.5 left-1/2 -translate-x-1/2 z-20 flex justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-auto w-fit max-w-[60%]">
          @foreach ($galleryImages as $index => $img)
            <button
              type="button"
              class="h-1 flex-1 min-w-[8px] max-w-[20px] rounded-full bg-white/40 hover:bg-white transition-all cursor-pointer data-[active=true]:bg-white data-[active=true]:w-6"
              data-gallery-dot="{{ $index }}"
              data-active="{{ $index === 0 ? 'true' : 'false' }}"
              aria-label="{{ sprintf(__('Ver imagen %d', 'sage'), $index + 1) }}"
            ></button>
          @endforeach
        </div>
      @endif

      <div class="absolute left-3 top-3 flex flex-col gap-1.5 z-10">
        @if ($badge === 'new')
          <x-badge variant="default">{{ __('Nuevo', 'sage') }}</x-badge>
        @elseif ($badge === 'sale')
          <x-badge variant="sale">{{ __('Oferta', 'sage') }}</x-badge>
        @endif

        @if (! $inStock)
          <x-badge variant="outline">{{ __('Agotado', 'sage') }}</x-badge>
        @endif
      </div>

      {{-- Logo de marca en la esquina superior derecha (solo el logo directo, sin contenedor de fondo) --}}
      @if ($brandLogoUrl)
        <img
          src="{{ $brandLogoUrl }}"
          alt="Marca"
          class="absolute top-2.5 right-2.5 md:top-3 md:right-3 z-20 h-6 md:h-9 w-auto object-contain brightness-0 invert opacity-90 pointer-events-none drop-shadow-[0_2px_5px_rgba(0,0,0,0.85)]"
        >
      @endif

      @if ($inStock && $product)
        <div class="absolute bottom-3 right-3 z-30 flex items-center gap-1.5 opacity-0 scale-90 translate-y-2 group-hover:opacity-100 group-hover:scale-100 group-hover:translate-y-0 transition-all duration-300 focus-within:opacity-100 max-md:opacity-100 max-md:translate-y-0 max-md:scale-100">
          {{-- Botón Vista Rápida (Eye) --}}
          <button
            type="button"
            class="flex size-9 items-center justify-center rounded-full bg-surface/95 text-ink shadow-md border border-white/10 hover:bg-emerald-400 hover:text-black hover:border-emerald-400 transition-all duration-200 cursor-pointer touch-manipulation"
            aria-label="{{ sprintf(__('Vista rápida de %s', 'sage'), $name) }}"
            data-quick-view="{{ $product->get_id() }}"
            onclick="event.preventDefault(); event.stopPropagation();"
          >
            <x-icon name="eye" class="size-4 pointer-events-none" />
          </button>

          {{-- Botón Agregar al Carrito (Cart) instantáneo o selección de opciones para variables --}}
          @if (! $product->is_type('variable'))
            <button
              type="button"
              class="flex size-9 items-center justify-center rounded-full bg-surface/95 text-ink shadow-md border border-white/10 hover:bg-emerald-400 hover:text-black hover:border-emerald-400 transition-all duration-200 cursor-pointer touch-manipulation"
              aria-label="{{ sprintf(__('Agregar %s al carrito', 'sage'), $name) }}"
              data-quick-add="{{ $product->get_id() }}"
              onclick="event.preventDefault(); event.stopPropagation();"
            >
              <x-icon name="cart" class="size-4 pointer-events-none" />
            </button>
          @else
            <button
              type="button"
              class="flex size-9 items-center justify-center rounded-full bg-surface/95 text-ink shadow-md border border-white/10 hover:bg-emerald-400 hover:text-black hover:border-emerald-400 transition-all duration-200 cursor-pointer touch-manipulation"
              aria-label="{{ sprintf(__('Seleccionar opciones para %s', 'sage'), $name) }}"
              data-quick-view="{{ $product->get_id() }}"
              onclick="event.preventDefault(); event.stopPropagation();"
            >
              <x-icon name="cart" class="size-4 pointer-events-none" />
            </button>
          @endif
        </div>
      @endif
    </div>

    <div class="mt-4 space-y-1.5 min-w-0">
      @if ($rating !== null)
        <x-rating :value="$rating" :count="$ratingCount" />
      @endif

      <h3 class="text-sm font-semibold text-ink group-hover:text-ink-muted transition-colors line-clamp-1">{{ $name }}</h3>

      <x-price :regular="$regularPrice" :sale="$salePrice" class="text-sm text-ink" />

      @if ($excerpt)
        <p class="hidden md:group-[.view-mode-list]:block text-xs leading-relaxed text-ink-subtle line-clamp-2 pt-1 max-w-xl">
          {{ $excerpt }}
        </p>
      @endif

      @if ($sizes)
        <ul class="flex flex-wrap gap-1.5 pt-1">
          @foreach ($sizes as $size)
            <li
              class="border px-1.5 py-0.5 text-[10px] font-medium tracking-wide transition-colors {{ $size['inStock'] ? 'border-line-strong text-ink-muted group-hover:border-ink' : 'border-line text-ink-faint line-through' }}"
            >
              {{ $size['label'] }}
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </a>
</article>

@once
  <script>
    document.addEventListener('click', (e) => {
      if (!e.target || typeof e.target.closest !== 'function') return;
      const dot = e.target.closest('[data-gallery-dot]');
      const prev = e.target.closest('[data-gallery-prev]');
      const next = e.target.closest('[data-gallery-next]');

      if (!dot && !prev && !next) return;

      e.preventDefault();
      e.stopPropagation();

      const gallery = (dot || prev || next).closest('[data-card-gallery]');
      if (!gallery) return;

      // Marcar que el usuario interactuó para pausar el reset automático al salir
      gallery.setAttribute('data-interacted', 'true');

      const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
      const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
      if (!imgs.length) return;

      let currentIndex = parseInt(gallery.getAttribute('data-active-index') || '0', 10);

      if (dot) {
        currentIndex = parseInt(dot.getAttribute('data-gallery-dot'), 10);
      } else if (prev) {
        currentIndex = (currentIndex - 1 + imgs.length) % imgs.length;
      } else if (next) {
        currentIndex = (currentIndex + 1) % imgs.length;
      }

      gallery.setAttribute('data-active-index', currentIndex);

      imgs.forEach((img, idx) => {
        if (idx === currentIndex) {
          img.classList.remove('opacity-0');
          img.classList.add('opacity-100');
        } else {
          img.classList.remove('opacity-100');
          img.classList.add('opacity-0');
        }
      });

      dots.forEach((d, idx) => {
        d.setAttribute('data-active', idx === currentIndex ? 'true' : 'false');
      });
    });

    // Hover sobre el dot: Cambiar de foto y marcar interacción
    document.addEventListener('mouseover', (e) => {
      if (!e.target || typeof e.target.closest !== 'function') return;
      const dot = e.target.closest('[data-gallery-dot]');
      if (!dot) return;

      const gallery = dot.closest('[data-card-gallery]');
      if (!gallery) return;

      gallery.setAttribute('data-interacted', 'true');

      const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
      const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
      const targetIndex = parseInt(dot.getAttribute('data-gallery-dot'), 10);

      gallery.setAttribute('data-active-index', targetIndex);

      imgs.forEach((img, idx) => {
        if (idx === targetIndex) {
          img.classList.remove('opacity-0');
          img.classList.add('opacity-100');
        } else {
          img.classList.remove('opacity-100');
          img.classList.add('opacity-0');
        }
      });

      dots.forEach((d, idx) => {
        d.setAttribute('data-active', idx === targetIndex ? 'true' : 'false');
      });
    });

    // Hover Flip: Mostrar segunda imagen al entrar y resetear al salir (si no hay interacción manual)
    document.addEventListener('mouseenter', (e) => {
      if (!e.target || typeof e.target.closest !== 'function') return;
      const gallery = e.target.closest('[data-card-gallery]');
      if (!gallery) return;

      if (gallery.getAttribute('data-interacted') === 'true') return;

      const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
      const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
      if (imgs.length < 2) return;

      // Cambiar a la segunda imagen (índice 1)
      gallery.setAttribute('data-active-index', '1');
      imgs[0].classList.remove('opacity-100');
      imgs[0].classList.add('opacity-0');
      imgs[1].classList.remove('opacity-0');
      imgs[1].classList.add('opacity-100');

      if (dots.length >= 2) {
        dots[0].setAttribute('data-active', 'false');
        dots[1].setAttribute('data-active', 'true');
      }
    }, true);

    document.addEventListener('mouseleave', (e) => {
      if (!e.target || typeof e.target.closest !== 'function') return;
      const gallery = e.target.closest('[data-card-gallery]');
      if (!gallery) return;

      if (gallery.getAttribute('data-interacted') === 'true') return;

      const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
      const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
      if (imgs.length < 2) return;

      // Retornar a la primera imagen (índice 0)
      gallery.setAttribute('data-active-index', '0');
      imgs.forEach((img, idx) => {
        if (idx === 0) {
          img.classList.remove('opacity-0');
          img.classList.add('opacity-100');
        } else {
          img.classList.remove('opacity-100');
          img.classList.add('opacity-0');
        }
      });

      dots.forEach((d, idx) => {
        d.setAttribute('data-active', idx === 0 ? 'true' : 'false');
      });
    }, true);
  </script>
@endonce
