{{--
  Plantilla de Edición de Detalles de Cuenta y Contraseña.
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

@php do_action('woocommerce_before_edit_account_form'); @endphp

<div class="space-y-8">
  {{-- Encabezado --}}
  <div class="border-b border-line pb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <span class="text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
        {{ __('Seguridad & Perfil', 'sage') }}
      </span>
      <h2 class="mt-1 text-xl font-bold uppercase tracking-wider text-ink md:text-2xl">
        {{ __('Detalles de Cuenta', 'sage') }}
      </h2>
    </div>
    <p class="text-xs text-ink-subtle">
      {{ __('Actualiza tu información personal y credenciales de acceso.', 'sage') }}
    </p>
  </div>

  <form class="woocommerce-EditAccountForm edit-account space-y-8" action="" method="post" {!! do_action('woocommerce_edit_account_form_tag') !!}>
    @php do_action('woocommerce_edit_account_form_start'); @endphp

    {{-- Sección 1: Datos Personales --}}
    <div class="rounded-2xl border border-line bg-surface/80 p-6 backdrop-blur-sm space-y-4">
      <div class="flex items-center gap-2.5 border-b border-line/60 pb-3">
        <x-icon name="user" class="size-4 text-emerald-400" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-ink">
          {{ __('Información Personal', 'sage') }}
        </h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <p class="form-row">
          <label for="account_first_name">
            {{ __('Nombre', 'woocommerce') }} <span class="required" aria-hidden="true">*</span>
          </label>
          <span class="woocommerce-input-wrapper">
            <input
              type="text"
              class="input-text"
              name="account_first_name"
              id="account_first_name"
              autocomplete="given-name"
              value="{{ esc_attr($user->first_name) }}"
              aria-required="true"
            />
          </span>
        </p>

        <p class="form-row">
          <label for="account_last_name">
            {{ __('Apellidos', 'woocommerce') }} <span class="required" aria-hidden="true">*</span>
          </label>
          <span class="woocommerce-input-wrapper">
            <input
              type="text"
              class="input-text"
              name="account_last_name"
              id="account_last_name"
              autocomplete="family-name"
              value="{{ esc_attr($user->last_name) }}"
              aria-required="true"
            />
          </span>
        </p>

        <p class="form-row md:col-span-2">
          <label for="account_display_name">
            {{ __('Nombre visible en la plataforma', 'woocommerce') }} <span class="required" aria-hidden="true">*</span>
          </label>
          <span class="woocommerce-input-wrapper">
            <input
              type="text"
              class="input-text"
              name="account_display_name"
              id="account_display_name"
              aria-describedby="account_display_name_description"
              value="{{ esc_attr($user->display_name) }}"
              aria-required="true"
            />
          </span>
          <span id="account_display_name_description" class="text-[11px] text-ink-subtle mt-1 block">
            {{ __('Así es como se mostrará tu nombre en las opiniones de productos y en el panel de usuario.', 'sage') }}
          </span>
        </p>

        <p class="form-row md:col-span-2">
          <label for="account_email">
            {{ __('Dirección de correo electrónico', 'woocommerce') }} <span class="required" aria-hidden="true">*</span>
          </label>
          <span class="woocommerce-input-wrapper">
            <input
              type="email"
              class="input-text"
              name="account_email"
              id="account_email"
              autocomplete="email"
              value="{{ esc_attr($user->user_email) }}"
              aria-required="true"
            />
          </span>
        </p>
      </div>

      @php do_action('woocommerce_edit_account_form_fields'); @endphp
    </div>

    {{-- Sección 2: Seguridad / Cambio de Contraseña --}}
    <div class="rounded-2xl border border-line bg-surface/80 p-6 backdrop-blur-sm space-y-4">
      <div class="flex items-center gap-2.5 border-b border-line/60 pb-3">
        <x-icon name="shield-check" class="size-4 text-emerald-400" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-ink">
          {{ __('Cambio de Contraseña (Opcional)', 'sage') }}
        </h3>
      </div>

      <p class="text-[11px] text-ink-subtle">
        {{ __('Deja estos campos en blanco si no deseas cambiar tu contraseña actual.', 'sage') }}
      </p>

      <div class="space-y-4">
        <p class="form-row">
          <label for="password_current">
            {{ __('Contraseña actual', 'woocommerce') }}
          </label>
          <span class="woocommerce-input-wrapper">
            <input
              type="password"
              class="input-text"
              name="password_current"
              id="password_current"
              autocomplete="current-password"
            />
          </span>
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <p class="form-row">
            <label for="password_1">
              {{ __('Nueva contraseña', 'woocommerce') }}
            </label>
            <span class="woocommerce-input-wrapper">
              <input
                type="password"
                class="input-text"
                name="password_1"
                id="password_1"
                autocomplete="new-password"
              />
            </span>
          </p>

          <p class="form-row">
            <label for="password_2">
              {{ __('Confirmar nueva contraseña', 'woocommerce') }}
            </label>
            <span class="woocommerce-input-wrapper">
              <input
                type="password"
                class="input-text"
                name="password_2"
                id="password_2"
                autocomplete="new-password"
              />
            </span>
          </p>
        </div>
      </div>
    </div>

    @php do_action('woocommerce_edit_account_form'); @endphp

    <div class="pt-2 flex items-center justify-between">
      @php wp_nonce_field('save_account_details', 'save-account-details-nonce'); @endphp
      <button
        type="submit"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-ink px-8 py-3.5 text-xs font-bold uppercase tracking-widest text-surface hover:bg-neutral-200 transition-all cursor-pointer shadow-xl"
        name="save_account_details"
        value="{{ esc_attr__('Guardar cambios', 'woocommerce') }}"
      >
        <x-icon name="check" class="size-4 text-surface" />
        <span>{{ __('Guardar Cambios', 'sage') }}</span>
      </button>
      <input type="hidden" name="action" value="save_account_details" />
    </div>

    @php do_action('woocommerce_edit_account_form_end'); @endphp
  </form>
</div>

@php do_action('woocommerce_after_edit_account_form'); @endphp
