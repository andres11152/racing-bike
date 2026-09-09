<form role="search" method="get" class="search-form relative flex items-center w-full max-w-md" action="{{ home_url('/') }}">
  <label class="w-full">
    <span class="sr-only">{{ __('Buscar:', 'sage') }}</span>
    <input
      type="search"
      class="w-full pl-11 pr-24 py-3 rounded-full border border-line-strong bg-surface-raised text-sm text-ink placeholder:text-neutral-500 focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 transition-all"
      placeholder="{!! esc_attr__('Buscar productos...', 'sage') !!}"
      value="{!! get_search_query() !!}"
      name="s"
    >
  </label>

  <button
    type="submit"
    class="absolute right-1.5 top-1.5 bottom-1.5 px-4 rounded-full bg-white text-black font-bold text-xs uppercase tracking-wider hover:bg-neutral-200 transition-all cursor-pointer"
  >
    {{ __('Buscar', 'sage') }}
  </button>
</form>
