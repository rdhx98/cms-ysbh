{{-- @props(['isPreview' => false])

@if($isPreview)
    <!-- 🟢 MODE PREVIEW: Layout khusus untuk membaca artikel -->
    <div {{ $attributes->merge(['class'=>'w-full h-[calc(100vh-4rem)] flex flex-col pt-2 md:pt-0 bg-paper rounded-lg border-2 border-forest overflow-y-auto overflow-x-hidden px-6 lg:px-12']) }}>

    <article class="tiptap h-full max-w-7xl mx-auto my-8 [&_img]:rounded-xl [&_img]:shadow-sm [&_a]:text-forest">
            {{ $slot }}
        </article>
    </div>
@else
    <!-- 🔵 MODE STANDAR: Layout untuk form, tabel, detail user, dll -->
    <div {{ $attributes->merge(['class' => 'bg-white rounded-lg w-full md:max-w-none flex flex-col items-center justify-center p-4 flex-1 grow']) }}>
        <div class="w-full min-w-0 max-w-7xl max-h-[calc(93vh)] h-full flex flex-col justify-start items-center">
            {{ $slot }}
        </div>
    </div>
@endif --}}
@props(['isPreview' => false])

@if($isPreview)
    <!-- 🟢 MODE PREVIEW: Layout khusus untuk membaca artikel -->
    <div {{ $attributes->merge(['class'=>'w-full h-[calc(100vh-4rem)] flex flex-col bg-paper rounded-lg border-2 border-forest overflow-hidden'])}}>

        <!-- AREA HEADER (Berdiri Sendiri) -->
        @if (isset($header))
            <div class="w-full flex-none px-6 lg:px-12 p-4 border-b border-forest/10 bg-white">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 ">
                    {{ $header }}
                </div>
            </div>
        @endif

        <!-- AREA ARTIKEL (Memenuhi Sisa Ruang & Bisa di-Scroll) -->
        <div class="w-full flex-1 overflow-y-auto overflow-x-hidden px-6 lg:px-12">
            <article class="tiptap max-w-7xl mx-auto my-8  [&>*:not(.tiptap-full-bleed):not([data-type='column-block'])]:max-w-4xl  [&>*:not(.tiptap-full-bleed):not([data-type='column-block'])]:mx-auto  [&_img]:rounded-xl [&_img]:shadow-sm [&_a]:text-forest">
                {{ $slot }}
            </article>
        </div>

    </div>
@else
    <!-- 🔵 MODE STANDAR: Layout untuk form, tabel, detail user, dll -->
    <div {{ $attributes->merge(['class' => 'bg-white rounded-lg w-full md:max-w-none flex flex-col items-center justify-center p-4 flex-1 grow']) }}>
        <div class="w-full min-w-0 max-w-7xl max-h-[calc(100vh-6rem)] flex flex-col justify-start items-center h-full min-h-0">
            {{ $slot }}
        </div>
    </div>
@endif
