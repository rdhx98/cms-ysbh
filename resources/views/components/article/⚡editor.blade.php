<?php

use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $user_id;

    public $category_id;
    public $title;
    public $slug;
    public string $content = '';
    public $featured_image;
    public $status;
    public $published_at;

    public $photo;

    public function mount()
    {
        // Mengatur default value ke tanggal hari ini saat halaman pertama kali dimuat
        $this->published_at = now()->format('Y-m-d');
    }

    public function save()
    {
        $this->validate([
            'content' => 'required|min:10',
        ]);
        // 1. Jika ini proses UPDATE (Artikel sudah ada di database sebelumnya)
        if (isset($this->articleId)) { 
            // Ambil isi konten lama dari database sebelum di-overwrite
            $oldContent = DB::table('articles')->where('id', $this->articleId)->value('content');

            if ($oldContent) {
                // Ekstrak semua URL gambar dari konten lama
                preg_match_all('/<img[^>]+src="([^">]+)"/', $oldContent, $oldMatches);
                $oldImages = $oldMatches[1] ?? [];

                // Ekstrak semua URL gambar dari konten baru yang siap disimpan
                preg_match_all('/<img[^>]+src="([^">]+)"/', $this->content, $newMatches);
                $newImages = $newMatches[1] ?? [];

                // Cari tahu gambar mana saja yang ada di konten lama tapi hilang di konten baru
                $deletedImages = array_diff($oldImages, $newImages);

                foreach ($deletedImages as $imageUrl) {
                    // Konversi URL absolute browser menjadi path internal storage Laravel
                    // Contoh: http://localhost/storage/articles/abc.jpg -> public/articles/abc.jpg
                    if (str_contains($imageUrl, 'storage/articles/')) {
                        $filename = basename($imageUrl);
                        $storagePath = 'articles/' . $filename;

                        if (Storage::disk('public')->exists($storagePath)) {
                            Storage::disk('public')->delete($storagePath);
                            Log::info("[Tiptap Cleaner] Berhasil menghapus file usang dari storage: " . $storagePath);
                        }
                    }
                }
            }
        }

        // 2. Lanjutkan proses penyimpanan data artikel Anda ke MariaDB...
        // DB::table('articles')->updateOrInsert([...]);

        // session()->flash('message', 'Artikel berhasil disimpan dan storage dibersihkan!');

        session()->flash('message', 'Artikel berhasil disimpan!');
    }

    public function uploadImage()
    {
        $this->validate([
            'photo' => 'image|max:5120',
        ]);

        // Hanya simpan file dan return URL murninya
        $path = $this->photo->store('articles', 'public');
        return asset('storage/' . $path);
    }
};
?>

