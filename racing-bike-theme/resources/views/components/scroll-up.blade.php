<button
  type="button"
  data-scroll-up
  class="fixed bottom-5 left-4 md:bottom-10 md:left-6 z-40 flex size-11 items-center justify-center rounded-full border border-emerald-500/30 bg-black/90 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.15)] transition-all duration-300 translate-y-4 opacity-0 pointer-events-none hover:border-emerald-400 hover:bg-emerald-500 hover:text-white hover:scale-105 active:scale-95 group"
  aria-label="{{ __('Subir al inicio', 'sage') }}"
>
  <svg class="size-5 transition-transform duration-300 group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
  </svg>
</button>

<script>
  (function() {
    const btn = document.querySelector('[data-scroll-up]');
    if (!btn) return;

    const toggleScrollButton = () => {
      if (window.scrollY > 400) {
        btn.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
        btn.classList.add('opacity-100', 'translate-y-0');
      } else {
        btn.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
        btn.classList.remove('opacity-100', 'translate-y-0');
      }
    };

    window.addEventListener('scroll', toggleScrollButton, { passive: true });
    
    btn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  })();
</script>
