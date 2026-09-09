{{-- Componente Enterprise: Sellos de Garantía & Respaldo Oficial Bogotá --}}

<div class="rounded-2xl bg-surface-raised p-5 border border-line shadow-xl space-y-4 text-ink">
  <div class="flex items-center gap-3 border-b border-line pb-3">
    <div class="flex size-9 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
      <x-icon name="shield-check" class="size-5" />
    </div>
    <div>
      <h4 class="text-xs font-bold uppercase tracking-wider text-white">
        {{ __('Respaldo Oficial RACING BIKE 1998', 'sage') }}
      </h4>
      <p class="text-[10px] text-ink-subtle">
        {{ __('Sede física en Bogotá — ', 'sage') . \App\contact_info()['address'] }}
      </p>
    </div>
  </div>

  <ul class="space-y-3 text-xs">
    <li class="flex items-start gap-3">
      <div class="mt-0.5 flex size-5 items-center justify-center rounded-full bg-white/10 text-white shrink-0 text-[10px] font-bold">
        ✓
      </div>
      <div>
        <strong class="font-bold text-white block">{{ __('Garantía de por Vida en el Marco', 'sage') }}</strong>
        <span class="text-ink-subtle text-[11px] leading-relaxed block">
          {{ __('Respaldada por escrito desde 1998 en nuestro taller especializado.', 'sage') }}
        </span>
      </div>
    </li>

    <li class="flex items-start gap-3">
      <div class="mt-0.5 flex size-5 items-center justify-center rounded-full bg-white/10 text-white shrink-0 text-[10px] font-bold">
        ✓
      </div>
      <div>
        <strong class="font-bold text-white block">{{ __('100% Armada & Ajustada', 'sage') }}</strong>
        <span class="text-ink-subtle text-[11px] leading-relaxed block">
          {{ __('Se entrega nivelada, engrasada y calibrada lista para rodar sin costos extra.', 'sage') }}
        </span>
      </div>
    </li>

    <li class="flex items-start gap-3">
      <div class="mt-0.5 flex size-5 items-center justify-center rounded-full bg-white/10 text-white shrink-0 text-[10px] font-bold">
        ✓
      </div>
      <div>
        <strong class="font-bold text-white block">{{ __('Envío Asegurado Nacional (Interrapidísimo)', 'sage') }}</strong>
        <span class="text-ink-subtle text-[11px] leading-relaxed block">
          {{ __('Despacho protegido a cualquier municipio de Colombia en 2 a 5 días hábiles.', 'sage') }}
        </span>
      </div>
    </li>

    <li class="flex items-start gap-3">
      <div class="mt-0.5 flex size-5 items-center justify-center rounded-full bg-white/10 text-white shrink-0 text-[10px] font-bold">
        ✓
      </div>
      <div>
        <strong class="font-bold text-white block">{{ __('Factura Electrónica DIAN por Siigo', 'sage') }}</strong>
        <span class="text-ink-subtle text-[11px] leading-relaxed block">
          {{ __('Compra 100% legal con NIT 1022436363-1.', 'sage') }}
        </span>
      </div>
    </li>
  </ul>
</div>
