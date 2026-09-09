{{--
  Template Name: Contacto
--}}

@extends('layouts.app')

@php
  use function App\contact_form_topics;
  use function App\contact_form_time_token;

  $topics = contact_form_topics();

  // El timestamp firmado viaja en el formulario: el servidor descarta los
  // envíos hechos en menos de 3 segundos sin tener que guardar estado.
  $formTime = time();
  $formToken = contact_form_time_token($formTime);

  // maps_embed apunta a la ficha del negocio (el maps_url corto no sirve dentro
  // de un iframe). Vive en contact_info() para no repetirlo aquí y en el footer.
  $mapEmbed = $contact['maps_embed'];

  $channels = [
    [
      'icon' => 'shopping-bag',
      'label' => __('Ventas y Asesoría Comercial', 'sage'),
      'value' => $contact['email_ventas'],
    ],
    [
      'icon' => 'shield-check',
      'label' => __('Garantías y Servicio Técnico', 'sage'),
      'value' => $contact['email_garantias'],
    ],
    [
      'icon' => 'lock',
      'label' => __('Pagos y Facturación Electrónica', 'sage'),
      'value' => $contact['email_pagos'],
    ],
    [
      'icon' => 'mail',
      'label' => __('Información General', 'sage'),
      'value' => $contact['email_contacto'],
    ],
  ];
@endphp

