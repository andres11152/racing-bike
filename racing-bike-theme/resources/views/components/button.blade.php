@props([
  'variant' => 'primary',
  'size' => 'md',
  'href' => null,
  'type' => 'button',
])

@php
  $baseClass = 'group relative inline-flex cursor-pointer items-center justify-center overflow-hidden rounded-full font-bold uppercase tracking-widest transition-all duration-300 select-none disabled:pointer-events-none disabled:opacity-40';

  $variantClasses = [
    'primary' => 'border border-line-strong bg-surface-raised text-ink',
    'secondary' => 'border border-line text-ink',
    'ghost' => 'text-ink',
  ];

  $sizeClasses = [
    'sm' => 'px-5 py-2.5 text-xs',
    'md' => 'px-7 py-3.5 text-xs',
    'lg' => 'px-9 py-4 text-sm',
  ];

  $class = trim("{$baseClass} {$variantClasses[$variant]} {$sizeClasses[$size]}");
@endphp

@if ($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
    {{-- 1. Círculo expansivo de fondo en el hover (Pintado primero, invisible en reposo con scale-0) --}}
    <div class="absolute left-[15%] top-[35%] z-0 h-3 w-3 scale-0 rounded-full bg-action transition-all duration-500 ease-out group-hover:left-0 group-hover:top-0 group-hover:h-full group-hover:w-full group-hover:scale-100 pointer-events-none"></div>

    {{-- 2. Texto Inicial (Pintado encima en el eje Z) --}}
    <span class="relative z-10 inline-flex items-center gap-2 translate-x-0 transition-all duration-300 group-hover:translate-x-10 group-hover:opacity-0">
      {{ $slot }}
    </span>

    {{-- 3. Texto + Flecha en Hover (Pintado arriba del todo en el eje Z).
         aria-hidden porque el texto real ya lo dice la capa 2 — sin esto el
         nombre accesible del botón queda duplicado ("Ver más Ver más"). --}}
    <div class="absolute inset-0 z-20 flex h-full w-full -translate-x-10 items-center justify-center gap-2 text-on-action font-bold opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100" aria-hidden="true">
      <span>{{ $slot }}</span>
      <x-icon name="chevron-right" class="size-4 shrink-0 transition-transform group-hover:translate-x-0.5" />
    </div>
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
    {{-- 1. Círculo expansivo de fondo en el hover (Pintado primero, invisible en reposo con scale-0) --}}
    <div class="absolute left-[15%] top-[35%] z-0 h-3 w-3 scale-0 rounded-full bg-action transition-all duration-500 ease-out group-hover:left-0 group-hover:top-0 group-hover:h-full group-hover:w-full group-hover:scale-100 pointer-events-none"></div>

    {{-- 2. Texto Inicial (Pintado encima en el eje Z) --}}
    <span class="relative z-10 inline-flex items-center gap-2 translate-x-0 transition-all duration-300 group-hover:translate-x-10 group-hover:opacity-0">
      {{ $slot }}
    </span>

    {{-- 3. Texto + Flecha en Hover (Pintado arriba del todo en el eje Z).
         aria-hidden porque el texto real ya lo dice la capa 2 — sin esto el
         nombre accesible del botón queda duplicado ("Ver más Ver más"). --}}
    <div class="absolute inset-0 z-20 flex h-full w-full -translate-x-10 items-center justify-center gap-2 text-on-action font-bold opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100" aria-hidden="true">
      <span>{{ $slot }}</span>
      <x-icon name="chevron-right" class="size-4 shrink-0 transition-transform group-hover:translate-x-0.5" />
    </div>
  </button>
@endif
