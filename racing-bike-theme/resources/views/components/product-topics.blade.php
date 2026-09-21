@props([
  'product',
  'specs' => [],
])

@php
  $description = $product->get_description();
  $hasSpecs = ! empty($specs);
@endphp

  {{-- overflow-hidden: evita que imágenes o tablas de la descripción desborden en mobile --}}
  <section class="border-t border-white/[0.08] bg-surface-raised/40 py-12 md:py-24 overflow-hidden" id="rb-product-details">
  <div class="rb-container w-full">
    
    {{-- Encabezado de la sección Enterprise (Full-Width Desktop) --}}
    <div class="mb-10 md:mb-16 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-white/[0.08] pb-6 md:pb-8">
      <div>
        <span class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-400">
          {{ __('Información Completa', 'sage') }}
        </span>
        <h2 class="mt-1.5 text-2xl sm:text-3xl lg:text-4xl font-extrabold uppercase tracking-tight text-white">
          {{ __('Detalles, Ficha y Garantía', 'sage') }}
        </h2>
      </div>
      <p class="text-xs sm:text-sm text-zinc-400 max-w-lg leading-relaxed">
        {{ __('Conoce las especificaciones técnicas certificadas, ingeniería de componentes y respaldo oficial de fábrica de Racing Bike 1998.', 'sage') }}
      </p>
    </div>

    {{-- Contenedor de Tópicos Desplegables con "+" / "−" --}}
    <div class="divide-y divide-white/[0.08] border-y border-white/[0.08] w-full" data-product-topics>

      {{-- TÓPICO 1: DESCRIPCIÓN & RENDIMIENTO (Abierto por defecto) --}}
      @if ($description)
        <div class="py-4 md:py-6" data-topic-item>
          <button
            type="button"
            class="group flex w-full items-center justify-between gap-4 py-3 md:py-4 text-left cursor-pointer transition-colors"
            data-accordion-toggle
            aria-expanded="true"
            aria-controls="topic-panel-description"
          >
            <div class="flex items-center gap-3 sm:gap-5">
              <span class="flex size-11 sm:size-12 items-center justify-center rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 shrink-0 shadow-lg shadow-emerald-500/5 group-hover:scale-105 group-hover:border-emerald-400 transition-all">
                <x-icon name="document-text" class="size-5 sm:size-6" />
              </span>
              <div>
                <span class="text-base sm:text-lg lg:text-xl font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors block">
                  {{ __('Descripción & Rendimiento', 'sage') }}
                </span>
                <span class="text-xs sm:text-sm text-zinc-400 hidden sm:block mt-0.5">
                  {{ __('Propósito de diseño, sensaciones en ruta y tecnología', 'sage') }}
                </span>
              </div>
            </div>

            {{-- Indicador dinámico "+" / "−" --}}
            <span class="flex size-9 sm:size-10 items-center justify-center rounded-full bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30 group-hover:text-emerald-400 transition-all shrink-0">
              <span class="rb-topic-plus flex items-center justify-center"><x-icon name="plus" class="size-4.5" /></span>
              <span class="rb-topic-minus flex items-center justify-center text-emerald-400"><x-icon name="minus" class="size-4.5" /></span>
            </span>
          </button>

          <div
            id="topic-panel-description"
            class="pt-6 pb-6 md:pt-8 md:pb-8 pl-2 sm:pl-16 transition-all duration-300 w-full"
          >
            <div class="rb-prose w-full">
              {!! apply_filters('the_content', $description) !!}
            </div>
          </div>
        </div>
      @endif

      {{-- TÓPICO 2: FICHA TÉCNICA & ESPECIFICACIONES --}}
      @if ($hasSpecs)
        <div class="py-4 md:py-6" data-topic-item>
          <button
            type="button"
            class="group flex w-full items-center justify-between gap-4 py-3 md:py-4 text-left cursor-pointer transition-colors"
            data-accordion-toggle
            aria-expanded="false"
            aria-controls="topic-panel-specs"
          >
            <div class="flex items-center gap-3 sm:gap-5">
              <span class="flex size-11 sm:size-12 items-center justify-center rounded-2xl bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:text-emerald-400 group-hover:border-emerald-500/30 group-hover:bg-emerald-500/10 group-hover:scale-105 transition-all shrink-0 shadow-lg">
                <x-icon name="wrench" class="size-5 sm:size-6" />
              </span>
              <div>
                <span class="text-base sm:text-lg lg:text-xl font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors block">
                  {{ __('Ficha Técnica & Componentes', 'sage') }}
                </span>
                <span class="text-xs sm:text-sm text-zinc-400 hidden sm:block mt-0.5">
                  {{ __('Ingeniería de marco, grupo de transmisión, frenos y ruedas', 'sage') }}
                </span>
              </div>
            </div>

            {{-- Indicador dinámico "+" / "−" --}}
            <span class="flex size-9 sm:size-10 items-center justify-center rounded-full bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30 group-hover:text-emerald-400 transition-all shrink-0">
              <span class="rb-topic-plus flex items-center justify-center"><x-icon name="plus" class="size-4.5" /></span>
              <span class="rb-topic-minus flex items-center justify-center text-emerald-400"><x-icon name="minus" class="size-4.5" /></span>
            </span>
          </button>

          <div
            id="topic-panel-specs"
            class="hidden pt-6 pb-6 md:pt-8 md:pb-8 pl-2 sm:pl-16 transition-all duration-300 w-full"
          >
            <div class="w-full rounded-2xl border border-white/[0.08] bg-black/40 p-1 shadow-2xl overflow-hidden">
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-px bg-white/[0.08] rounded-xl overflow-hidden">
                @foreach ($specs as $attribute)
                  @continue(! $attribute->get_visible())
                  <div class="bg-surface-raised/90 hover:bg-surface-raised transition-colors p-4 sm:p-5 flex flex-col justify-between">
                    <dt class="text-[11px] font-bold uppercase tracking-widest text-emerald-400 mb-1.5 flex items-center gap-2">
                      <span class="size-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"></span>
                      {{ ucfirst(wc_attribute_label($attribute->get_name())) }}
                    </dt>
                    <dd class="text-sm sm:text-[15px] font-semibold text-white break-words">
                      @if ($attribute->is_taxonomy())
                        {{ implode(', ', wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])) }}
                      @else
                        {{ implode(', ', $attribute->get_options()) }}
                      @endif
                    </dd>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @endif

      {{-- TÓPICO 3: ENVÍOS, ARMADO & GARANTÍA OFICIAL --}}
      <div class="py-4 md:py-6" data-topic-item>
        <button
          type="button"
          class="group flex w-full items-center justify-between gap-4 py-3 md:py-4 text-left cursor-pointer transition-colors"
          data-accordion-toggle
          aria-expanded="false"
          aria-controls="topic-panel-warranty"
        >
          <div class="flex items-center gap-3 sm:gap-5">
            <span class="flex size-11 sm:size-12 items-center justify-center rounded-2xl bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:text-emerald-400 group-hover:border-emerald-500/30 group-hover:bg-emerald-500/10 group-hover:scale-105 transition-all shrink-0 shadow-lg">
              <x-icon name="shield-check" class="size-5 sm:size-6" />
            </span>
            <div>
              <span class="text-base sm:text-lg lg:text-xl font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors block">
                {{ __('Envíos, Entrega & Garantía Oficial', 'sage') }}
              </span>
              <span class="text-xs sm:text-sm text-zinc-400 hidden sm:block mt-0.5">
                {{ __('Armado en Bogotá, despachos a toda Colombia y respaldo de fábrica', 'sage') }}
              </span>
            </div>
          </div>

          {{-- Indicador dinámico "+" / "−" --}}
          <span class="flex size-9 sm:size-10 items-center justify-center rounded-full bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30 group-hover:text-emerald-400 transition-all shrink-0">
            <span class="rb-topic-plus flex items-center justify-center"><x-icon name="plus" class="size-4.5" /></span>
            <span class="rb-topic-minus flex items-center justify-center text-emerald-400"><x-icon name="minus" class="size-4.5" /></span>
          </span>
        </button>

        <div
          id="topic-panel-warranty"
          class="hidden pt-6 pb-6 md:pt-8 md:pb-8 pl-2 sm:pl-16 transition-all duration-300 w-full"
        >
          <div class="grid gap-6 md:grid-cols-3 w-full">
            {{-- Card 1: Bogotá --}}
            <div class="rounded-2xl border border-white/[0.08] bg-gradient-to-b from-white/[0.04] to-black/60 p-6 sm:p-8 shadow-xl flex flex-col justify-between group hover:border-emerald-500/40 transition-all">
              <div>
                <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 mb-4 shadow-sm group-hover:scale-110 transition-transform">
                  <x-icon name="wrench" class="size-5" />
                </div>
                <h4 class="text-base sm:text-lg font-bold uppercase tracking-wider text-white mb-2">
                  {{ __('100% Armada en Bogotá', 'sage') }}
                </h4>
                <p class="text-sm text-zinc-300 leading-relaxed">
                  {{ __('Si vives en Bogotá, nuestros técnicos especializados calibran cambios, frenos y componentes para entregártela lista para rodar sin costo adicional.', 'sage') }}
                </p>
              </div>
              <div class="mt-6 pt-4 border-t border-white/[0.06] flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">
                  {{ __('Servicio Taller Incluido', 'sage') }}
                </span>
                <span class="text-xs text-zinc-400">{{ __('Sin costo extra', 'sage') }}</span>
              </div>
            </div>

            {{-- Card 2: Envíos Nacionales --}}
            <div class="rounded-2xl border border-white/[0.08] bg-gradient-to-b from-white/[0.04] to-black/60 p-6 sm:p-8 shadow-xl flex flex-col justify-between group hover:border-emerald-500/40 transition-all">
              <div>
                <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 mb-4 shadow-sm group-hover:scale-110 transition-transform">
                  <x-icon name="truck" class="size-5" />
                </div>
                <h4 class="text-base sm:text-lg font-bold uppercase tracking-wider text-white mb-2">
                  {{ __('Envíos a Todo Colombia', 'sage') }}
                </h4>
                <p class="text-sm text-zinc-300 leading-relaxed">
                  {{ __('Despacho asegurado en 2 a 5 días hábiles a cualquier municipio. Embalada en caja reforzada de alta densidad con protecciones de cuadro.', 'sage') }}
                </p>
              </div>
              <div class="mt-6 pt-4 border-t border-white/[0.06] flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">
                  {{ __('Cobertura Nacional Asegurada', 'sage') }}
                </span>
                <span class="text-xs text-zinc-400">{{ __('2 a 5 días hábiles', 'sage') }}</span>
              </div>
            </div>

            {{-- Card 3: Garantía de por Vida --}}
            <div class="rounded-2xl border border-white/[0.08] bg-gradient-to-b from-white/[0.04] to-black/60 p-6 sm:p-8 shadow-xl flex flex-col justify-between group hover:border-emerald-500/40 transition-all">
              <div>
                <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 mb-4 shadow-sm group-hover:scale-110 transition-transform">
                  <x-icon name="shield-check" class="size-5" />
                </div>
                <h4 class="text-base sm:text-lg font-bold uppercase tracking-wider text-white mb-2">
                  {{ __('Garantía en el Marco', 'sage') }}
                </h4>
                <p class="text-sm text-zinc-300 leading-relaxed">
                  {{ __('Garantía oficial de por vida en marcos seleccionados (Trek, GW, Orbea) respaldada de forma directa en nuestra sede física desde 1998.', 'sage') }}
                </p>
              </div>
              <div class="mt-6 pt-4 border-t border-white/[0.06] flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-widest text-emerald-400">
                  {{ __('Respaldo desde 1998', 'sage') }}
                </span>
                <span class="text-xs text-zinc-400">{{ __('Tienda física oficial', 'sage') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- TÓPICO 4: ASESORÍA DE TALLA Y AJUSTE ERGONÓMICO --}}
      <div class="py-4 md:py-6" data-topic-item>
        <button
          type="button"
          class="group flex w-full items-center justify-between gap-4 py-3 md:py-4 text-left cursor-pointer transition-colors"
          data-accordion-toggle
          aria-expanded="false"
          aria-controls="topic-panel-sizing"
        >
          <div class="flex items-center gap-3 sm:gap-5">
            <span class="flex size-11 sm:size-12 items-center justify-center rounded-2xl bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:text-emerald-400 group-hover:border-emerald-500/30 group-hover:bg-emerald-500/10 group-hover:scale-105 transition-all shrink-0 shadow-lg">
              <x-icon name="ruler" class="size-5 sm:size-6" />
            </span>
            <div>
              <span class="text-base sm:text-lg lg:text-xl font-bold uppercase tracking-wider text-white group-hover:text-emerald-400 transition-colors block">
                {{ __('Guía de Tallas & Asesoría Ergonómica', 'sage') }}
              </span>
              <span class="text-xs sm:text-sm text-zinc-400 hidden sm:block mt-0.5">
                {{ __('Recomendaciones de marco por estatura y entrepierna', 'sage') }}
              </span>
            </div>
          </div>

          {{-- Indicador dinámico "+" / "−" --}}
          <span class="flex size-9 sm:size-10 items-center justify-center rounded-full bg-white/[0.04] border border-white/[0.08] text-zinc-300 group-hover:bg-emerald-500/10 group-hover:border-emerald-500/30 group-hover:text-emerald-400 transition-all shrink-0">
            <span class="rb-topic-plus flex items-center justify-center"><x-icon name="plus" class="size-4.5" /></span>
            <span class="rb-topic-minus flex items-center justify-center text-emerald-400"><x-icon name="minus" class="size-4.5" /></span>
          </span>
        </button>

        <div
          id="topic-panel-sizing"
          class="hidden pt-6 pb-6 md:pt-8 md:pb-8 pl-2 sm:pl-16 transition-all duration-300 w-full"
        >
          <div class="rounded-2xl border border-white/[0.08] bg-gradient-to-r from-black/60 via-surface-raised/50 to-black/60 p-6 sm:p-10 w-full shadow-2xl flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="max-w-2xl">
              <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-emerald-400 mb-3">
                <x-icon name="ruler" class="size-3.5" />
                <span>{{ __('Ajuste de Geometría Personalizado', 'sage') }}</span>
              </div>
              <h4 class="text-lg sm:text-xl font-bold uppercase tracking-wider text-white mb-2 sm:mb-3">
                {{ __('¿No estás seguro de tu talla de marco?', 'sage') }}
              </h4>
              <p class="text-sm sm:text-base text-zinc-300 leading-relaxed mb-6">
                {{ __('La medida ideal depende de tu estatura y tiro de entrepierna. Puedes usar nuestra herramienta interactiva de tallas o consultar directamente a un técnico en WhatsApp para recomendación personalizada según la geometría del modelo.', 'sage') }}
              </p>
              <div class="flex flex-wrap items-center gap-4">
                <a
                  href="{{ home_url('/encuentra-tu-talla/') }}"
                  class="inline-flex items-center gap-2.5 rounded-full bg-emerald-500 hover:bg-emerald-400 text-black px-5 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-bold uppercase tracking-wider transition-all shadow-lg hover:scale-105"
                >
                  <x-icon name="ruler" class="size-4 sm:size-4.5" />
                  <span>{{ __('Calculador de Talla Interactivo', 'sage') }}</span>
                </a>

                @if (function_exists('rb_cro_get_smart_whatsapp_url'))
                  <a
                    href="{{ rb_cro_get_smart_whatsapp_url('product', $product) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2.5 rounded-full border border-white/[0.15] bg-white/[0.05] hover:bg-white/[0.1] text-white px-5 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold uppercase tracking-wider transition-all"
                  >
                    <x-icon name="phone" class="size-3.5 sm:size-4 text-emerald-400" />
                    <span>{{ __('Consultar con un mecánico', 'sage') }}</span>
                  </a>
                @endif
              </div>
            </div>

            {{-- Badges de confianza --}}
            <div class="grid grid-cols-2 gap-4 shrink-0 w-full lg:w-auto">
              <div class="rounded-2xl bg-black/50 border border-white/[0.08] p-5 flex flex-col items-center justify-center text-center min-w-[130px] sm:min-w-[150px]">
                <span class="text-3xl font-black text-emerald-400">100%</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-300 mt-1">{{ __('Ajuste Seguro', 'sage') }}</span>
              </div>
              <div class="rounded-2xl bg-black/50 border border-white/[0.08] p-5 flex flex-col items-center justify-center text-center min-w-[130px] sm:min-w-[150px]">
                <span class="text-3xl font-black text-white">1998</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-300 mt-1">{{ __('Trayectoria', 'sage') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>
