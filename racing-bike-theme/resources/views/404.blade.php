@extends('layouts.app')

@section('content')
  @php
    $suggestedProducts = function_exists('wc_get_products') ? wc_get_products([
        'limit' => 4,
        'status' => 'publish',
        'orderby' => 'popularity',
        'order' => 'DESC',
    ]) : [];

    if (empty($suggestedProducts) && function_exists('wc_get_products')) {
        $suggestedProducts = wc_get_products([
            'limit' => 4,
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    $links = \App\site_links();
    $contact = \App\contact_info();
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
  @endphp

  <div class="relative overflow-hidden pt-12 pb-20 md:pt-16 md:pb-28">
    {{-- Efectos de iluminación y profundidad de fondo --}}
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 size-96 sm:size-[550px] bg-emerald-500/10 rounded-full blur-[130px] pointer-events-none -z-10"></div>
    <div class="absolute top-1/3 left-1/4 size-72 bg-white/[0.02] rounded-full blur-[100px] pointer-events-none -z-10"></div>

    <div class="rb-container">
      {{-- Hero Section 404 --}}
      <div class="text-center max-w-3xl mx-auto">
        {{-- Badge pill con acento esmeralda pulsante --}}
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-widest backdrop-blur-md mb-6 shadow-sm">
          <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
          {{ __('Error 404 • Fuera de ruta', 'sage') }}
        </div>

        {{-- Gran número 404 con tipografía futurista deportiva --}}
        <div class="relative inline-block select-none my-2">
          <span class="text-8xl sm:text-[130px] md:text-[160px] font-black uppercase tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-white via-neutral-200 to-neutral-700 leading-none font-display drop-shadow-[0_20px_40px_rgba(0,0,0,0.8)]">
            404
          </span>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-20 blur-sm">
            <span class="text-8xl sm:text-[130px] md:text-[160px] font-black uppercase tracking-tighter text-emerald-400 leading-none font-display">
              404
            </span>
          </div>
        </div>

        {{-- Titular y Mensaje en Español --}}
        <h1 class="mt-4 text-2xl sm:text-4xl md:text-5xl font-bold uppercase tracking-tight text-white leading-tight font-display">
          {{ __('Te has salido del pelotón', 'sage') }}
        </h1>
        <p class="mt-4 text-sm sm:text-base text-ink-muted max-w-xl mx-auto leading-relaxed">
          {{ __('La página, ruta o bicicleta que buscas no existe, fue reubicada o cambió de trayectoria. No pierdas el ritmo, usemos el mapa para volver a conectar con la carrera.', 'sage') }}
        </p>

        {{-- Buscador Enterprise Inteligente --}}
        <div class="mt-8 max-w-lg mx-auto w-full">
          <form role="search" method="get" class="relative flex items-center w-full group" action="{{ home_url('/') }}">
            <div class="absolute left-4.5 pointer-events-none text-ink-subtle group-focus-within:text-emerald-400 transition-colors">
              <x-icon name="search" class="size-5" />
            </div>
            <input
              type="search"
              name="s"
              placeholder="{{ __('Buscar bicicletas, componentes, accesorios...', 'sage') }}"
              class="w-full pl-12 pr-28 py-3.5 sm:py-4 rounded-full border border-line-strong bg-[#121316]/90 backdrop-blur-md text-sm text-white placeholder:text-neutral-500 focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 transition-all shadow-xl"
              required
            />
            <button
              type="submit"
              class="absolute right-2 top-2 bottom-2 px-5 rounded-full bg-white text-black font-bold text-xs uppercase tracking-wider hover:bg-neutral-200 active:scale-95 transition-all cursor-pointer inline-flex items-center justify-center shadow-md"
            >
              {{ __('Buscar', 'sage') }}
            </button>
          </form>
        </div>

        {{-- Botones de Acción Primaria --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3.5">
          <a
            href="{{ $shopUrl }}"
            class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-full bg-white text-black font-bold text-xs uppercase tracking-widest hover:bg-neutral-200 active:scale-95 transition-all shadow-lg cursor-pointer"
          >
            <x-icon name="shopping-bag" class="size-4" />
            <span>{{ __('Explorar Catálogo', 'sage') }}</span>
          </a>

          <a
            href="{{ home_url('/') }}"
            class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-full bg-surface-raised text-white hover:bg-surface-muted border border-line font-bold text-xs uppercase tracking-widest active:scale-95 transition-all cursor-pointer"
          >
            <span>{{ __('Volver al Inicio', 'sage') }}</span>
          </a>
        </div>
      </div>

      {{-- Hub de Accesos Rápidos (4 tarjetas interactivas) --}}
      <div class="mt-16 sm:mt-20">
        <div class="text-center mb-8">
          <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-400 mb-1">
            {{ __('Rutas sugeridas', 'sage') }}
          </p>
          <h2 class="text-lg sm:text-xl font-bold uppercase tracking-wider text-white">
            {{ __('¿Hacia dónde quieres pedalear?', 'sage') }}
          </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {{-- Tarjeta 1: Bicicletas --}}
          <a
            href="{{ $links['road'] ?? home_url('/categoria-producto/bicicletas/') }}"
            class="group relative flex flex-col justify-between p-6 rounded-2xl border border-line bg-surface-raised/40 hover:bg-surface-raised hover:border-emerald-500/40 transition-all duration-300 shadow-sm hover:shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)]"
          >
            <div>
              <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-4 group-hover:scale-110 transition-transform">
                <x-icon name="sparkles" class="size-5" />
              </div>
              <h3 class="text-sm font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors">
                {{ __('Bicicletas de Gama Alta', 'sage') }}
              </h3>
              <p class="mt-1.5 text-xs text-ink-subtle leading-relaxed">
                {{ __('Modelos de Ruta y Gravel en carbono y aluminio aerodinámico.', 'sage') }}
              </p>
            </div>
            <div class="mt-5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-emerald-400 group-hover:translate-x-1 transition-transform">
              <span>{{ __('Ver modelos', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-3.5" />
            </div>
          </a>

          {{-- Tarjeta 2: Componentes --}}
          <a
            href="{{ $links['parts'] ?? home_url('/categoria-producto/componentes/') }}"
            class="group relative flex flex-col justify-between p-6 rounded-2xl border border-line bg-surface-raised/40 hover:bg-surface-raised hover:border-emerald-500/40 transition-all duration-300 shadow-sm hover:shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)]"
          >
            <div>
              <div class="flex size-11 items-center justify-center rounded-xl bg-white/5 text-white border border-white/10 mb-4 group-hover:scale-110 transition-transform">
                <x-icon name="wrench" class="size-5" />
              </div>
              <h3 class="text-sm font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors">
                {{ __('Grupos & Componentes', 'sage') }}
              </h3>
              <p class="mt-1.5 text-xs text-ink-subtle leading-relaxed">
                {{ __('Transmisiones Shimano, ruedas tubeless, marcos y repuestos.', 'sage') }}
              </p>
            </div>
            <div class="mt-5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-white group-hover:text-emerald-400 group-hover:translate-x-1 transition-transform">
              <span>{{ __('Ver catálogo', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-3.5" />
            </div>
          </a>

          {{-- Tarjeta 3: Encuentra tu talla --}}
          <a
            href="{{ $links['sizeGuide'] }}"
            class="group relative flex flex-col justify-between p-6 rounded-2xl border border-line bg-surface-raised/40 hover:bg-surface-raised hover:border-emerald-500/40 transition-all duration-300 shadow-sm hover:shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)]"
          >
            <div>
              <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-4 group-hover:scale-110 transition-transform">
                <x-icon name="ruler" class="size-5" />
              </div>
              <h3 class="text-sm font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors">
                {{ __('Encuentra tu talla', 'sage') }}
              </h3>
              <p class="mt-1.5 text-xs text-ink-subtle leading-relaxed">
                {{ __('Calcula tu talla ideal según tu estatura en menos de un minuto.', 'sage') }}
              </p>
            </div>
            <div class="mt-5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-emerald-400 group-hover:translate-x-1 transition-transform">
              <span>{{ __('Calcular talla', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-3.5" />
            </div>
          </a>

          {{-- Tarjeta 4: Asesoría WhatsApp --}}
          <a
            href="{{ \App\whatsapp_url(__('Hola Racing Bike 1998, no encontré lo que buscaba en la web y quisiera asesoría personalizada.', 'sage')) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="group relative flex flex-col justify-between p-6 rounded-2xl border border-line bg-surface-raised/40 hover:bg-surface-raised hover:border-emerald-500/40 transition-all duration-300 shadow-sm hover:shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)]"
          >
            <div>
              <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-4 group-hover:scale-110 transition-transform">
                <x-icon name="phone" class="size-5" />
              </div>
              <h3 class="text-sm font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors">
                {{ __('Asesoría WhatsApp', 'sage') }}
              </h3>
              <p class="mt-1.5 text-xs text-ink-subtle leading-relaxed">
                {{ __('Chatea directamente con nuestro equipo técnico y comercial en Bogotá.', 'sage') }}
              </p>
            </div>
            <div class="mt-5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-emerald-400 group-hover:translate-x-1 transition-transform">
              <span>{{ __('Hablar con asesor', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-3.5" />
            </div>
          </a>
        </div>
      </div>

      {{-- Productos Recomendados (si existen) --}}
      @if (!empty($suggestedProducts))
        <div class="mt-20 border-t border-line/60 pt-16">
          <div class="mb-8 flex items-end justify-between">
            <div>
              <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">
                {{ __('Recomendados', 'sage') }}
              </p>
              <h2 class="mt-1 text-xl sm:text-2xl font-bold uppercase tracking-wider text-white">
                {{ __('Máquinas para volver a la pista', 'sage') }}
              </h2>
            </div>
            <a href="{{ $shopUrl }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-ink-subtle hover:text-white transition-colors">
              <span>{{ __('Ver tienda', 'sage') }}</span>
              <x-icon name="chevron-right" class="size-3.5" />
            </a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            @foreach ($suggestedProducts as $product)
              <x-product-card :product="$product" list-id="404_recommendations" list-name="Recomendados 404" :position="$loop->index" />
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
