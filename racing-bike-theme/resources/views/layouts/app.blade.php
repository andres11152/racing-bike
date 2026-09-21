<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ── Preconnects de rendimiento ──────────────────────────────────────
         fonts.googleapis / gstatic acortan el chain crítico de fuentes.
         s3.amazonaws.com y cdn.addi.com solo se precargan si Addi está activo.
    ── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if (is_product() || is_cart() || is_checkout())
      <link rel="preconnect" href="https://s3.amazonaws.com" crossorigin>
      <link rel="preconnect" href="https://cdn.addi.com" crossorigin>
    @endif

    {{-- Google Fonts asíncronas para eliminar render-blocking --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syncopate:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syncopate:wght@400;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
      <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syncopate:wght@400;700&display=swap">
    </noscript>

    @php(do_action('get_header'))
    @php(wp_head())

    {{-- app.js es un módulo Vite cargado directo (sin wp_enqueue_script), así
         que wp_localize_script() no puede engancharse aquí: se imprime como
         global de window en su lugar. --}}
    <script>window.rbAjax = @json($ajax);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    {{-- Fondo ambiental de haces de luz — fijo al viewport, detrás de todo.
         Se asoma por las secciones sin fondo opaco propio (héroe, franjas
         editoriales) y por las superficies con backdrop-blur (header, modales). --}}
    <canvas data-beams-canvas class="fixed inset-0 z-0 pointer-events-none blur-[15px]" aria-hidden="true"></canvas>
    <div data-beams-pulse class="fixed inset-0 z-0 pointer-events-none rb-beams-overlay bg-surface/5" aria-hidden="true"></div>

    <div id="app" class="relative z-10 flex min-h-screen flex-col">
      <a class="sr-only focus:not-sr-only" href="#main">
        {{ __('Saltar al contenido', 'sage') }}
      </a>

      @include('sections.header')

      <main id="main" class="main flex-1">
        @yield('content')
      </main>

      @hasSection('sidebar')
        <aside class="sidebar">
          @yield('sidebar')
        </aside>
      @endif

      @include('sections.footer')

      <x-cart-drawer />
      <x-whatsapp-button />
      <x-scroll-up />

      {{-- Modal de Vista Rápida (Quick View Modal) --}}
      <x-quick-view-modal />

      {{-- Modal de Búsqueda Rápida --}}
      <x-search-overlay />
      
      {{-- Cinematic Bottom Glass Blur Overlay --}}
      <div data-bottom-blur class="fixed bottom-0 inset-x-0 h-24 pointer-events-none z-30 bg-gradient-to-t from-white/[0.04] via-white/[0.01] to-transparent backdrop-blur-[1.5px] opacity-0 transition-opacity duration-700 hidden md:block"></div>
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
    
    <x-cookie-banner />
  </body>
</html>
