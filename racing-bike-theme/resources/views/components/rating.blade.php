@props([
  'value' => 0,
  'count' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
  <div class="flex items-center gap-0.5 text-ink" aria-hidden="true">
    @for ($i = 1; $i <= 5; $i++)
      <x-icon name="star" class="size-3.5 {{ $i <= round($value) ? 'text-ink' : 'text-ink-faint' }}" />
    @endfor
  </div>

  <span class="sr-only">{{ $value }} {{ __('out of 5 stars', 'sage') }}</span>

  @if ($count !== null)
    <span class="text-xs text-ink-subtle">({{ $count }})</span>
  @endif
</div>
