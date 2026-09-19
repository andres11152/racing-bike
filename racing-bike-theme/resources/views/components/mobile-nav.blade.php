<div class="drawer lg:hidden" id="mobile-nav" data-nav-drawer data-open="false" role="dialog" aria-modal="true" aria-label="{{ __('Menú', 'sage') }}">
  <div class="absolute inset-0 bg-surface/70" data-nav-close></div>

  <nav class="drawer-panel absolute inset-y-0 left-0 flex w-full max-w-xs -translate-x-full flex-col bg-surface-raised transition-transform duration-300 ease-out">
    <div class="flex items-center justify-between border-b border-line px-6 py-4">
      <span class="text-xs font-semibold uppercase tracking-widest text-ink">{{ __('Menú', 'sage') }}</span>
      <button type="button" class="text-ink-muted transition-colors hover:text-ink" data-nav-close aria-label="{{ __('Cerrar menú', 'sage') }}">
        <x-icon name="close" class="size-5" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-5">
      <ul class="flex flex-col">
        @foreach ($primaryMenu as $item)
          <li class="border-b border-line last:border-b-0">
            @if ($item['children'])
              <button
                type="button"
                class="flex w-full items-center justify-between py-4 text-sm font-medium uppercase tracking-widest text-ink"
                data-accordion-toggle
                aria-expanded="false"
                aria-controls="submenu-{{ $item['id'] }}"
              >
                {{ $item['title'] }}
                <x-icon name="chevron-down" class="size-4 transition-transform" data-accordion-icon />
              </button>

              <ul id="submenu-{{ $item['id'] }}" class="hidden pb-4" data-accordion-panel>
                <li>
                  <a href="{{ $item['url'] }}" class="block py-2 text-sm text-ink transition-colors hover:text-ink-muted">
                    {{ __('Ver todo', 'sage') }}
                  </a>
                </li>
                @foreach ($item['children'] as $child)
                  <li>
                    <a href="{{ $child['url'] }}" class="block py-2 text-sm text-ink-muted transition-colors hover:text-ink">
                      {{ $child['title'] }}
                    </a>
                  </li>
                @endforeach
              </ul>
            @else
              <a href="{{ $item['url'] }}" class="block py-4 text-sm font-medium uppercase tracking-widest text-ink">
                {{ $item['title'] }}
              </a>
            @endif
          </li>
        @endforeach
      </ul>
    </div>

    {{--
      La calculadora de talla no vive en el menú de WordPress (ese es el
      árbol de catálogo, gestionado en el admin) y hasta ahora no tenía
      ningún enlace ni en el header ni en la navegación móvil, pese a ser
      uno de los activos que más deberían empujar conversión.
    --}}
    <div class="border-t border-line px-6 py-5">
      <ul class="space-y-3">
        @foreach ([
          ['href' => $links['sizeGuide'], 'icon' => 'ruler', 'label' => __('Encuentra tu talla', 'sage')],
        ] as $item)
          <li>
            <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-4 py-3 text-sm font-semibold text-ink transition-colors hover:border-emerald-500/40 hover:bg-emerald-500/10">
              <x-icon :name="$item['icon']" class="size-5 text-emerald-400" />
              {{ $item['label'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    {{-- Enlaces institucionales: no van en el menú principal porque ese es el
         árbol de catálogo y apretarlos ahí rompe el mega menú de escritorio. --}}
    <div class="border-t border-line px-6 py-5">
      <ul class="space-y-4">
        @foreach ([
          ['href' => $links['about'], 'icon' => 'sparkles', 'label' => __('Sobre Nosotros', 'sage')],
          ['href' => $links['faqs'], 'icon' => 'help', 'label' => __('Preguntas Frecuentes', 'sage')],
          ['href' => $links['legal'], 'icon' => 'scale', 'label' => __('Políticas y Legales', 'sage')],
        ] as $item)
          <li>
            <a href="{{ $item['href'] }}" class="flex items-center gap-3 text-sm text-ink-muted transition-colors hover:text-ink">
              <x-icon :name="$item['icon']" class="size-5" />
              {{ $item['label'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    <div class="border-t border-line px-6 py-5">
      <a href="{{ wc_get_page_permalink('myaccount') }}" class="flex items-center gap-3 text-sm text-ink-muted transition-colors hover:text-ink">
        <x-icon name="user" class="size-5" />
        {{ __('Mi cuenta', 'sage') }}
      </a>

      {{--
        El corazón de wishlist con su contador sólo vivía en el header de
        escritorio (`hidden md:block`, ver sections/header.blade.php) — en
        móvil no había ninguna forma de llegar a la lista de deseos guardada,
        ni siquiera desde este menú.
      --}}
      <a
        href="{{ function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('wishlist') : wc_get_page_permalink('myaccount') }}"
        class="mt-4 flex items-center gap-3 text-sm text-ink-muted transition-colors hover:text-ink"
      >
        <span class="relative flex items-center">
          <x-icon name="heart" class="size-5" />
          <span
            class="absolute -right-2 -top-2 flex size-4 items-center justify-center bg-action text-[10px] font-semibold text-on-action"
            data-wishlist-count
          >0</span>
        </span>
        {{ __('Mi lista de deseos', 'sage') }}
      </a>

      <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-4 flex items-center gap-3 text-sm text-ink-muted transition-colors hover:text-ink">
        <x-icon name="phone" class="size-5 text-emerald-400" />
        {{ sprintf(__('WhatsApp %s', 'sage'), $contact['whatsapp_display']) }}
      </a>
    </div>
  </nav>
</div>
