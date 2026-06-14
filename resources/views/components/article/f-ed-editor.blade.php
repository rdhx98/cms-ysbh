<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
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

        // Proses simpan data artikel ke database MariaDB kamu di sini...

        session()->flash('message', 'Artikel berhasil disimpan!');
    }

    public function uploadImage()
    {
        // $this->validate([
        // 'photo' => 'image|max:5120',
        // ]);

        // // Hanya simpan file dan return URL murninya
        // $path = $this->photo->store('articles', 'public');
        // return asset('storage/' . $path);
        console.log('Upload image triggered, but nu uh');

    }
    public function saveArticle()
    {
        // 1. Jalankan Validasi Teks Utama Anda di sini (kecuali foto karena foto sudah ada di dalam string HTML)
        $this->validate([
            'title'   => 'required|min:5',
            'content' => 'required', // Ini adalah properti yang terikat dengan Tiptap
        ]);

        $htmlContent = $this->content;

        // 2. Deteksi apakah ada data gambar Base64 di dalam teks artikel
        if (str_contains($htmlContent, 'data:image')) {

            // Siasati keanehan parser HTML bawaan PHP terhadap karakter UTF-8 / Libxml
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $images = $dom->getElementsByTagName('img');

            foreach ($images as $img) {
                $src = $img->getAttribute('src');

                // Jika src diawali dengan data:image, berarti ini adalah gambar lokal yang harus kita upload ke storage
                if (str_starts_with($src, 'data:image')) {

                    // Ekstrak tipe ekstensi gambar (png, jpeg, webp, jfif, dll) dan datanya
                    list($type, $data) = explode(';', $src);
                    list(, $data)      = explode(',', $data);

                    // Ambil ekstensi asli dari string type data:image/png
                    $extension = explode('/', $type)[1];
                    if ($extension === 'jpeg') { $extension = 'jpg'; }

                    // Decode string base64 kembali menjadi biner fisik gambar
                    $decodedImage = base64_decode($data);

                    // Buat nama file unik yang aman
                    $fileName = 'articles/' . Str::random(40) . '.' . $extension;

                    // Simpan biner file ke folder storage public Anda
                    Storage::disk('public')->put($fileName, $decodedImage);

                    // Ganti src gambar lokal (Base64) menjadi URL asli publik yang bersih
                    $imageUrl = asset('storage/' . $fileName);
                    $img->setAttribute('src', $imageUrl);
                }
            }

            // Simpan kembali DOM yang telah di-update menjadi string HTML murni
            $htmlContent = $dom->saveHTML();
        }

        // 3. Simpan ke Database
        // Ganti 'Artikel' dengan Model Database yang Anda gunakan
        \App\Models\Post::create([
            'title'   => $this->title,
            'content' => $htmlContent, // HTML yang sudah bersih dengan link URL storage asli
            // properti lainnya ...
        ]);

        // TRIGGER DARI SISI BELAKANG: Kirim event ke browser bahwa simpan sukses
        $this->dispatch('artikel-tersimpan');

        session()->flash('success', 'Artikel berhasil diterbitkan dengan aman!');
        return redirect()->route('articles.index'); // atau ke halaman yang sesuai
    }
};
?>

