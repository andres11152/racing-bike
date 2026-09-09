@props([
  'name' => 'quantity',
  'value' => 1,
  'min' => 1,
  'max' => null,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center border border-line-strong']) }} data-quantity-input>
  <button
    type="button"
    class="flex size-10 items-center justify-center text-ink-muted transition-colors hover:text-ink"
    data-quantity-decrement
    aria-label="{{ __('Decrease quantity', 'sage') }}"
  >
    <x-icon name="minus" class="size-3.5" />
  </button>

  <input
    type="number"
    name="{{ $name }}"
    value="{{ $value }}"
    min="{{ $min }}"
    @if ($max) max="{{ $max }}" @endif
    class="w-10 border-x border-line-strong bg-transparent text-center text-sm text-ink [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
    data-quantity-value
  >

  <button
    type="button"
    class="flex size-10 items-center justify-center text-ink-muted transition-colors hover:text-ink"
    data-quantity-increment
    aria-label="{{ __('Increase quantity', 'sage') }}"
  >
    <x-icon name="plus" class="size-3.5" />
  </button>
</div>
