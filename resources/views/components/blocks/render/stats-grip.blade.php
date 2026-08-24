@props(['data', 'lang', 'allContent' => []])

@php
    $colorTitle = $data['color_title'] ?? '#eab308';
    $colorDesc = $data['color_desc'] ?? '#ffffff';
    $colorBorder = $data['color_border'] ?? '#4b5563';
    $cols = $data['columns'] ?? 4;
    $items = $data['items'] ?? [];
@endphp

<div class="grid gap-4 my-8" style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
    @foreach($items as $item)
        <div class="p-6 rounded-xl border flex flex-col items-center justify-center text-center shadow-sm"
             style="border-color: {{ $colorBorder }}; background-color: #1f2937;">
            <span class="text-3xl font-extrabold block mb-2" style="color: {{ $colorTitle }};">
                {{ $item['title'][$lang] ?? '' }}
            </span>
            <span class="text-sm font-medium" style="color: {{ $colorDesc }};">
                {{ $item['description'][$lang] ?? '' }}
            </span>
        </div>
    @endforeach
</div>
