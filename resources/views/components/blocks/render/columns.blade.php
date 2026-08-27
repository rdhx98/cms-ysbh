{{-- resources/views/components/blocks/frontend/columns.blade.php --}}
@props(['data', 'lang', 'allContent' => []])

@php
    // $data dan $lang dikirim dari looping utama frontend
    $leftZoneIds = $data['left_zone'] ?? [];
    $rightZoneIds = $data['right_zone'] ?? [];
    $bgColor = $data['bg_color'] ?? 'transparent';
    $isReverse = $data['mobile_reverse'] ?? false;
@endphp

<section class="w-full py-12" style="background-color: {{ $bgColor }};">

    {{-- 🌟 2. INNER CONTAINER (Konten dikunci di tengah) --}}
    <div class="max-w-7xl mx-auto px-5 sm:px-8">

        {{-- GRID KOLOM --}}
        {{-- <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-14"> --}}
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 lg:gap-14">

            {{-- Render Anak di Kolom Kiri --}}
            <div class="max-w-none {{ $isReverse ? 'order-2 md:order-1' : 'order-1' }}">
                @foreach($leftZoneIds as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <x-dynamic-component
                            :component="'blocks.render.' . str_replace('_', '-', $childBlock['type'])"
                            :data="$childBlock['data']"
                            :lang="$lang"
                            :all-content="$allContent"
                        />
                    @endif
                @endforeach
            </div>

            {{-- Render Anak di Kolom Kanan --}}
            <div class="max-w-none {{ $isReverse ? 'order-1 md:order-2' : 'order-2' }}">
                @foreach($rightZoneIds as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <x-dynamic-component
                            :component="'blocks.render.' . str_replace('_', '-', $childBlock['type'])"
                            :data="$childBlock['data']"
                            :lang="$lang"
                            :all-content="$allContent"
                        />
                    @endif
                @endforeach
            </div>

        </div>
    </div>
</section>
{{-- <section class="w-full py-12" style="background-color: {{ $bgColor }};">
    <div class="max-w-7xl mx-auto px-5 grid grid-cols-1 md:grid-cols-2 gap-8 my-8">

        <!-- Render Kolom Kiri -->
        <div>
            @foreach($leftZoneIds as $childId)
                @if(isset($allContent[$childId]))
                    @php $childBlock = $allContent[$childId]; @endphp
                    <x-dynamic-component
                        :component="'blocks.render.' . str_replace('_', '-', $childBlock['type'])"
                        :data="$childBlock['data']"
                        :lang="$lang"
                        :all-content="$allContent"
                    />
                @endif
            @endforeach
        </div>

        <!-- Render Kolom Kanan -->
        <div>
            @foreach($rightZoneIds as $childId)
                @if(isset($allContent[$childId]))
                    @php $childBlock = $allContent[$childId]; @endphp
                    <x-dynamic-component
                        :component="'blocks.render.' . str_replace('_', '-', $childBlock['type'])"
                        :data="$childBlock['data']"
                        :lang="$lang"
                        :all-content="$allContent"
                    />
                @endif
            @endforeach
        </div>

    </div>
</section> --}}
