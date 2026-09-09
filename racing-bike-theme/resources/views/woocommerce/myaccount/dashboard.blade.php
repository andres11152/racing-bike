{{--
  Plantilla del Escritorio interno de Mi Cuenta.
--}}

@php
  if (! defined('ABSPATH')) exit;
  $current_user = wp_get_current_user();
  $name = ($current_user && $current_user->exists()) ? $current_user->display_name : 'Cliente';
@endphp

<div class="space-y-6">
  <div class="border-b border-line pb-4">
    <h2 class="text-xl font-bold uppercase tracking-wider text-ink">
      {{ sprintf(__('Bienvenido, %s', 'sage'), $name) }}
    </h2>
    <p class="mt-2 text-xs text-ink-muted leading-relaxed">
      {{ __('Desde el panel de tu cuenta puedes consultar tus pedidos recientes, gestionar tus direcciones de entrega en Colombia y editar la contraseña y detalles de tu perfil.', 'sage') }}
    </p>
  </div>

  {{-- Tarjetas de Acceso Rápido --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
    <a href="{{ esc_url(wc_get_account_endpoint_url('orders')) }}" class="p-5 rounded-lg border border-line bg-surface hover:border-line-strong transition-all duration-200 group">
      <div class="flex items-center justify-between mb-3">
        <x-icon name="truck" class="size-6 text-ink-subtle group-hover:text-ink transition-colors" />
        <x-icon name="chevron-right" class="size-4 text-ink-subtle group-hover:translate-x-1 transition-transform" />
      </div>
      <h3 class="text-xs font-bold uppercase tracking-wider text-ink">{{ __('Mis Pedidos', 'sage') }}</h3>
      <p class="text-[11px] text-ink-subtle mt-1">{{ __('Historial y seguimiento', 'sage') }}</p>
    </a>

    <a href="{{ esc_url(wc_get_account_endpoint_url('edit-address')) }}" class="p-5 rounded-lg border border-line bg-surface hover:border-line-strong transition-all duration-200 group">
      <div class="flex items-center justify-between mb-3">
        <x-icon name="user" class="size-6 text-ink-subtle group-hover:text-ink transition-colors" />
        <x-icon name="chevron-right" class="size-4 text-ink-subtle group-hover:translate-x-1 transition-transform" />
      </div>
      <h3 class="text-xs font-bold uppercase tracking-wider text-ink">{{ __('Direcciones', 'sage') }}</h3>
      <p class="text-[11px] text-ink-subtle mt-1">{{ __('Envío y facturación', 'sage') }}</p>
    </a>

    <a href="{{ esc_url(wc_get_account_endpoint_url('edit-account')) }}" class="p-5 rounded-lg border border-line bg-surface hover:border-line-strong transition-all duration-200 group">
      <div class="flex items-center justify-between mb-3">
        <x-icon name="shield-check" class="size-6 text-ink-subtle group-hover:text-ink transition-colors" />
        <x-icon name="chevron-right" class="size-4 text-ink-subtle group-hover:translate-x-1 transition-transform" />
      </div>
      <h3 class="text-xs font-bold uppercase tracking-wider text-ink">{{ __('Seguridad', 'sage') }}</h3>
      <p class="text-[11px] text-ink-subtle mt-1">{{ __('Contraseña y datos', 'sage') }}</p>
    </a>
  </div>

  <div class="pt-4 text-xs text-ink-subtle border-t border-line flex items-center justify-between">
    <span>{!! sprintf(__('¿No eres %s?', 'sage'), '<strong>' . esc_html($name) . '</strong>') !!}</span>
    <a href="{{ esc_url(wc_logout_url()) }}" class="text-ink hover:underline font-bold">
      {{ __('Cerrar sesión', 'sage') }} &rarr;
    </a>
  </div>
</div>
