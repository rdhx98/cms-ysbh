{{-- IMAGE BUBBLE MENU --}}
<div x-ref="imageBubbleMenu"
    class="absolute invisible opacity-0 bg-white dark:bg-zinc-800 p-1.5 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700/50 z-50 text-xs font-medium flex items-center gap-1">

    {{-- BUTTON ALIGN LEFT --}}
    <button type="button" @click="setImageAlignment('left')"
        :class="isImageAlignActive('left') ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' :
            'text-zinc-600 dark:text-zinc-400 border-transparent'"
        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center"
        title="Rata Kiri">
        <x-dynamic-component :component="'lucide-align-start-vertical'" class="h-4 w-4" stroke-width="2.5" />
    </button>

    {{-- BUTTON ALIGN CENTER --}}
    <button type="button" @click="setImageAlignment('center')"
        :class="isImageAlignActive('center') ?
            'bg-sage-soft text-forest font-semibold border-forest shadow-sm' :
            'text-zinc-600 dark:text-zinc-400 border-transparent'"
        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center"
        title="Rata Tengah">
        <x-dynamic-component :component="'lucide-align-center-vertical'" class="h-4 w-4" stroke-width="2.5" />
    </button>

    {{-- BUTTON ALIGN RIGHT --}}
    <button type="button" @click="setImageAlignment('right')"
        :class="isImageAlignActive('right') ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' :
            'text-zinc-600 dark:text-zinc-400 border-transparent'"
        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center"
        title="Rata Kanan">
        <x-dynamic-component :component="'lucide-align-end-vertical'" class="h-4 w-4" stroke-width="2.5" />
    </button>

    <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-700 mx-1"></div>

    {{-- BUTTONS WIDTH PERCENTAGE (Tetap Menggunakan Skema Seragam Baju Baru Anda) --}}
    @foreach ([25, 50, 100] as $width)
        <button type="button" @click="setImageWidth({{ $width }})"
            :class="isImageWidthActive({{ $width }}) ?
                'bg-sage-soft text-forest font-semibold border-forest shadow-sm' :
                'text-zinc-600 dark:text-zinc-400 border-transparent'"
            class="px-2 py-1 rounded hover:bg-sage-soft hover:text-forest font-semibold transition cursor-pointer border text-[11px]">
            {{ $width }}%
        </button>
    @endforeach
    {{-- @foreach ([25, 50, 100] as $width)
        <button type="button" @click="setImageWidth({{ $width }})"
            class="p-1.5 rounded hover:bg-sage-soft hover:text-forest text-zinc-600 dark:text-zinc-400 font-semibold transition cursor-pointer border border-transparent text-[11px]">{{ $width }}%</button>
    @endforeach --}}
    <template x-if="isImageCaptionActive()">
        <div class="flex items-center gap-1 border-l border-zinc-200 pl-1 ml-1">
            <button type="button" @click="toggleCaptionPosition()"
                class="px-2 py-1 rounded hover:bg-sage-soft hover:text-forest font-semibold transition cursor-pointer text-[11px]"
                x-text="isCaptionPositionTop() ? 'Caption ke Bawah' : 'Caption ke Atas'">
            </button>
            <button type="button" @click="removeCurrentImageCaption()"
                class="px-2 py-1 rounded hover:bg-red-50 text-red-500 font-semibold transition cursor-pointer text-[11px]">
                Hapus Caption
            </button>
        </div>
    </template>
    <x-editor.toolbar-button command="createImageCaption" activeType="alpine" activeName="isActive('caption')" title="Caption" icon="captions" />

    <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-700 mx-1"></div>

    {{-- ➕ TOMBOL HAPUS AKSI (Warna Merah Alarm Krisis) --}}
    <button type="button" @click="deleteSelectedImage()"
        class="p-1.5 rounded hover:bg-red-100 hover:text-red-600 border border-transparent hover:border-red-200 transition cursor-pointer flex items-center justify-center"
        title="Hapus Gambar">
        <x-dynamic-component :component="'lucide-trash-2'" class="h-4 w-4" stroke-width="2.5" />
    </button>

</div>
