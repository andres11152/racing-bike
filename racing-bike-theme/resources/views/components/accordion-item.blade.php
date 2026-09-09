@props([
  'title',
  'id' => 'faq-' . uniqid(),
])

<div class="border-b border-line/60 py-4 last:border-0" data-accordion-item>
  <button
    type="button"
    class="flex w-full items-center justify-between py-3 text-left font-semibold text-white group cursor-pointer transition-colors hover:text-emerald-400"
    aria-expanded="false"
    aria-controls="{{ $id }}-content"
    id="{{ $id }}-trigger"
    data-accordion-trigger
  >
    <span class="text-sm md:text-base pr-4" data-accordion-title>{{ $title }}</span>
    <x-icon
      name="chevron-right"
      class="size-4 shrink-0 text-ink-subtle transition-transform duration-300 group-aria-expanded:rotate-90 group-hover:text-emerald-400"
    />
  </button>

  <div
    id="{{ $id }}-content"
    role="region"
    aria-labelledby="{{ $id }}-trigger"
    class="grid grid-rows-[0fr] transition-all duration-300 ease-in-out opacity-0 pointer-events-none"
    data-accordion-content
  >
    <div class="overflow-hidden">
      <div class="text-xs md:text-sm leading-relaxed text-ink-muted pb-4 pt-2 pr-6" data-accordion-text>
        {{ $slot }}
      </div>

      {{-- Sistema de Feedback / Calificación --}}
      <div class="border-t border-line/30 pt-3 pb-3 flex items-center justify-between text-[11px]" data-feedback-container>
        <span class="text-ink-subtle">{{ __('¿Te sirvió esta respuesta?', 'sage') }}</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-2.5 py-1 rounded bg-surface-raised border border-line text-ink-subtle hover:text-white transition-colors cursor-pointer"
            data-feedback-btn="yes"
          >
            {{ __('Sí 👍', 'sage') }}
          </button>
          <button
            type="button"
            class="px-2.5 py-1 rounded bg-surface-raised border border-line text-ink-subtle hover:text-white transition-colors cursor-pointer"
            data-feedback-btn="no"
          >
            {{ __('No 👎', 'sage') }}
          </button>
        </div>
      </div>
      <div class="hidden text-[11px] text-emerald-400 pb-3" data-feedback-thanks>
        {{ __('¡Gracias por tu opinión! Nos ayuda a mejorar.', 'sage') }}
      </div>
      <div class="hidden text-[11px] text-amber-400 pb-3 leading-relaxed" data-feedback-contact>
        {{ __('Lamentamos escuchar eso. ¿Te gustaría chatear con un asesor en WhatsApp?', 'sage') }}
        <a href="{{ $contact['whatsapp_url'] }}" target="_blank" class="underline ml-1 font-bold text-white hover:text-emerald-400 transition-colors">
          {{ __('Hablar ahora', 'sage') }}
        </a>
      </div>
    </div>
  </div>
</div>

{{-- Script único registrado globalmente para evitar duplicidad si hay múltiples items --}}
@once
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Evento de abrir / cerrar acordeón
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-accordion-trigger]');
        if (!trigger) return;

        const item = trigger.closest('[data-accordion-item]');
        const content = item.querySelector('[data-accordion-content]');
        if (!content) return;

        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

        // Cambiar estado ARIA
        trigger.setAttribute('aria-expanded', !isExpanded);

        if (!isExpanded) {
          content.classList.remove('grid-rows-[0fr]', 'opacity-0', 'pointer-events-none');
          content.classList.add('grid-rows-[1fr]', 'opacity-100');
        } else {
          content.classList.remove('grid-rows-[1fr]', 'opacity-100');
          content.classList.add('grid-rows-[0fr]', 'opacity-0', 'pointer-events-none');
        }
      });

      // Evento de feedback de utilidad (Sí / No)
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-feedback-btn]');
        if (!btn) return;

        const container = btn.closest('[data-feedback-container]');
        const item = btn.closest('[data-accordion-item]');
        const type = btn.getAttribute('data-feedback-btn');

        if (container && item) {
          container.classList.add('hidden');
          if (type === 'yes') {
            const thanks = item.querySelector('[data-feedback-thanks]');
            if (thanks) thanks.classList.remove('hidden');
          } else {
            const contact = item.querySelector('[data-feedback-contact]');
            if (contact) contact.classList.remove('hidden');
          }
        }
      });
    });
  </script>
@endonce