<div class="max-w-5xl mx-auto p-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <div class="h-[calc(100vh-120px)] flex flex-col justify-between">

        <form wire:submit.prevent="save" class="flex-1 flex flex-col min-h-0 space-y-4">
            {{-- judul dan tags --}}
            <div class="">
                <input type="text" placeholder="Judul Artikel..."
                    class="w-full p-1.5 text-3xl font-bold border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 pb-2 mb-4 dark:bg-transparent" />
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <input type="date" wire:model="published_at" placeholder="Kategori (pisahkan dengan koma)"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:mb-0 mb-4 md:w-1/2" />
                    <input type="text" placeholder="Tags (pisahkan dengan koma)"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:w-1/2" />
                </div>
            </div>

            <div x-data="setupEditor('content', $wire)" @buka-modal-link.window="isLinkOpen = true"
                class="flex-1 flex flex-col min-h-0 border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative"
                wire:ignore>


                {{-- IMAGE BUBBLE MENU --}}
                <div x-ref="imageBubbleMenu"
                    class="absolute invisible opacity-0 bg-white dark:bg-zinc-800 p-1.5 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700/50 z-50 text-xs font-medium flex items-center gap-1">
                    
                    {{-- BUTTON ALIGN LEFT --}}
                    <button type="button" @click="setImageAlignment('left')"
                        :class="isImageAlignActive('left') ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' : 'text-zinc-600 dark:text-zinc-400 border-transparent'"
                        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center" title="Rata Kiri">
                        <x-dynamic-component :component="'lucide-align-start-vertical'" class="h-4 w-4" stroke-width="2.5" />
                    </button>

                    {{-- BUTTON ALIGN CENTER --}}
                    <button type="button" @click="setImageAlignment('center')"
                        :class="isImageAlignActive('center') ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' : 'text-zinc-600 dark:text-zinc-400 border-transparent'"
                        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center" title="Rata Tengah">
                        <x-dynamic-component :component="'lucide-align-center-vertical'" class="h-4 w-4" stroke-width="2.5" />
                    </button>

                    {{-- BUTTON ALIGN RIGHT --}}
                    <button type="button" @click="setImageAlignment('right')"
                        :class="isImageAlignActive('right') ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' : 'text-zinc-600 dark:text-zinc-400 border-transparent'"
                        class="p-1.5 rounded hover:bg-sage-soft hover:text-forest transition cursor-pointer border flex items-center justify-center" title="Rata Kanan">
                        <x-dynamic-component :component="'lucide-align-end-vertical'" class="h-4 w-4" stroke-width="2.5" />
                    </button>

                    <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-700 mx-1"></div>

                    {{-- BUTTONS WIDTH PERCENTAGE (Tetap Menggunakan Skema Seragam Baju Baru Anda) --}}
                    @foreach([25, 50, 100] as $width)
                        <button type="button" @click="setImageWidth({{ $width }})"
                            :class="isImageWidthActive({{ $width }}) ? 'bg-sage-soft text-forest font-semibold border-forest shadow-sm' : 'text-zinc-600 dark:text-zinc-400 border-transparent'"
                            class="px-2 py-1 rounded hover:bg-sage-soft hover:text-forest font-semibold transition cursor-pointer border text-[11px]">
                            {{ $width }}%
                        </button>
                    @endforeach
                    {{-- @foreach([25, 50, 100] as $width)
                        <button type="button" @click="setImageWidth({{ $width }})"
                            class="p-1.5 rounded hover:bg-sage-soft hover:text-forest text-zinc-600 dark:text-zinc-400 font-semibold transition cursor-pointer border border-transparent text-[11px]">{{ $width }}%</button>
                    @endforeach --}}

                    <div class="h-4 w-px bg-zinc-300 dark:bg-zinc-700 mx-1"></div>

                    {{-- ➕ TOMBOL HAPUS AKSI (Warna Merah Alarm Krisis) --}}
                    <button type="button" @click="deleteSelectedImage()"
                        class="p-1.5 rounded hover:bg-red-100 hover:text-red-600 border border-transparent hover:border-red-200 transition cursor-pointer flex items-center justify-center" title="Hapus Gambar">
                        <x-dynamic-component :component="'lucide-trash-2'" class="h-4 w-4" stroke-width="2.5" />
                    </button>
                </div>

                {{-- IMAGE BUBBLE --}}
                {{-- <div x-ref="imageBubbleMenu"
                    class="absolute invisible opacity-0 bg-sage-soft  p-1.5 rounded-lg shadow-xl border border-zinc-700/50 z-50 text-xs font-medium flex items-center gap-1">
                    <button type="button" @click="setImageAlignment('left')"
                        class="px-2 py-1 rounded hover:bg-forest text-forest hover:text-white transition cursor-pointer">
                        <x-dynamic-component :component="'lucide-align-start-vertical'" class="h-5 w-5" stroke-width="2" />
                    </button>
                    <button type="button" @click="setImageAlignment('center')"
                        class="px-2 py-1 rounded hover:bg-forest text-forest hover:text-white transition cursor-pointer">
                        <x-dynamic-component :component="'lucide-align-center-vertical'" class="h-5 w-5" stroke-width="2" />
                    </button>
                    <button type="button" @click="setImageAlignment('right')"
                        class="px-2 py-1 rounded hover:bg-forest text-forest hover:text-white transition cursor-pointer">
                        <x-dynamic-component :component="'lucide-align-end-vertical'" class="h-5 w-5" stroke-width="2" />
                    </button>

                    <div class="h-4 w-px bg-zinc-700 mx-1"></div>

                    <button type="button" @click="setImageWidth(25)"
                        class="px-2 py-1 rounded hover:bg-forest hover:text-white text-forest transition cursor-pointer">25%</button>
                    <button type="button" @click="setImageWidth(50)"
                        class="px-2 py-1 rounded hover:bg-forest hover:text-white text-forest transition cursor-pointer">50%</button>
                    <button type="button" @click="setImageWidth(100)"
                        class="px-2 py-1 rounded hover:bg-forest hover:text-white text-forest transition cursor-pointer">100%</button>
                </div> --}}

                {{-- BASIC BUBBLE --}}
                <div x-ref="bubbleMenuElement"
                    class="absolute invisible opacity-0 bg-zinc-100 dark:bg-zinc-800 text-forest p-1 rounded-lg shadow-xl border border-zinc-700/50 z-50 flex items-center gap-1">

                    <x-layouts::app.editor-toolbar-btn command="toggleBold" activeName="bold" title="Tebal (Ctrl+B)"
                        icon="bold" />

                    <x-layouts::app.editor-toolbar-btn command="toggleItalic" activeName="italic"
                        title="Miring (Ctrl+I)" icon="italic" />

                    <x-layouts::app.editor-toolbar-btn command="toggleStrike" activeName="strike"
                        title="Coretan (Ctrl+S)" icon="strikethrough" />

                    <x-layouts::app.editor-toolbar-btn command="toggleUnderline" activeName="underline"
                        title="Garis Bawah (Ctrl+⇑+X)" icon="underline" />
                </div>

                <!-- BENJAMIN BUTTONS -->
                <div
                    class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                    {{-- BOLD | ITALIC | STRIKE | UNDERLINE --}}
                    <div class="flex items-center gap-1 ">

                        <x-layouts::app.editor-toolbar-btn command="toggleBold" activeName="bold" title="Tebal (Ctrl+B)"
                            icon="bold" />

                        <x-layouts::app.editor-toolbar-btn command="toggleItalic" activeName="italic"
                            title="Miring (Ctrl+I)" icon="italic" />

                        <x-layouts::app.editor-toolbar-btn command="toggleStrike" activeName="strike"
                            title="Coretan (Ctrl+⇑+X)" icon="strikethrough" />

                        <x-layouts::app.editor-toolbar-btn command="toggleUnderline" activeName="underline"
                            title="Garis Bawah (Ctrl+U)" icon="underline" />
                    </div>

                    {{-- FONT FAMILY --}}
                    <div class="flex flex-wrap items-center gap-4 p-2 bg-gray-50 border-l border-gray-200">
                        <div class="flex items-center gap-2">
                            <select id="font-family-select" :value="getCurrentFont()"
                                @change="changeFontFamily($event.target.value)"
                                class="block w-48 px-3 py-1.5 text-sm bg-white border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="default">Plus Jakarta Sans</option>
                                <option value="Arial">Arial</option>
                                <option value="Jetbrains Mono">Jetbrains Mono</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Times New Roman">Times New Roman</option>
                            </select>
                        </div>
                    </div>

                    {{-- <x-layouts::app.editor-toolbar-btn command="toggleHiddenMarks" activeName="" activeParams="{}"
                        activeType="alpine" title="Tampilkan pilcrow" icon="pilcrow" > --}}

                    <x-layouts::app.editor-toolbar-btn command="toggleHiddenMarks()" activeName="showMarks"
                        activeParams="{}" activeType="alpine" title="Tampilkan Tanda Baca Terselubung"
                        icon="pilcrow" />

                    {{-- INDENTATION --}}
                    <div class="flex items-center gap-1 border-l border-zinc-300 dark:border-zinc-700 pl-2 ml-1">

                        <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="left"
                            {{-- Kirim string murninya ke sini --}} activeParams="{ textAlign: 'left' }" activeType="textAlign"
                            title="Rata Kiri" icon="align-left" />

                        <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="center"
                            activeParams="{ textAlign: 'center' }" activeType="textAlign" title="Rata Tengah"
                            icon="align-center" />

                        <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="right"
                            activeParams="{ textAlign: 'right' }" activeType="textAlign" title="Rata Kanan"
                            icon="align-right" />

                        <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="justify"
                            activeParams="{ textAlign: 'justify' }" activeType="textAlign" title="Rata Kiri Kanan"
                            icon="align-justify" />

                        <x-layouts::app.editor-toolbar-btn command="toggleIndent" activeName="paragraph"
                            activeParams="{ indent: true }" activeType="default"
                            title="Menjorokkan Baris Pertama (Tab)" icon="list-indent-increase" />

                    </div>

                    <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="1"
                        activeParams="{ level: 1 }" activeType="heading" title="Heading 1" icon="heading-1" />
                    <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="2"
                        activeParams="{ level: 2 }" activeType="heading" title="Heading 2" icon="heading-2" />

                    <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    {{-- TASK LIST BUTTON --}}
                    <x-layouts::app.editor-toolbar-btn command="toggleBulletList" activeName="" activeParams="{}"
                        activeType="heading" title="Bullet list" icon="list" />

                    {{-- Tombol Daftar Tugas (Task List) --}}
                    <x-layouts::app.editor-toolbar-btn command="toggleTaskList" activeName="taskList"
                        title="Daftar Tugas" icon="list-todo" />

                    {{-- Tombol Daftar Angka (1.) --}}
                    <x-layouts::app.editor-toolbar-btn command="none" {{-- Diabaikan karena activeType orderedList langsung menembak helper kustom Anda --}} activeName="number"
                        activeParams="{ listStyle: 'number' }" activeType="orderedList"
                        title="Daftar Angka (1 -> a -> i)" icon="list-tree">
                        <span class="text-xs font-bold ml-0.5">1.</span>
                    </x-layouts::app.editor-toolbar-btn>

                    {{-- Tombol Daftar Kapital (A.) --}}
                    <x-layouts::app.editor-toolbar-btn command="none" activeName="alpha"
                        activeParams="{ listStyle: 'alpha' }" activeType="orderedList"
                        title="Daftar Kapital (A -> 1 -> i)" icon="list-tree">
                        <span class="text-xs font-bold ml-0.5">A.</span>
                    </x-layouts::app.editor-toolbar-btn>

                    <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    {{-- Tombol Kutipan (Blockquote) --}}
                    <x-layouts::app.editor-toolbar-btn command="toggleBlockquote" activeName="blockquote"
                        title="Kutipan (Blockquote)" icon="quote" />

                    {{-- Tombol Blok Kode (Code Block) --}}
                    <x-layouts::app.editor-toolbar-btn command="toggleCodeBlock" activeName="codeBlock"
                        title="Blok Kode (Code Block)" icon="code-xml" />

                    <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    {{-- OPEN URL MODAL --}}
                    <button type="button" @click="openLinkModal(); $dispatch('buka-modal-link');"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' :
                            'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent"
                        title="Sisipkan Tautan">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </button>

                    <button type="button" @click="runCommand('insertTable')"
                        class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">📊
                        +Table</button>

                    <template x-if="isActive('table', {}, updatedAt)">
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded ml-2">
                            <button type="button" @click="runCommand('addColumnAfter')"
                                class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Col</button>
                            <button type="button" @click="runCommand('addRowAfter')"
                                class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Row</button>
                            <button type="button" @click="runCommand('deleteTable')"
                                class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                        </div>
                    </template>

                    {{-- OPEN UPLOAD IMAGE DIALOG --}}
                    <button type="button" @click="triggerFileSelect()"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' :
                            'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent"
                        title="Sisipkan Gambar">
                        <x-dynamic-component :component="'lucide-image-plus'" class="h-4 w-4" stroke-width="2" />
                    </button>

                </div>

                {{-- editor area --}}
                <div class="flex-1 flex flex-col min-h-0 relative bg-white dark:bg-zinc-900">

                    {{-- MODAL INPUT LINK: Sekarang posisinya mutlak di tengah atas AREA TEKS saja --}}
                    <div x-show="isLinkOpen" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                        @click.away="isLinkOpen = false; clearLinkInputs();" {{-- 💡 KUNCI POSISI: top-4 membuat modal berjarak sedikit dari batas bawah toolbar --}}
                        class="absolute left-1/2 top-4 -translate-x-1/2 w-80 bg-white p-4 rounded-md shadow-2xl ring-1 ring-black ring-opacity-5 z-40 border border-zinc-200"
                        style="display: none;">

                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Teks Tautan (Title)</label>
                                <input type="text" x-model="linkInputText" placeholder="Masukkan teks tampilan..."
                                    class="w-full text-sm px-2 py-1.5 border rounded focus:outline-none focus:border-emerald-500 bg-white text-zinc-800"
                                    @keydown.enter.prevent="submitLink(); isLinkOpen = false;" />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">URL Tujuan</label>
                                <input type="text" x-model="linkInputUrl" placeholder="https://example.com"
                                    class="w-full text-sm px-2 py-1.5 border rounded focus:outline-none focus:border-emerald-500 bg-white text-zinc-800"
                                    @keydown.enter.prevent="submitLink(); isLinkOpen = false;" />
                            </div>

                            <div class="flex justify-end gap-2 text-xs pt-1 border-t border-gray-100">
                                <template x-if="checkButtonActive('link', {}, 'default')">
                                    <button type="button" @click="unsetLink(); isLinkOpen = false;"
                                        class="text-red-500 px-2 py-1 hover:underline mr-auto cursor-pointer">
                                        Copt Link
                                    </button>
                                </template>
                                <button type="button" @click="isLinkOpen = false; clearLinkInputs();"
                                    class="text-gray-500 px-3 py-1 hover:bg-gray-100 rounded cursor-pointer">
                                    Batal
                                </button>
                                <button type="button" @click="submitLink(); isLinkOpen = false;"
                                    class="bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700 font-medium cursor-pointer">
                                    Terapkan
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- UPLOADING IMAGES --}}
                    <div wire:loading class="absolute left-1/2 top-4 -translate-x-1/2 z-40" style="display: none;">

                        {{-- Box styling dibuat senada dengan komponen Sage-Soft & Amber Alert --}}
                        <div
                            class="bg-amber-50 dark:bg-zinc-800 border border-amber-200 dark:border-amber-900/50 px-4 py-2 rounded-full shadow-lg flex items-center gap-2 select-none">
                            {{-- Efek putaran kustom CSS internal --}}
                            <svg class="animate-spin h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span
                                class="text-xs text-amber-700 dark:text-amber-400 font-semibold animate-pulse tracking-wide">
                                ⏳ Mengunggah berkas gambar...
                            </span>
                        </div>
                    </div>

                    {{-- AREA WRAPPER TEXT UTAMA TIPTAP --}}
                    <div x-ref="editorElement"
                        class="prose prose-zinc dark:prose-invert max-w-none p-6 flex-1 overflow-y-auto dark:text-zinc-100 focus:outline-none">
                    </div>

                </div>

                <input type="file" x-ref="fileInput" accept="image/*" multiple class="hidden"
                    @change="handleMultipleImageUpload($event.target.files); $event.target.value = ''" />

            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm shadow cursor-pointer">
                    {{ __('Simpan Artikel') }}
                </button>
            </div>
        </form>

    </div>
