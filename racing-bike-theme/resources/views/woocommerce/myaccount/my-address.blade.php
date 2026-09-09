{{--
  Plantilla moderna y optimizada de Libreta de Direcciones (Facturación y Envío)
  Diseñada para Racing Bike 1998 — Estilo oscuro premium / ciclismo de alto rendimiento.
--}}

@php
  if (! defined('ABSPATH')) exit;

  $customer_id = get_current_user_id();
  $customer = new \WC_Customer($customer_id);

  if (! wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses = apply_filters(
      'woocommerce_my_account_get_addresses',
      [
        'billing'  => __('Dirección de facturación', 'sage'),
        'shipping' => __('Dirección de envío', 'sage'),
      ],
      $customer_id
    );
  } else {
    $get_addresses = apply_filters(
      'woocommerce_my_account_get_addresses',
      [
        'billing' => __('Dirección de facturación', 'sage'),
      ],
      $customer_id
    );
  }

  $getStateName = function($stateCode, $countryCode = 'CO') {
    if (empty($stateCode)) return '';
    $cleanCode = str_replace(['CO-', 'co-'], '', $stateCode);
    $states = \WC()->countries->get_states($countryCode);
    if (is_array($states) && isset($states[$cleanCode])) {
      return $states[$cleanCode];
    }
    if (is_array($states) && isset($states[$stateCode])) {
      return $states[$stateCode];
    }
    return $stateCode;
  };
@endphp

