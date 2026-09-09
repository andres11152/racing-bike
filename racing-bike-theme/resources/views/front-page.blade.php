@extends('layouts.app')

@section('content')
  <x-hero-carousel :slides="$slides" />

  @if ($categories)
    <section class="border-y border-line">
      <div class="rb-container">
        <div class="grid grid-cols-1 divide-y divide-line md:grid-cols-3 md:divide-x md:divide-y-0">
          @foreach ($categories as $category)
            <a href="{{ $category['url'] }}" class="group flex items-center justify-between py-6 md:px-8 md:py-8 md:first:pl-0 md:last:pr-0">
              <span class="text-sm font-bold uppercase tracking-widest text-ink transition-colors group-hover:text-ink-muted">
                {{ $category['name'] }}
              </span>

              <span class="flex items-center gap-2 text-xs text-ink-subtle">
                {{ sprintf(_n('%s producto', '%s productos', $category['count'], 'sage'), number_format_i18n($category['count'])) }}
                <x-icon name="chevron-right" class="size-4 transition-transform group-hover:translate-x-1" />
              </span>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @if ($featuredProducts)
    <section class="rb-container py-12 md:py-16" data-reveal>
      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Selección', 'sage') }}</p>
          <h2 class="mt-2 text-2xl font-bold uppercase tracking-widest text-ink md:text-3xl">
            {{ __('Destacados', 'sage') }}
          </h2>
        </div>

        <div class="flex items-center gap-4">
          <x-catalog-view-switcher data-target-grid="#featured-grid-container" />
          <a href="{{ $shopUrl }}" class="shrink-0 text-xs font-bold uppercase tracking-widest text-ink-subtle transition-colors hover:text-ink">
            {{ __('Ver todo', 'sage') }}
          </a>
        </div>
      </div>

      {{-- Tarjetas grandes con gap reducido (1 col en móvil, 2 en sm, 3 en md/lg, 4 en xl) --}}
      <div id="featured-grid-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 transition-all duration-300">
        @foreach ($featuredProducts as $index => $product)
          <x-product-card :product="$product" list-id="home_featured" list-name="Destacados" :position="$index" />
        @endforeach
      </div>
    </section>
  @endif

  {{--
    Muro de reseñas con foto. Server-rendered y condicionado a un mínimo de
    contenido real: menos de 3 reseñas con foto se ve como una sección vacía,
    no como curación, así que directamente no se pinta.
  --}}
  @if (count($photoReviews) >= 3)
    <section class="border-y border-line bg-surface-raised py-12 md:py-16" data-reveal>
      <div class="rb-container">
        <x-carousel-shelf
          aria-label="{{ __('Reseñas de clientes con foto', 'sage') }}"
          tracking-id="home_photo_wall"
          autoplay
          :interval="6000"
          dots
          show-pause
          controls-position="top"
          title="{{ __('Rodando con RACING BIKE', 'sage') }}"
          subtitle="{{ __('Clientes reales', 'sage') }}"
        >
          @foreach ($photoReviews as $review)
            <a
              href="{{ $review['product_url'] }}"
              class="group flex w-[72%] shrink-0 snap-start flex-col rounded-lg border border-line bg-surface p-4 transition-colors hover:border-ink-faint sm:w-[42%] lg:w-[24%]"
              data-carousel-slide
            >
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1" aria-hidden="true">
                  @for ($i = 1; $i <= 5; $i++)
                    <span class="text-xs {{ $i <= $review['rating'] ? 'text-ink' : 'text-ink-faint' }}">★</span>
                  @endfor
                </div>

                @if (! empty($review['verified']))
                  <span class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-ink-subtle">
                    <x-icon name="shield-check" class="size-3" />
                    {{ __('Verificada', 'sage') }}
                  </span>
                @endif
              </div>

              <p class="mt-2.5 text-xs leading-relaxed text-ink line-clamp-3 md:text-sm">
                &ldquo;{{ $review['excerpt'] }}&rdquo;
              </p>

              {{--
                srcset real en vez de servir siempre el tamaño "large"
                (1024px) para una tarjeta que se pinta a ~280px: WordPress ya
                genera 300/600/768px al subir la foto de la reseña, sólo
                hacía falta pedirlos. photo_id puede faltar si el dato viene
                de una versión vieja del transient cacheado por el plugin —
                de ahí el fallback al <img> plano con la URL "large".
              --}}
              <div class="mt-3 aspect-video w-full overflow-hidden rounded bg-surface-muted">
                @php
                  $reviewAlt = sprintf(__('Foto de reseña de %s sobre %s en Racing Bike 1998', 'sage'), $review['author'] ?? 'Cliente', $review['product_name'] ?? 'Bicicleta');
                @endphp
                @if (! empty($review['photo_id']))
                  {!! wp_get_attachment_image($review['photo_id'], 'large', false, [
                    'alt' => $reviewAlt,
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'sizes' => '(min-width: 1024px) 24vw, (min-width: 640px) 42vw, 72vw',
                    'class' => 'size-full object-cover transition-transform duration-500 group-hover:scale-105',
                  ]) !!}
                @else
                  <img
                    src="{{ $review['photo'] }}"
                    alt="{{ $reviewAlt }}"
                    loading="lazy"
                    decoding="async"
                    class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                  >
                @endif
              </div>

              <p class="mt-2.5 text-[10px] font-semibold uppercase tracking-wide text-ink-subtle">
                {{ $review['author'] }} &middot; {{ $review['product_name'] }}
              </p>
            </a>
          @endforeach
        </x-carousel-shelf>
      </div>
    </section>
  @endif

  {{-- Banda editorial a 100% de ancho --}}
  <section class="border-y border-line bg-surface-raised" data-reveal>
    <div class="rb-container grid gap-10 py-16 md:grid-cols-2 md:items-center md:py-24">
      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Desde 1998', 'sage') }}</p>
        <h2 class="mt-3 text-3xl font-bold uppercase tracking-tight text-ink text-balance md:text-4xl">
          {{ __('Cada marco pasa por nuestras manos', 'sage') }}
        </h2>
        <p class="mt-5 max-w-xl text-sm leading-relaxed text-ink-muted md:text-base">
          {{ __('Ensamblamos, alineamos y ajustamos cada bicicleta en Bogotá antes de entregarla. No vendemos cajas: entregamos bicicletas listas para rodar, con la geometría ajustada a quien la va a montar.', 'sage') }}
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-4">
          <a
            href="{{ \App\whatsapp_url(__('Hola Racing Bike 1998, quisiera agendar una cita o mantenimiento en su taller de Bogotá.', 'sage')) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="group inline-flex items-center gap-2.5 px-7 py-3.5 md:px-8 md:py-4 rounded-full bg-emerald-400 text-black font-black text-xs uppercase tracking-widest hover:bg-emerald-300 active:scale-95 transition-all shadow-[0_10px_30px_rgba(16,185,129,0.25)] cursor-pointer"
          >
            <x-icon name="phone" class="size-4 shrink-0" />
            <span>{{ __('Agendar en el Taller', 'sage') }}</span>
          </a>

          <a
            href="{{ \App\page_url('sobre-nosotros') }}"
            class="inline-flex items-center gap-2 px-6 py-3.5 md:px-7 md:py-4 rounded-full border border-white/20 bg-white/5 text-ink hover:bg-white/10 hover:border-white/40 text-xs font-bold uppercase tracking-widest transition-all cursor-pointer"
          >
            <span>{{ __('Nuestra Historia', 'sage') }}</span>
            <x-icon name="chevron-right" class="size-3.5 text-ink-subtle" />
          </a>
        </div>
      </div>

      <dl class="grid grid-cols-2 gap-px overflow-hidden border border-line bg-line">
        @foreach ([
          ['value' => (string) $contact['years'], 'label' => __('años en la ruta', 'sage'), 'target' => $contact['years'], 'suffix' => ''],
          ['value' => '3', 'label' => __('puntos de servicio', 'sage'), 'target' => 3, 'suffix' => ''],
          ['value' => '48h', 'label' => __('para armado y ajuste', 'sage'), 'target' => 48, 'suffix' => 'h'],
          ['value' => '∞', 'label' => __('garantía en marcos', 'sage'), 'target' => null, 'suffix' => '∞'],
        ] as $stat)
          <div class="bg-surface-raised px-6 py-8">
            <dt class="sr-only">{{ $stat['label'] }}</dt>
            <dd>
              @if ($stat['target'] !== null)
                <span
                  class="block text-3xl font-bold tracking-tight text-ink md:text-4xl"
                  data-stat-counter
                  data-target="{{ $stat['target'] }}"
                  data-suffix="{{ $stat['suffix'] }}"
                >0</span>
              @else
                <span class="block text-3xl font-bold tracking-tight text-ink md:text-4xl">{{ $stat['value'] }}</span>
              @endif
              <span class="mt-1 block text-xs uppercase tracking-widest text-ink-subtle">{{ $stat['label'] }}</span>
            </dd>
          </div>
        @endforeach
      </dl>
    </div>
  </section>

  {{-- Marcas aliadas --}}
  <section class="rb-container py-12 md:py-16 border-t border-line" data-reveal>
    <p class="text-center text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">
      {{ __('Trabajamos con marcas reconocidas', 'sage') }}
    </p>
    <ul class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-4 md:gap-x-8">
      @foreach (\App\brands_with_logo() as $brand)
        <li class="h-16 md:h-24 w-auto flex items-center justify-center">
          <img
            src="{{ \App\brand_logo_url($brand) }}"
            alt="{{ $brand->name }}"
            class="h-full w-auto object-contain brand-logo-white-green"
          >
        </li>
      @endforeach
    </ul>
  </section>



  {{--
    Vistos recientemente: 100% client-side (ver app.js), oculta hasta que el
    JS confirma que hay al menos 2 productos en el historial del visitante —
    server-side no hay forma de saberlo. Va cerca del final para que, si el
    contenido aparece después del primer render, no desplace nada que el
    usuario ya esté viendo (sin impacto en CLS).
  --}}
  <section class="rb-container py-12 md:py-16" data-reveal hidden data-recently-viewed-shelf>
    <x-carousel-shelf
      aria-label="{{ __('Productos vistos recientemente', 'sage') }}"
      tracking-id="home_recently_viewed"
      controls-position="top"
      title="{{ __('Vistos recientemente', 'sage') }}"
      subtitle="{{ __('Para ti', 'sage') }}"
    >
      <div data-recently-viewed-track class="contents"></div>
    </x-carousel-shelf>
  </section>

  {{-- Muro social oficial de Instagram (@racing_bike98) mediante Smash Balloon --}}
  <x-instagram-feed :profile-url="$contact['instagram'] ?? 'https://www.instagram.com/racing_bike98/'" />

  {{-- Sección de FAQs en la Home --}}
  <section class="border-t border-line bg-[#080809]" data-reveal>
    <div class="rb-container grid grid-cols-1 lg:grid-cols-12 gap-10 md:gap-16 py-16 md:py-24">
      <div class="lg:col-span-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Respuestas rápidas', 'sage') }}</p>
        <h2 class="mt-3 text-3xl font-bold uppercase tracking-tight text-ink text-balance md:text-4xl">
          {{ __('Dudas frecuentes', 'sage') }}
        </h2>
        <p class="mt-4 text-sm leading-relaxed text-ink-muted">
          {{ __('Resolvemos los puntos clave antes de tu compra. Si no encuentras lo que buscas, puedes visitar el centro de ayuda completo.', 'sage') }}
        </p>

        <x-button variant="secondary" size="lg" :href="$faqsUrl" class="mt-8">
          {{ __('Ver todas las preguntas', 'sage') }}
        </x-button>
      </div>

      <div class="lg:col-span-7 divide-y divide-line/40">
        <x-accordion-item :title="__('¿Qué medios de pago y financiación manejan?', 'sage')">
          {{ __('Aceptamos pagos online mediante Mercado Pago (tarjetas de crédito, PSE). También admitimos financiación con ADDI y Sistecrédito, pagos contra entrega, transferencias directas y pago presencial en nuestra tienda.', 'sage') }}
        </x-accordion-item>

        <x-accordion-item :title="__('¿Realizan envíos a otras ciudades?', 'sage')">
          {{ __('Sí. Realizamos envíos desde Bogotá a diferentes ciudades de Colombia, dependiendo de la cobertura del operador logístico (principalmente Interrapidísimo).', 'sage') }}
        </x-accordion-item>

        <x-accordion-item :title="__('¿Cuánto tarda en llegar mi pedido y el armado?', 'sage')">
          {{ __('Los envíos nacionales tardan de 2 a 5 días hábiles. Si compras una bicicleta completa, el proceso de preparación, armado y ajuste en Bogotá nos toma 48 horas.', 'sage') }}
        </x-accordion-item>

        <x-accordion-item :title="__('¿Los productos tienen garantía?', 'sage')">
          {{ __('Sí. Los productos cuentan con garantía oficial. Nuestros marcos propios tienen Garantía de Por Vida por defectos de fábrica. Los componentes de otras marcas (Shimano, SRAM, etc.) tienen la garantía de sus distribuidores oficiales.', 'sage') }}
        </x-accordion-item>
      </div>
    </div>
  </section>

  <section class="border-t border-line">
    <div class="rb-container">
      <div class="grid grid-cols-1 divide-y divide-line md:grid-cols-3 md:divide-x md:divide-y-0">
        @foreach ([
          ['icon' => 'truck', 'title' => __('Envío nacional', 'sage'), 'copy' => __('A todo Colombia en 2 a 5 días hábiles.', 'sage')],
          ['icon' => 'shield-check', 'title' => __('Garantía de por vida', 'sage'), 'copy' => __('En marcos, respaldada desde 1998.', 'sage')],
          ['icon' => 'user', 'title' => __('Asesoría experta', 'sage'), 'copy' => __('Ajuste y recomendación personalizada.', 'sage')],
        ] as $feature)
          <div class="flex items-center gap-4 py-8 md:px-8 md:first:pl-0 md:last:pr-0">
            <x-icon :name="$feature['icon']" class="size-6 shrink-0 text-ink-subtle" />
            <div>
              <h3 class="text-sm font-medium text-ink">{{ $feature['title'] }}</h3>
              <p class="mt-1 text-xs text-ink-subtle">{{ $feature['copy'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endsection
