{{--
  Template Name: Preguntas Frecuentes
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    <div class="rb-container py-12 md:py-20">
      
      {{-- Encabezado de Página & Buscador --}}
      <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Centro de Ayuda', 'sage') }}
        </p>
        <h1 class="mt-3 text-3xl font-bold uppercase tracking-tight text-white md:text-5xl">
          {{ __('Preguntas Frecuentes', 'sage') }}
        </h1>
        <p class="mt-4 text-sm md:text-base text-ink-muted leading-relaxed">
          {{ __('Encuentra respuestas rápidas sobre nuestras bicicletas, componentes, despachos nacionales, garantías y agendamiento de taller.', 'sage') }}
        </p>

        {{-- Barra de Búsqueda Instantánea con diseño Glassmorphic --}}
        <div class="mt-8 max-w-xl relative">
          <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-ink-subtle">
            <x-icon name="search" class="size-5" />
          </div>
          <input
            type="text"
            id="faq-search"
            placeholder="{{ __('¿En qué te podemos ayudar? (ej: envíos, garantías, taller...)', 'sage') }}"
            class="w-full pl-12 pr-4 py-4 rounded-2xl bg-[#0A0A0B]/80 border border-line text-sm text-white placeholder-ink-subtle focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 backdrop-blur-xl transition-all shadow-xl"
          >
        </div>
      </div>

      {{-- Layout de Dos Columnas --}}
      <div class="mt-16 grid gap-12 lg:grid-cols-12 lg:items-start">
        
        {{-- Navegación Lateral con Iconos (Sticky en Escritorio) --}}
        <aside class="lg:col-span-4 lg:sticky lg:top-28">
          <nav class="flex flex-wrap gap-2 lg:flex-col lg:gap-1" aria-label="{{ __('Categorías de ayuda', 'sage') }}">
            @foreach ([
              'racing-bike' => [
                'label' => __('Sobre Racing Bike', 'sage'),
                'icon' => '<svg class="size-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" /></svg>'
              ],
              'shipping' => [
                'label' => __('Envíos & Cobertura', 'sage'),
                'icon' => '<svg class="size-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM19.5 18.75a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM2.25 5.25h9.75v10.5H2.25V5.25Zm9.75 3.75h4.5l3 3v3.75h-7.5V9ZM6 18.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11.25 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" /></svg>'
              ],
              'taller' => [
                'label' => __('Servicio Técnico & Taller', 'sage'),
                'icon' => '<svg class="size-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A1.5 1.5 0 0019.5 21l2-2a1.5 1.5 0 000-2.25l-5.83-5.83M11.42 15.17l2.43-2.43m-2.43 2.43L4.25 7.75a1.5 1.5 0 010-2.25l2-2a1.5 1.5 0 012.25 0l7.42 7.42m-9.67 9.67L17.25 12" /></svg>'
              ],
              'payments' => [
                'label' => __('Métodos de Pago', 'sage'),
                'icon' => '<svg class="size-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5M3 16.5h1.5m-1.5-12h18A2.25 2.25 0 0 1 22.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 17.25V6.75A2.25 2.25 0 0 1 3.75 4.5Z" /></svg>'
              ],
              'warranties' => [
                'label' => __('Garantías & Devoluciones', 'sage'),
                'icon' => '<svg class="size-4 mr-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>'
              ],
            ] as $key => $data)
              <a
                href="#{{ $key }}"
                class="flex items-center rounded-full lg:rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-wider text-ink-subtle hover:text-white hover:bg-surface-raised border border-line lg:border-transparent transition-all"
              >
                {!! $data['icon'] !!}
                {{ $data['label'] }}
              </a>
            @endforeach
          </nav>
        </aside>

        {{-- Contenido de FAQs --}}
        <div class="space-y-16 lg:col-span-8">
          
          {{-- Sección: Sobre Racing Bike --}}
          <section id="racing-bike" class="scroll-mt-28">
            <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 border-b border-line/40 pb-2">
              {{ __('Sobre Racing Bike', 'sage') }}
            </h2>
            <div class="mt-4 divide-y divide-line/40">
              <x-accordion-item :title="__('¿Qué productos puedo encontrar en Racing Bike?', 'sage')">
                {{ __('Contamos con bicicletas, componentes, accesorios, equipamiento y productos para ciclismo de ruta, MTB, gravel y otras modalidades.', 'sage') }}
              </x-accordion-item>

              <x-accordion-item :title="__('¿Me pueden asesorar para elegir mi bicicleta?', 'sage')">
                {{ __('Claro. Nuestro equipo te asesora según tu experiencia, modalidad de ciclismo, presupuesto y necesidades. La idea es que compres lo que realmente necesitas.', 'sage') }}
              </x-accordion-item>

              <x-accordion-item :title="__('¿Solo venden productos para ciclistas profesionales?', 'sage')">
                {{ __('No. Atendemos desde personas que están comprando su primera bicicleta hasta ciclistas con años de experiencia. Lo importante es encontrar el producto adecuado para ti.', 'sage') }}
              </x-accordion-item>
            </div>
          </section>

          {{-- Sección: Envíos & Cobertura --}}
          <section id="shipping" class="scroll-mt-28">
            <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 border-b border-line/40 pb-2">
              {{ __('Envíos & Cobertura', 'sage') }}
            </h2>
            <div class="mt-4 divide-y divide-line/40">
              <x-accordion-item :title="__('¿Realizan envíos a otras ciudades?', 'sage')">
                {{ __('Sí. Realizamos envíos desde Bogotá a diferentes ciudades de Colombia, dependiendo de la cobertura del operador logístico (principalmente Interrapidísimo).', 'sage') }}
              </x-accordion-item>

              <x-accordion-item :title="__('¿Cuánto tarda en llegar mi pedido?', 'sage')">
                {{ __('El tiempo depende de la ciudad de destino, el operador logístico y la disponibilidad del producto. Normalmente oscila entre 2 y 5 días hábiles a nivel nacional. Si compras una bicicleta completa, el proceso de armado y ajuste en Bogotá nos toma 48 horas.', 'sage') }}
              </x-accordion-item>

              <x-accordion-item :title="__('¿Dónde está ubicada Racing Bike?', 'sage')">
                {{ sprintf(__('Nuestra tienda y taller físico se encuentran en la %1$s, %2$s, Colombia.', 'sage'), $contact['address'], $contact['city']) }}
              </x-accordion-item>
            </div>
          </section>

          {{-- Sección: Servicio Técnico & Taller --}}
          <section id="taller" class="scroll-mt-28">
            <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 border-b border-line/40 pb-2">
              {{ __('Servicio Técnico & Taller', 'sage') }}
            </h2>
            <div class="mt-4 divide-y divide-line/40">
              <x-accordion-item :title="__('¿También cuentan con servicio técnico?', 'sage')">
                {{ __('Sí. Contamos con taller y servicio técnico especializado para realizar mantenimiento preventivo, alistamientos, diagnósticos avanzados, bike fitting y diferentes trabajos de mecánica especializada para tu bicicleta.', 'sage') }}
              </x-accordion-item>

              <x-accordion-item :title="__('¿Cómo puedo comunicarme con Racing Bike?', 'sage')">
                {{ __('Puedes contactarnos directamente a través de nuestro WhatsApp oficial, por nuestras redes sociales (Instagram/Facebook) o visitando nuestra tienda física en Bogotá.', 'sage') }}
              </x-accordion-item>
            </div>
          </section>

          {{-- Sección: Métodos de Pago --}}
          <section id="payments" class="scroll-mt-28">
            <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 border-b border-line/40 pb-2">
              {{ __('Métodos de Pago & Financiación', 'sage') }}
            </h2>
            <div class="mt-4 divide-y divide-line/40">
              <x-accordion-item :title="__('¿Qué medios de pago manejan?', 'sage')">
                {{ __('Aceptamos pagos online mediante Mercado Pago (tarjetas de crédito, PSE). También admitimos financiación con ADDI y Sistecrédito, pagos contra entrega, transferencias directas y pago presencial en nuestra tienda en efectivo o datáfono.', 'sage') }}
              </x-accordion-item>
            </div>
          </section>

          {{-- Sección: Garantías & Devoluciones --}}
          <section id="warranties" class="scroll-mt-28">
            <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 border-b border-line/40 pb-2">
              {{ __('Garantías & Devoluciones', 'sage') }}
            </h2>
            <div class="mt-4 divide-y divide-line/40">
              <x-accordion-item :title="__('¿Los productos tienen garantía?', 'sage')">
                {{ __('Sí. Los productos cuentan con la garantía correspondiente de acuerdo con la legislación colombiana. Nuestros marcos de marca propia cuentan con Garantía de Por Vida por defectos de fábrica. Los componentes de otras marcas (Shimano, SRAM, etc.) tienen la garantía provista por sus distribuidores oficiales.', 'sage') }}
                <a href="{{ $links['warranty'] }}" class="mt-2 inline-block text-[11px] font-bold uppercase tracking-wider text-emerald-400 hover:underline">{{ __('Ver política de garantías', 'sage') }}</a>
              </x-accordion-item>

              <x-accordion-item :title="__('¿Puedo cambiar o devolver un producto?', 'sage')">
                {{ __('Los cambios y devoluciones se gestionan conforme al Estatuto del Consumidor en Colombia. Cuentas con 5 días hábiles para retractarte de compras online (el producto debe estar sin usar, en caja original y etiquetas). Dispones de 15 días calendario para solicitar cambios de talla o color.', 'sage') }}
                <a href="{{ $links['returns'] }}" class="mt-2 inline-block text-[11px] font-bold uppercase tracking-wider text-emerald-400 hover:underline">{{ __('Ver política de cambios y devoluciones', 'sage') }}</a>
              </x-accordion-item>

              <x-accordion-item :title="__('¿Cómo puedo solicitar una garantía?', 'sage')">
                {{ __('Comunícate directamente a nuestro WhatsApp o correo electrónico con los datos de tu compra y fotos/detalles del caso. Gestionaremos la revisión técnica e inspección de inmediato.', 'sage') }}
              </x-accordion-item>
            </div>
          </section>

        </div>
      </div>

      {{-- CTA Final optimizado --}}
      <div class="mt-20 relative overflow-hidden rounded-3xl bg-[#0A0A0B]/90 border border-line p-8 md:p-12 text-center shadow-2xl backdrop-blur-xl">
        <div class="absolute -left-10 -bottom-10 -z-10 size-40 rounded-full bg-emerald-500/5 blur-3xl"></div>
        <div class="absolute -right-10 -top-10 -z-10 size-40 rounded-full bg-emerald-500/5 blur-3xl"></div>

        <div class="flex items-center justify-center gap-2 mb-4">
          <span class="relative flex size-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full size-2 bg-emerald-500"></span>
          </span>
          <span class="text-xs uppercase font-bold tracking-widest text-emerald-400">{{ __('Asesores en línea', 'sage') }}</span>
        </div>

        <h3 class="text-xl md:text-2xl font-bold uppercase tracking-wider text-white">
          {{ __('¿Tienes otra pregunta?', 'sage') }}
        </h3>
        <p class="mt-3 text-xs md:text-sm text-ink-muted max-w-lg mx-auto leading-relaxed">
          {{ __('Escríbenos directamente. Respondemos habitualmente en menos de 5 minutos y te brindamos asesoría experta y personalizada.', 'sage') }}
        </p>
        <div class="mt-8 flex justify-center">
          <x-button variant="primary" size="lg" href="{{ $contact['whatsapp_url'] }}" target="_blank">
            <x-icon name="phone" class="size-4 mr-2" />
            {{ __('Chatear por WhatsApp', 'sage') }}
          </x-button>
        </div>
      </div>
    </div>
  @endwhile

  {{-- Script de filtrado dinámico (Live Search) --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const searchInput = document.getElementById('faq-search');
      const sections = document.querySelectorAll('section.scroll-mt-28');

      if (!searchInput) return;

      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();

        sections.forEach(section => {
          let sectionHasMatches = false;
          const items = section.querySelectorAll('[data-accordion-item]');

          items.forEach(item => {
            const title = item.querySelector('[data-accordion-title]').textContent.toLowerCase();
            const text = item.querySelector('[data-accordion-text]').textContent.toLowerCase();

            if (title.includes(query) || text.includes(query)) {
              item.classList.remove('hidden');
              sectionHasMatches = true;
            } else {
              item.classList.add('hidden');
            }
          });

          // Ocultar sección completa si no contiene coincidencias
          if (sectionHasMatches) {
            section.classList.remove('hidden');
          } else {
            section.classList.add('hidden');
          }
        });
      });
    });
  </script>

  {{-- Marcado de Datos Estructurados JSON-LD FAQPage para Google SEO --}}
  <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "FAQPage",
      "mainEntity": [
        {
          "@@type": "Question",
          "name": "¿Qué productos puedo encontrar en Racing Bike?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Contamos con bicicletas, componentes, accesorios, equipamiento y productos para ciclismo de ruta, MTB, gravel y otras modalidades."
          }
        },
        {
          "@@type": "Question",
          "name": "¿Me pueden asesorar para elegir mi bicicleta?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Claro. Nuestro equipo te asesora según tu experiencia, modalidad de ciclismo, presupuesto y necesidades. La idea es que compres lo que realmente necesitas."
          }
        },
        {
          "@@type": "Question",
          "name": "¿Realizan envíos a otras ciudades?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Sí. Realizamos envíos desde Bogotá a diferentes ciudades de Colombia, dependiendo de la cobertura del operador logístico (principalmente Interrapidísimo)."
          }
        },
        {
          "@@type": "Question",
          "name": "¿Cuánto tarda en llegar mi pedido?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "El tiempo depende de la ciudad de destino, el operador logístico y la disponibilidad del producto. Normalmente oscila entre 2 y 5 días hábiles a nivel nacional. Si compras una bicicleta completa, el proceso de armado y ajuste en Bogotá nos toma 48 horas."
          }
        },
        {
          "@@type": "Question",
          "name": "¿También cuentan con servicio técnico?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Sí. Contamos con taller y servicio técnico especializado para realizar mantenimiento preventivo, alistamientos, diagnósticos avanzados, bike fitting y diferentes trabajos de mecánica especializada para tu bicicleta."
          }
        },
        {
          "@@type": "Question",
          "name": "¿Qué medios de pago manejan?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Aceptamos pagos online mediante Mercado Pago (tarjetas de crédito, PSE). También admitimos financiación con ADDI y Sistecrédito, pagos contra entrega, transferencias directas y pago presencial en nuestra tienda en efectivo o datáfono."
          }
        },
        {
          "@@type": "Question",
          "name": "¿Los productos tienen garantía?",
          "acceptedAnswer": {
            "@@type": "Answer",
            "text": "Sí. Los productos cuentan con la garantía correspondiente de acuerdo con la legislación colombiana. Nuestros marcos de marca propia cuentan con Garantía de Por Vida por defectos de fábrica. Los componentes de otras marcas (Shimano, SRAM, etc.) tienen la garantía provista por sus distribuidores oficiales."
          }
        }
      ]
    }
  </script>
@endsection
