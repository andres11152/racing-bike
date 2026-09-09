{{--
  Footer interactivo de 21st.dev (HoverFooter) adaptado a Racing Bike 1998 con Blade + Tailwind CSS.
--}}

<footer class="relative overflow-hidden bg-[#0A0A0B] border border-line my-6 mx-4 sm:mx-6 md:mx-8 rounded-3xl text-ink z-10 shadow-2xl">
  {{-- Degradado de fondo radial de 21st.dev --}}
  <div
    class="absolute inset-0 z-0 pointer-events-none"
    style="background: radial-gradient(125% 125% at 50% 10%, #0F0F11 40%, rgba(255, 255, 255, 0.08) 100%);"
  ></div>

  <div class="w-full px-6 py-12 md:px-12 md:py-16 z-10 relative">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 pb-10 border-b border-line/60">
      
      {{-- Columna 1: Marca y Propuesta de Valor --}}
      <div class="flex flex-col space-y-4">
        <a href="{{ home_url('/') }}" class="flex items-center space-x-2 group">
          <div class="rb-logo-wrapper">
            <img
              src="{{ get_theme_file_uri('public/images/logo-blanco.svg') }}"
              alt="{!! $siteName !!}"
              class="h-32 md:h-44 w-auto object-contain"
            >
          </div>
        </a>
        <p class="text-xs leading-relaxed text-ink-muted">
          {{ sprintf(__('Bicicletas de alto rendimiento, taller especializado y asesoría experta en Bogotá. %d años acompañando ciclistas.', 'sage'), $contact['years']) }}
        </p>

        <div class="pt-2 flex items-center space-x-4 text-emerald-400">
          <a href="{{ $contact['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="hover:text-white transition-colors">
            <x-icon name="instagram" class="size-5" />
          </a>
          <a href="{{ $contact['facebook'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="hover:text-white transition-colors">
            <x-icon name="facebook" class="size-5" />
          </a>
          @if (!empty($contact['tiktok']))
            <a href="{{ $contact['tiktok'] }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok" class="hover:text-white transition-colors">
              <x-icon name="tiktok" class="size-5" />
            </a>
          @endif
        </div>
      </div>

      {{-- Columna 2: Navegación Tienda --}}
      <div>
        <h2 class="text-white text-xs font-bold uppercase tracking-widest mb-5">
          {{ __('Tienda & Catálogo', 'sage') }}
        </h2>
        <ul class="space-y-3 text-xs text-ink-subtle">
          <li><a href="{{ $links['road'] }}" class="hover:text-ink transition-colors">{{ __('Bicicletas de Ruta', 'sage') }}</a></li>
          <li><a href="{{ $links['gravel'] }}" class="hover:text-ink transition-colors">{{ __('Bicicletas Gravel', 'sage') }}</a></li>
          <li><a href="{{ $links['parts'] }}" class="hover:text-ink transition-colors">{{ __('Componentes & Grupos', 'sage') }}</a></li>
          <li class="relative">
            <a href="{{ $links['sizeGuide'] }}" class="hover:text-ink transition-colors">{{ __('Asesoría en Talla de Marco', 'sage') }}</a>
            <span class="absolute top-0 right-[-10px] w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </li>
        </ul>
      </div>

      {{-- Columna 3: Soporte & Garantías --}}
      <div>
        <h2 class="text-white text-xs font-bold uppercase tracking-widest mb-5">
          {{ __('Atención & Garantía', 'sage') }}
        </h2>
        <ul class="space-y-3 text-xs text-ink-subtle">
          <li><a href="{{ $links['warranty'] }}" class="hover:text-ink transition-colors">{{ __('Garantía de Por Vida en Marcos', 'sage') }}</a></li>
          <li><a href="{{ $links['shipping'] }}" class="hover:text-ink transition-colors">{{ __('Envíos Nacionales (Interrapidísimo)', 'sage') }}</a></li>
          <li>
            <a href="{{ \App\whatsapp_url(__('Hola Racing Bike 1998, quisiera agendar un servicio de taller.', 'sage')) }}" target="_blank" rel="noopener noreferrer" class="hover:text-ink transition-colors">
              {{ __('Agendamiento de Taller Bogotá', 'sage') }}
            </a>
          </li>
          <li><a href="{{ $links['returns'] }}" class="hover:text-ink transition-colors">{{ __('Políticas de Cambio & Devoluciones', 'sage') }}</a></li>
          <li><a href="{{ $links['faqs'] }}" class="hover:text-ink transition-colors">{{ __('Preguntas Frecuentes', 'sage') }}</a></li>
          <li><a href="{{ $links['about'] }}" class="hover:text-ink transition-colors">{{ __('Sobre Nosotros', 'sage') }}</a></li>
          <li><a href="{{ $links['contact'] }}" class="hover:text-ink transition-colors">{{ __('Contacto', 'sage') }}</a></li>
        </ul>
      </div>

      {{-- Columna 4: Contacto Oficial Bogotá --}}
      <div>
        <h2 class="text-white text-xs font-bold uppercase tracking-widest mb-5">
          {{ __('Contacto Oficial', 'sage') }}
        </h2>

        {{-- Minimapa de Google Maps con filtro oscuro estético (ancho completo adaptable) --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-line bg-surface-muted h-36 sm:h-40 lg:h-44 w-full max-w-full">
          <iframe
            src="{{ $contact['maps_embed'] }}"
            class="size-full border-0 grayscale invert contrast-125 opacity-70 transition-all hover:grayscale-0 hover:invert-0 hover:opacity-100"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>

        <ul class="space-y-3.5 text-xs text-ink-subtle">
          <li class="flex items-start space-x-3">
            <x-icon name="map-pin" class="size-4 text-emerald-400 shrink-0 mt-0.5" />
            <a href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-ink transition-colors">
              {{ $contact['address'] }}, {{ $contact['city'] }}
            </a>
          </li>
          <li class="flex items-center space-x-3">
            <x-icon name="phone" class="size-4 text-emerald-400 shrink-0" />
            <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-ink transition-colors font-medium">
              WhatsApp: {{ $contact['whatsapp_display'] }}
            </a>
          </li>
          <li class="flex items-center space-x-3">
            <x-icon name="mail" class="size-4 text-emerald-400 shrink-0" />
            <a href="mailto:{{ $contact['email'] }}" class="hover:text-ink transition-colors">
              {{ $contact['email'] }}
            </a>
          </li>
          <li class="text-[10px] text-ink-subtle pt-1">
            NIT: {{ $contact['nit'] }}
          </li>
        </ul>
      </div>

    </div>

    {{-- Fila Inferior: Copyright e Legales --}}
    <div class="flex flex-col md:flex-row justify-between items-center text-xs text-ink-subtle pt-6 space-y-4 md:space-y-0">
      <p class="text-center md:text-left">
        &copy; {{ date('Y') }} {!! $siteName !!}. {{ __('Todos los derechos reservados.', 'sage') }}
      </p>
      <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
        <a href="{{ $links['privacy'] }}" class="hover:text-ink transition-colors">{{ __('Tratamiento de Datos (Ley 1581)', 'sage') }}</a>
        <a href="{{ $links['terms'] }}" class="hover:text-ink transition-colors">{{ __('Términos & Condiciones', 'sage') }}</a>
      </div>
    </div>
  </div>

  {{-- Efecto Interactivo 21st.dev: Text Hover Effect con Máscara SVG --}}
  <div class="lg:flex hidden h-[16rem] md:h-[20rem] -mt-8 -mb-6 items-center justify-center overflow-hidden select-none pointer-events-auto border-t border-line/40">
    <svg
      data-footer-text-effect
      width="100%"
      height="100%"
      viewBox="0 0 500 85"
      xmlns="http://www.w3.org/2000/svg"
      class="select-none uppercase cursor-pointer w-full h-full"
    >
      <defs>
        {{-- Degradado multicolor al hacer hover --}}
        <linearGradient id="rbTextGradient" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#ffffff" />
          <stop offset="25%" stop-color="#f3f4f6" />
          <stop offset="50%" stop-color="#10b981" />
          <stop offset="75%" stop-color="#10b981" />
          <stop offset="100%" stop-color="#ffffff" />
        </linearGradient>

        {{-- Máscara radial que sigue el puntero del mouse --}}
        <radialGradient id="rbRevealMask" gradientUnits="userSpaceOnUse" r="35%" cx="50%" cy="50%">
          <stop offset="0%" stop-color="white" />
          <stop offset="100%" stop-color="black" />
        </radialGradient>

        <mask id="rbTextMask">
          <rect x="0" y="0" width="100%" height="100%" fill="url(#rbRevealMask)" />
        </mask>
      </defs>

      {{-- Capa base transparente con trazo tenue --}}
      <text
        x="50%"
        y="50%"
        text-anchor="middle"
        dominant-baseline="middle"
        stroke-width="0.5"
        class="fill-transparent stroke-neutral-800 font-sans text-[72px] font-black tracking-widest opacity-60"
      >
        RACING BIKE
      </text>

      {{-- Capa animada con trazo de luz --}}
      <text
        x="50%"
        y="50%"
        text-anchor="middle"
        dominant-baseline="middle"
        stroke-width="0.5"
        class="fill-transparent stroke-white/30 font-sans text-[72px] font-black tracking-widest"
      >
        RACING BIKE
      </text>

      {{-- Capa revelada por la máscara al mover el mouse --}}
      <text
        x="50%"
        y="50%"
        text-anchor="middle"
        dominant-baseline="middle"
        stroke="url(#rbTextGradient)"
        stroke-width="0.7"
        mask="url(#rbTextMask)"
        class="fill-transparent font-sans text-[72px] font-black tracking-widest"
      >
        RACING BIKE
      </text>
    </svg>
  </div>
</footer>