<div class="max-w-5xl mx-auto p-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <div class="h-[calc(100vh-120px)] flex flex-col justify-between">

        <form wire:submit.prevent="save" class="flex-1 flex flex-col min-h-0 space-y-4">
            {{-- judul dan tags --}}
            <div>
                <input
                    type="text"
                    wire:model="title"
                    placeholder="Judul Artikel..."
                    class="w-full p-1.5 text-3xl font-bold border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 pb-2 mb-4 dark:bg-transparent"
                />
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <input
                        type="date"
                        wire:model="published_at"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:mb-0 mb-4 md:w-1/2"
                    /> {{-- Tag </input> ilegal sudah dibuang --}}

                    <input
                        type="text"
                        placeholder="Tags (pisahkan dengan koma)"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:w-1/2"
                    />
                </div>
            </div>

            <div
                x-data="setupEditor('content', $wire)"
                :class="{ 'show-marks': showMarks }"
                class="flex-1 flex flex-col min-h-0 border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative"
                wire:ignore>

                <div
                    id="imageBubbleMenu"
                    x-ref="imageBubbleMenu"
                    x-cloak
                    class="hidden items-center gap-1 bg-emerald-50 dark:bg-zinc-800 border border-emerald-200 dark:border-zinc-700 p-1 rounded-lg shadow-md" >
                    <button
                        type="button"
                        @click="setImageAlignment('left')"
                        :class="imageHasStyle('left') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Kiri"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"/></svg>
                    </button>

                    <button
                        type="button"
                        @click="setImageAlignment('center')"
                        :class="imageHasStyle('center') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Tengah"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M6 18h12"/></svg>
                    </button>

                    <button
                        type="button"
                        @click="setImageAlignment('right')"
                        :class="imageHasStyle('right') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Kanan"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"/></svg>
                    </button>

                    <div class="h-4 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button
                        type="button"
                        @click="setImageWidth('25')"
                        :class="imageHasStyle('25%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        25%
                    </button>

                    <button
                        type="button"
                        @click="setImageWidth('50')"
                        :class="imageHasStyle('50%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        50%
                    </button>

                    <button
                        type="button"
                        @click="setImageWidth('100')"
                        :class="imageHasStyle('100%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        100%
                    </button>
                </div>

                {{-- MENU BUBBLE --}}
                <div
                    x-ref="bubbleMenuElement"
                    class="absolute invisible opacity-0 bg-zinc-900 dark:bg-zinc-800 text-white p-1.5 rounded-lg shadow-xl border border-zinc-700/50 z-50 flex items-center gap-1" >
                    <button type="button" @click="runCommand('toggleBold')" class="px-2 py-1 rounded font-bold text-xs hover:bg-zinc-700 transition">B</button>
                    <button type="button" @click="runCommand('toggleItalic')" class="px-2 py-1 rounded italic text-xs hover:bg-zinc-700 transition">I</button>
                </div>

                <div class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                    <button type="button" @click="runCommand('toggleBold')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bold', {}, updatedAt) }" class="px-3 py-1.5 rounded font-bold text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">B</button>
                    <button type="button" @click="runCommand('toggleItalic')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('italic', {}, updatedAt) }" class="px-3 py-1.5 rounded italic text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">I</button>
                    <button type="button" @click="runCommand('toggleStrike')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('strike', {}, updatedAt) }" class="px-3 py-1.5 rounded line-through text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">S</button>

                    <div class="flex items-center gap-1 border-l border-zinc-300 pl-2 ml-1">
                        <button
                            type="button"
                            @click="showMarks = !showMarks"
                            :class="showMarks ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700' : 'text-zinc-600'"
                            class="p-1.5 rounded hover:bg-zinc-100 transition cursor-pointer flex items-center"
                            title="Tampilkan/Sembunyikan Tanda Paragraf (MS Word Style)"
                        >
                            <span class="font-mono text-sm font-bold">¶</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-l border-zinc-300 dark:border-zinc-700 pl-2 ml-1">
                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'left')"
                            :class="isActive({ textAlign: 'left' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kiri"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'center')"
                            :class="isActive({ textAlign: 'center' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Tengah"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'right')"
                            :class="isActive({ textAlign: 'right' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kanan"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'justify')"
                            :class="isActive({ textAlign: 'justify' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kiri Kanan"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('toggleHeading', 1)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 1 }, updatedAt) }" class="px-2 py-1.5 rounded font-black text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H1</button>
                    <button type="button" @click="runCommand('toggleHeading', 2)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 2 }, updatedAt) }" class="px-2 py-1.5 rounded font-extrabold text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H2</button>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('toggleBulletList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bulletList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">• List</button>
                    <button type="button" @click="runCommand('toggleOrderedList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('orderedList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">1. List</button>
                    <button type="button" @click="runCommand('toggleTaskList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('taskList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">☑ Task</button>
                    <button type="button" @click="runCommand('toggleBlockquote')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('blockquote', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs italic cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">“ Quote</button>
                    <button type="button" @click="runCommand('toggleCodeBlock')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('codeBlock', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-mono cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">&lt;/&gt; Code</button>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('setLink')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('link', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">🔗 Link</button>
                    <button type="button" @click="runCommand('insertTable')" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">📊 +Table</button>

                    <template x-if="isActive('table', {}, updatedAt)">
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded ml-2">
                            <button type="button" @click="runCommand('addColumnAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Col</button>
                            <button type="button" @click="runCommand('addRowAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Row</button>
                            <button type="button" @click="runCommand('deleteTable')" class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                        </div>
                    </template>

                    {{-- Tombol Unggah Gambar dipindah ke atas agar sejajar dengan toolbar menu lainnya --}}
                    <button
                        type="button"
                        @click="triggerFileSelect()"
                        class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition text-zinc-600 dark:text-zinc-400 ml-1"
                        title="Unggah Gambar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>

                    <div wire:loading>
                        <span class="text-xs text-amber-600 animate-pulse ml-2 font-medium">⏳ Mengunggah berkas gambar...</span>
                    </div>

                </div>

                <div
                    x-ref="editorElement"
                    class="prose prose-zinc dark:prose-invert max-w-none p-6 flex-1 overflow-y-auto dark:text-zinc-100 focus:outline-none" >
                </div>

                {{-- Input File tunggal yang bersih (Duplikat dibuang & Reset Value Aman) --}}
                <input
                    type="file"
                    x-ref="fileInput"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="handleMultipleImageUpload($event.target.files); $event.target.value = ''"
                />

            </div>

            <div class="flex justify-end gap-4">
                <button
                    type="button"
                    @click="forceDeleteDraft()"
                    class="px-4 py-2 bg-zinc-100 hover:bg-red-50 text-zinc-600 hover:text-red-600 border border-zinc-300 hover:border-red-200 rounded-lg transition flex items-center gap-2"
                    title="Buang semua ketikan dan mulai dari awal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus Draf
                </button>
                <button
                    type="button"
                    wire:click="saveArticle"
                    class="px-6 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm shadow cursor-pointer">
                    {{ __('Create Article') }}
                </button>
            </div>
        </form>

    </div>
</div>

{{-- OLD --}}
{{-- <div class="max-w-5xl mx-auto p-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <div class="h-[calc(100vh-120px)] flex flex-col justify-between">

        <form wire:submit.prevent="save" class="flex-1 flex flex-col min-h-0 space-y-4">
            {{-- judul dan tags --
            <div class="">
                <input
                    type="text"
                    placeholder="Judul Artikel..."
                    class="w-full p-1.5 text-3xl font-bold border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 pb-2 mb-4 dark:bg-transparent"
                />
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <input
                        type="date"
                        wire:model="published_at"
                        placeholder="Kategori (pisahkan dengan koma)"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:mb-0 mb-4 md:w-1/2"
                    /></input>
                    <input
                        type="text"
                        placeholder="Tags (pisahkan dengan koma)"
                        class="w-full p-1.5 text-sm border-0 border-b border-zinc-300 dark:border-zinc-700 focus:ring-0 focus:border-forest/70 dark:bg-transparent md:w-1/2"
                    />
                </div>
            </div>

            <div
                x-data="setupEditor('content', $wire)"
                :class="{ 'show-marks': showMarks }"
                class="flex-1 flex flex-col min-h-0 border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative"
                wire:ignore>

                <div
                    id="imageBubbleMenu"
                    x-ref="imageBubbleMenu"
                    x-cloak
                    class="hidden items-center gap-1 bg-emerald-50 dark:bg-zinc-800 border border-emerald-200 dark:border-zinc-700 p-1 rounded-lg shadow-md" >
                    <button
                        type="button"
                        @click="setImageAlignment('left')"
                        :class="imageHasStyle('left') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Kiri"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"/></svg>
                    </button>

                    <button
                        type="button"
                        @click="setImageAlignment('center')"
                        :class="imageHasStyle('center') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Tengah"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M6 18h12"/></svg>
                    </button>

                    <button
                        type="button"
                        @click="setImageAlignment('right')"
                        :class="imageHasStyle('right') ? 'bg-emerald-800 text-white dark:bg-emerald-600' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="p-1.5 rounded transition cursor-pointer" title="Rata Kanan"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"/></svg>
                    </button>

                    <div class="h-4 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button
                        type="button"
                        @click="setImageWidth('25')"
                        :class="imageHasStyle('25%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        25%
                    </button>

                    <button
                        type="button"
                        @click="setImageWidth('50')"
                        :class="imageHasStyle('50%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        50%
                    </button>

                    <button
                        type="button"
                        @click="setImageWidth('100')"
                        :class="imageHasStyle('100%') ? 'bg-emerald-800 text-white dark:bg-emerald-600 font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-emerald-100'"
                        class="px-2 py-1 text-xs rounded transition cursor-pointer"
                    >
                        100%
                    </button>
                </div>

                {{-- MENU BUBBLE --
                <div
                    x-ref="bubbleMenuElement"
                    class="absolute invisible opacity-0 bg-zinc-900 dark:bg-zinc-800 text-white p-1.5 rounded-lg shadow-xl border border-zinc-700/50 z-50 flex items-center gap-1" >
                    <button type="button" @click="runCommand('toggleBold')" class="px-2 py-1 rounded font-bold text-xs hover:bg-zinc-700 transition">B</button>
                    <button type="button" @click="runCommand('toggleItalic')" class="px-2 py-1 rounded italic text-xs hover:bg-zinc-700 transition">I</button>
                </div>

                <div class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                    <button type="button" @click="runCommand('toggleBold')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bold', {}, updatedAt) }" class="px-3 py-1.5 rounded font-bold text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">B</button>
                    <button type="button" @click="runCommand('toggleItalic')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('italic', {}, updatedAt) }" class="px-3 py-1.5 rounded italic text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">I</button>
                    <button type="button" @click="runCommand('toggleStrike')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('strike', {}, updatedAt) }" class="px-3 py-1.5 rounded line-through text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">S</button>

                    <div class="flex items-center gap-1 border-l border-zinc-300 pl-2 ml-1">
                        <button
                            type="button"
                            @click="showMarks = !showMarks"
                            :class="showMarks ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700' : 'text-zinc-600'"
                            class="p-1.5 rounded hover:bg-zinc-100 transition cursor-pointer flex items-center"
                            title="Tampilkan/Sembunyikan Tanda Paragraf (MS Word Style)"
                        >
                            <span class="font-mono text-sm font-bold">¶</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-l border-zinc-300 dark:border-zinc-700 pl-2 ml-1">
                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'left')"
                            :class="isActive({ textAlign: 'left' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kiri"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'center')"
                            :class="isActive({ textAlign: 'center' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Tengah"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'right')"
                            :class="isActive({ textAlign: 'right' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kanan"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"></path></svg>
                        </button>

                        <button
                            type="button"
                            @click="runCommand('setTextAlign', 'justify')"
                            :class="isActive({ textAlign: 'justify' }) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                            title="Rata Kiri Kanan"
                        >
                            <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('toggleHeading', 1)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 1 }, updatedAt) }" class="px-2 py-1.5 rounded font-black text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H1</button>
                    <button type="button" @click="runCommand('toggleHeading', 2)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 2 }, updatedAt) }" class="px-2 py-1.5 rounded font-extrabold text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H2</button>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('toggleBulletList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bulletList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">• List</button>
                    <button type="button" @click="runCommand('toggleOrderedList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('orderedList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">1. List</button>
                    <button type="button" @click="runCommand('toggleTaskList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('taskList', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">☑ Task</button>
                    <button type="button" @click="runCommand('toggleBlockquote')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('blockquote', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs italic cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">“ Quote</button>
                    <button type="button" @click="runCommand('toggleCodeBlock')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('codeBlock', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-mono cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">&lt;/&gt; Code</button>

                    <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                    <button type="button" @click="runCommand('setLink')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('link', {}, updatedAt) }" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">🔗 Link</button>
                    <button type="button" @click="runCommand('insertTable')" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">📊 +Table</button>

                    <template x-if="isActive('table', {}, updatedAt)">
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded ml-2">
                            <button type="button" @click="runCommand('addColumnAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Col</button>
                            <button type="button" @click="runCommand('addRowAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Row</button>
                            <button type="button" @click="runCommand('deleteTable')" class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                        </div>
                    </template>

                    {{-- <button type="button" @click="triggerFileSelect()" class="px-2.5 py-1.5 rounded text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 cursor-pointer flex items-center gap-1">
                        🖼️ + Image
                    </button> --

                    <div class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                        <div wire:loading>
                            <span class="text-xs text-amber-600 animate-pulse ml-2 font-medium">⏳ Mengunggah berkas gambar...</span>
                        </div>
                    </div>

                </div>

                <div
                    x-ref="editorElement"
                    class="prose prose-zinc dark:prose-invert max-w-none p-6 flex-1 overflow-y-auto dark:text-zinc-100 focus:outline-none" >
                </div>

                <input
                    type="file"
                    x-ref="fileInput"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="handleMultipleImageUpload($event.target.files); $event.target.value = ''"
                />
                <input
                    type="file"
                    x-ref="fileInput"
                    class="hidden"
                    multiple
                    accept="image/*"
                    @change="handleMultipleImageUpload($event.target.files)"
                >

                <button
                    type="button"
                    @click="triggerFileSelect()"
                    class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition text-zinc-600 dark:text-zinc-400"
                    title="Unggah Gambar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>

            </div>

            <div class="flex justify-end gap-4">
                <button
                    type="button"
                    @click="forceDeleteDraft()"
                    class="px-4 py-2 bg-zinc-100 hover:bg-red-50 text-zinc-600 hover:text-red-600 border border-zinc-300 hover:border-red-200 rounded-lg transition flex items-center gap-2"
                    title="Buang semua ketikan dan mulai dari awal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus Draf
                </button>
                <button
                    {{-- type="submit"  --
                    type="button"
                    wire:click="saveArticle"
                    class="px-6 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm shadow cursor-pointer">
                    {{ __('Create Article') }}
                </button>
            </div>
        </form>

    </div>
</div> --}}

<style>

    .ProseMirror img {
        display: block !important;
        max-width: 100% !important;
        height: auto !important;
        min-width: 50px !important; /* Mencegah gambar menyusut jadi 0px */
        min-height: 50px !important;
    }

    /* Jika gambar dalam status terseleksi oleh Prosemirror */
    .ProseMirror-selectednode {
        outline: 3px solid #10b981 !important; /* Memberi border emerald saat gambar diklik */
    }

    /* Style CSS bawaan kamu tetap dipertahankan di sini semisal placeholder & task list... */
    .tiptap p.is-editor-empty:first-child::before { color: #a1a1aa; content: attr(data-placeholder); float: left; height: 0; pointer-events: none; }
    .tiptap ul[data-type="taskList"] { list-style: none !important; padding-left: 0.5rem !important; margin-top: 1rem !important; margin-bottom: 1rem !important; }
    .tiptap ul[data-type="taskList"] li { margin-top: 0.5rem !important; margin-bottom: 0.5rem !important; padding-left: 0 !important; }
    .tiptap ul[data-type="taskList"] li::before { display: none !important; }
    .tiptap ul[data-type="taskList"] input[type="checkbox"] { -webkit-appearance: none; appearance: none; background-color: transparent; margin: 0 !important; cursor: pointer; width: 1.2rem !important; height: 1.2rem !important; border: 2px solid #d1d5db; border-radius: 0.375rem; display: grid; place-content: center; transition: all 0.15s ease-in-out; }
    .dark .tiptap ul[data-type="taskList"] input[type="checkbox"] { border-color: #52525b; }
    .tiptap ul[data-type="taskList"] input[type="checkbox"]:checked { background-color: #15803d; border-color: #15803d; }
    .tiptap ul[data-type="taskList"] input[type="checkbox"]::before { content: ""; width: 0.55rem; height: 0.35rem; transform: scale(0) rotate(-45deg); transform-origin: center; transition: 100ms transform ease-in-out; box-shadow: inset 0.15rem -0.15rem 0px 0px white; }
    .tiptap ul[data-type="taskList"] input[type="checkbox"]:checked::before { transform: scale(1) rotate(-45deg) translate(0.05rem, -0.05rem); }
    .tiptap ul[data-type="taskList"] li > div { flex: 1 1 auto; margin: 0 !important; }
    .tiptap img {
        display: block !important;
        max-width: 100% !important;
        height: auto !important;
        min-height: 50px; /* Memastikan space gambar tidak nol piksel saat loading */
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color 0.2s;
    }

    /* Beri highlight tipis jika gambar sedang diklik/aktif */
    .tiptap img.ProseMirror-selectednode {
        outline: 3px solid #10b981; /* Warna hijau emerald sesuai tema */
        border-radius: 0.5rem;
    }
    /* Memaksa elemen internal Tiptap (ProseMirror) untuk mengisi penuh tinggi kontainer */
    .tiptap .ProseMirror {
        min-height: 100%;
        outline: none; /* Menghilangkan border biru bawaan browser saat fokus */
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
        height: 100%; /* Tambahkan ini agar layout flexbox mengisi penuh area */
        outline: none;
    }
    /* 1. KONDISI UMUM: Paragraf yang sudah ada isinya */
    .show-marks .ProseMirror p,
    .show-marks .ProseMirror h1,
    .show-marks .ProseMirror h2,
    .show-marks .ProseMirror h3,
    .show-marks .ProseMirror li {
        position: relative !important;
        padding-right: 1rem !important;
    }

    .show-marks .ProseMirror p::after,
    .show-marks .ProseMirror h1::after,
    .show-marks .ProseMirror h2::after,
    .show-marks .ProseMirror h3::after,
    .show-marks .ProseMirror li::after {
        content: "¶" !important;
        position: absolute !important;
        color: #000000 !important; /* Abu-abu slate-300 */
        font-weight: normal !important;
        font-style: normal !important;
        user-select: none !important;
        pointer-events: none !important;
        opacity: 0.8 !important;
        margin-left: 0.25rem !important;
    }

    /* 2. KONDISI KHUSUS: Baris Kosong / Baru di-Enter (.is-empty) */
    /* Kita paksa baris kosong menggunakan flexbox agar kursor dan pilcrow sejajar secara horizontal */
    .show-marks .ProseMirror p.is-empty,
    .show-marks .ProseMirror h1.is-empty,
    .show-marks .ProseMirror h2.is-empty,
    .show-marks .ProseMirror h3.is-empty {
        display: flex !important;
        align-items: center !important;
        position: relative !important;
    }

    /* Atur posisi pilcrow tepat di titik awal kursor berkedip */
    .show-marks .ProseMirror p.is-empty::after,
    .show-marks .ProseMirror h1.is-empty::after,
    .show-marks .ProseMirror h2.is-empty::after,
    .show-marks .ProseMirror h3.is-empty::after {
        content: "¶" !important;
        position: static !important; /* Lepas dari absolute agar sejajar horizontal dengan kursor */
        display: inline-block !important;
        margin-left: 0.15rem !important; /* Beri jarak super tipis di kanan kursor */
        color: #000000 !important;
        /* color: #cbd5e1 !important; */
    }

    /* 3. SEMBUNYIKAN PLACEHOLDER SAAT PILCROW AKTIF */
    /* Tiptap biasanya menaruh teks placeholder di p.is-empty::before */
    .show-marks .ProseMirror p.is-empty::before {
        content: "" !important; /* Hancurkan teks placeholder secara paksa */
        display: none !important;
    }
</style>
