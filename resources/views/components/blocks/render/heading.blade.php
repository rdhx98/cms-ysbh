@props(['data', 'lang', 'allContent' => []])

<h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">
    {!! strip_tags($data['text'][$lang] ?? '', ['span', 'strong', 'em', 'u', 's', 'a', 'br']) !!}
</h2>
