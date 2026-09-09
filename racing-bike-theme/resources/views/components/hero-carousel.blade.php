@props([
  'slides' => [],
  'interval' => 8000,
  'trackingId' => 'hero_home',
])

@if (count($slides))
  {{--
    Carrusel sobre scroll-snap nativo: sin librería (ahorra ~40 KB), deslizable
    con el dedo y navegable con teclado aunque el JS no cargue. app.js sólo
    añade autoplay, puntos e indicador de posición.
  --}}
  <section
    class="relative"
    data-carousel
    data-autoplay="true"
    data-interval="{{ $interval }}"
    data-ga4-context-id="{{ $trackingId }}"
    role="region"
    aria-roledescription="{{ __('carrusel', 'sage') }}"
    aria-label="{{ __('Colecciones destacadas', 'sage') }}"
  >
    {{-- Anuncia el cambio de diapositiva a lectores de pantalla sólo cuando
    lo dispara el usuario (el JS no la actualiza durante el autoplay). --}}
    <div class="sr-only" aria-live="polite" aria-atomic="true" data-carousel-live></div>

    <div
      class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
      data-carousel-track
    >
      @foreach ($slides as $index => $slide)
        <div
          class="relative w-full shrink-0 snap-center"
          data-carousel-slide
          data-ga4-promotion-id="{{ $slide['id'] ?? $index }}"
          data-ga4-promotion-name="{{ $slide['title'] }}"
          data-ga4-slot="{{ $index }}"
          role="group"
          aria-roledescription="{{ __('diapositiva', 'sage') }}"
          aria-label="{{ sprintf(__('%1$d de %2$d', 'sage'), $index + 1, count($slides)) }}"
        >
          <div class="relative aspect-4/5 md:aspect-16/7 overflow-hidden">
            @if (!empty($slide['video_desktop']) || !empty($slide['video_mobile']))
              {{--
                data-video-src en vez de src: evita que el navegador descargue
                AMBOS vídeos al parsear el HTML. hidden/md:block sólo cambia
                display, que no impide la descarga con preload distinto de
                "none". app.js activa un único <video> por viewport (el que
                matchMedia diga que corresponde) y nunca toca el otro.
              --}}
              @if (!empty($slide['video_desktop']))
                <video
                  data-video-src="{{ $slide['video_desktop'] }}"
                  data-video-variant="desktop"
                  class="absolute inset-0 size-full object-cover hidden md:block"
                  @if (!empty($slide['image_desktop']))
                    poster="{{ $slide['image_desktop'] }}"
                  @endif
                  loop
                  muted
                  playsinline
                  preload="none"
                ></video>
              @endif
              @if (!empty($slide['video_mobile']))
                <video
                  data-video-src="{{ $slide['video_mobile'] }}"
                  data-video-variant="mobile"
                  class="absolute inset-0 size-full object-cover md:hidden"
                  @if (!empty($slide['image_mobile']))
                    poster="{{ $slide['image_mobile'] }}"
                  @endif
                  loop
                  muted
                  playsinline
                  preload="none"
                ></video>
              @endif
            @elseif (!empty($slide['image_desktop']) || !empty($slide['image_mobile']))
              <picture class="size-full">
                @if (!empty($slide['image_desktop']))
                  <source media="(min-width: 768px)" srcset="{{ $slide['image_desktop'] }}">
                @endif
                <img
                  src="{{ $slide['image_mobile'] ?: $slide['image_desktop'] }}"
                  alt="{{ !empty($slide['alt']) ? $slide['alt'] : ($slide['title'] ?? 'Racing Bike 1998') }}"
                  class="size-full object-cover ken-burns"
                  @if ($index === 0)
                    fetchpriority="high"
                  @else
                    loading="lazy"
                  @endif
                  decoding="async"
                >
              </picture>
            @elseif (!empty($slide['image']))
              <img
                src="{{ $slide['image'] }}"
                alt="{{ !empty($slide['alt']) ? $slide['alt'] : ($slide['title'] ?? 'Racing Bike 1998') }}"
                class="size-full object-cover ken-burns"
                @if ($index === 0)
                  fetchpriority="high"
                @else
                  loading="lazy"
                @endif
                decoding="async"
              >
            @else
              <div class="size-full bg-surface-muted"></div>
            @endif

            {{-- Degradado desde abajo: mantiene legible el texto sobre cualquier foto. --}}
            <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/40 to-transparent"></div>

            <div class="absolute inset-x-0 bottom-0">
              <div class="rb-container pb-12 md:pb-20">
                <div class="max-w-xl">
                  @if (! empty($slide['eyebrow']))
                    <p class="text-xs font-semibold uppercase tracking-widest text-ink-muted">
                      {{ $slide['eyebrow'] }}
                    </p>
                  @endif

                  {{--
                    Sólo este carrusel se usa en la home, y sólo la primera
                    diapositiva es la que carga visible al pintar la página
                    (las demás llegan después, vía scroll-snap/autoplay), así
                    que es la única candidata real a h1: es el titular
                    principal de la portada y hasta ahora la home no
                    declaraba ninguno.
                  --}}
                  @if ($index === 0)
                    <h1 class="mt-3 text-3xl font-bold uppercase tracking-tight text-ink text-balance md:text-5xl">
                      {{ $slide['title'] }}
                    </h1>
                  @else
                    <h2 class="mt-3 text-3xl font-bold uppercase tracking-tight text-ink text-balance md:text-5xl">
                      {{ $slide['title'] }}
                    </h2>
                  @endif

                  @if (! empty($slide['url']))
                    @php
                      $slideHref = str_starts_with($slide['url'], 'http') ? $slide['url'] : home_url($slide['url']);
                    @endphp
                    <x-button variant="primary" size="lg" :href="$slideHref" class="mt-6">
                      {{ $slide['cta'] ?: __('Ver colección', 'sage') }}
                    </x-button>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if (count($slides) > 1)
      {{-- Controles: flechas en escritorio, puntos en todos los tamaños. --}}
      <button
        type="button"
        class="absolute left-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center bg-surface/60 text-ink backdrop-blur transition-colors hover:bg-surface md:flex"
        data-carousel-prev
        aria-label="{{ __('Anterior', 'sage') }}"
      >
        <x-icon name="chevron-right" class="size-5 rotate-180" />
      </button>

      <button
        type="button"
        class="absolute right-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center bg-surface/60 text-ink backdrop-blur transition-colors hover:bg-surface md:flex"
        data-carousel-next
        aria-label="{{ __('Siguiente', 'sage') }}"
      >
        <x-icon name="chevron-right" class="size-5" />
      </button>

      <div class="absolute inset-x-0 bottom-5 flex items-center justify-center gap-3">
        {{--
          Botones planos, no pestañas: no hay ningún role="tabpanel" detrás
          de cada diapositiva (son role="group"), así que role="tablist"/"tab"
          aquí sería ARIA inválido — un lector de pantalla anunciaría pestañas
          que no existen.
        --}}
        <div class="flex items-center gap-2" aria-label="{{ __('Ir a la diapositiva', 'sage') }}">
          @foreach ($slides as $index => $slide)
            {{--
              El punto visual mide 2px de alto; el botón es de 24px×24px —
              un área táctil de 2px es casi imposible de tocar con el dedo.
            --}}
            <button
              type="button"
              class="group flex h-6 w-8 items-center justify-center"
              data-carousel-dot
              data-active="{{ $index === 0 ? 'true' : 'false' }}"
              aria-label="{{ sprintf(__('Ir a la diapositiva %d', 'sage'), $index + 1) }}"
            >
              <span class="block h-0.5 w-full bg-ink-faint transition-colors group-data-[active=true]:bg-ink"></span>
            </button>
          @endforeach
        </div>

        <button
          type="button"
          class="ml-2 text-ink-muted transition-colors hover:text-ink"
          data-carousel-pause
          data-pause-label="{{ __('Pausar carrusel', 'sage') }}"
          data-resume-label="{{ __('Reanudar carrusel', 'sage') }}"
          aria-label="{{ __('Pausar carrusel', 'sage') }}"
        >
          <span class="block size-2.5 border-x-2 border-current" data-carousel-pause-icon></span>
        </button>
      </div>
    @endif
  </section>
@endif
