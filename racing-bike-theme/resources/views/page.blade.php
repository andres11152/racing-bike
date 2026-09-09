@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @if (is_checkout() || is_cart() || is_account_page())
      @includeFirst(['partials.content-page', 'partials.content'])
    @else
      <div class="rb-container py-10 md:py-16">
        @include('partials.page-header')
        <div class="mt-8">
          @includeFirst(['partials.content-page', 'partials.content'])
        </div>
      </div>
    @endif
  @endwhile
@endsection
