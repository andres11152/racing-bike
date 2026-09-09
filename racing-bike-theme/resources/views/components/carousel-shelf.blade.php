@props([
  'trackingId' => null,
  'ariaLabel' => '',
  'autoplay' => false,
  'interval' => 7000,
  'dots' => false,
  'showPause' => false,
  'controlsPosition' => 'side', // 'side' o 'top'
  'title' => '',
  'subtitle' => '',
])

<div
  {{ $attributes->merge(['class' => 'relative']) }}
  data-carousel
  data-autoplay="{{ $autoplay ? 'true' : 'false' }}"
  data-interval="{{ $interval }}"
  @if ($trackingId)
    data-ga4-context-id="{{ $trackingId }}"
  @endif
  role="region"
  aria-roledescription="{{ __('carrusel', 'sage') }}"
  aria-label="{{ $ariaLabel }}"
>
  <div class="sr-only" aria-live="polite" aria-atomic="true" data-carousel-live></div>

  {{-- Controles arriba en un encabezado flex --}}
  @if ($controlsPosition === 'top')
    <div class="mb-8 flex items-end justify-between gap-4">
      <div class="min-w-0">
        @if ($subtitle)
          <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ $subtitle }}</p>
        @endif
        @if ($title)
          <h2 class="mt-2 text-xl sm:text-2xl font-bold uppercase tracking-widest text-ink md:text-3xl break-words">
            {{ $title }}
          </h2>
        @endif
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <button
          type="button"
          class="flex size-10 items-center justify-center rounded-xl border border-line bg-surface text-ink hover:bg-surface-muted transition-colors cursor-pointer"
          data-carousel-prev
          aria-label="{{ __('Anterior', 'sage') }}"
        >
          <x-icon name="chevron-right" class="size-4 rotate-180" />
        </button>
        <button
          type="button"
          class="flex size-10 items-center justify-center rounded-xl border border-line bg-surface text-ink hover:bg-surface-muted transition-colors cursor-pointer"
          data-carousel-next
          aria-label="{{ __('Siguiente', 'sage') }}"
        >
          <x-icon name="chevron-right" class="size-4" />
        </button>
      </div>
    </div>
  @endif

  <div
    class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    data-carousel-track
  >
    {{ $slot }}
  </div>

  {{-- Controles clásicos laterales si no van arriba --}}
  @if ($controlsPosition !== 'top')
    <button
      type="button"
      class="absolute left-0 top-[38%] hidden size-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-surface text-ink shadow-md transition-colors hover:bg-surface-muted md:flex"
      data-carousel-prev
      aria-label="{{ __('Anterior', 'sage') }}"
    >
      <x-icon name="chevron-right" class="size-4 rotate-180" />
    </button>

    <button
      type="button"
      class="absolute right-0 top-[38%] hidden size-10 translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-surface text-ink shadow-md transition-colors hover:bg-surface-muted md:flex"
      data-carousel-next
      aria-label="{{ __('Siguiente', 'sage') }}"
    >
      <x-icon name="chevron-right" class="size-4" />
    </button>
  @endif

  @if ($dots || $showPause || $autoplay)
    <div class="mt-4 flex items-center justify-center gap-3">
      @if ($dots)
        <div class="flex flex-wrap items-center justify-center gap-2" data-carousel-dots aria-label="{{ __('Ir a la diapositiva', 'sage') }}"></div>
      @endif

      @if ($showPause || $autoplay)
        <button
          type="button"
          class="text-ink-muted transition-colors hover:text-ink"
          data-carousel-pause
          data-pause-label="{{ __('Pausar carrusel', 'sage') }}"
          data-resume-label="{{ __('Reanudar carrusel', 'sage') }}"
          aria-label="{{ __('Pausar carrusel', 'sage') }}"
        >
          <span class="block size-2.5 border-x-2 border-current" data-carousel-pause-icon></span>
        </button>
      @endif
    </div>
  @endif
</div>
