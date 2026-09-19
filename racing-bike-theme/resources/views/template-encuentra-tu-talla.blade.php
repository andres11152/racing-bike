{{--
  Template Name: Encuentra tu talla
--}}

@extends('layouts.app')

@php
  $steps = [
    [
      'icon' => 'ruler',
      'title' => __('1. Ingresa tu estatura', 'sage'),
      'text' => __('Desliza el control con tu estatura en centímetros. Es el dato que más pesa en la geometría del marco.', 'sage'),
    ],
    [
      'icon' => 'sparkles',
      'title' => __('2. Te sugerimos el marco ideal', 'sage'),
      'text' => __('El calculador cruza tu estatura con nuestras tablas de geometría y te devuelve la talla recomendada en segundos.', 'sage'),
    ],
    [
      'icon' => 'wrench',
      'title' => __('3. Ajuste personalizado al entregar', 'sage'),
      'text' => __('La talla es el punto de partida. Cada bicicleta se entrega 100% armada y ajustada a tu medida en nuestro taller.', 'sage'),
    ],
  ];

  // La tabla de esta sección ya NO se escribe a mano aquí: hasta ahora
  // tenía sus propios rangos de estatura (XS 160-168cm, S 168-175cm...)
  // que nunca se actualizaron cuando el cálculo real del modal
  // (racing-bike-size-calculator/assets/js/calculator.js) pasó a basarse
  // en entrepierna estimada, no en la estatura directa — el visitante
  // veía una talla en esta tabla y otra distinta al usar "Calcular mi
  // talla" para la misma estatura. La tabla ahora la genera
  // calculator.js en tiempo real llamando a la MISMA función
  // (window.RBSizeCalculator.calculateDisciplineSize) que usa el modal,
  // así que estructuralmente no puede desalinearse: ver
  // initRbSizeReferenceTable() en ese archivo.
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
          ['label' => __('Encuentra tu talla', 'sage')],
        ]"
      />

      <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
          {{ __('Encuentra tu talla', 'sage') }}
        </p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink text-balance md:text-5xl">
          {{ __('La talla correcta cambia cómo se siente cada pedalada', 'sage') }}
        </h1>
        <p class="mt-6 text-sm leading-relaxed text-ink-muted md:text-lg">
          {{ __('Un marco muy grande o muy pequeño te resta control y comodidad, sin importar qué tan buenos sean los componentes. Cuéntanos tu estatura y en 10 segundos te decimos qué talla buscar.', 'sage') }}
        </p>

        <div class="mt-8">
          <x-button type="button" size="lg" data-open-size-finder>
            {{ __('Calcular mi talla', 'sage') }}
          </x-button>
        </div>
      </div>
    </section>

    {{-- Cómo funciona --}}
    <section class="border-y border-line bg-surface-raised">
      <div class="rb-container py-14 md:py-20">
        <div class="max-w-2xl">
          <p class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">
            {{ __('Cómo funciona', 'sage') }}
          </p>
          <h2 class="mt-3 text-2xl font-bold tracking-tight text-ink text-balance md:text-3xl">
            {{ __('Tres pasos, sin registrarte', 'sage') }}
          </h2>
        </div>

        <div class="mt-10 grid gap-4 md:grid-cols-3 md:gap-6">
          @foreach ($steps as $step)
            <div class="rounded-2xl border border-line bg-surface p-6 transition-colors hover:border-line-strong md:p-8">
              <span class="inline-flex size-11 items-center justify-center rounded-xl border border-line text-ink">
                <x-icon :name="$step['icon']" class="size-5" />
              </span>
              <h3 class="mt-5 text-sm font-bold tracking-widest text-ink">{{ $step['title'] }}</h3>
              <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $step['text'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- Tabla de referencia + respaldo --}}
    <section class="rb-container py-14 md:py-20">
      <div class="grid gap-12 lg:grid-cols-12 lg:items-start">

        <div class="lg:col-span-7">
          <h2 class="border-b border-line pb-3 text-sm font-bold uppercase tracking-widest text-ink">
            {{ __('Tabla de referencia por estatura', 'sage') }}
          </h2>
          <p class="mt-4 text-sm leading-relaxed text-ink-muted">
            {{ __('Una primera guía mientras usas el calculador. Si estás justo en el límite entre dos tallas, elige la menor para más agilidad, o la mayor para más estabilidad en ruta.', 'sage') }}
          </p>

          <div class="mt-6 overflow-hidden rounded-2xl border border-line">
            <table class="w-full text-left">
              <thead class="bg-surface-raised text-xs font-semibold uppercase tracking-wider text-ink">
                <tr>
                  <th class="border-b border-line px-4 py-3">{{ __('Talla', 'sage') }}</th>
                  <th class="border-b border-line px-4 py-3">{{ __('Estatura', 'sage') }}</th>
                  <th class="border-b border-line px-4 py-3">{{ __('Referencia', 'sage') }}</th>
                </tr>
              </thead>
              {{--
                Filas generadas por initRbSizeReferenceTable() en
                calculator.js, llamando a la misma
                window.RBSizeCalculator.calculateDisciplineSize() que usa
                el modal — ver el comentario en el @php de arriba. La fila
                de "Cargando…" es lo único que se ve si JS no corre.
              --}}
              <tbody class="divide-y divide-line text-ink-muted" data-rb-size-reference-table data-discipline="road">
                <tr>
                  <td class="px-4 py-3 text-xs text-ink-subtle" colspan="3">{{ __('Cargando tabla de referencia…', 'sage') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-6 flex flex-wrap items-center gap-4">
            <x-button type="button" size="md" variant="secondary" data-open-size-finder>
              {{ __('Prefiero el cálculo personalizado', 'sage') }}
            </x-button>
            <p class="text-xs leading-relaxed text-ink-subtle">
              {{ __('Toma tu estatura exacta, no un rango.', 'sage') }}
            </p>
          </div>
        </div>

        <aside class="lg:col-span-5 lg:sticky lg:top-28">
          <x-trust-badges />

          <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="flex items-start gap-3 text-sm leading-relaxed text-ink-muted">
              <x-icon name="help" class="mt-0.5 size-5 shrink-0 text-emerald-400" />
              <span>
                {{ __('¿Sigues con dudas después de calcular tu talla?', 'sage') }}
                <a href="{{ $links['contact'] }}" class="text-ink underline underline-offset-2 transition-colors hover:text-emerald-400">
                  {{ __('Escríbenos', 'sage') }}</a>
                {{ __('y te asesoramos antes de que compres.', 'sage') }}
              </span>
            </p>
          </div>
        </aside>

      </div>
    </section>

  @endwhile
@endsection

{{--
  El botón "Calcular mi talla" (data-open-size-finder) lo escucha el plugin
  Racing Bike Size Calculator, que inyecta su propio modal (#rb-size-finder-modal)
  en el wp_footer de TODO el sitio — no hay que incluir ni duplicar nada aquí.
  Ver racing-bike-size-calculator/racing-bike-size-calculator.php y assets/js/calculator.js.
--}}

