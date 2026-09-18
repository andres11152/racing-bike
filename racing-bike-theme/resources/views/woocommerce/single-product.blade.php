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

      // Logo real de la marca, editable en WooCommerce > Atributos > Marca
      // (term meta, ver app/product-brands.php).
      $brandLogoUrl = \App\product_brand_logo_url($product->get_id());

      // El banner de "Talla Biomecánica Recomendada" solo tiene sentido en
      // bicicletas (que tienen talla de cuadro). Se determina por
      // pertenencia a la categoría "Bicicletas" o a alguna de sus
      // subcategorías (Ruta, Gravel, Pista, etc.).
      $isBikeProduct = false;
      $bikeDiscipline = function_exists('\\App\\rb_get_product_discipline') ? \App\rb_get_product_discipline($product) : null;

      $bikeCategoryTerm = get_term_by('slug', 'bicicletas', 'product_cat');
      if ($bikeCategoryTerm) {
        $bikeCategoryIds = array_merge(
          [$bikeCategoryTerm->term_id],
          get_term_children($bikeCategoryTerm->term_id, 'product_cat')
        );
        $productCategoryIds = wc_get_product_term_ids($product->get_id(), 'product_cat');
        $isBikeProduct = (bool) array_intersect($bikeCategoryIds, $productCategoryIds);
      }

      if ($bikeDiscipline) {
        $isBikeProduct = true;
      }
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

        <div class="grid gap-10 md:grid-cols-2 md:gap-12 lg:gap-16">
          <x-product-gallery :product="$product" />

          {{-- La columna de compra acompaña el scroll en pantallas altas. --}}
          <div class="md:sticky md:top-28 md:self-start">
            <div class="flex items-center justify-between">
              @if ($primaryCategory)
                <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
                  {{ $primaryCategory->name }}
                </p>
              @endif

              {{-- Logo de marca en la ficha de producto --}}
              @if ($brandLogoUrl)
                <div class="h-12 md:h-16 w-28 flex items-center justify-center bg-white/[0.04] border border-white/[0.08] rounded-xl px-3 py-1.5" title="Marca">
                  <img
                    src="{{ $brandLogoUrl }}"
                    alt="Marca"
                    class="h-full w-auto object-contain brightness-0 invert opacity-100"
                  >
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

            @if ($isBikeProduct)
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
        // Los atributos usados para variaciones (talla, longitud de biela,
        // etc.) ya se seleccionan arriba en el selector de compra; mostrarlos
        // otra vez aquí es redundante y en variantes rotas confunde más.
        $attributes = array_filter($product->get_attributes(), fn ($attribute) => ! $attribute->get_variation());
      @endphp

      @if ($product->get_description() || $attributes)
        <div class="border-t border-line">
          <div class="rb-container grid gap-12 py-14 md:grid-cols-2 md:py-20">
            @if ($product->get_description())
              <div>
                <h2 class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
                  {{ __('Descripción', 'sage') }}
                </h2>
                <div class="rb-prose mt-5 max-w-prose text-sm leading-relaxed text-ink-muted">
                  {!! apply_filters('the_content', $product->get_description()) !!}
                </div>
              </div>
            @endif

            @if ($attributes)
              <div>
                <h2 class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
                  {{ __('Ficha técnica', 'sage') }}
                </h2>

                <dl class="mt-5 border-t border-line">
                  @foreach ($attributes as $attribute)
                    @continue(! $attribute->get_visible())

                    <div class="flex justify-between gap-6 border-b border-line py-3 text-sm">
                      <dt class="text-ink-subtle">{{ wc_attribute_label($attribute->get_name()) }}</dt>
                      <dd class="text-right text-ink">
                        @if ($attribute->is_taxonomy())
                          {{ implode(', ', wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])) }}
                        @else
                          {{ implode(', ', $attribute->get_options()) }}
                        @endif
                      </dd>
                    </div>
                  @endforeach
                </dl>
              </div>
            @endif
          </div>
        </div>
      @endif

      {{-- Reseñas de clientes (plugin racing-bike-reviews) --}}
      @if (function_exists('rb_reviews_render_section'))
        <div class="rb-container">
          {!! rb_reviews_render_section($product) !!}
        </div>
      @endif

      {{-- Preguntas Frecuentes (FAQ Accordion) --}}
      <section class="border-t border-line bg-surface-raised">
        <div class="rb-container py-14 md:py-20">
          <div class="mb-10 text-center">
            <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Resolviendo tus dudas', 'sage') }}</p>
            <h2 class="mt-2 text-xl font-bold uppercase tracking-tight text-ink md:text-2xl">
              {{ __('Preguntas frecuentes de compra', 'sage') }}
            </h2>
          </div>

          <div class="divide-y divide-line border-y border-line">
            @foreach ([
              ['q' => __('¿Cómo entregan la bicicleta si estoy en Bogotá o en otra ciudad?', 'sage'), 'a' => __('En Bogotá entregamos tu bicicleta 100% armada, calibrada y lista para rodar sin costo adicional. Para envíos al resto de Colombia (Medellín, Cali, Barranquilla, Bucaramanga, etc.), va protegida en caja reforzada con transportadora aliada, pre-ensamblada al 90%.')],
              ['q' => __('¿En qué consiste la garantía de por vida en el marco?', 'sage'), 'a' => __('Cubrimos cualquier defecto de fábrica o fallo estructural en el marco de por vida para el comprador original. Respaldado directamente en nuestra sede física desde 1998.')],
              ['q' => __('¿Cómo sé cuál es mi talla ideal de marco?', 'sage'), 'a' => __('Puedes consultar nuestra Guía de Tallas interactiva o escribirnos al WhatsApp. Con tu estatura en cm y tiro de pierna, nuestros mecánicos te indican la medida exacta de marco.')],
            ] as $index => $faq)
              <div class="py-5">
                <button
                  type="button"
                  class="flex w-full items-center justify-between gap-4 text-left text-sm font-bold text-ink"
                  data-accordion-toggle
                  aria-expanded="false"
                  aria-controls="faq-panel-{{ $index }}"
                >
                  <span>{{ $faq['q'] }}</span>
                  <x-icon name="chevron-down" class="size-4 shrink-0 text-ink-subtle transition-transform duration-200" data-accordion-icon />
                </button>
                <div id="faq-panel-{{ $index }}" class="hidden mt-3 text-xs leading-relaxed text-ink-muted">
                  {{ $faq['a'] }}
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>

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
