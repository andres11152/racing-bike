@props([
  'items' => [],
])

<nav {{ $attributes->merge(['class' => 'text-xs']) }} aria-label="{{ __('Breadcrumb', 'sage') }}">
  <ol class="flex flex-wrap items-center gap-1.5 text-ink-subtle">
    @foreach ($items as $item)
      <li class="flex items-center gap-1.5">
        @if (! $loop->last && ! empty($item['href']))
          <a href="{{ $item['href'] }}" class="transition-colors hover:text-ink">{{ $item['label'] }}</a>
          <x-icon name="chevron-right" class="size-3 text-ink-faint" />
        @else
          <span class="text-ink" aria-current="page">{{ $item['label'] }}</span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
