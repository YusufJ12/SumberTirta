@include('layouts.template.header')

@include('layouts.template.sidebar')

@include('layouts.template.navbar')

{{-- @include('template.content') --}}
@yield('content')

@include('layouts.template.footer')
