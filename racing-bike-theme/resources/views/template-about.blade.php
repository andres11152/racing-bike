{{--
  Template Name: Sobre Nosotros
--}}

@extends('layouts.app')

@php
  $brands = ['GW', 'Continental', 'Shimano', 'Trek', 'Orbea', 'Magene', 'CLIFF'];

  $pillars = [
    [
      'icon' => 'sparkles',
      'title' => __('Productos de calidad', 'sage'),
      'text' => __('Seleccionamos marcas reconocidas y las probamos antes de recomendarlas. No llenamos la vitrina: curamos lo que de verdad rinde en carretera, trocha y ciudad.', 'sage'),
    ],
    [
      'icon' => 'user',
      'title' => __('Asesoría personalizada', 'sage'),
      'text' => __('Una buena experiencia empieza por escuchar. Preguntamos por tu experiencia, tu modalidad, tu presupuesto y tus objetivos antes de sugerirte nada.', 'sage'),
    ],
    [
      'icon' => 'wrench',
      'title' => __('Servicio técnico propio', 'sage'),
      'text' => __('Taller especializado en mantenimiento, alistamiento, diagnóstico y bike fitting. Ensamblamos y ajustamos cada bicicleta antes de entregarla.', 'sage'),
    ],
  ];
@endphp

