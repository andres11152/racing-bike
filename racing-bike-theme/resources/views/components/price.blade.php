@props([
  'regular' => null,
  'sale' => null,
  'currency' => '$',
])

@php
  $onSale = $sale !== null && $sale !== '' && $sale !== $regular && (float) $sale > 0 && (float) $sale < (float) $regular;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-2 gap-y-0.5 min-w-0 max-w-full leading-snug']) }}>
  @if ($onSale)
    <span class="text-xs font-normal text-ink-subtle line-through whitespace-nowrap">
      {{ $currency }}{{ number_format((float) $regular, 0, ',', '.') }}
    </span>
    <span class="font-bold text-ink whitespace-nowrap">
      {{ $currency }}{{ number_format((float) $sale, 0, ',', '.') }}
    </span>
  @else
    <span class="font-bold text-ink whitespace-nowrap">
      {{ $currency }}{{ number_format((float) $regular, 0, ',', '.') }}
    </span>
  @endif
</div>
