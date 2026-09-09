{{--
  Template Name: Políticas y Legales
--}}

@extends('layouts.app')

@php
  /*
   | Un solo array gobierna el índice lateral y los títulos de sección: así el
   | menú no puede quedar desincronizado del contenido al editar la página.
   */
  $sections = [
    'terminos'   => ['label' => __('Términos y condiciones', 'sage'), 'icon' => 'scale'],
    'envios'     => ['label' => __('Envíos', 'sage'), 'icon' => 'truck'],
    'cambios'    => ['label' => __('Cambios y devoluciones', 'sage'), 'icon' => 'refresh'],
    'garantias'  => ['label' => __('Garantías', 'sage'), 'icon' => 'shield-check'],
    'privacidad' => ['label' => __('Privacidad y datos', 'sage'), 'icon' => 'lock'],
    'contacto'   => ['label' => __('Contacto', 'sage'), 'icon' => 'phone'],
  ];

  /* Fecha real de la última modificación de la página, no una constante que
     alguien olvide actualizar. */
  $updated = get_the_modified_date('j \d\e F \d\e Y');
@endphp

@section('content')
  @while(have_posts())
    @php the_post(); @endphp

    <div class="rb-container py-10 md:py-16">

      <x-breadcrumbs
        class="mb-8"
        :items="[
          ['label' => __('Inicio', 'sage'), 'href' => home_url('/')],
          ['label' => __('Políticas y Legales', 'sage')],
        ]"
      />

      <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Información legal', 'sage') }}
        </p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-ink text-balance md:text-5xl">
          {{ __('Políticas y Legales', 'sage') }}
        </h1>
        <p class="mt-5 text-sm leading-relaxed text-ink-muted md:text-base">
          {{ __('En Racing Bike trabajamos para ofrecerte una experiencia de compra segura, transparente y acompañada, desde la elección de tu producto hasta el servicio posventa.', 'sage') }}
        </p>
        <p class="mt-4 text-xs text-ink-subtle">
          {{ sprintf(__('Última actualización: %s', 'sage'), $updated) }}
        </p>
      </div>

      <div class="mt-14 grid gap-12 lg:grid-cols-12 lg:items-start">

        {{-- Índice lateral --}}
        <aside class="lg:col-span-4 lg:sticky lg:top-28">
          <p class="mb-4 hidden text-[11px] font-semibold uppercase tracking-widest text-ink-subtle lg:block">
            {{ __('En esta página', 'sage') }}
          </p>
          <nav class="flex flex-wrap gap-2 lg:flex-col lg:gap-1" aria-label="{{ __('Secciones de las políticas', 'sage') }}">
            @foreach ($sections as $id => $section)
              <a
                href="#{{ $id }}"
                data-legal-link="{{ $id }}"
                class="flex items-center gap-2.5 rounded-full border border-line px-4 py-2 text-xs font-bold uppercase tracking-wider text-ink-subtle transition-all hover:bg-surface-raised hover:text-ink lg:rounded-xl lg:border-transparent aria-[current=true]:bg-surface-raised aria-[current=true]:text-ink"
              >
                <x-icon :name="$section['icon']" class="size-4 shrink-0" />
                {{ $section['label'] }}
              </a>
            @endforeach
          </nav>
        </aside>

        {{-- Cuerpo legal --}}
        <div class="space-y-14 lg:col-span-8">

          {{-- Términos y condiciones --}}
          <section id="terminos" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['terminos']['label'] }}
            </h2>
            <div class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
              <p>
                {{ __('Los precios, promociones y disponibilidad de nuestros productos están sujetos al inventario y a las condiciones vigentes al momento de la compra. Para procesar tu pedido y la entrega necesitamos que la información que nos proporciones sea correcta y esté completa.', 'sage') }}
              </p>
              <p>
                {{ __('Nuestros productos cuentan con las garantías establecidas por la legislación colombiana y por las condiciones de cada fabricante. La garantía no cubre daños ocasionados por accidentes, uso inadecuado, modificaciones, instalación incorrecta ni desgaste normal, en los términos que la ley permite.', 'sage') }}
              </p>
              <p>
                {{ __('Las relaciones de consumo derivadas de este sitio se rigen por la Ley 1480 de 2011 (Estatuto del Consumidor) y demás normas colombianas aplicables.', 'sage') }}
              </p>
            </div>
          </section>

          {{-- Envíos --}}
          <section id="envios" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['envios']['label'] }}
            </h2>
            <div class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
              <p>
                {{ __('Realizamos envíos desde Bogotá a diferentes destinos de Colombia a través de nuestro operador logístico (principalmente Interrapidísimo), sujetos a su cobertura.', 'sage') }}
              </p>
              <p>
                {{ __('Los tiempos y costos de envío se informan al momento de la compra y pueden variar según la ciudad de destino, la disponibilidad del producto y las condiciones externas de transporte. Como referencia, los envíos nacionales suelen tomar entre 2 y 5 días hábiles.', 'sage') }}
              </p>
              <p>
                {{ __('Si compras una bicicleta completa, súmale el tiempo de ensamble, alineación y ajuste en nuestro taller de Bogotá: aproximadamente 48 horas antes del despacho. No vendemos cajas; entregamos bicicletas listas para rodar.', 'sage') }}
              </p>
              <p class="text-ink">
                {{ __('Al recibir tu pedido, revisa el estado del paquete y repórtanos cualquier novedad lo antes posible.', 'sage') }}
              </p>
            </div>
          </section>

          {{-- Cambios y devoluciones --}}
          <section id="cambios" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['cambios']['label'] }}
            </h2>
            <div class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
              <p>
                {{ __('Los cambios, devoluciones y solicitudes de garantía se gestionan de acuerdo con la legislación colombiana vigente.', 'sage') }}
              </p>

              <div class="rounded-2xl border border-line bg-surface-raised p-5 md:p-6">
                <p class="text-xs font-bold uppercase tracking-widest text-ink">
                  {{ __('Derecho de retracto', 'sage') }}
                </p>
                <p class="mt-3">
                  {{ __('En las compras realizadas a distancia cuentas con 5 días hábiles, contados desde la entrega, para ejercer el derecho de retracto conforme al artículo 47 de la Ley 1480 de 2011. El producto debe devolverse sin uso, en su empaque original y con sus etiquetas.', 'sage') }}
                </p>
              </div>

              <div class="rounded-2xl border border-line bg-surface-raised p-5 md:p-6">
                <p class="text-xs font-bold uppercase tracking-widest text-ink">
                  {{ __('Cambios de talla o color', 'sage') }}
                </p>
                <p class="mt-3">
                  {{ __('Dispones de 15 días calendario desde la entrega para solicitar cambios de talla o color, siempre que el producto esté sin uso y en su empaque original.', 'sage') }}
                </p>
              </div>

              <p>
                {{ __('Para solicitar un cambio, devolución o garantía, escríbenos indicando tu número de pedido y los datos del producto. Algunas solicitudes requieren una revisión técnica previa en nuestro taller.', 'sage') }}
              </p>
            </div>
          </section>

          {{-- Garantías --}}
          <section id="garantias" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['garantias']['label'] }}
            </h2>
            <div class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
              <p>
                {{ __('Todos los productos cuentan con la garantía legal establecida por la Ley 1480 de 2011 y con las condiciones particulares definidas por cada fabricante.', 'sage') }}
              </p>
              <ul class="space-y-3">
                <li class="flex gap-3">
                  <x-icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-ink" />
                  <span>{{ __('Marcos de nuestra marca propia: garantía de por vida contra defectos de fábrica.', 'sage') }}</span>
                </li>
                <li class="flex gap-3">
                  <x-icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-ink" />
                  <span>{{ __('Componentes de terceros (Shimano, SRAM, Continental y demás marcas aliadas): garantía provista por sus distribuidores oficiales en Colombia.', 'sage') }}</span>
                </li>
              </ul>
              <p>
                {{ sprintf(__('Para radicar una garantía o solicitud técnica, escríbenos directamente a %s con tu número de pedido, factura o cédula y una descripción con fotos/video del caso. Gestionamos la revisión técnica y te informamos el resultado.', 'sage'), $contact['email_garantias']) }}
              </p>
            </div>
          </section>

          {{-- Privacidad --}}
          <section id="privacidad" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['privacidad']['label'] }}
            </h2>
            <div class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
              <p>
                {{ __('En Racing Bike protegemos la información personal de nuestros clientes y la utilizamos únicamente para fines relacionados con la gestión de compras, pagos, entregas, garantías, atención al cliente y, cuando exista autorización previa y expresa, comunicaciones comerciales.', 'sage') }}
              </p>
              <p>
                {{ __('El tratamiento de datos personales se realiza conforme a la Ley 1581 de 2012 y al Decreto 1074 de 2015. Como titular, puedes solicitar en cualquier momento la consulta, actualización, corrección, supresión de tu información o la revocatoria de la autorización otorgada.', 'sage') }}
              </p>

              <div class="rounded-2xl border border-line bg-surface-raised p-5 md:p-6 space-y-3">
                <p class="text-xs font-bold uppercase tracking-widest text-ink">
                  {{ __('Uso de Cookies y Tecnologías Similares', 'sage') }}
                </p>
                <p>
                  {{ __('Empleamos cookies técnicas y necesarias para el funcionamiento del carrito de compras, sesiones de usuario y recomendación de tallas. Opcionalmente, y bajo tu consentimiento previo, empleamos cookies analíticas (Google Analytics) y publicitarias (Meta Pixel) para entender la navegación y optimizar el servicio.', 'sage') }}
                </p>
                <p>
                  {{ __('Puedes gestionar tus preferencias de cookies en cualquier momento aceptando o rechazando las no esenciales desde el aviso de privacidad de la web.', 'sage') }}
                </p>
              </div>

              <p>
                {{ sprintf(__('Para ejercer estos derechos escríbenos a %s. Atendemos las consultas en los plazos previstos por la ley y, si consideras que tu solicitud no fue atendida adecuadamente, puedes acudir a la Superintendencia de Industria y Comercio.', 'sage'), $contact['email_contacto']) }}
              </p>
            </div>
          </section>

          {{-- Contacto --}}
          <section id="contacto" class="scroll-mt-28" data-legal-section>
            <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
              {{ $sections['contacto']['label'] }}
            </h2>
            <p class="mt-5 text-sm leading-relaxed text-ink-muted">
              {{ __('Para preguntas sobre pedidos, cotizaciones, pagos, garantías o tratamiento de datos personales, comunícate a nuestros canales oficiales:', 'sage') }}
            </p>

            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
              <li>
                <a
                  href="{{ $contact['whatsapp_url'] }}"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon name="phone" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Atención WhatsApp', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium text-ink">{{ $contact['whatsapp_display'] }}</span>
                  </span>
                </a>
              </li>
              <li>
                <a
                  href="mailto:{{ $contact['email_contacto'] }}"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon name="mail" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Atención General / PQR', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium break-all text-ink">{{ $contact['email_contacto'] }}</span>
                  </span>
                </a>
              </li>
              <li>
                <a
                  href="mailto:{{ $contact['email_ventas'] }}"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon name="shopping-bag" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Ventas y Asesoría Comercial', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium break-all text-ink">{{ $contact['email_ventas'] }}</span>
                  </span>
                </a>
              </li>
              <li>
                <a
                  href="mailto:{{ $contact['email_garantias'] }}"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon name="shield-check" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Garantías y Servicio Técnico', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium break-all text-ink">{{ $contact['email_garantias'] }}</span>
                  </span>
                </a>
              </li>
              <li class="sm:col-span-2">
                <a
                  href="mailto:{{ $contact['email_pagos'] }}"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon name="lock" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Pagos y Facturación Electrónica', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium break-all text-ink">{{ $contact['email_pagos'] }}</span>
                  </span>
                </a>
              </li>
              <li class="sm:col-span-2">
                <div class="flex items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                  <x-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Tienda y taller en Bogotá', 'sage') }}</span>
                    <span class="mt-1 block text-sm font-medium text-ink">{{ $contact['address'] }}, {{ $contact['city'] }}, {{ __('Colombia', 'sage') }}</span>
                  </span>
                </div>
              </li>
            </ul>

            <p class="mt-6 text-xs leading-relaxed text-ink-subtle">
              {{ sprintf(__('Racing Bike · NIT %s · Desde %d, creando sueños sobre dos ruedas.', 'sage'), $contact['nit'], $contact['founded']) }}
            </p>
            <p class="mt-2 text-xs leading-relaxed text-ink-subtle">
              {{ __('Estas políticas están sujetas a la legislación colombiana vigente y podrán actualizarse cuando sea necesario.', 'sage') }}
            </p>
          </section>

        </div>
      </div>
    </div>
  @endwhile

  {{-- Resalta en el índice la sección que se está leyendo. Es un documento
       largo: sin esto se pierde la referencia de dónde estás. --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const links = document.querySelectorAll('[data-legal-link]');
      const sections = document.querySelectorAll('[data-legal-section]');

      if (!links.length || !sections.length || !('IntersectionObserver' in window)) {
        return;
      }

      const setCurrent = (id) => {
        links.forEach((link) => {
          link.setAttribute('aria-current', link.dataset.legalLink === id ? 'true' : 'false');
        });
      };

      const observer = new IntersectionObserver((entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];

        if (visible) {
          setCurrent(visible.target.id);
        }
      }, { rootMargin: '-120px 0px -60% 0px', threshold: 0 });

      sections.forEach((section) => observer.observe(section));
    });
  </script>
@endsection
