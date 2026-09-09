{{--
  Plantilla principal de Mi Cuenta (Dashboard interno cuando el usuario inició sesión).
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

<div class="max-w-6xl mx-auto px-4 py-10 md:py-16">
  {{-- Header del Panel de Cliente --}}
  <div class="mb-10 border-b border-line pb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Panel de Cliente', 'sage') }}</span>
      <h1 class="mt-1 text-2xl font-bold uppercase tracking-wider text-ink md:text-3xl">
        {{ __('Mi Cuenta', 'sage') }}
      </h1>
    </div>

    @php
      $current_user = wp_get_current_user();
      $name = ($current_user && $current_user->exists()) ? $current_user->display_name : 'Cliente';
      $userEmail = ($current_user && $current_user->exists()) ? $current_user->user_email : '';
    @endphp

    <div class="flex items-center gap-3 bg-surface-raised px-4 py-2.5 rounded-full border border-line">
      <div class="size-8 rounded-full bg-ink text-surface font-bold flex items-center justify-center text-xs uppercase">
        {{ substr($name, 0, 1) }}
      </div>
      <div class="text-xs">
        <p class="font-bold text-ink">{{ $name }}</p>
        @if ($userEmail)
          <p class="text-[10px] text-ink-subtle">{{ $userEmail }}</p>
        @endif
      </div>
    </div>
  </div>

  {{-- Layout en 2 columnas: Menú Lateral + Contenido --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <aside class="lg:col-span-4 bg-surface-raised p-5 rounded-xl border border-line shadow-2xl">
      @php do_action('woocommerce_before_account_navigation'); @endphp

      <nav class="woocommerce-MyAccount-navigation" aria-label="{{ __('Navegación de cuenta', 'sage') }}">
        <ul class="space-y-1.5 text-xs font-semibold uppercase tracking-wider">
          @foreach (wc_get_account_menu_items() as $endpoint => $label)
            @php
              // Traducir etiquetas por defecto de WooCommerce al español de Colombia
              $labelsMap = [
                'dashboard' => __('Escritorio / Inicio', 'sage'),
                'orders' => __('Mis Pedidos', 'sage'),
                'downloads' => __('Descargas', 'sage'),
                'edit-address' => __('Direcciones de Envío', 'sage'),
                'edit-account' => __('Detalles de Cuenta', 'sage'),
                'customer-logout' => __('Cerrar Sesión', 'sage'),
              ];
              $displayLabel = $labelsMap[$endpoint] ?? $label;
              $isActive = is_wc_endpoint_url($endpoint) || ($endpoint === 'dashboard' && is_account_page() && ! is_wc_endpoint_url());
            @endphp
            <li>
              <a
                href="{{ esc_url(wc_get_account_endpoint_url($endpoint)) }}"
                class="flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 {{ $isActive ? 'bg-ink text-surface font-bold shadow-lg' : 'text-ink-muted hover:text-ink hover:bg-surface' }}"
              >
                <span>{{ $displayLabel }}</span>
                <x-icon name="chevron-right" class="size-4 opacity-70" />
              </a>
            </li>
          @endforeach
        </ul>
      </nav>

      @php do_action('woocommerce_after_account_navigation'); @endphp
    </aside>

    <main class="lg:col-span-8 bg-surface-raised p-6 md:p-8 rounded-xl border border-line shadow-2xl min-h-[300px]">
      @php do_action('woocommerce_account_content'); @endphp
    </main>
  </div>
</div>
