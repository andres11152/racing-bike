@props([
  'price' => 0,
])

@php
  $numericPrice = (float) $price;
  if ($numericPrice <= 0) $numericPrice = 2850000;

  // Cálculos de cuotas
  $addi3 = number_format(round($numericPrice / 3), 0, ',', '.');
  $siste6 = number_format(round($numericPrice / 6), 0, ',', '.');
  $credit12 = number_format(round($numericPrice / 12), 0, ',', '.');
  $formattedTotal = number_format($numericPrice, 0, ',', '.');
@endphp

<div class="mt-4 rounded-2xl bg-surface-raised p-4 border border-line shadow-lg" data-financing-widget>
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="flex size-9 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-black text-xs shrink-0">
        %
      </div>
      <div>
        <p class="text-xs font-bold text-white">
          {{ __('O págala desde', 'sage') }}
          <span class="text-emerald-400 font-black">${{ $addi3 }} COP/mes</span>
        </p>
        <p class="text-[11px] text-ink-subtle">
          {{ __('A 3 cuotas a 0% interés con ADDI. Sistecrédito próximamente.', 'sage') }}
        </p>
      </div>
    </div>

    <button
      type="button"
      class="text-xs font-bold uppercase tracking-wider text-ink-muted hover:text-white underline underline-offset-4 cursor-pointer shrink-0 transition-colors"
      data-open-financing-modal
    >
      {{ __('Ver plan', 'sage') }}
    </button>
  </div>

  {{-- Insignias de Medios de Pago en Colombia --}}
  <div class="mt-3 pt-3 border-t border-line/60 flex flex-wrap items-center justify-between gap-2 text-[10px] font-semibold uppercase tracking-wider text-ink-subtle">
    <span class="flex items-center gap-1.5">
      <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
      {{ __('Financiación inmediata sin tarjeta', 'sage') }}
    </span>
    <div class="flex items-center gap-3.5">
      <img
        src="{{ get_theme_file_uri('public/images/addi.png') }}"
        alt="ADDI"
        class="h-[30px] w-auto object-contain rounded-md opacity-90 hover:opacity-100 transition-opacity"
      >
      <div class="relative">
        <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-amber-500 px-1.5 py-0.5 text-[7px] font-black uppercase tracking-wider text-black shadow">
          {{ __('Próximamente', 'sage') }}
        </span>
        <img
          src="{{ get_theme_file_uri('public/images/sistecredito.png') }}"
          alt="{{ __('Sistecrédito (próximamente)', 'sage') }}"
          class="h-[42px] w-auto object-contain rounded-md opacity-50 grayscale"
        >
      </div>
    </div>
  </div>
</div>

{{-- Modal de Financiación Colombiana Enterprise --}}
<div
  class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/85 backdrop-blur-2xl transition-all duration-300 p-4 md:p-6"
  data-financing-modal
  role="dialog"
  aria-modal="true"
  aria-labelledby="financing-modal-title"