<div class="space-y-8">
  {{-- Encabezado de la sección --}}
  <div class="border-b border-line pb-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <span class="text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
          {{ __('Logística & Facturación', 'sage') }}
        </span>
        <h2 class="mt-1 text-xl font-bold uppercase tracking-wider text-ink md:text-2xl">
          {{ __('Libreta de Direcciones', 'sage') }}
        </h2>
      </div>

      <div class="hidden sm:flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-xs text-ink-subtle">
        <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="font-medium tracking-wide">{{ __('Cobertura Nacional Colombia', 'sage') }}</span>
      </div>
    </div>

    <p class="mt-2.5 text-xs text-ink-muted leading-relaxed max-w-2xl">
      {{ __('Las siguientes direcciones se precargarán por defecto en el checkout para agilizar tus compras, calcular las tarifas de transporte y emitir tus comprobantes de facturación.', 'sage') }}
    </p>
  </div>

  {{-- Grid de Tarjetas de Direcciones --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach ($get_addresses as $name => $address_title)
      @php
        $isBilling = ($name === 'billing');
        $formattedAddress = wc_get_account_formatted_address($name);

        // Extracción de datos del cliente
        $firstName = $isBilling ? $customer->get_billing_first_name() : $customer->get_shipping_first_name();
        $lastName  = $isBilling ? $customer->get_billing_last_name() : $customer->get_shipping_last_name();
        $fullName  = trim($firstName . ' ' . $lastName);
        $company   = $isBilling ? $customer->get_billing_company() : $customer->get_shipping_company();
        $address1  = $isBilling ? $customer->get_billing_address_1() : $customer->get_shipping_address_1();
        $address2  = $isBilling ? $customer->get_billing_address_2() : $customer->get_shipping_address_2();
        $city      = $isBilling ? $customer->get_billing_city() : $customer->get_shipping_city();
        $stateRaw  = $isBilling ? $customer->get_billing_state() : $customer->get_shipping_state();
        $stateName = $getStateName($stateRaw);
        $postcode  = $isBilling ? $customer->get_billing_postcode() : $customer->get_shipping_postcode();
        $phone     = $isBilling ? $customer->get_billing_phone() : ($customer->get_shipping_phone() ?: $customer->get_billing_phone());
        $email     = $isBilling ? $customer->get_billing_email() : '';

        $hasData = ! empty($address1) || ! empty($city) || ! empty($fullName) || ! empty($formattedAddress);
        $editUrl = esc_url(wc_get_endpoint_url('edit-address', $name));
      @endphp

      <div class="flex flex-col justify-between rounded-2xl border border-line bg-surface/80 p-5 sm:p-6 backdrop-blur-sm transition-all duration-200 hover:border-line-strong hover:shadow-xl group relative">
        {{-- Parte Superior: Título, Ícono y Estado --}}
        <div>
          {{-- Fila 1: Ícono + Categoría a la izquierda, Badge a la derecha (nunca se desborda) --}}
          <div class="flex items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2.5">
              <div class="flex size-9 items-center justify-center rounded-xl bg-surface-raised border border-line text-ink group-hover:border-ink/40 transition-colors shrink-0">
                @if ($isBilling)
                  <x-icon name="document-text" class="size-4.5 text-emerald-400" />
                @else
                  <x-icon name="truck" class="size-4.5 text-emerald-400" />
                @endif
              </div>
              <span class="text-[10px] font-bold uppercase tracking-widest text-ink-subtle">
                {{ $isBilling ? __('Facturación', 'sage') : __('Logística', 'sage') }}
              </span>
            </div>

            <div class="shrink-0">
              @if ($hasData)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 text-[10px] font-semibold text-emerald-400 whitespace-nowrap shadow-sm">
                  <x-icon name="check" class="size-3 shrink-0" />
                  <span>{{ $isBilling ? __('Configurada', 'sage') : __('Predeterminada', 'sage') }}</span>
                </span>
              @else
                <span class="inline-flex items-center rounded-full bg-surface-muted border border-line px-2.5 py-1 text-[10px] font-semibold text-ink-subtle whitespace-nowrap">
                  {{ __('Sin registrar', 'sage') }}
                </span>
              @endif
            </div>
          </div>

          {{-- Fila 2: Título a ancho completo y Subtítulo --}}
          <div class="border-b border-line/60 pb-3.5">
            <h3 class="text-sm font-bold uppercase tracking-wider text-ink leading-tight">
              {{ $isBilling ? __('Dirección de Facturación', 'sage') : __('Dirección de Entrega', 'sage') }}
            </h3>
            <p class="text-[11px] text-ink-subtle mt-1">
              {{ $isBilling ? __('Emisión de facturas y recibos', 'sage') : __('Destino de paquetes y pedidos', 'sage') }}
            </p>
          </div>

          {{-- Cuerpo de la Tarjeta: Información Detallada --}}
          <div class="py-5">
            @if ($hasData)
              <div class="space-y-3.5 text-xs">
                {{-- Nombre / Razón Social --}}
                @if ($fullName || $company)
                  <div class="flex items-start gap-2.5">
                    <x-icon name="user" class="size-4 text-ink-subtle shrink-0 mt-0.5" />
                    <div>
                      <span class="font-bold text-ink uppercase tracking-wide">
                        {{ $fullName ?: $name }}
                      </span>
                      @if ($company)
                        <span class="block text-[11px] text-ink-subtle mt-0.5 font-medium">{{ $company }}</span>
                      @endif
                    </div>
                  </div>
                @endif

                {{-- Dirección física --}}
                @if ($address1)
                  <div class="flex items-start gap-2.5">
                    <x-icon name="map-pin" class="size-4 text-ink-subtle shrink-0 mt-0.5" />
                    <div class="text-ink-muted leading-snug">
                      <span class="text-ink font-semibold block">{{ $address1 }}</span>
                      @if ($address2)
                        <span class="block text-ink-subtle text-[11px]">{{ $address2 }}</span>
                      @endif
                    </div>
                  </div>
                @endif

                {{-- Ciudad, Departamento y Código Postal --}}
                @if ($city || $stateName || $postcode)
                  <div class="flex items-center flex-wrap gap-2 pt-1">
                    <div class="flex items-center gap-1.5 text-ink-muted text-xs font-medium">
                      <span>{{ $city }}</span>
                      @if ($city && $stateName)
                        <span class="text-ink-subtle">·</span>
                      @endif
                      <span>{{ $stateName }}</span>
                    </div>

                    @if ($postcode)
                      <span class="rounded bg-surface-raised border border-line px-2 py-0.5 text-[10px] font-mono font-medium text-ink-subtle">
                        CP {{ $postcode }}
                      </span>
                    @endif
                  </div>
                @endif

                {{-- Teléfono / Contacto --}}
                @if ($phone)
                  <div class="flex items-center gap-2.5 pt-1 text-ink-subtle">
                    <x-icon name="phone" class="size-3.5 shrink-0" />
                    <span class="font-mono text-[11px] text-ink-muted">{{ $phone }}</span>
                  </div>
                @endif

                {{-- Email (Facturación) --}}
                @if ($email)
                  <div class="flex items-center gap-2.5 text-ink-subtle">
                    <x-icon name="mail" class="size-3.5 shrink-0" />
                    <span class="text-[11px] text-ink-muted truncate">{{ $email }}</span>
                  </div>
                @endif

                {{-- Sello de despacho seguro en la tarjeta de envío --}}
                @if (! $isBilling)
                  <div class="mt-4 rounded-xl border border-line/40 bg-surface-raised/60 p-2.5 text-[11px] text-ink-subtle flex items-center gap-2">
                    <x-icon name="shield-check" class="size-4 text-emerald-400 shrink-0" />
                    <span>{{ __('Envíos rastreados y asegurados a nivel nacional.', 'sage') }}</span>
                  </div>
                @endif
              </div>
            @else
              {{-- Estado Vacío --}}
              <div class="py-6 text-center">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full border border-dashed border-line bg-surface-raised/40 text-ink-subtle">
                  @if ($isBilling)
                    <x-icon name="document-text" class="size-5" />
                  @else
                    <x-icon name="truck" class="size-5" />
                  @endif
                </div>
                <p class="text-xs font-semibold text-ink">
                  {{ $isBilling ? __('Sin datos de facturación', 'sage') : __('Sin dirección de entrega', 'sage') }}
                </p>
                <p class="mt-1 text-[11px] text-ink-subtle max-w-xs mx-auto leading-relaxed">
                  {{ $isBilling
                    ? __('Registra tu NIT o cédula para la emisión de tus facturas electrónicas.', 'sage')
                    : __('Agrega tu dirección en Colombia para calcular fletes y agilizar tu compra.', 'sage')
                  }}
                </p>
              </div>
            @endif

            @php do_action('woocommerce_my_account_after_my_address', $name); @endphp
          </div>
        </div>

        {{-- Parte Inferior: Botón de Acción --}}
        <div class="border-t border-line/60 pt-4 mt-2">
          <a
            href="{{ $editUrl }}"
            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border {{ $hasData ? 'border-line bg-surface-raised hover:bg-ink hover:text-surface hover:border-ink' : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-black' }} px-4 py-3 text-xs font-bold uppercase tracking-wider text-ink transition-all duration-200 cursor-pointer group-hover:border-line-strong"
          >
            @if ($hasData)
              <x-icon name="pencil" class="size-4 transition-transform group-hover:scale-110" />
              <span>{{ $isBilling ? __('Editar Facturación', 'sage') : __('Editar Dirección', 'sage') }}</span>
            @else
              <x-icon name="plus" class="size-4" />
              <span>{{ $isBilling ? __('Registrar Facturación', 'sage') : __('Agregar Dirección', 'sage') }}</span>
            @endif
          </a>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Callout de Asistencia y Soporte --}}
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-line bg-surface-raised/80 p-5 text-xs">
    <div class="flex items-center gap-3">
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface border border-line text-emerald-400">
        <x-icon name="help" class="size-5" />
      </div>
      <div>
        <p class="font-bold uppercase tracking-wider text-ink">
          {{ __('¿Necesitas cambiar la dirección de un pedido en tránsito?', 'sage') }}
        </p>
        <p class="text-[11px] text-ink-subtle mt-0.5">
          {{ __('Si tu orden ya fue despachada, escríbenos a soporte para notificar a la transportadora.', 'sage') }}
        </p>
      </div>
    </div>

    @php
      $whatsappMsg = __('Hola Racing Bike 1998, necesito cambiar la dirección de entrega de mi pedido.', 'sage');
      $whatsappUrl = function_exists('\App\whatsapp_url') ? \App\whatsapp_url($whatsappMsg) : 'https://wa.me/';
    @endphp

    <a
      href="{{ $whatsappUrl }}"
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex items-center gap-2 rounded-xl bg-surface border border-line hover:border-emerald-500/50 hover:text-emerald-400 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-ink shrink-0 transition-colors"
    >
      <x-icon name="phone" class="size-3.5 text-emerald-400" />
      <span>{{ __('Contactar Soporte', 'sage') }}</span>
    </a>
  </div>
</div>
