<div
  id="preloader"
  class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden bg-surface transition-opacity duration-700 ease-out"
  data-preloader
>
  {{-- Resplandor ambiental — mismo verde del fondo de haces del sitio. --}}
  <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
    <div class="preloader-glow size-72 rounded-full bg-emerald-500/25 blur-[90px] md:size-96"></div>
  </div>

  <div class="relative flex flex-col items-center text-center px-4">
    {{-- Mismo logotipo que el footer, no un texto aparte. --}}
    <img
      src="{{ get_theme_file_uri('public/images/logo-blanco.svg') }}"
      alt="{{ get_bloginfo('name') }}"
      class="preloader-logo h-40 w-auto object-contain md:h-56"
    >

    <div class="mt-8 h-[3px] w-40 overflow-hidden rounded-full bg-line-strong/60 relative">
      <div class="preloader-bar absolute inset-y-0 left-0 w-1/2 rounded-full animate-marquee"></div>
    </div>

    <p class="preloader-caption mt-4 text-[10px] uppercase tracking-[0.25em] text-ink-subtle">
      {{ __('Cargando experiencia...', 'sage') }}
    </p>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const preloader = document.getElementById('preloader');
    if (preloader) {
      setTimeout(() => {
        preloader.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => preloader.remove(), 700);
      }, 300);
    }
  });
</script>