>
  <div class="relative w-full max-w-xl rounded-3xl bg-[#0A0A0B] border border-white/10 p-6 md:p-8 shadow-[0_25px_70px_rgba(0,0,0,0.95)] overflow-hidden text-ink animate-fade-in">
    
    {{-- Brillo ambiental de fondo --}}
    <div class="absolute -right-12 -top-12 -z-10 size-40 rounded-full bg-emerald-500/15 blur-3xl pointer-events-none"></div>

    {{-- Encabezado del Modal --}}
    <div class="flex items-center justify-between border-b border-line pb-5 mb-6">
      <div class="flex items-center gap-3">
        <div class="flex size-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
          <x-icon name="shield-check" class="size-5" />
        </div>
        <div>
          <h3 id="financing-modal-title" class="text-base md:text-lg font-bold uppercase tracking-wider text-white">
            {{ __('Medios de Pago & Financiación', 'sage') }}
          </h3>
          <p class="text-xs text-ink-subtle">
            {{ __('Facilidades de pago para Colombia respaldadas por RACING BIKE 1998', 'sage') }}
          </p>
        </div>
      </div>

      <button
        type="button"
        class="flex size-9 items-center justify-center rounded-full bg-surface-raised text-ink-subtle hover:text-white border border-line hover:border-white/40 transition-all cursor-pointer shrink-0"
        data-close-financing-modal
        aria-label="{{ __('Cerrar financiación', 'sage') }}"
      >
        <x-icon name="close" class="size-4" />
      </button>
    </div>

    {{-- Lista de Opciones de Pago y Financiación --}}
    <div class="space-y-3.5 max-h-[60vh] overflow-y-auto pr-1 [scrollbar-width:thin]">
      
      {{-- ADDI --}}
      <div class="p-4 rounded-2xl bg-[#121316] border border-line space-y-2 hover:border-blue-500/40 transition-colors">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="px-2.5 py-1 rounded-lg bg-blue-500/15 text-blue-400 border border-blue-500/30 text-[11px] font-black uppercase tracking-wider">
            ADDI (3 Cuotas 0% Interés)
          </span>
          <span class="text-sm font-black text-white ml-auto">
            ${{ $addi3 }} <span class="text-xs font-normal text-ink-subtle">COP / mes</span>
          </span>
        </div>
        <p class="text-xs text-ink-muted leading-relaxed">
          {{ __('Aprobación en 3 minutos solo con tu cédula y celular sin tarjeta de crédito ni papeleo.', 'sage') }}
        </p>
      </div>

      {{-- Sistecrédito (próximamente) --}}
      <div class="p-4 rounded-2xl bg-[#121316] border border-line space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 text-amber-400/60 border border-amber-500/20 text-[11px] font-black uppercase tracking-wider">
            Sistecrédito (Hasta 6 Cuotas)
            <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-black">
              {{ __('Próximamente', 'sage') }}
            </span>
          </span>
          <span class="text-sm font-black text-white/50 ml-auto">
            ${{ $siste6 }} <span class="text-xs font-normal text-ink-subtle">COP / mes</span>
          </span>
        </div>
        <p class="text-xs text-ink-subtle/70 leading-relaxed">
          {{ __('Muy pronto podrás pagar tu bicicleta quincenal o mensualmente con Sistecrédito. Aún no está disponible.', 'sage') }}
        </p>
      </div>

      {{-- Tarjetas de Crédito --}}
      <div class="p-4 rounded-2xl bg-[#121316] border border-line space-y-2 hover:border-purple-500/40 transition-colors">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="px-2.5 py-1 rounded-lg bg-purple-500/15 text-purple-400 border border-purple-500/30 text-[11px] font-black uppercase tracking-wider">
            Tarjetas Crédito (Hasta 12 Cuotas)
          </span>
          <span class="text-sm font-black text-white ml-auto">
            ${{ $credit12 }} <span class="text-xs font-normal text-ink-subtle">COP / mes</span>
          </span>
        </div>
        <p class="text-xs text-ink-muted leading-relaxed">
          {{ __('Difiere el valor de tu compra con tarjetas Visa, Mastercard o American Express mediante Mercado Pago.', 'sage') }}
        </p>
      </div>

      {{-- PSE / Nequi / Daviplata --}}
      <div class="p-4 rounded-2xl bg-[#121316] border border-line space-y-2 hover:border-sky-500/40 transition-colors">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="px-2.5 py-1 rounded-lg bg-sky-500/15 text-sky-400 border border-sky-500/30 text-[11px] font-black uppercase tracking-wider">
            PSE / Transferencia / Nequi
          </span>
          <span class="text-sm font-black text-white ml-auto">
            ${{ $formattedTotal }} <span class="text-xs font-normal text-ink-subtle">COP Total</span>
          </span>
        </div>
        <p class="text-xs text-ink-muted leading-relaxed">
          {{ __('Pago seguro de contado con transferencia bancaria y factura electrónica oficial DIAN.', 'sage') }}
        </p>
      </div>
    </div>

    {{-- Botón de Cierre / Acción --}}
    <div class="mt-6 text-center border-t border-line/50 pt-5">
      <button
        type="button"
        class="w-full py-3.5 px-6 rounded-full bg-white text-black font-black uppercase tracking-widest text-xs transition-all hover:bg-neutral-200 active:scale-95 cursor-pointer shadow-xl"
        data-close-financing-modal
      >
        {{ __('Entendido, elegir opciones', 'sage') }}
      </button>
    </div>
  </div>
</div>
