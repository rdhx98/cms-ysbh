@props(['data', 'lang', 'allContent' => []])

@if(!empty($data['url']))
    <div class="my-8">
        <img src="{{ $data['url'] }}" alt="Image block" class="rounded-xl shadow-md w-full h-auto object-cover">
    </div>
@endif
