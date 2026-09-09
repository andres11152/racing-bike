{{--
  Navegación de escritorio. Los paneles se abren por :hover y :focus-within,
  de modo que el teclado funciona sin JS; app.js sólo añade el cierre con Escape.
--}}
<nav class="hidden md:block" aria-label="{{ __('Navegación principal', 'sage') }}" data-mega-menu>
  <ul class="flex items-center gap-7">
    @foreach ($primaryMenu as $item)
      <li class="group static">
        <a
          href="{{ $item['url'] }}"
          class="flex items-center gap-1 py-6 text-xs font-medium uppercase tracking-widest transition-colors {{ $item['current'] ? 'text-ink' : 'text-ink-muted hover:text-ink' }}"
          @if ($item['children']) aria-haspopup="true" aria-expanded="false" @endif
        >
          {{ $item['title'] }}

          @if ($item['children'])
            <x-icon name="chevron-down" class="size-3 transition-transform group-hover:rotate-180" />
          @endif
        </a>

        @if ($item['children'])
          {{--
            El enlace mide 64px de alto pero el header mide 113px (lo marca el
            logo), y como la fila está centrada quedan ~24px muertos entre el
            enlace y el panel, que arranca en `top-full` del header. Al bajar
            el ratón hacia el panel se cruzaba ese hueco, se perdía el :hover
            y el panel se cerraba.

            `before:*` añade un puente invisible que cubre exactamente esa
            franja. Al ser hijo del panel, mantiene el :hover del <li>. Sólo
            está activo con el panel abierto (cuando está cerrado hereda
            `visibility: hidden` y no recibe puntero), y su alto es justo el
            del hueco, así que no tapa los enlaces ni bloquea pasar a otro
            elemento del menú.
          --}}
          <div
            class="invisible absolute inset-x-0 top-full z-40 border-y border-line bg-surface-raised opacity-0 transition-[opacity,visibility] duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 before:absolute before:inset-x-0 before:bottom-full before:h-6 before:content-['']"
            data-mega-panel
          >
            <div class="rb-container grid gap-8 py-10 md:grid-cols-4">
              <div class="md:col-span-3">
                <ul class="grid grid-cols-2 gap-x-8 gap-y-3 lg:grid-cols-3">
                  @foreach ($item['children'] as $child)
                    <li>
                      <a
                        href="{{ $child['url'] }}"
                        class="block py-1 text-sm text-ink-muted transition-colors hover:text-ink"
                      >
                        {{ $child['title'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>

                <a
                  href="{{ $item['url'] }}"
                  class="mt-8 inline-flex items-center gap-2 text-xs font-medium uppercase tracking-widest text-ink transition-colors hover:text-ink-muted"
                >
                  {{ __('Ver todo en', 'sage') }} {{ $item['title'] }}
                  <x-icon name="chevron-right" class="size-3.5" />
                </a>
              </div>

              <a href="{{ $item['url'] }}" class="group/feat block">
                <div class="aspect-4/3 overflow-hidden bg-surface-muted">
                  @if ($item['image'])
                    <img
                      src="{{ $item['image'] }}"
                      alt="{{ $item['title'] ?? __('Categoría', 'sage') }}"
                      loading="lazy"
                      decoding="async"
                      class="size-full object-cover transition-transform duration-500 group-hover/feat:scale-105"
                    >
                  @endif
                </div>
                <p class="mt-3 text-xs font-medium uppercase tracking-widest text-ink">
                  {{ $item['title'] }}
                </p>
              </a>
            </div>
          </div>
        @endif
      </li>
    @endforeach
  </ul>
</nav>
