{{--
  Navegación de escritorio Enterprise. Los paneles se abren por :hover y :focus-within,
  de modo que el teclado funciona sin JS; app.js añade el cierre con Escape.
--}}
<nav class="hidden lg:block" aria-label="{{ __('Navegación principal', 'sage') }}" data-mega-menu>
  <ul class="flex items-center gap-4 xl:gap-6 2xl:gap-8">
    @foreach ($primaryMenu as $item)
      <li class="group static">
        <a
          href="{{ $item['url'] }}"
          class="relative flex items-center gap-1.5 py-7 text-xs xl:text-[13px] font-bold uppercase tracking-[0.1em] transition-all {{ $item['current'] ? 'text-emerald-400' : 'text-ink-muted hover:text-white' }}"
          @if ($item['children']) aria-haspopup="true" aria-expanded="false" @endif
        >
          <span>{{ $item['title'] }}</span>

          @if ($item['children'])
            <x-icon name="chevron-down" class="size-3 text-ink-subtle transition-transform duration-200 group-hover:rotate-180 group-hover:text-emerald-400" />
          @endif

          {{-- Línea indicadora activa / hover --}}
          <span class="absolute bottom-0 inset-x-0 h-0.5 bg-emerald-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-center {{ $item['current'] ? 'scale-x-100' : '' }}"></span>
        </a>

        @if ($item['children'])
          {{--
            Mega Panel Dropdown con Glassmorphism Enterprise
            Puente invisible before:* para cubrir el espacio entre enlace y panel
          --}}
          <div
            class="invisible absolute inset-x-0 top-full z-40 border-y border-white/[0.08] bg-surface-raised/98 backdrop-blur-2xl shadow-2xl opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 before:absolute before:inset-x-0 before:bottom-full before:h-4 before:content-['']"
            data-mega-panel
          >
            <div class="rb-container grid gap-8 py-8 md:grid-cols-4">
              {{-- Subcategorías en cuadrícula limpia --}}
              <div class="md:col-span-3">
                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-400 mb-4 pb-2 border-b border-white/[0.06]">
                  {{ __('Colección', 'sage') }} · {{ $item['title'] }}
                </p>
                <ul class="grid grid-cols-2 gap-x-8 gap-y-3 lg:grid-cols-3">
                  @foreach ($item['children'] as $child)
                    <li>
                      <a
                        href="{{ $child['url'] }}"
                        class="group/link flex items-center gap-2 py-1.5 text-sm text-ink-muted transition-all hover:text-white"
                      >
                        <span class="size-1.5 rounded-full bg-white/20 group-hover/link:bg-emerald-400 transition-colors shrink-0"></span>
                        <span class="group-hover/link:translate-x-1 transition-transform duration-150">{{ $child['title'] }}</span>
                      </a>
                    </li>
                  @endforeach
                </ul>

                <a
                  href="{{ $item['url'] }}"
                  class="mt-6 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-emerald-400 transition-colors hover:text-emerald-300 group/all"
                >
                  {{ __('Ver catálogo completo de', 'sage') }} {{ $item['title'] }}
                  <x-icon name="chevron-right" class="size-3.5 group-hover/all:translate-x-1 transition-transform" />
                </a>
              </div>

              {{-- Tarjeta destacada con imagen de categoría --}}
              <a href="{{ $item['url'] }}" class="group/feat block">
                <div class="aspect-4/3 overflow-hidden rounded-2xl bg-surface-muted border border-white/[0.08] relative group-hover/feat:border-emerald-500/40 transition-all shadow-xl">
                  @if ($item['image'])
                    <img
                      src="{{ $item['image'] }}"
                      alt="{{ $item['title'] ?? __('Categoría', 'sage') }}"
                      loading="lazy"
                      decoding="async"
                      class="size-full object-cover transition-transform duration-700 group-hover/feat:scale-105"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white">
                      <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-400 block mb-0.5">{{ __('Destacado', 'sage') }}</span>
                        <span class="text-sm font-bold uppercase tracking-wider">{{ $item['title'] }}</span>
                      </div>
                      <div class="size-8 rounded-full bg-white/10 flex items-center justify-center text-white backdrop-blur group-hover/feat:bg-emerald-500 group-hover/feat:text-black transition-colors">
                        <x-icon name="chevron-right" class="size-4" />
                      </div>
                    </div>
                  @endif
                </div>
              </a>
            </div>
          </div>
        @endif
      </li>
    @endforeach
  </ul>
</nav>
