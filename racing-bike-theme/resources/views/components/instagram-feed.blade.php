@props([
  'profileUrl' => 'https://www.instagram.com/racing_bike98/',
  'handle' => '@racing_bike98',
])

<section class="border-t border-line bg-[#0A0A0B] py-14 md:py-20" data-reveal>
  <div class="rb-container">
    {{-- Encabezado oficial del Muro de Instagram --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex size-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
            {{ __('Comunidad #RacingBike', 'sage') }}
          </p>
        </div>
        <h2 class="mt-2 text-2xl font-bold uppercase tracking-tight text-ink md:text-3xl">
          {{ __('Síguenos en Instagram', 'sage') }}
        </h2>
      </div>

      <a
        href="{{ $profileUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        class="group inline-flex items-center gap-2.5 rounded-full border border-line bg-surface px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-ink transition-all hover:border-ink hover:bg-surface-raised"
      >
        <x-icon name="instagram" class="size-4 text-ink transition-transform group-hover:scale-110" />
        <span>{{ $handle }}</span>
        <x-icon name="chevron-right" class="size-3.5 transition-transform group-hover:translate-x-1" />
      </a>
    </div>

    {{-- Renderizado del Feed Oficial de Smash Balloon --}}
    <div class="rb-instagram-official-feed [&_.sbi_header]:hidden [&_#sbi_load]:hidden [&_.sb_instagram_header]:hidden">
      {!! do_shortcode('[instagram-feed showheader=false showbutton=false showfollow=false cols=6 num=6]') !!}
    </div>
  </div>
</section>

