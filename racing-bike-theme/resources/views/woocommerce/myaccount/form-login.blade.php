{{--
  Plantilla del Formulario de Ingreso y Registro de WooCommerce (Mi Cuenta) optimizada y en español.
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

<div class="max-w-md mx-auto px-4 py-12 md:py-20">
  <div class="bg-surface-raised p-6 md:p-8 rounded-xl border border-line shadow-2xl space-y-6">
    <div class="text-center border-b border-line pb-6">
      <span class="text-xs font-semibold uppercase tracking-widest text-ink-subtle">{{ __('Acceso Clientes', 'sage') }}</span>
      <h1 class="mt-1 text-2xl font-bold uppercase tracking-wider text-ink">
        {{ __('Mi Cuenta', 'sage') }}
      </h1>
    </div>

    @php do_action('woocommerce_before_customer_login_form'); @endphp

    <div class="u-columns col2-set" id="customer_login">
      {{-- Formulario de Ingreso (Login) --}}
      <div class="u-column1 col-1">
        <h2 class="text-sm font-bold uppercase tracking-widest text-ink mb-4">{{ __('Ingresar', 'sage') }}</h2>

        <form class="woocommerce-form woocommerce-form-login login space-y-4" method="post">
          @php do_action('woocommerce_login_form_start'); @endphp

          <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="username" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
              {{ __('Usuario o correo electrónico', 'sage') }}&nbsp;<span class="required">*</span>
            </label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none" name="username" id="username" autocomplete="username" value="{{ ! empty($_POST['username']) ? wp_unslash($_POST['username']) : '' }}" />
          </p>

          <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="password" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
              {{ __('Contraseña', 'sage') }}&nbsp;<span class="required">*</span>
            </label>
            <input class="woocommerce-Input woocommerce-Input--text input-text w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none" type="password" name="password" id="password" autocomplete="current-password" />
          </p>

          @php do_action('woocommerce_login_form'); @endphp

          <div class="flex items-center justify-between text-xs pt-2">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme flex items-center gap-2 cursor-pointer text-ink-muted">
              <input class="woocommerce-form__input woocommerce-form__input-checkbox rounded border-line-strong bg-surface text-ink focus:ring-0" name="rememberme" type="checkbox" id="rememberme" value="forever" />
              <span>{{ __('Recordarme', 'sage') }}</span>
            </label>

            <p class="woocommerce-LostPassword lost_password">
              <a href="{{ esc_url(wp_lostpassword_url()) }}" class="text-ink-subtle hover:text-ink transition-colors underline">
                {{ __('¿Olvidaste tu contraseña?', 'sage') }}
              </a>
            </p>
          </div>

          @php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); @endphp

          <button type="submit" class="woocommerce-button button woocommerce-form-login__submit w-full inline-flex items-center justify-center gap-2 font-bold uppercase tracking-widest bg-action text-on-action px-6 py-3.5 text-xs hover:bg-action-hover transition-all duration-200 shadow-xl cursor-pointer" name="login" value="{{ esc_attr__('Acceder', 'woocommerce') }}">
            {{ __('Acceder', 'sage') }}
          </button>

          @php do_action('woocommerce_login_form_end'); @endphp
        </form>
      </div>

      {{-- Registro (si está habilitado en WooCommerce) --}}
      @if ('yes' === get_option('woocommerce_enable_myaccount_registration'))
        <div class="u-column2 col-2 border-t border-line pt-6 mt-6">
          <h2 class="text-sm font-bold uppercase tracking-widest text-ink mb-4">{{ __('Registrarse', 'sage') }}</h2>

          <form method="post" class="woocommerce-form woocommerce-form-register register space-y-4" @php do_action('woocommerce_register_form_tag'); @endphp>
            @php do_action('woocommerce_register_form_start'); @endphp

            @if ('no' === get_option('woocommerce_registration_generate_username'))
              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_username" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Nombre de usuario', 'sage') }}&nbsp;<span class="required">*</span>
                </label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none" name="username" id="reg_username" autocomplete="username" value="{{ ! empty($_POST['username']) ? wp_unslash($_POST['username']) : '' }}" />
              </p>
            @endif

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
              <label for="reg_email" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                {{ __('Correo electrónico', 'sage') }}&nbsp;<span class="required">*</span>
              </label>
              <input type="email" class="woocommerce-Input woocommerce-Input--email input-text w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none" name="email" id="reg_email" autocomplete="email" value="{{ ! empty($_POST['email']) ? wp_unslash($_POST['email']) : '' }}" />
            </p>

            @if ('no' === get_option('woocommerce_registration_generate_password'))
              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_password" class="text-[11px] font-bold uppercase tracking-wider text-ink-subtle">
                  {{ __('Contraseña', 'sage') }}&nbsp;<span class="required">*</span>
                </label>
                <input type="password" class="woocommerce-Input woocommerce-Input--text input-text w-full rounded border border-line-strong bg-surface px-4 py-3 text-sm text-ink transition-colors focus:border-ink focus:outline-none" name="password" id="reg_password" autocomplete="new-password" />
              </p>
            @endif

            @php do_action('woocommerce_register_form'); @endphp

            @php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); @endphp

            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit w-full inline-flex items-center justify-center gap-2 font-bold uppercase tracking-widest bg-action text-on-action px-6 py-3.5 text-xs hover:bg-action-hover transition-all duration-200 shadow-xl cursor-pointer" name="register" value="{{ esc_attr__('Registrarse', 'woocommerce') }}">
              {{ __('Registrarse', 'sage') }}
            </button>

            @php do_action('woocommerce_register_form_end'); @endphp
          </form>
        </div>
      @endif
    </div>

    @php do_action('woocommerce_before_customer_login_form'); @endphp
  </div>
</div>