@section('content')
  @while(have_posts())
    @php the_post(); @endphp


    {{-- Hero editorial --}}
    <section class="rb-container pt-10 pb-14 md:pt-16 md:pb-20">
      <x-breadcrumbs
        class="mb-8"
        :items="[
          ['label' => __('Inicio', 'sage'), 'href' => home_url('/')],
          ['label' => __('Sobre Nosotros', 'sage')],
        ]"
      />

      <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Sobre Nosotros', 'sage') }}
        </p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink text-balance md:text-5xl">
          {{ __('Más que bicicletas, creamos sueños', 'sage') }}
        </h1>
        <p class="mt-6 text-sm leading-relaxed text-ink-muted md:text-lg">
          {{ sprintf(__('Desde %d, en Racing Bike hemos acompañado a generaciones de ciclistas en Bogotá y en toda Colombia. Nacimos con una idea sencilla: ofrecer productos de calidad y, junto con ellos, algo que para nosotros vale igual: asesoría, confianza y acompañamiento en cada etapa del camino.', 'sage'), $contact['founded']) }}
        </p>
      </div>

      {{-- Cifras de respaldo --}}
      <dl class="mt-12 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-line bg-line md:grid-cols-4">
        @foreach ([
          ['value' => $contact['years'], 'label' => __('Años acompañando ciclistas', 'sage')],
          ['value' => $contact['founded'], 'label' => __('Año de fundación', 'sage')],
          ['value' => count($brands).'+', 'label' => __('Marcas aliadas', 'sage')],
          ['value' => __('Bogotá', 'sage'), 'label' => __('Tienda y taller propio', 'sage')],
        ] as $stat)
          <div class="bg-surface px-5 py-7 md:px-7">
            <dt class="text-2xl font-bold tracking-tight text-ink md:text-4xl">{{ $stat['value'] }}</dt>
            <dd class="mt-2 text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">{{ $stat['label'] }}</dd>
          </div>
        @endforeach
      </dl>
    </section>

    {{-- Detrás de cada bicicleta hay una historia --}}
    <section class="border-y border-line bg-surface-raised">
      <div class="rb-container grid gap-10 py-14 md:grid-cols-12 md:items-start md:py-20">
        <div class="md:col-span-5">
          <h2 class="text-2xl font-bold tracking-tight text-ink text-balance md:text-3xl">
            {{ __('Detrás de cada bicicleta hay una historia', 'sage') }}
          </h2>
        </div>

        <div class="space-y-5 text-sm leading-relaxed text-ink-muted md:col-span-7 md:text-base">
          <p>
            {{ __('Puede ser la primera vez que alguien se monta en una bici, el sueño de completar una gran ruta, una nueva aventura en MTB, una salida de gravel o simplemente encontrar un espacio para disfrutar, desconectar y pedalear.', 'sage') }}
          </p>
          <p>
            {{ __('Por eso no buscamos simplemente vender un producto. Queremos entender lo que necesitas, recomendarte lo que realmente se adapta a ti y acompañarte incluso después de tu compra.', 'sage') }}
          </p>
          <p class="text-ink">
            {{ __('Para nosotros, un cliente no termina su experiencia cuando sale de la tienda: ahí empieza el acompañamiento.', 'sage') }}
          </p>
        </div>
      </div>
    </section>

    {{-- Nuestra forma de hacer las cosas --}}
    <section class="rb-container py-14 md:py-20">
      <div class="max-w-2xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Nuestra forma de hacer las cosas', 'sage') }}
        </p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-ink text-balance md:text-3xl">
          {{ __('Una buena experiencia empieza por escuchar', 'sage') }}
        </h2>
      </div>

      <div class="mt-10 grid gap-4 md:grid-cols-3 md:gap-6">
        @foreach ($pillars as $pillar)
          <div class="rounded-2xl border border-line bg-surface-raised p-6 transition-colors hover:border-line-strong md:p-8">
            <span class="inline-flex size-11 items-center justify-center rounded-xl border border-line text-ink">
              <x-icon :name="$pillar['icon']" class="size-5" />
            </span>
            <h3 class="mt-5 text-sm font-bold tracking-widest text-ink">{{ $pillar['title'] }}</h3>
            <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $pillar['text'] }}</p>
          </div>
        @endforeach
      </div>

      {{-- Marcas aliadas --}}
      <div class="mt-12 rounded-2xl border border-line px-6 py-8 md:px-10">
        <p class="text-center text-[11px] font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Trabajamos con marcas reconocidas', 'sage') }}
        </p>
        <ul class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-4 md:gap-x-8">
          @foreach (\App\brands_with_logo() as $brand)
            <li class="h-16 md:h-24 w-auto flex items-center justify-center">
              <img
                src="{{ \App\brand_logo_url($brand) }}"
                alt="{{ $brand->name }}"
                loading="lazy"
                decoding="async"
                class="h-full w-auto object-contain brand-logo-white-green"
              >
            </li>
          @endforeach
        </ul>
        <p class="mt-8 text-center text-xs leading-relaxed text-ink-subtle">
          {{ __('Seleccionadas para ofrecer alternativas reales según las necesidades y objetivos de cada ciclista.', 'sage') }}
        </p>
      </div>
    </section>

    {{-- Los años --}}
    <section class="border-y border-line bg-surface-raised">
      <div class="rb-container py-14 md:py-20">
        <div class="grid gap-10 md:grid-cols-12 md:items-start">
          <div class="md:col-span-5">
            <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
              {{ sprintf(__('Desde %d', 'sage'), $contact['founded']) }}
            </p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-ink text-balance md:text-3xl">
              {{ sprintf(__('%d años acompañando ciclistas', 'sage'), $contact['years']) }}
            </h2>
          </div>

          <div class="space-y-5 text-sm leading-relaxed text-ink-muted md:col-span-7 md:text-base">
            <p>
              {{ __('Hemos visto pasar muchas bicicletas, muchas rutas y, sobre todo, muchas historias. Hemos tenido el privilegio de acompañar a personas en su primera bicicleta y ser parte de muchas primeras veces: la primera ruta, el primer ascenso, el primer entrenamiento.', 'sage') }}
            </p>
            <p>
              {{ __('Esos momentos en los que una bicicleta termina convirtiéndose en mucho más que un medio de transporte son una de las razones por las que seguimos haciendo lo que hacemos.', 'sage') }}
            </p>
          </div>
        </div>

        {{-- Cita destacada --}}
        <blockquote class="mt-12 border-l-2 border-line-strong pl-6 md:mt-16 md:pl-10">
          <p class="text-lg font-medium leading-relaxed text-ink text-balance md:text-2xl">
            {{ __('El ciclismo no se trata únicamente de componentes, pesos o especificaciones. También se trata de tranquilidad, comodidad, libertad y disfrute.', 'sage') }}
          </p>
        </blockquote>
      </div>
    </section>

    {{-- Hacia dónde vamos --}}
    <section class="rb-container py-14 md:py-20">
      <div class="grid gap-10 md:grid-cols-12 md:items-start">
        <div class="md:col-span-5">
          <h2 class="text-2xl font-bold tracking-tight text-ink text-balance md:text-3xl">
            {{ __('Hacia dónde vamos', 'sage') }}
          </h2>
        </div>

        <div class="space-y-5 text-sm leading-relaxed text-ink-muted md:col-span-7 md:text-base">
          <p>
            {{ __('Nuestro objetivo es seguir creciendo junto al ciclismo. Queremos consolidarnos como una tienda referente en Bogotá y llevar nuestra experiencia a más ciclistas en Colombia, fortaleciendo la comunidad y manteniendo aquello que nos ha acompañado desde el comienzo: productos de calidad, personal capacitado y, sobre todo, calidad humana.', 'sage') }}
          </p>
          <p class="text-ink">
            {{ __('Porque para nosotros, cada bicicleta puede ser el comienzo de algo mucho más grande.', 'sage') }}
          </p>
        </div>
      </div>
    </section>

    {{-- CTA final --}}
    <section class="rb-container pb-16 md:pb-24">
      <div class="relative overflow-hidden rounded-3xl border border-line bg-surface-raised p-8 text-center md:p-14">
        <p class="font-display text-xs font-bold uppercase tracking-[0.3em] text-ink-subtle">
          {{ sprintf(__('Racing Bike · desde %d', 'sage'), $contact['founded']) }}
        </p>
        <h2 class="mt-4 text-xl font-bold tracking-tight text-ink text-balance md:text-3xl">
          {{ __('Creando sueños sobre dos ruedas', 'sage') }}
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-ink-muted">
          {{ __('Cuéntanos qué quieres rodar y te ayudamos a encontrar la bicicleta que se adapta a ti, a tu terreno y a tu presupuesto.', 'sage') }}
        </p>

        <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <x-button
            variant="primary"
            size="lg"
            :href="\App\whatsapp_url(__('Hola Racing Bike 1998, quisiera asesoría para elegir mi bicicleta.', 'sage'))"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ __('Hablar con un asesor', 'sage') }}
          </x-button>

          <x-button variant="secondary" size="lg" :href="wc_get_page_permalink('shop')">
            {{ __('Ver catálogo', 'sage') }}
          </x-button>
        </div>

        <p class="mt-8 text-xs text-ink-subtle">
          {{ __('¿Prefieres visitarnos?', 'sage') }}
          <span class="text-ink-muted">{{ $contact['address'] }}, {{ $contact['city'] }}</span>
        </p>
      </div>
    </section>

  @endwhile

  {{-- Datos estructurados: identifica el negocio ante Google (Knowledge Panel,
       resultados locales). El sitio no declaraba ninguno hasta ahora. --}}
  @php
    $orgSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'BicycleStore',
      'name' => get_bloginfo('name'),
      'url' => home_url('/'),
      'description' => __('Tienda y taller de bicicletas en Bogotá desde 1998: ruta, MTB, gravel, componentes, accesorios y servicio técnico especializado.', 'sage'),
      'foundingDate' => (string) $contact['founded'],
      'telephone' => '+'.$contact['whatsapp'],
      'email' => $contact['email'],
      'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $contact['address'],
        'addressLocality' => $contact['city'],
        'addressRegion' => $contact['region'],
        'addressCountry' => $contact['country'],
      ],
      'sameAs' => array_values(array_filter([$contact['instagram'], $contact['facebook'], $contact['tiktok'] ?? ''])),
    ];
  @endphp

  <script type="application/ld+json">
    {!! wp_json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
  </script>
@endsection
