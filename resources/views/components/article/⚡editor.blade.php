<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $user_id;

    public $article_id;
    public $category_id;
    public $title;
    public $slug;
    public string $content = '';
    public $featured_image;
    public $status;
    public $published_at;

    public $photo;

    // Properti baru untuk mendukung fitur seleksi gambar
    public array $extracted_images = []; // Menyimpan daftar semua URL gambar dari editor
    public ?string $selected_image_url = null; // Menyimpan URL gambar yang dipilih penulis

    public function mount(){
        // Mengatur default value ke tanggal hari ini saat halaman pertama kali dimuat
        $this->published_at = now()->format('Y-m-d');
    }

    public function scanEditorImages()
    {
        $this->extracted_images = [];

        if (!empty($this->content)) {
            // Naikkan batas backtrack regex PHP secara dinamis khusus untuk method ini
            // agar PHP tidak menyerah saat membaca string Base64 yang sangat panjang
            ini_set('pcre.backtrack_limit', '5000000');

            // Ambil semua URL gambar dari tag <img>
            preg_match_all('/<img[^>]+src="([^">]+)"/', $this->content, $matches);
            if (!empty($matches[1])) {
                $this->extracted_images = $matches[1];
            }
        }

        // Tentukan default ke gambar pertama jika belum ada pilihan
        if (empty($this->selected_image_url) && empty($this->featured_image) && !empty($this->extracted_images) && empty($this->photo)) {
            $this->selected_image_url = $this->extracted_images[0];
            $this->featured_image = basename($this->extracted_images[0]);
        }
    }

    /**
     * Fungsi ketika penulis mengklik/memilih salah satu gambar dari editor
     */
    public function selectImageFromEditor($url)
    {
        $this->photo = null; // Batalkan file upload kustom jika ada
        $this->selected_image_url = $url;
        $this->featured_image = basename($url); // Ambil nama filenya saja untuk database
    }

    /**
     * Lifecycle hook Livewire: Otomatis berjalan ketika penulis mengunggah file kustom lewat input file
     */
    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:15360',
        ]);

        // Reset pilihan gambar dari editor karena penulis beralih ke upload kustom
        $this->selected_image_url = null;
        // featured_image sementara diisi nama file aslinya untuk pratinjau local temporaryUrl()
        $this->featured_image = $this->photo->getClientOriginalName();
    }

    private function processAndTrimImages($htmlContent){
        if (empty($htmlContent)) return $htmlContent;

        // Gunakan DOMDocument untuk membaca HTML secara aman di sisi Backend
        $dom = new \DOMDocument();

        // Libatkan libxml_use_internal_errors agar tidak memicu warning jika ada tag HTML5 kustom
        libxml_use_internal_errors(true);
        // Muat string HTML dengan encoding UTF-8
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $images = $dom->getElementsByTagName('img');
        $hasChanges = false;

        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // 🚀 DETEKSI & POTONG (TRIM) HANYA GAMBAR BASE64
            if (Str::startsWith($src, 'data:image/')) {
                try {
                    // Memecah format data:image/png;base64,XXXXXX
                    $parts = explode(',', $src);
                    if (count($parts) < 2) continue;

                    $metadata = $parts[0]; // data:image/png;base64
                    $base64Data = $parts[1]; // data biner murni

                    // Ambil ekstensi file (png, jpeg, webp, dll)
                    $extension = 'png';
                    if (preg_match('/data:image\/(?<mime>.*?);/', $metadata, $groups)) {
                        $extension = $groups['mime'];
                    }

                    // Decode string base64 menjadi biner fisik
                    $decodedImage = base64_decode($base64Data);

                    // 🌟 DISELARASKAN: Gunakan folder 'articles' agar sinkron dengan sistem Cleaner Anda
                    $filename = 'article-' . Str::uuid() . '.' . $extension;
                    $storagePath = 'articles/' . $filename;

                    // Simpan file ke folder storage publik public/articles/...
                    Storage::disk('public')->put($storagePath, $decodedImage);

                    // Dapatkan URL publik gambar tersebut
                    $fileUrl = asset('storage/' . $storagePath);

                    // 🌟 SULAP: Ganti src Base64 raksasa dengan URL gambar server yang ringan!
                    $img->setAttribute('src', $fileUrl);

                    // Tambahkan class kustom untuk styling frontend Anda
                    $img->setAttribute('class', 'rounded-lg max-w-full my-2 inline-block tiptap-trimmed-image');

                    $hasChanges = true;
                } catch (\Exception $e) {
                    // Jika gagal di-decode, hapus tag gambarnya agar database tidak bengkak
                    $img->parentNode->removeChild($img);
                    $hasChanges = true;
                }
            }
        }

        // Jika ada gambar yang berhasil diproses, kembalikan HTML yang sudah bersih, jika tidak kembalikan apa adanya
        return $hasChanges ? $dom->saveHTML() : $htmlContent;
    }

    public function save()
    {
        $this->validate([
            'content' => 'required|min:10',
        ]);
        $this->content = $this->processAndTrimImages($this->content);
        // 1. Jika ini proses UPDATE (Artikel sudah ada di database sebelumnya)
        if (isset($this->article_id)) {
            // Ambil isi konten lama dari database sebelum di-overwrite
            $oldContent = DB::table('articles')->where('id', $this->article_id)->value('content');

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
        // === TAHAP 3: EKSEKUSI PENYIMPANAN DATA UTAMA VIA ELOQUENT ===
        // ✅ Otomatis mendeteksi CREATE jika article_id null, dan UPDATE jika article_id memiliki nilai
        $article = Post::updateOrCreate(
            ['id' => $this->article_id ?? null],
            [
                'user_id'           => auth()->id() ?? $this->user_id, // Fallback ke properti jika auth kosong
                'category_id'       => $this->category_id,
                'title'             => $this->title,
                'slug'              => $this->slug,
                'content'           => $this->content, // HTML bersih, ringan, bebas base64
                'featured_image'    => $this->featured_image,
                'status'            => $this->status,
                'published_at'      => $this->published_at,
            ]
        );

        // Jika ini adalah artikel baru yang baru dibuat, ikat ID-nya ke properti komponen
        if (!isset($this->article_id)) {
            $this->article_id = $article->id;
        }

        session()->flash('message', 'Artikel berhasil disimpan!');
    }
    public function uploadImage()
    {
        // 1. Validasi standar untuk mengamankan server
        $this->validate([
            'photo' => 'image|max:15360', // Batas aman 15MB
        ]);

        $tempPath = $this->photo->getRealPath();
        $extension = strtolower($this->photo->getClientOriginalExtension());
        $filename = 'article-' . uniqid() . '.webp';
        $savePath = storage_path('app/public/articles/' . $filename);

        // 💡 STRATEGI HIBRIDA: Cek apakah ekstensi Imagick benar-benar aktif di server
        if ($extension === 'gif' && class_exists('\Imagick')) {
            try {
                // Jalur ini hanya akan dieksekusi jika Imagick terpasang sempurna (seperti di Hostinger nanti)
                \Intervention\Image\Laravel\Facades\Image::withDriver(new \Intervention\Image\Drivers\Imagick\Driver())
                    ->read($tempPath)
                    ->scale(width: 1000) // Pangkas resolusi agar hemat storage
                    ->toWebp(70)        // Kompres menjadi Animated WebP
                    ->save($savePath);

                return asset('storage/articles/' . $filename);
            } catch (\Exception $e) {
                // Jika terjadi kegagalan tak terduga, langsung lompat ke jalur aman (fallback)
                Log::warning('Gagal kompresi GIF di backend, menggunakan file asli: ' . $e->getMessage());
            }
        }

        // 💡 JALUR AMAN (FALLBACK):
        // Digunakan di laptop lokal (karena Imagick Herd error) DAN untuk file JPG/PNG biasa.
        // File disimpan murni dan orisinal tanpa memicu error 500!
        $path = $this->photo->store('articles', 'public');
        return asset('storage/' . $path);
    }


};
?>

