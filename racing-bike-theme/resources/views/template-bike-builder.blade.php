{{--
  Template Name: Bike Builder
--}}

@extends('layouts.app')

@section('content')
  @php
    // Intentar buscar imágenes reales de productos en la base de datos como referencias
    $road_image = 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=800';
    $mtb_image = 'https://images.unsplash.com/photo-1544192240-4a34fed0104c?w=800';
    $gravel_image = 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=800';

    if (function_exists('wc_get_products')) {
      $road_list = wc_get_products(['category' => 'ruta', 'limit' => 1]);
      if (!empty($road_list)) {
        $road_image = wp_get_attachment_image_url($road_list[0]->get_image_id(), 'large') ?: $road_image;
      }
      $mtb_list = wc_get_products(['category' => 'mtb', 'limit' => 1]);
      if (!empty($mtb_list)) {
        $mtb_image = wp_get_attachment_image_url($mtb_list[0]->get_image_id(), 'large') ?: $mtb_image;
      }
      $gravel_list = wc_get_products(['category' => 'gravel', 'limit' => 1]);
      if (!empty($gravel_list)) {
        $gravel_image = wp_get_attachment_image_url($gravel_list[0]->get_image_id(), 'large') ?: $gravel_image;
      }
    }

    $product_id = function_exists('rb_bb_get_custom_bike_product_id') ? rb_bb_get_custom_bike_product_id() : 0;
  @endphp

  <div class="rb-bike-builder-wrapper min-h-screen bg-[#080809] text-white py-10" id="rb-bike-builder" data-product-id="{{ $product_id }}">
    <div class="rb-container">
      
      <!-- Encabezado de la página: Lenguaje Adictos al Ciclismo -->
      <div class="mb-10 text-center md:text-left">
        <span class="text-xs font-semibold uppercase tracking-widest text-action">Watts puros & Geometría a tu medida</span>
        <h1 class="mt-2 text-4xl font-extrabold uppercase tracking-tight text-ink md:text-5xl">
          El Laboratorio: Arma tu Bici <span class="text-action">.</span>
        </h1>
        <p class="mt-3 text-ink-muted text-sm max-w-2xl">
          Elige tu disciplina, calibra tu transmisión y configura las ruedas con las que vas a devorar kilómetros, tragar polvo o coronar puertos de montaña. Ajustes dinámicos en tiempo real.
        </p>
      </div>

      <!-- Configuración en Dos Columnas -->
      <div class="grid gap-8 lg:grid-cols-[1.2fr_1fr] items-start">
        
        <!-- Panel Izquierdo: Visualizador Dinámico -->
        <div class="sticky top-28 flex flex-col items-center justify-center p-6 rounded-2xl border border-line bg-surface-raised overflow-hidden shadow-2xl">
          <div class="absolute inset-0 bg-radial-glow opacity-30 pointer-events-none"></div>
          
          <div class="relative w-full aspect-video flex items-center justify-center">
            <img 
              id="bb-preview-road" 
              src="{{ $road_image }}" 
              alt="Bicicleta de Ruta" 
              class="absolute max-h-full object-contain transition-all duration-500 scale-100 opacity-100"
            >
            <img 
              id="bb-preview-mtb" 
              src="{{ $mtb_image }}" 
              alt="Bicicleta de Montaña" 
              class="absolute max-h-full object-contain transition-all duration-500 scale-95 opacity-0 pointer-events-none"
            >
            <img 
              id="bb-preview-gravel" 
              src="{{ $gravel_image }}" 
              alt="Bicicleta de Gravel" 
              class="absolute max-h-full object-contain transition-all duration-500 scale-95 opacity-0 pointer-events-none"
            >
          </div>

          <!-- Detalles rápidos del resumen -->
          <div class="mt-6 w-full border-t border-line/50 pt-4 flex justify-between items-center text-xs text-ink-subtle">
            <div class="flex items-center gap-1.5">
              <span class="size-2 rounded-full bg-action animate-pulse"></span>
              <span id="bb-summary-type">Ruta / Road</span>
            </div>
            <div id="bb-summary-specs">Shimano 105 • Aluminio Ligero</div>
          </div>
        </div>

        <!-- Panel Derecho: Opciones de Configuración -->
        <div class="space-y-6">

          <!-- PASO 1: MARCO -->
          <div class="p-6 rounded-2xl border border-line bg-surface-raised space-y-4">
            <div class="flex items-center gap-3">
              <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-line text-xs font-bold text-action">1</span>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Paso 1: Elige tu Terreno & Geometría (El Marco)</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <button 
                type="button" 
                class="bb-option-btn active" 
                data-step="marco" 
                data-value="Ruta (Carbono Aero)" 
                data-price="1500000"
                data-target-img="bb-preview-road"
                data-label="Ruta / Road"
              >
                <span class="block text-xl mb-1">🚴</span>
                <span class="block text-xs font-bold uppercase">Ruta</span>
                <span class="block text-[11px] text-ink-subtle mt-1">$1.500.000 COP</span>
              </button>
              
              <button 
                type="button" 
                class="bb-option-btn" 
                data-step="marco" 
                data-value="MTB (Aluminio Rígido)" 
                data-price="1200000"
                data-target-img="bb-preview-mtb"
                data-label="Montaña / MTB"
              >
                <span class="block text-xl mb-1">⛰️</span>
                <span class="block text-xs font-bold uppercase">MTB</span>
                <span class="block text-[11px] text-ink-subtle mt-1">$1.200.000 COP</span>
              </button>
              
              <button 
                type="button" 
                class="bb-option-btn" 
                data-step="marco" 
                data-value="Gravel (Aventura)" 
                data-price="1800000"
                data-target-img="bb-preview-gravel"
                data-label="Gravel"
              >
                <span class="block text-xl mb-1">🏕️</span>
                <span class="block text-xs font-bold uppercase">Gravel</span>
                <span class="block text-[11px] text-ink-subtle mt-1">$1.800.000 COP</span>
              </button>
            </div>

            <!-- Tallas del marco -->
            <div class="pt-3 border-t border-line/30">
              <label class="block text-xs font-bold uppercase tracking-wider text-ink-subtle mb-2">Talla del marco</label>
              <div class="flex gap-2">
                @foreach (['XS', 'S', 'M', 'L'] as $talla)
                  <button 
                    type="button" 
                    class="bb-talla-btn @if($talla === 'M') active @endif"
                    data-talla="{{ $talla }}"
                  >
                    {{ $talla }}
                  </button>
                @endforeach
              </div>
            </div>
          </div>

          <!-- PASO 2: GRUPO DE CAMBIOS -->
          <div class="p-6 rounded-2xl border border-line bg-surface-raised space-y-4">
            <div class="flex items-center gap-3">
              <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-line text-xs font-bold text-action">2</span>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Paso 2: El Corazón de tu Bici (Transmisión & Watts)</h3>
            </div>
            
            <div class="space-y-2">
              <!-- OPCIONES RUTA -->
              <button 
                type="button" 
                class="bb-row-btn active" 
                data-step="grupo" 
                data-compatibility="road"
                data-value="Shimano 105 Mechanical (2x11)" 
                data-price="0"
                data-label="Shimano 105"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Shimano 105 Mecánico (2x11)</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn" 
                data-step="grupo" 
                data-compatibility="road"
                data-value="Shimano Ultegra Di2 (2x12)" 
                data-price="2500000"
                data-label="Shimano Ultegra Di2"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Shimano Ultegra Di2 Electrónico (2x12)</span>
                  <span class="text-xs text-ink-subtle">+ $2.500.000 COP</span>
                </span>
              </button>

              <!-- OPCIONES MTB -->
              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="grupo" 
                data-compatibility="mtb"
                data-value="Shimano Deore M6100 (1x12)" 
                data-price="0"
                data-label="Shimano Deore"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Shimano Deore M6100 (1x12)</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="grupo" 
                data-compatibility="mtb"
                data-value="SRAM GX Eagle Lunar (1x12)" 
                data-price="1500000"
                data-label="SRAM GX Eagle"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">SRAM GX Eagle Lunar (1x12)</span>
                  <span class="text-xs text-ink-subtle">+ $1.500.000 COP</span>
                </span>
              </button>

              <!-- OPCIONES GRAVEL -->
              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="grupo" 
                data-compatibility="gravel"
                data-value="Shimano GRX RX400 (2x10)" 
                data-price="0"
                data-label="Shimano GRX"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Shimano GRX RX400 (2x10)</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="grupo" 
                data-compatibility="gravel"
                data-value="SRAM Apex AXS Wireless (1x12)" 
                data-price="1800000"
                data-label="SRAM Apex AXS"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">SRAM Apex AXS Inalámbrico (1x12)</span>
                  <span class="text-xs text-ink-subtle">+ $1.800.000 COP</span>
                </span>
              </button>
            </div>
          </div>

          <!-- PASO 3: RUEDAS -->
          <div class="p-6 rounded-2xl border border-line bg-surface-raised space-y-4">
            <div class="flex items-center gap-3">
              <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-line text-xs font-bold text-action">3</span>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Paso 3: El Contacto con el Suelo (Ruedas & Grip)</h3>
            </div>
            
            <div class="space-y-2">
              <!-- OPCIONES RUTA -->
              <button 
                type="button" 
                class="bb-row-btn active" 
                data-step="ruedas" 
                data-compatibility="road"
                data-value="Aluminio R28 Clincher" 
                data-price="0"
                data-label="Aluminio R28"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Aluminio R28 Clincher</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn" 
                data-step="ruedas" 
                data-compatibility="road"
                data-value="Carbono Perfil 50mm Ruta" 
                data-price="1800000"
                data-label="Carbono Perfil 50mm"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Carbono Perfil 50mm Ruta</span>
                  <span class="text-xs text-ink-subtle">+ $1.800.000 COP</span>
                </span>
              </button>

              <!-- OPCIONES MTB -->
              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="ruedas" 
                data-compatibility="mtb"
                data-value="Aluminio XC 29\" Tubeless" 
                data-price="0"
                data-label="Aluminio XC 29\""
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Aluminio XC 29" Tubeless Ready</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="ruedas" 
                data-compatibility="mtb"
                data-value="Carbono Pro XC 29\" Elite" 
                data-price="2200000"
                data-label="Carbono Pro XC 29\""
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Carbono Pro XC 29" Elite</span>
                  <span class="text-xs text-ink-subtle">+ $2.200.000 COP</span>
                </span>
              </button>

              <!-- OPCIONES GRAVEL -->
              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="ruedas" 
                data-compatibility="gravel"
                data-value="Aluminio Gravel 700x40c" 
                data-price="0"
                data-label="Aluminio Gravel"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Aluminio Gravel 700x40c</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn hidden" 
                data-step="ruedas" 
                data-compatibility="gravel"
                data-value="Carbono Gravel Elite 700c" 
                data-price="2000000"
                data-label="Carbono Gravel"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Carbono Gravel Elite 700c</span>
                  <span class="text-xs text-ink-subtle">+ $2.000.000 COP</span>
                </span>
              </button>
            </div>
          </div>

          <!-- PASO 4: COMPONENTES DE CONTACTO -->
          <div class="p-6 rounded-2xl border border-line bg-surface-raised space-y-4">
            <div class="flex items-center gap-3">
              <span class="flex size-7 items-center justify-center rounded-full bg-[#1b1c1e] border border-line text-xs font-bold text-action">4</span>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-ink">Paso 4: Ergonomía & Confort (El Sillín para fondos largos)</h3>
            </div>
            
            <div class="space-y-2">
              <button 
                type="button" 
                class="bb-row-btn active" 
                data-step="contacto" 
                data-value="Sillín Comfort Standard" 
                data-price="0"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Sillín Comfort Standard</span>
                  <span class="text-xs text-action font-medium">Incluido</span>
                </span>
              </button>

              <button 
                type="button" 
                class="bb-row-btn" 
                data-step="contacto" 
                data-value="Sillín Carbono Pro Antiprostático" 
                data-price="3500000"
              >
                <span class="flex items-center justify-between w-full">
                  <span class="text-xs font-semibold">Sillín Carbono Pro Antiprostático</span>
                  <span class="text-xs text-ink-subtle">+ $350.000 COP</span>
                </span>
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>

  <!-- Barra de Resumen Fija Inferior (Sticky Footer) -->
  <div class="sticky bottom-0 left-0 right-0 z-30 border-t border-line bg-surface/90 backdrop-blur py-5 shadow-2xl">
    <div class="rb-container flex flex-col sm:flex-row items-center justify-between gap-4">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-ink-subtle">Presupuesto de tu Bici</p>
        <div class="flex items-baseline gap-2 mt-1">
          <span class="text-2xl font-black text-white" id="bb-total-display">$0 COP</span>
          <span class="text-xs text-ink-muted">IVA Incluido</span>
        </div>
      </div>

      <button 
        type="button" 
        id="bb-add-to-cart-btn"
        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-action hover:bg-action-dark text-black font-black uppercase text-sm tracking-wider px-8 py-4 transition-all hover:scale-105 active:scale-95 cursor-pointer shadow-lg"
      >
        <svg class="size-4 animate-spin hidden" id="bb-loading-spinner" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="bb-btn-text">¡Coronar Bici & Empezar a Rodar!</span>
      </button>
    </div>
  </div>
@endsection
