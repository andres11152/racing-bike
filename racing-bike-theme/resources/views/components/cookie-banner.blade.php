@props([
  'policyUrl' => \App\site_links()['privacy'] ?? (get_privacy_policy_url() ?: '#'),
])

<div
  id="cookie-banner"
  {{--
    bottom-24/md:bottom-28 en vez de bottom-3/md:bottom-6: el botón de
    WhatsApp y el de volver arriba viven en bottom-5/bottom-10 con right-4 y
    left-4 respectivamente (ver whatsapp-button y scroll-up). Con el offset
    anterior este banner —a ancho completo en móvil, z-[200]— quedaba encima
    de ambos mientras el visitante no daba consentimiento, tapando el canal
    de venta principal del sitio.
  --}}
  class="fixed bottom-24 inset-x-3 md:bottom-28 md:right-6 md:left-auto md:max-w-md z-[200] transform translate-y-12 opacity-0 pointer-events-none transition-all duration-500 ease-out"
  role="dialog"
  aria-labelledby="cookie-title"
  aria-describedby="cookie-desc"
>
  <div class="relative overflow-hidden rounded-2xl md:rounded-3xl bg-[#0A0A0B]/95 border border-line p-4 md:p-6 shadow-2xl backdrop-blur-2xl">
    {{-- Efecto de luz tenue de fondo --}}
    <div class="absolute -right-10 -top-10 -z-10 size-28 rounded-full bg-emerald-500/10 blur-2xl"></div>

    <div class="flex items-start gap-3 md:gap-4">
      <div class="flex size-8 md:size-10 shrink-0 items-center justify-center rounded-xl md:rounded-2xl bg-surface-raised border border-line text-emerald-400">
        <x-icon name="shield-check" class="size-4 md:size-5" />
      </div>

      <div class="flex-1">
        <h3 id="cookie-title" class="text-xs md:text-sm font-bold uppercase tracking-wider text-white">
          {{ __('Privacidad & Cookies', 'sage') }}
        </h3>
        <p id="cookie-desc" class="mt-1 md:mt-2 text-[11px] md:text-xs leading-relaxed text-ink-muted">
          {{ __('Utilizamos cookies para optimizar tu navegación y procesar pedidos de forma segura según la Ley 1581 en Colombia.', 'sage') }}
          @if ($policyUrl !== '#')
            <a href="{{ $policyUrl }}" class="underline hover:text-white transition-colors ml-1">
              {{ __('Ver datos.', 'sage') }}
            </a>
          @endif
        </p>
      </div>
    </div>

    <div class="mt-3 md:mt-5 flex items-center justify-end gap-2 md:gap-3 border-t border-line/40 pt-3 md:pt-4">
      <button
        type="button"
        id="cookie-reject"
        class="rounded-full px-3 py-1.5 text-[11px] md:text-xs font-semibold text-ink-subtle hover:text-white transition-colors cursor-pointer"
      >
        {{ __('Solo Necesarias', 'sage') }}
      </button>

      <button
        type="button"
        id="cookie-accept"
        class="rounded-full bg-white px-4 py-2 md:px-5 md:py-2.5 text-[11px] md:text-xs font-bold uppercase tracking-wider text-black hover:bg-neutral-200 active:scale-95 transition-all cursor-pointer shadow-md"
      >
        {{ __('Aceptar', 'sage') }}
      </button>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const banner = document.getElementById('cookie-banner');
    const acceptBtn = document.getElementById('cookie-accept');
    const rejectBtn = document.getElementById('cookie-reject');

    if (!banner || !acceptBtn || !rejectBtn) return;

    // Verificar si ya existe consentimiento guardado
    const consent = localStorage.getItem('rb_cookies_consent');

    if (!consent) {
      // Mostrar banner con retraso sutil para mejor UX
      setTimeout(() => {
        banner.classList.remove('pointer-events-none', 'translate-y-12', 'opacity-0');
        banner.classList.add('translate-y-0', 'opacity-100');
      }, 1500);
    }

    const hideBanner = () => {
      banner.classList.remove('translate-y-0', 'opacity-100');
      banner.classList.add('translate-y-12', 'opacity-0', 'pointer-events-none');
    };

    acceptBtn.addEventListener('click', () => {
      localStorage.setItem('rb_cookies_consent', 'accepted');
      hideBanner();
      // Disparar evento para scripts externos (Analytics, Pixel, etc.)
      window.dispatchEvent(new CustomEvent('cookies-accepted'));
    });

    rejectBtn.addEventListener('click', () => {
      localStorage.setItem('rb_cookies_consent', 'rejected');
      hideBanner();
      window.dispatchEvent(new CustomEvent('cookies-rejected'));
    });
  });
</script>
