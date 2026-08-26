@if(request()->query('mode') === 'raw')
    {{-- Jika mode raw, gunakan tata letak kanvas kosong --}}
    @include('layouts.landing.index', get_defined_vars())
@else
    {{-- Jika mode normal, gunakan tata letak CMS --}}
    @include('layouts.app', get_defined_vars())
@endif