@section('content')
  @while(have_posts())
    @php the_post(); @endphp

    {{-- Hero editorial --}}
    <section class="rb-container pt-10 pb-12 md:pt-16 md:pb-16">
      <x-breadcrumbs
        class="mb-8"
        :items="[
          ['label' => __('Inicio', 'sage'), 'href' => home_url('/')],
          ['label' => __('Contacto', 'sage')],
        ]"
      />

      <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Contacto', 'sage') }}
        </p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink text-balance md:text-5xl">
          {{ __('Hablemos de tu próxima bicicleta', 'sage') }}
        </h1>
        <p class="mt-6 text-sm leading-relaxed text-ink-muted md:text-lg">
          {{ sprintf(__('Escríbenos y te responde alguien que sabe de bicicletas. Llevamos desde %d asesorando ciclistas: cuéntanos qué buscas, qué dudas tienes sobre tallas o componentes, y te acompañamos.', 'sage'), $contact['founded']) }}
        </p>
      </div>
    </section>

    {{-- Formulario + canales directos --}}
    <section class="rb-container pb-14 md:pb-20">
      <div class="grid gap-12 lg:grid-cols-12 lg:items-start">

        {{-- Formulario --}}
        <div class="lg:col-span-7">
          <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
            {{ __('Envíanos un mensaje', 'sage') }}
          </h2>

          <form id="rb-contact-form" class="mt-8 grid gap-5" novalidate>
            @php wp_nonce_field('rb_contact_nonce', 'rb_contact_nonce_field', false); @endphp
            <input type="hidden" name="rb_contact_time" value="{{ $formTime }}">
            <input type="hidden" name="rb_contact_token" value="{{ $formToken }}">

            {{-- Honeypot: fuera de la vista y del recorrido de tabulación. --}}
            <div class="absolute left-[-9999px] h-px w-px overflow-hidden" aria-hidden="true">
              <label for="rb-contact-aux">{{ __('No rellenar', 'sage') }}</label>
              <input type="text" id="rb-contact-aux" name="rb_contact_aux" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label for="rb-contact-name" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Nombre', 'sage') }} <span class="text-emerald-400">*</span>
                </label>
                <input
                  type="text"
                  id="rb-contact-name"
                  name="name"
                  required
                  autocomplete="name"
                  class="mt-2 w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none"
                >
              </div>

              <div>
                <label for="rb-contact-email" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Correo electrónico', 'sage') }} <span class="text-emerald-400">*</span>
                </label>
                <input
                  type="email"
                  id="rb-contact-email"
                  name="email"
                  required
                  autocomplete="email"
                  class="mt-2 w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none"
                >
              </div>

              <div>
                <label for="rb-contact-phone" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Teléfono / WhatsApp', 'sage') }}
                  <span class="font-medium normal-case tracking-normal text-ink-subtle">{{ __('(opcional)', 'sage') }}</span>
                </label>
                <input
                  type="tel"
                  id="rb-contact-phone"
                  name="phone"
                  autocomplete="tel"
                  class="mt-2 w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none"
                >
              </div>

              <div>
                <label for="rb-contact-topic" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Motivo', 'sage') }} <span class="text-emerald-400">*</span>
                </label>
                <select
                  id="rb-contact-topic"
                  name="topic"
                  required
                  class="mt-2 w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none"
                >
                  @foreach ($topics as $key => $topic)
                    <option value="{{ $key }}">{{ $topic['label'] }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div>
              <label for="rb-contact-message" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                {{ __('Mensaje', 'sage') }} <span class="text-emerald-400">*</span>
              </label>
              <textarea
                id="rb-contact-message"
                name="message"
                rows="6"
                required
                placeholder="{{ __('Cuéntanos qué necesitas: modelo, talla, presupuesto, dudas técnicas…', 'sage') }}"
                class="mt-2 w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors placeholder:text-ink-faint focus:border-ink focus:outline-none"
              ></textarea>
            </div>

            <label class="flex items-start gap-3 text-xs leading-relaxed text-ink-muted">
              <input
                type="checkbox"
                name="consent"
                value="1"
                required
                class="mt-0.5 size-4 shrink-0 rounded border-line-strong bg-surface accent-emerald-500"
              >
              <span>
                {{ __('Autorizo el tratamiento de mis datos personales para recibir respuesta a esta solicitud, conforme a la', 'sage') }}
                <a href="{{ $links['privacy'] }}" class="text-ink underline underline-offset-2 transition-colors hover:text-emerald-400">
                  {{ __('Política de Tratamiento de Datos (Ley 1581)', 'sage') }}</a>.
              </span>
            </label>

            <div class="flex flex-wrap items-center gap-4">
              <x-button type="submit" size="lg" data-contact-submit>
                {{ __('Enviar mensaje', 'sage') }}
              </x-button>

              <p
                class="text-xs leading-relaxed"
                data-contact-feedback
                role="status"
                aria-live="polite"
              ></p>
            </div>
          </form>
        </div>

        {{-- Canales directos --}}
        <aside class="lg:col-span-5 lg:sticky lg:top-28">
          <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
            {{ __('Canales directos', 'sage') }}
          </h2>

          {{-- WhatsApp: el canal más rápido, va primero. --}}
          <a
            href="{{ $contact['whatsapp_url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-6 flex items-start gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-5 transition-colors hover:border-emerald-500/60"
          >
            <x-icon name="phone" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
            <span>
              <span class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
                <span class="relative flex size-1.5">
                  <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex size-1.5 rounded-full bg-emerald-400"></span>
                </span>
                {{ __('WhatsApp · Respuesta inmediata', 'sage') }}
              </span>
              <span class="mt-1 block text-sm font-medium text-ink">{{ $contact['whatsapp_display'] }}</span>
            </span>
          </a>

          <ul class="mt-4 grid gap-4">
            @foreach ($channels as $channel)
              <li>
                <a
                  href="mailto:{{ $channel['value'] }}"
                  class="flex h-full items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5 transition-colors hover:border-emerald-500/40"
                >
                  <x-icon :name="$channel['icon']" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
                  <span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ $channel['label'] }}</span>
                    <span class="mt-1 block text-sm font-medium break-all text-ink">{{ $channel['value'] }}</span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>

          <p class="mt-6 text-xs leading-relaxed text-ink-subtle">
            {{ sprintf(__('Racing Bike · NIT %s · Desde %d, creando sueños sobre dos ruedas.', 'sage'), $contact['nit'], $contact['founded']) }}
          </p>
        </aside>

      </div>
    </section>

    {{-- Tienda y taller --}}
    <section class="border-y border-line bg-surface-raised">
      <div class="rb-container py-14 md:py-20">
        <div class="max-w-2xl">
          <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
            {{ __('Visítanos', 'sage') }}
          </p>
          <h2 class="mt-3 text-2xl font-bold tracking-tight text-ink md:text-3xl">
            {{ __('Tienda y taller en Bogotá', 'sage') }}
          </h2>
          <p class="mt-4 flex items-start gap-2 text-sm leading-relaxed text-ink-muted md:text-base">
            <x-icon name="map-pin" class="mt-1 size-4 shrink-0 text-emerald-400" />
            <span>{{ $contact['address'] }}, {{ $contact['city'] }}, {{ __('Colombia', 'sage') }}</span>
          </p>
        </div>

        {{-- El iframe sólo se inyecta al pulsar: así la página no carga trackers
             de Google ni penaliza el LCP en cada visita. --}}
        <div
          class="mt-8 overflow-hidden rounded-2xl border border-line"
          data-map
          data-map-src="{{ $mapEmbed }}"
          data-map-title="{{ esc_attr(sprintf(__('Mapa de %s', 'sage'), $contact['address'])) }}"
        >
          <button
            type="button"
            data-map-trigger
            class="group flex aspect-[16/10] w-full cursor-pointer flex-col items-center justify-center gap-4 bg-surface px-6 text-center transition-colors hover:bg-surface-muted md:aspect-[21/9]"
          >
            <x-icon name="map-pin" class="size-8 text-emerald-400 transition-transform duration-300 group-hover:scale-110" />
            <span class="text-xs font-bold uppercase tracking-widest text-ink">{{ __('Ver mapa', 'sage') }}</span>
            <span class="max-w-sm text-xs leading-relaxed text-ink-subtle">
              {{ __('El mapa se carga desde Google al pulsar. Así mantenemos la página rápida y sin rastreadores de terceros.', 'sage') }}
            </span>
          </button>
        </div>

        <div class="mt-6">
          <x-button :href="$contact['maps_url']" variant="secondary" size="md" target="_blank" rel="noopener noreferrer">
            {{ __('Cómo llegar', 'sage') }}
          </x-button>
        </div>
      </div>
    </section>

    {{-- Schema. app/seo.php sólo emite BicycleStore en portada; su propio
         comentario documenta que la entidad puede repetirse en otras páginas. --}}
    @php
      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => __('Contacto', 'sage'),
        'url' => get_permalink(),
        'mainEntity' => [
          '@type' => 'BicycleStore',
          'name' => $siteName,
          'url' => home_url('/'),
          'telephone' => '+'.$contact['whatsapp'],
          'email' => $contact['email_contacto'],
          'foundingDate' => (string) $contact['founded'],
          'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $contact['address'],
            'addressLocality' => $contact['city'],
            'addressRegion' => $contact['region'],
            'addressCountry' => $contact['country'],
          ],
          'sameAs' => array_values(array_filter([
            $contact['instagram'],
            $contact['facebook'],
            $contact['tiktok'] ?? '',
          ])),
        ],
      ];
    @endphp
    <script type="application/ld+json">
      {!! wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Mapa diferido.
        const mapWrap = document.querySelector('[data-map]');
        const mapTrigger = mapWrap?.querySelector('[data-map-trigger]');

        mapTrigger?.addEventListener('click', () => {
          const iframe = document.createElement('iframe');
          iframe.src = mapWrap.dataset.mapSrc;
          iframe.title = mapWrap.dataset.mapTitle;
          iframe.loading = 'lazy';
          iframe.referrerPolicy = 'no-referrer-when-downgrade';
          iframe.className = 'aspect-[16/10] w-full border-0 md:aspect-[21/9]';
          iframe.allowFullscreen = true;
          mapWrap.replaceChildren(iframe);
        });

        // Formulario.
        const form = document.getElementById('rb-contact-form');

        if (!form) {
          return;
        }

        const submit = form.querySelector('[data-contact-submit]');
        const feedback = form.querySelector('[data-contact-feedback]');

        // El botón tiene tres capas internas para la animación de hover: tocar
        // su innerHTML las destruye. El estado de carga va en el mensaje, y el
        // botón sólo se deshabilita (ya trae disabled:opacity-40 de fábrica).
        const setFeedback = (message, tone) => {
          const tones = {
            ok: 'text-emerald-400',
            error: 'text-red-400',
            pending: 'text-ink-subtle',
          };

          feedback.textContent = message;
          feedback.className = `text-xs leading-relaxed ${tones[tone] ?? tones.pending}`;
        };

        form.addEventListener('submit', async (event) => {
          event.preventDefault();

          if (!form.checkValidity()) {
            form.reportValidity();
            return;
          }

          submit.disabled = true;
          setFeedback(@json(__('Enviando…', 'sage')), 'pending');

          const data = new FormData(form);
          data.append('action', 'rb_contact_submit');
          data.append('nonce', form.querySelector('#rb_contact_nonce_field').value);

          try {
            const response = await fetch(@json(admin_url('admin-ajax.php')), {
              method: 'POST',
              body: data,
              credentials: 'same-origin',
            });

            const result = await response.json();

            if (result.success) {
              form.reset();
              setFeedback(result.data?.message ?? '', 'ok');
            } else {
              setFeedback(result.data?.message ?? @json(__('No pudimos enviar el mensaje.', 'sage')), 'error');
            }
          } catch (error) {
            setFeedback(@json(__('No pudimos enviar el mensaje. Revisa tu conexión e inténtalo de nuevo.', 'sage')), 'error');
          } finally {
            submit.disabled = false;
          }
        });
      });
    </script>
  @endwhile
@endsection
