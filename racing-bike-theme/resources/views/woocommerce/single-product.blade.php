{{--
  Ficha de producto.

  La maquetación es propia, pero el formulario de compra se delega a
  `woocommerce_template_single_add_to_cart()`: las variaciones dependen del JS
  de WooCommerce, que espera su markup exacto. Reimplementarlo rompería la
  selección de talla y el precio dinámico.
--}}

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();

      $product = wc_get_product();

      $terms = get_the_terms($product->get_id(), 'product_cat');
      $primaryCategory = $terms && ! is_wp_error($terms) ? end($terms) : null;

      $breadcrumbs = [['label' => __('Inicio', 'sage'), 'href' => home_url('/')]];
      $breadcrumbs[] = ['label' => __('Tienda', 'sage'), 'href' => get_permalink(wc_get_page_id('shop'))];

      if ($primaryCategory) {
        $link = get_term_link($primaryCategory);
        $breadcrumbs[] = ['label' => $primaryCategory->name, 'href' => is_wp_error($link) ? '' : $link];
      }

      $breadcrumbs[] = ['label' => $product->get_name()];

      // Payload para "Vistos recientemente": lo consume app.js desde
      // localStorage, no hay nada de esto en el servidor.
      $trackViewPrice = $product->is_type('variable') ? $product->get_variation_price('min') : $product->get_price();
      $trackViewImageId = $product->get_image_id();

      $trackViewData = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'url' => $product->get_permalink(),
        'image' => $trackViewImageId ? wp_get_attachment_image_url($trackViewImageId, 'medium') : null,
        'price' => $trackViewPrice !== '' ? (float) $trackViewPrice : null,
      ];

      // Nombre de la marca (pa_marca), sin logo — mismo criterio que el
      // filtro de la tienda: solo texto, nada de SVGs ni imágenes.
      $brandTerms = get_the_terms($product->get_id(), 'pa_marca');
      $brandName = ($brandTerms && ! is_wp_error($brandTerms)) ? reset($brandTerms)->name : null;

      // El banner de "Talla Biomecánica Recomendada" solo tiene sentido en
      // bicicletas (que tienen talla de cuadro). Se determina por
      // pertenencia a la categoría "Bicicletas" o a alguna de sus
      // subcategorías (Ruta, Gravel, Pista, etc.).
      $isBikeProduct = false;
      $bikeDiscipline = function_exists('\\App\\rb_get_product_discipline') ? \App\rb_get_product_discipline($product) : null;

      $bikeCategorySlugs = ['bicicletas', 'marcos', 'cuadros', 'marcos-y-tenedores'];
      $bikeCategoryIds = [];
      foreach ($bikeCategorySlugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term) {
          $bikeCategoryIds[] = $term->term_id;
          $bikeCategoryIds = array_merge($bikeCategoryIds, get_term_children($term->term_id, 'product_cat'));
        }
      }
      $productCategoryIds = wc_get_product_term_ids($product->get_id(), 'product_cat');
      $isBikeProduct = (bool) array_intersect($bikeCategoryIds, $productCategoryIds);

      // ...pero además el producto tiene que ofrecer tallas de verdad. Hay
      // bicicletas cargadas sin atributo de talla (talla única, infantiles,
      // cuadros sueltos) y ahí el banner recomendaba un marco que el
      // cliente no podía seleccionar en ninguna parte de la ficha.
      //
      // El slug del atributo de talla no es uniforme en el catálogo del
      // cliente (pa_talla, pa_talla-cuadro, talla), así que se busca por
      // nombre o etiqueta, igual que en components/product-card.
      $hasSizeAttribute = false;

      foreach ($product->get_attributes() as $attribute) {
        $attributeName = $attribute->get_name();
        $attributeLabel = wc_attribute_label($attributeName);

        $looksLikeSize = stripos($attributeName, 'talla') !== false
          || stripos($attributeLabel, 'talla') !== false
          || stripos($attributeName, 'size') !== false
          || stripos($attributeLabel, 'size') !== false;

        if ($looksLikeSize && $attribute->get_options()) {
          $hasSizeAttribute = true;
          break;
        }
      }

      $showSizeBanner = $isBikeProduct && $hasSizeAttribute;
    @endphp

    <div
      id="product-{{ $product->get_id() }}"
      {{ wc_product_class('', $product) }}
      data-rb-track-view="{{ wp_json_encode($trackViewData) }}"
      data-bike-discipline="{{ $bikeDiscipline }}"
    >
      <div class="rb-container py-8 md:py-12">
        <x-breadcrumbs :items="$breadcrumbs" class="mb-8" />

        @php
          woocommerce_output_all_notices();
        @endphp

        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 md:gap-12 lg:gap-16">
          <x-product-gallery :product="$product" />

          {{-- La columna de compra acompaña el scroll en pantallas altas. --}}
          <div class="md:sticky md:top-28 md:self-start min-w-0">
            <div class="flex items-center justify-between">
              @if ($primaryCategory)
                <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
                  {{ $primaryCategory->name }}
                </p>
              @endif

              {{-- Marca en texto: sin logo ni SVG, solo el nombre. --}}
              @if ($brandName)
                <div class="h-12 md:h-16 flex items-center justify-center bg-white/[0.04] border border-white/[0.08] rounded-xl px-4" title="Marca">
                  <span class="text-xs md:text-sm font-bold uppercase tracking-wider text-ink">{{ $brandName }}</span>
                </div>
              @endif
            </div>

            <h1 class="mt-3 text-3xl font-bold uppercase tracking-tight text-ink text-balance md:text-4xl">
              {{ $product->get_name() }}
            </h1>

            @if ($product->get_rating_count())
              <a href="#rb-reviews" class="mt-4 inline-block">
                <x-rating :value="(float) $product->get_average_rating()" :count="$product->get_rating_count()" />
              </a>
            @endif

            <div class="rb-woo-price mt-5 text-xl">
              {!! $product->get_price_html() !!}
            </div>

            <x-financing-calculator :price="$product->get_price()" />

            @if ($excerpt = $product->get_short_description())
              <div class="mt-6 max-w-prose text-sm leading-relaxed text-ink-muted">
                {!! wpautop($excerpt) !!}
              </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-b border-line py-3.5">
              <span class="text-xs text-ink-subtle flex items-center gap-1.5 font-medium">
                <x-icon name="truck" class="size-4 text-ink-subtle" />
                {{ __('Envío a todo Colombia — cotizado por ciudad', 'sage') }}
              </span>
            </div>

            @if (function_exists('rb_cro_get_stock_badge'))
              <div class="mt-4">
                {!! rb_cro_get_stock_badge($product) !!}
              </div>
            @endif

            @if ($showSizeBanner)
              <div
                id="rb-size-recommendation-banner"
                class="mt-6"
                data-bike-discipline="{{ $bikeDiscipline }}"
                data-product-name="{{ esc_attr($product->get_name()) }}"
              ></div>
            @endif

            <div class="rb-woo-add-to-cart mt-6">
              @php
                woocommerce_template_single_add_to_cart();
              @endphp
            </div>

            @if (function_exists('rb_cro_get_smart_whatsapp_url'))
              <div class="mt-3">
                <a
                  href="{{ rb_cro_get_smart_whatsapp_url('product', $product) }}"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex w-full items-center justify-center gap-2 rounded-full border border-line bg-surface-raised/80 px-4 py-3 text-xs font-bold uppercase tracking-wider text-ink hover:text-white hover:border-emerald-500/60 transition-all shadow-sm"
                >
                  <x-icon name="phone" class="size-4 text-emerald-400" />
                  <span>{{ __('Consultar asesoría experta por WhatsApp', 'sage') }}</span>
                </a>
              </div>
            @endif

            {{-- Argumentos de confianza en el punto de decisión, no en el footer. --}}
            <ul class="mt-8 grid gap-4 border-t border-line pt-8">
              <li class="flex items-start gap-3 text-sm">
                <x-icon name="truck" class="mt-0.5 size-5 shrink-0 text-ink-subtle" />
                <span class="text-ink-muted">
                  <strong class="font-medium text-ink">{{ __('Envío nacional', 'sage') }}</strong> —
                  {{ __('a todo Colombia en 2 a 5 días hábiles.', 'sage') }}
                </span>
              </li>
              <li class="flex items-start gap-3 text-sm">
                <x-icon name="shield-check" class="mt-0.5 size-5 shrink-0 text-ink-subtle" />
                <span class="text-ink-muted">
                  <strong class="font-medium text-ink">{{ __('Garantía de por vida en el marco', 'sage') }}</strong> —
                  {{ __('respaldada desde 1998.', 'sage') }}
                </span>
              </li>
              <li class="flex items-start gap-3 text-sm">
                <x-icon name="user" class="mt-0.5 size-5 shrink-0 text-ink-subtle" />
                <span class="text-ink-muted">
                  <strong class="font-medium text-ink">{{ __('Armado y ajuste incluidos', 'sage') }}</strong> —
                  {{ __('la entregamos lista para rodar.', 'sage') }}
                </span>
              </li>
            </ul>

            @if ($sku = $product->get_sku())
              <p class="mt-6 text-xs uppercase tracking-widest text-ink-subtle">
                {{ __('Referencia', 'sage') }}: {{ $sku }}
              </p>
            @endif
          </div>
        </div>
      </div>

      {{-- Descripción y ficha técnica --}}
      @php
        // Los atributos usados para variaciones (talla, longitud de biela, color, etc.)
        // ya se seleccionan arriba en el selector de compra; mostrarlos otra vez en la
        // ficha técnica es redundante. La ficha técnica debe lucir puramente las especificaciones de ingeniería.
        $attributes = array_filter($product->get_attributes(), function ($attribute) use ($product) {
          if ($attribute->get_variation()) {
            return false;
          }
          if ($product->is_type('variable') && in_array($attribute->get_name(), ['pa_color', 'pa_talla', 'pa_color-familia', 'pa_longitud-de-biela', 'Color', 'Talla'], true)) {
            return false;
          }
          return true;
        });
      @endphp

      {{-- Tópicos Enterprise: Descripción, Ficha Técnica, Garantía/Envíos, Guía de Tallas --}}
      <x-product-topics :product="$product" :specs="$attributes" />

      {{-- Reseñas de clientes (plugin racing-bike-reviews) --}}
      @if (function_exists('rb_reviews_render_section'))
        <div class="rb-container py-12">
          {!! rb_reviews_render_section($product) !!}
        </div>
      @endif

      {{-- Relacionados --}}
      @php
        $relatedIds = wc_get_related_products($product->get_id(), 4);
        $related = $relatedIds ? wc_get_products(['include' => $relatedIds, 'limit' => 4, 'status' => 'publish']) : [];
      @endphp

      @if ($related)
        <section class="border-t border-line">
          <div class="rb-container py-14 md:py-20">
            <h2 class="mb-10 text-xl font-bold uppercase tracking-widest text-ink md:text-2xl">
              {{ __('También te puede interesar', 'sage') }}
            </h2>

            <div class="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-4 md:gap-x-8">
              @foreach ($related as $relatedProduct)
                <x-product-card :product="$relatedProduct" />
              @endforeach
            </div>
          </div>
        </section>
      @endif

      {{-- Barra Flotante de Compra (Sticky Add-to-Cart Bar) --}}
      <div data-sticky-buy-bar class="fixed bottom-0 inset-x-0 z-40 border-t border-line bg-surface-raised/95 backdrop-blur-md p-4 transition-all duration-300 transform translate-y-full opacity-0 shadow-lg">
        <div class="rb-container flex items-center justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            {!! $product->get_image('thumbnail', ['class' => 'size-12 rounded object-cover border border-line shrink-0']) !!}
            <div class="min-w-0">
              <h3 class="text-xs font-bold uppercase tracking-wide text-ink truncate">{{ $product->get_name() }}</h3>
              <p class="text-sm font-semibold text-ink-muted">{!! $product->get_price_html() !!}</p>
            </div>
          </div>
          <button type="button" data-sticky-action class="shrink-0 rounded bg-ink px-4 py-2 md:px-6 md:py-2.5 text-xs font-bold uppercase tracking-widest text-surface hover:bg-ink-muted transition-colors">
            <span class="md:hidden">{{ __('Comprar', 'sage') }}</span>
            <span class="hidden md:inline">{{ __('Comprar / Elegir opciones', 'sage') }}</span>
          </button>
        </div>
      </div>

      {{-- Modal de Guía de Tallas --}}
      <x-size-guide-modal />
    </div>
  @endwhile
@endsection
