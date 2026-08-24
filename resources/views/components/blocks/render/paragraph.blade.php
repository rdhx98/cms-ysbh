@props(['data', 'lang', 'allContent' => []])

<div class="max-w-none text-gray-600 tiptap-content">
    {!! $data['text'][$lang] ?? '' !!}
</div>