<div class="max-w-5xl mx-auto p-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <div class="h-[calc(100vh-120px)] flex flex-col justify-between">

        <div x-data="{ show: false, message: '' }"
            x-on:saved.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
            class="fixed bottom-5 right-5 z-50">

            @if (session()->has('message'))
                <div x-data="{ init() { setTimeout(() => $el.remove(), 3000) } }"
                    class="bg-slate-900 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('message') }}</span>
                </div>
            @endif
        </div>

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

            {{-- PESAN GALAT --}}
            @if (session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    class="p-4 rounded-lg bg-red-50 dark:bg-zinc-800 border border-red-200 dark:border-red-900/50 flex items-start gap-3 select-none">
                    <div class="p-1 bg-white dark:bg-zinc-700 rounded-md text-red-600 shadow-sm  shrink-0">
                        <x-dynamic-component :component="'lucide-alert-triangle'" class="h-5 w-5" stroke-width="2.5" />
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Gagal Memproses Gambar</h4>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">{{ session('error') }}</p>
                    </div>
                    <button type="button" @click="show = false"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition cursor-pointer text-xs font-bold pl-2">
                        ✕
                    </button>
                </div>
            @endif

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
                    <button type="button" @click="insertMediaPlaceholder()"
                        :class="checkButtonActive('mediaPlaceholder', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent"
                        title="Sisipkan Kotak Penampung Media">
                        <x-dynamic-component :component="'lucide-image-plus'" class="h-4 w-4" stroke-width="2" />
                    </button>

                    {{-- PREVIEW THUMBNAIL BUTTOn --}}
                    <button type="button"
                        wire:click="scanEditorImages"
                        @click="$dispatch('buka-featured-modal')"
                        {{-- :class="checkButtonActive('mediaPlaceholder', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'" --}}
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent"
                        title="Sisipkan Kotak Penampung Media">
                        <x-dynamic-component :component="'lucide-view'" class="h-4 w-4" stroke-width="2" />
                    </button>

                    {{-- <button type="button" @click="triggerFileSelect()"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' :
                            'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent"
                        title="Sisipkan Gambar">
                        <x-dynamic-component :component="'lucide-image-plus'" class="h-4 w-4" stroke-width="2" />
                    </button> --}}

                </div>

                {{-- EDITOR's AREA --}}
                <div class="flex-1 flex flex-col min-h-0 relative bg-white dark:bg-zinc-900">

                    {{-- 🌌 ZONA DROP OVERLAY GLOBAL (SUNTIKAN PASIF SAH) --}}

                    <div x-data="{ isLocalDrag: false }"
                        {{-- 💡 KUNCI MATI: Jika di dalam editor sedang AKTIF (.isActive) node mediaPlaceholder, overlay besar DIPAKSA tidak bereaksi (null / false) --}}
                        @dragenter.window.prevent="isUploading || isLinkOpen || (window.tiptapEditor && window.tiptapEditor.isActive('mediaPlaceholder')) ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files'))"
                        @dragover.window.prevent="isUploading || isLinkOpen || (window.tiptapEditor && window.tiptapEditor.isActive('mediaPlaceholder')) ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files'))"
                        @dragleave.window.prevent="event.clientX === 0 && event.clientY === 0 ? isLocalDrag = false : null"
                        @drop.window.prevent="isLocalDrag = false; handleMultipleImageUpload($event.dataTransfer.files);"
                        x-show="isLocalDrag"
                        x-transition
                        class="absolute inset-0 bg-sage-soft/80 dark:bg-zinc-800/90 z-50 p-4 flex items-center justify-center pointer-events-none"
                        style="display: none;">
                        <div class="border-2 border-dashed border-forest dark:border-amber-500 rounded-xl w-full h-full flex flex-col items-center justify-center gap-3 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xs shadow-inner">
                            <div class="p-4 bg-white dark:bg-zinc-800 rounded-full shadow-md text-forest dark:text-amber-500 animate-bounce">
                                <x-dynamic-component :component="'lucide-image-plus'" class="h-8 w-8" stroke-width="2" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-sm font-bold text-forest dark:text-zinc-100 tracking-wide">Lepaskan Gambar di Sini</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Berkas otomatis diunggah ke server Yayasan SBH</p>
                            </div>
                        </div>
                    </div>

                    {{-- <div x-data="{ isLocalDrag: false }"
                        {{-- 💡 PERBAIKAN: Overlay besar HANYA boleh muncul jika kursor tidak sedang berada di atas/mengincar area placeholder kecil --
                        @dragenter.window.prevent="isUploading || isLinkOpen ? null : ($event.target.closest('.media-placeholder-zone') ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files')))"
                        @dragover.window.prevent="isUploading || isLinkOpen ? null : ($event.target.closest('.media-placeholder-zone') ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files')))"
                        @dragleave.window.prevent="event.clientX === 0 && event.clientY === 0 ? isLocalDrag = false : null"
                        @drop.window.prevent="isLocalDrag = false; handleMultipleImageUpload($event.dataTransfer.files);"
                        x-show="isLocalDrag"
                        x-transition
                        class="absolute inset-0 bg-sage-soft/80 dark:bg-zinc-800/90 z-50 p-4 flex items-center justify-center pointer-events-none"
                        style="display: none;">
                        <div class="border-2 border-dashed border-forest dark:border-amber-500 rounded-xl w-full h-full flex flex-col items-center justify-center gap-3 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xs shadow-inner">
                            <div class="p-4 bg-white dark:bg-zinc-800 rounded-full shadow-md text-forest dark:text-amber-500 animate-bounce">
                                <x-dynamic-component :component="'lucide-image-plus'" class="h-8 w-8" stroke-width="2" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-sm font-bold text-forest dark:text-zinc-100 tracking-wide">Lepaskan Gambar di Sini</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Berkas otomatis diunggah ke server Yayasan SBH</p>
                            </div>
                        </div>
                    </div> --}}

                    {{-- <div x-data="{ isLocalDrag: false }"
                        @dragenter.window.prevent="isLocalDrag = true"
                        @dragover.window.prevent="isLocalDrag = true"
                        @dragleave.window.prevent="event.clientX === 0 && event.clientY === 0 ? isLocalDrag = false : null"
                        @drop.window="isLocalDrag = false"
                        x-show="isLocalDrag"
                        x-transition
                        class="absolute inset-0 bg-sage-soft/80 dark:bg-zinc-800/90 z-50 p-4 flex items-center justify-center pointer-events-none"
                        style="display: none;">
                        <div class="border-2 border-dashed border-forest dark:border-amber-500 rounded-xl w-full h-full flex flex-col items-center justify-center gap-3 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xs shadow-inner">
                            <div class="p-4 bg-white dark:bg-zinc-800 rounded-full shadow-md text-forest dark:text-amber-500 animate-bounce">
                                <x-dynamic-component :component="'lucide-image-plus'" class="h-8 w-8" stroke-width="2" />
                            </div>
                            <div class="text-center">
                                <h3 class="text-sm font-bold text-forest dark:text-zinc-100 tracking-wide">Lepaskan Gambar di Sini</h3>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Berkas otomatis diunggah ke server Yayasan SBH</p>
                            </div>
                        </div>
                    </div> --}}

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

            <!-- AREA MODAL -->
            <div x-data="{ isOpen: false }"
                @buka-featured-modal.window="isOpen = true"
                class="relative" >
                <div
                    x-show="isOpen"
                    class="hidden md:flex fixed inset-0 z-50 items-center justify-center overflow-x-hidden overflow-y-auto"
                    style="display: none;"
                >
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="isOpen = false"></div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border w-full max-w-3xl max-h-[85vh] flex flex-col z-10 overflow-hidden">
                        <div class="p-4 border-b flex items-center justify-between bg-white dark:bg-gray-900">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Pilih Gambar Sampul</h3>
                            <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto grid grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-950 flex-1 min-h-[250px]">
                            <div class="relative aspect-video rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 flex flex-col items-center justify-center p-4 text-center overflow-hidden">
                                <input type="file" wire:model="photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" />
                                <span class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300">Upload File Baru</span>
                                <div wire:loading wire:target="photo" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center">
                                    <span class="text-xs">Memuat...</span>
                                </div>
                            </div>

                            @foreach($extracted_images as $imgUrl)
                                <div
                                    wire:click="selectImageFromEditor('{{ $imgUrl }}')"
                                    class="relative aspect-video rounded-xl overflow-hidden cursor-pointer border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600 ring-4 ring-blue-500/20' : 'border-transparent' }}"
                                >
                                    <img src="{{ $imgUrl }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>

                        <div class="p-4 border-t flex justify-end bg-gray-50 dark:bg-gray-900">
                            <button type="button" @click="isOpen = false" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-xs font-semibold shadow-sm">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>


                <div
                    x-show="isOpen"
                    class="flex md:hidden fixed inset-0 z-50 items-end justify-center"
                    style="display: none;"
                >
                    <div class="fixed inset-0 bg-black/60" @click="isOpen = false"></div>
                    <div class="bg-white dark:bg-gray-900 w-full rounded-t-2xl max-h-[75vh] flex flex-col z-10 overflow-hidden shadow-2xl">
                        <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto my-3 dark:bg-gray-700" @click="isOpen = false"></div>

                        <div class="p-4 overflow-y-auto space-y-4 bg-gray-50 dark:bg-gray-950 flex-1">
                            <div class="relative w-full py-3 px-4 rounded-xl border-2 border-dashed border-gray-300 bg-white flex items-center justify-center space-x-2">
                                <input type="file" wire:model="photo" class="absolute inset-0 w-full h-full opacity-0 z-10" accept="image/*" />
                                <span class="text-xs font-semibold text-gray-700">Ambil Foto / Upload Gambar</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                @foreach($extracted_images as $imgUrl)
                                    <div wire:click="selectImageFromEditor('{{ $imgUrl }}')" class="relative aspect-video rounded-xl overflow-hidden border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600' : 'border-transparent' }}">
                                        <img src="{{ $imgUrl }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="p-4 bg-white dark:bg-gray-900 border-t">
                            <button type="button" @click="isOpen = false" class="w-full bg-blue-600 text-white py-3 rounded-xl text-xs font-bold text-center">
                                Terapkan Gambar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>
