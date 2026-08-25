{{-- @props(['data', 'lang', 'allContent' => []])

@if(!empty($data['url']))
    <div class="my-8">
        <img src="{{ $data['url'] }}" alt="Image block" class="rounded-xl shadow-md w-full h-auto object-cover">
    </div>
@endif --}}


@props([
    'data',
    'lang',
    'allContent' => []
])

@if(!empty($data['url']))
    <div class="w-full my-6 flex flex-col justify-center">
        <img src="{{ $data['url'] }}" 
             alt="Visual konten" 
             loading="lazy" 
             class="w-full h-auto rounded-xl shadow-md border border-gray-100 object-cover" 
        />
    </div>
@endif