</div>

{{-- 

    <style>
        Style CSS bawaan kamu tetap dipertahankan di sini semisal placeholder & task list...
        .tiptap p.is-editor-empty:first-child::before {
            color: #a1a1aa;
            content: attr(data-placeholder);
            float: left;
            height: 0;
            pointer-events: none;
        }

        .tiptap ul[data-type="taskList"] {
            list-style: none !important;
            padding-left: 0.5rem !important;
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .tiptap ul[data-type="taskList"] li {
            margin-top: 0.5rem !important;
            margin-bottom: 0.5rem !important;
            padding-left: 0 !important;
        }

        .tiptap ul[data-type="taskList"] li::before {
            display: none !important;
        }

        .tiptap ul[data-type="taskList"] input[type="checkbox"] {
            -webkit-appearance: none;
            appearance: none;
            background-color: transparent;
            margin: 0 !important;
            cursor: pointer;
            width: 1.2rem !important;
            height: 1.2rem !important;
            border: 2px solid #d1d5db;
            border-radius: 0.375rem;
            display: grid;
            place-content: center;
            transition: all 0.15s ease-in-out;
        }

        .dark .tiptap ul[data-type="taskList"] input[type="checkbox"] {
            border-color: #52525b;
        }

        .tiptap ul[data-type="taskList"] input[type="checkbox"]:checked {
            background-color: #15803d;
            border-color: #15803d;
        }

        .tiptap ul[data-type="taskList"] input[type="checkbox"]::before {
            content: "";
            width: 0.55rem;
            height: 0.35rem;
            transform: scale(0) rotate(-45deg);
            transform-origin: center;
            transition: 100ms transform ease-in-out;
            box-shadow: inset 0.15rem -0.15rem 0px 0px white;
        }

        .tiptap ul[data-type="taskList"] input[type="checkbox"]:checked::before {
            transform: scale(1) rotate(-45deg) translate(0.05rem, -0.05rem);
        }

        .tiptap ul[data-type="taskList"] li>div {
            flex: 1 1 auto;
            margin: 0 !important;
        }

        .tiptap img {
            display: block !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 50px;
            /* Memastikan space gambar tidak nol piksel saat loading */
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }

        /* Beri highlight tipis jika gambar sedang diklik/aktif */
        .tiptap img.ProseMirror-selectednode {
            outline: 3px solid #10b981;
            /* Warna hijau emerald sesuai tema */
            border-radius: 0.5rem;
        }

        /* Memaksa elemen internal Tiptap (ProseMirror) untuk mengisi penuh tinggi kontainer */
        .tiptap .ProseMirror {
            min-height: 100%;
            outline: none;
            /* Menghilangkan border biru bawaan browser saat fokus */
        }

        /* Opsional: Mempercantik tampilan scrollbar di area editor agar senada dengan Tailwind Zinc */

        [x-ref="editorElement"]::-webkit-scrollbar {
            width: 8px;
        }

        [x-ref="editorElement"]::-webkit-scrollbar-track {
            background: transparent;
        }

        [x-ref="editorElement"]::-webkit-scrollbar-thumb {
            background-color: #d4d4d8;
            border-radius: 4px;
        }

        .dark [x-ref="editorElement"]::-webkit-scrollbar-thumb {
            background-color: #52525b;
        }

        .tiptap .ProseMirror {
            min-height: 100%;
            height: 100%;
            /* Tambahkan ini agar layout flexbox mengisi penuh area */
            outline: none;
        }


        /* BELOW THIS LINE IS EXPERIMENTAL AF */

        /* this if the js is short */
        /* --- 1. JAGA MARGIN PARAGRAF ASLI --- */
        .tiptap p {
            margin-top: 4px !important;
            margin-bottom: 4px !important;
            min-height: 24px !important;
        }

        /* --- 2. PENANDA TIPTAP (MENYATU SEPERTI KARAKTER BIASA) --- */
        .show-invisible-marks .tiptap-invisible-para,
        .show-invisible-marks .tiptap-invisible-break {
            display: inline !important;
            /* Jadikan karakter teks biasa */

            /* KUNCI ANTI MELAR: Wajib meniru persis gaya font di sebelahnya! */
            font-family: inherit !important;
            font-size: inherit !important;
            line-height: inherit !important;
            vertical-align: baseline !important;

            user-select: none !important;
            pointer-events: none !important;
        }

        .show-invisible-marks .tiptap-invisible-para {
            color: #10b981 !important;
            /* Hijau Emerald */
            margin-left: 4px !important;
            opacity: 0.8 !important;
            /* Dibuat sedikit transparan agar nyaman di mata */
        }

        .show-invisible-marks .tiptap-invisible-break {
            color: #c300ff !important;
            /* Ungu */
            margin-left: 2px !important;
            margin-right: 2px !important;
            opacity: 0.8 !important;
        }


        /* this if the js is long */
        /* --- 1. JAGA MARGIN PARAGRAF ASLI --- */
        .tiptap p {
            margin-top: 4px !important;
            margin-bottom: 4px !important;
            min-height: 24px !important;
        }

        /* --- 2. PENANDA PARAGRAF (¶) - ELEMEN HANTU 0 PIKSEL --- */
        .show-invisible-marks .tiptap-invisible-para {
            display: inline-block !important;
            position: static !important;
            /* Jangan pakai absolute lagi */

            /* Trik agar tidak makan tempat dan bikin melar */
            width: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            overflow: visible !important;

            vertical-align: baseline !important;
            font-family: monospace !important;
            font-size: 0.85em !important;
            font-weight: bold !important;
            color: #10b981 !important;
            /* Hijau */

            user-select: none !important;
            pointer-events: none !important;
            margin-left: 2px !important;
        }

        /* --- 3. PENANDA SHIFT+ENTER (↵) - ELEMEN HANTU 0 PIKSEL --- */
        .show-invisible-marks .tiptap-invisible-break {
            display: inline-block !important;
            position: static !important;
            /* Jangan pakai absolute lagi */

            /* Trik agar tidak turun baris dan tidak melar */
            width: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            overflow: visible !important;

            vertical-align: baseline !important;
            font-family: monospace !important;
            font-size: 0.85em !important;
            font-weight: bold !important;
            color: #c300ff !important;
            /* Ungu */

            user-select: none !important;
            pointer-events: none !important;
            margin-left: 2px !important;
        }

        /* Hanya sembunyikan separator & trailing break KETIKA tombol ¶ aktif */
        .show-invisible-marks img.ProseMirror-separator {
            display: inline !important;
            min-height: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .show-invisible-marks .ProseMirror-trailingBreak {
            display: none !important;
        }

        /* Saat editor kosong, Tiptap biasanya memberikan class '.is-empty' pada paragraf pertama.
        Jika di dalam paragraf kosong tersebut terdapat widget penanda '¶',
        maka sembunyikan placeholder bawaan Tiptap.
        */
        .tiptap p.is-empty:has(.tiptap-invisible-para)::before {
            content: none !important;
            display: none !important;
        }

        /* Alternatif: Jika extension Placeholder Anda menargetkan class global '.is-editor-empty'
        pada pembungkus utama Tiptap, gunakan rule ini:
        */
        .tiptap.is-editor-empty:has(.tiptap-invisible-para)::before {
            content: none !important;
            display: none !important;
        }

        /* Trik CSS Tampilan Spasi ala MS Word */
        /* .tiptap-invisible-space {
            position: relative;
        }

        /* Memunculkan titik tengah tanpa menggeser layout teks asli */
        /* .tiptap-invisible-space::before {
            content: "·";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            color: #a1a1aa; /* Warna abu-abu (zinc-400), bisa disesuaikan *
            font-weight: bold;
            pointer-events: none;
            user-select: none;
        } */
    </style>
--}}
