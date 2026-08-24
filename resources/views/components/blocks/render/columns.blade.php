{{-- resources/views/components/blocks/frontend/columns.blade.php --}}
@php
    // $data dan $lang dikirim dari looping utama frontend
    $leftZoneIds = $data['left_zone'] ?? [];
    $rightZoneIds = $data['right_zone'] ?? [];
@endphp

<div class="max-w-7xl mx-auto px-5 grid grid-cols-1 md:grid-cols-2 gap-8 my-8">

    {{-- Render Kolom Kiri --}}
    <div>
        @foreach($leftZoneIds as $childId)
            @if(isset($allContent[$childId]))
                @php $childBlock = $allContent[$childId]; @endphp
                <x-dynamic-component
                    :component="'blocks.frontend.' . str_replace('_', '-', $childBlock['type'])"
                    :data="$childBlock['data']"
                    :lang="$lang"
                    :all-content="$allContent"
                />
            @endif
        @endforeach
    </div>

    {{-- Render Kolom Kanan --}}
    <div>
        @foreach($rightZoneIds as $childId)
            @if(isset($allContent[$childId]))
                @php $childBlock = $allContent[$childId]; @endphp
                <x-dynamic-component
                    :component="'blocks.frontend.' . str_replace('_', '-', $childBlock['type'])"
                    :data="$childBlock['data']"
                    :lang="$lang"
                    :all-content="$allContent"
                />
            @endif
        @endforeach
    </div>

</div>
