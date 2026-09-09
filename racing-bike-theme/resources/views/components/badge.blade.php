@props([
  'variant' => 'default',
])

@php
  $variants = [
    'default' => 'bg-surface-muted text-ink',
    'sale' => 'bg-action text-on-action',
    'outline' => 'border border-line-strong text-ink',
    'sold-out' => 'bg-transparent text-ink-subtle line-through',
  ];

  $class = $variants[$variant] ?? $variants['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 text-[10px] font-semibold uppercase tracking-widest {$class}"]) }}>
  {{ $slot }}
</span>
