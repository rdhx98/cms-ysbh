<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;

use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithFileUploads;

    public int $user_id;

    public $article_id;
    public $category_id;
    public $tags = [];
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
        $this->user_id = auth()->id() ?? $this->user_id;
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
        // $this->featured_image = $this->photo->getClientOriginalName();
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

    protected function rules()
    {
        return [
            'category_id' => 'required|numeric',
            'title' => [
                'required',
                Rule::unique('posts')->ignore($this->article_id),
            ],
            'slug' => [
                'required',
                Rule::unique('posts')->ignore($this->article_id),
            ],
            'content' => 'required|min:5',
            'featured_image' => 'required',
        ];
    }

    public function makeSlug(string $title) {
        // 1. Ubah semua huruf menjadi huruf kecil
        $slug = strtolower($title);

        // 2. Ganti karakter non-alfanumerik (termasuk spasi) dengan tanda hubung (-)
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);

        // 3. Hapus tanda hubung di awal dan akhir string (jika ada)
        $slug = trim($slug, '-');

        return $slug;
    }

    public function save()
    {
        $this->content = $this->processAndTrimImages($this->content);

        // 3. AUTO SCAN: Paksa sistem mencari gambar di dalam editor secara otomatis (di belakang layar)
        $this->scanEditorImages();

        // 4. FALLBACK DEFAULT URL: Jika ternyata penulis tidak upload foto kustom DAN editor murni teks saja
        if (empty($this->featured_image) && empty($this->photo)) {
            // Ganti 'default.jpg' dengan nama file gambar default Anda yang ada di storage/public
            $this->featured_image = 'default.webp';
            $this->selected_image_url = asset('storage/articles/default.webp');
        }

        // $this->validate();

        // $this->content = $this->processAndTrimImages($this->content);
        // 1. Jika ini proses UPDATE (Artikel sudah ada di database sebelumnya)
        if (isset($this->article_id)) {
            // Ambil isi konten lama dari database sebelum di-overwrite
            $oldContent = DB::table('posts')->where('id', $this->article_id)->value('content');

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

        $slugContainer = $this->title;
        $slug = $this->makeSlug($slugContainer);
        $this->slug = $slug;
        $this->status = 'pending';

        $dumpTruck = [
            'id user' => $this->user_id,
            'id kategori' => $this->category_id,
            'tags' => $this->tags,
            'title' => $this->title,
            'slug' => $this->slug,
            'url thumbnail' => $this->featured_image,
            'status' => $this->status,
            'tanggal dibuat' => $this->published_at,
        ];
        dd($dumpTruck);


        $article = Post::updateOrCreate(
            ['id' => $this->article_id ?? null],
            [
                // 'user_id'           => auth()->id() ?? $this->user_id, // Fallback ke properti jika auth kosong
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

<div class="max-w-5xl mx-auto p-6" >
    <x-slot:title>{{ __('Write Article') }}</x-slot:title>

    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css"> --}}
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
            {{-- ========================================================================= --}}
            {{-- 📝 JUDUL & PENGATURAN DOKUMEN (MINIMALIST UI DENGAN UNIVERSAL MODAL)      --}}
            {{-- ========================================================================= --}}
            <div x-data="{
                    isMetaOpen: false,
                    title: @entangle('title'),
                    categoryId: @entangle('category_id'),
                    selectedTags: @entangle('tags'),
                    publishedAt: @entangle('published_at'),
                }"
                class="shrink-0 relative mb-2 md:mb-4 z-20">

                {{-- ================== TAMPILAN UTAMA (HANYA JUDUL & TOMBOL) ================== --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between border-0 border-b border-zinc-200 dark:border-zinc-800 pb-2 md:pb-4 gap-2">

                    {{-- Input Judul --}}
                    <input type="text" x-model="title" placeholder="Judul Artikel..."
                        class="w-full p-1 text-2xl md:text-3xl font-bold border-0 bg-transparent focus:ring-0 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-600" />

                    {{-- 🌟 TOMBOL BUKA MODAL THUMBNAIL (Dipindah ke sini) --}}
                    <div class="flex items-center gap-2 shrink-0 md:ml-4">
                        <button type="button" wire:click="scanEditorImages" @click="$dispatch('buka-featured-modal')"
                            class="shrink-0 p-2 px-3 text-xs md:text-sm font-medium text-zinc-600 hover:text-forest dark:text-zinc-400 bg-zinc-100 hover:bg-sage-soft dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-colors flex items-center justify-center gap-2 border border-zinc-200 dark:border-zinc-700 cursor-pointer"
                            title="Pilih Gambar Sampul">
                            <x-dynamic-component :component="'lucide-image'" class="h-4 w-4 md:h-5 md:w-5" stroke-width="2" />
                            <span class="hidden md:inline">Sampul Artikel</span>
                            <span class="md:hidden">Sampul</span>
                        </button>

                        {{-- Tombol Buka Pengaturan Meta --}}
                        <button type="button" @click="isMetaOpen = true"
                            class="shrink-0 p-2 px-3 text-xs md:text-sm font-medium text-zinc-600 hover:text-forest dark:text-zinc-400 bg-zinc-100 hover:bg-sage-soft dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-colors flex items-center justify-center gap-2 border border-zinc-200 dark:border-zinc-700 cursor-pointer"
                            title="Pengaturan Artikel">
                            <span>⚙️</span>
                            <span class="hidden md:inline">Pengaturan Dokumen</span>
                            <span class="md:hidden">Pengaturan</span>
                        </button>

                    </div>
                    {{-- Tombol Buka Pengaturan --}}
                    {{-- <button @click="isMetaOpen = true" type="button"
                        class="shrink-0 cursor-pointer md:ml-4 p-2 px-3 text-xs md:text-sm font-medium text-zinc-600 hover:text-forest dark:text-zinc-400 bg-zinc-100 hover:bg-sage-soft dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-colors flex items-center justify-center gap-2 border border-zinc-200 dark:border-zinc-700"
                        title="Pengaturan Artikel">
                        <span>⚙️</span>
                        <span>Pengaturan Dokumen</span>
                    </button> --}}
                </div>


                {{-- ================== UNIVERSAL MODAL / BOTTOM SHEET ================== --}}
                {{-- Trik Tailwind: items-end untuk HP (Bottom Sheet), md:items-center untuk Desktop (Modal Tengah) --}}
                <template x-teleport="body">
                    <div x-show="isMetaOpen"
                        class="fixed inset-0 z-999 flex items-end justify-center md:items-center p-0 md:p-4"
                        style="display: none;">

                        {{-- Overlay Backdrop --}}
                        <div x-show="isMetaOpen"
                            x-transition:enter="transition-opacity ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isMetaOpen = false"></div>

                        {{-- Konten Modal --}}
                        {{-- Meluncur dari bawah di HP, Fade & Scale di Desktop --}}
                        <div x-show="isMetaOpen"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95"
                            x-transition:enter-end="translate-y-0 md:opacity-100 md:scale-100"
                            x-transition:leave="transition ease-in duration-200 transform"
                            x-transition:leave-start="translate-y-0 md:opacity-100 md:scale-100"
                            x-transition:leave-end="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95"
                            class="relative bg-white dark:bg-zinc-900 w-full md:w-[500px] rounded-t-2xl md:rounded-2xl h-[85vh] md:h-[90vh] flex flex-col overflow-hidden shadow-2xl z-10">

                            {{-- Handle Drag (Hanya terlihat di Mobile) --}}
                            <div class="md:hidden w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto my-3" @click="isMetaOpen = false"></div>

                            {{-- Header Modal (Desktop) --}}
                            <div class="hidden md:flex p-4 border-b border-zinc-100 dark:border-zinc-800 items-center justify-between">
                                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-wide">Pengaturan Meta Dokumen</h3>
                                <button type="button" @click="isMetaOpen = false" class="text-zinc-400 hover:text-red-500 transition cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            {{-- Konten Input Modal --}}
                            <div class="p-5 md:p-6 overflow-y-auto space-y-5 bg-zinc-50 dark:bg-zinc-950 flex-1">
                                <div class="md:hidden">
                                    <h4 class="text-xs font-bold text-zinc-400 dark:text-zinc-500 mb-2 tracking-wide uppercase">Detail Dokumen</h4>
                                </div>

                                {{-- Input Tanggal --}}
                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal Dibuat</label>
                                    <input type="date" x-model="publishedAt"
                                        class="w-full p-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-forest/20 focus:border-forest text-zinc-800 dark:text-zinc-200" />
                                </div>

                                {{-- Kategori --}}
                                <div class="space-y-1.5" wire:ignore
                                    x-data="{
                                        tom: null,
                                        init() {
                                            this.tom = new window.TomSelect(this.$refs.catInput, {
                                                create: false,
                                                placeholder: 'Pilih Kategori...'
                                            });
                                            this.tom.setValue(this.categoryId, true);
                                            this.tom.on('change', val => this.categoryId = val);
                                        }
                                    }">
                                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kategori</label>
                                    <select x-ref="catInput">
                                        <option value="">Pilih Kategori...</option>
                                        @foreach(\App\Models\Category::all() as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Tags --}}
                                <div class="space-y-1.5" wire:ignore
                                    x-data="{
                                        tom: null,
                                        init() {
                                            this.tom = new window.TomSelect(this.$refs.tagInput, {
                                                create: true,
                                                plugins: ['remove_button'],
                                                maxItems: null, // 🌟 KUNCI 1: Paksa mode multi-select (tak terbatas)
                                                placeholder: 'Ketik atau Cari Tags...'
                                            });

                                            // Set nilai awal saat halaman dimuat
                                            this.tom.setValue(this.selectedTags, true);

                                            // 🌟 KUNCI 2: Cegat data sebelum dikirim ke Livewire
                                            this.tom.on('change', val => {
                                                if (!val) {
                                                    // Jika kosong, kirim array kosong
                                                    this.selectedTags = [];
                                                } else if (typeof val === 'string') {
                                                    // Jika TomSelect mengembalikan string ber-koma (tag1,tag2), pecah jadi Array!
                                                    this.selectedTags = val.split(',');
                                                } else {
                                                    // Jika sudah berupa Array, pastikan ter-copy dengan aman
                                                    this.selectedTags = Array.isArray(val) ? val : [val];
                                                }
                                            });
                                        }
                                    }">
                                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tags</label>
                                    <select x-ref="tagInput" multiple>
                                        @foreach(\App\Models\Tag::all() as $tag)
                                            <option value="{{ $tag->name }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Tags --}}
                                {{-- <div class="space-y-1.5" wire:ignore
                                    x-data="{
                                        tom: null,
                                        init() {
                                            this.tom = new window.TomSelect(this.$refs.tagInput, {
                                                create: true,
                                                plugins: ['remove_button'],
                                                placeholder: 'Ketik atau Cari Tags...'
                                            });
                                            this.tom.setValue(this.selectedTags, true);
                                            this.tom.on('change', val => this.selectedTags = val);
                                        }
                                    }">
                                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tags</label>
                                    <select x-ref="tagInput" multiple>
                                        @foreach(\App\Models\Tag::all() as $tag)
                                            <option value="{{ $tag->name }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div> --}}
                            </div>

                            {{-- Tombol Tutup Modal --}}
                            <div class="p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <button type="button" @click="isMetaOpen = false"
                                    class="w-full bg-forest hover:bg-forest/90 text-white py-2.5 rounded-xl text-sm font-bold text-center transition-colors cursor-pointer shadow-sm">
                                    Terapkan & Selesai
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>


            {{-- INIT --}}
            <div x-data="setupEditor('content', $wire)" @buka-modal-link.window="isLinkOpen = true"

                :style="isUploading ? { cursor: 'wait !important' } : {}"
                {{-- :class="{ 'tiptap-locked': isUploading }"
                class="flex-1 flex flex-col-reverse md:flex-col min-h-0 border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative" --}}
                {{-- 🌟 KUNCI LAYAR PENUH: Jika isFullscreen true, elemen ini akan menutupi seluruh layar (fixed inset-0) dengan z-index 100 --}}
                :class="isFullscreen
                    ? 'fixed inset-0 z-100 bg-white dark:bg-zinc-950 flex flex-col-reverse md:flex-col'
                    : 'flex-1 flex flex-col-reverse md:flex-col min-h-0 border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative'"
                class="transition-all duration-300 ease-in-out"
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

                {{-- BENJAMIN BUTTONS V2 --}}
                <div
                    x-data="{ expanded: false }"
                    class="bg-zinc-50 dark:bg-zinc-800 border-t md:border-t-0 md:border-b border-zinc-200 dark:border-zinc-700 z-10 select-none shadow-[0_-4px_10px_rgba(0,0,0,0.02)] md:shadow-none transition-all">

                    <div class="flex items-start md:items-center w-full">
                        <div x-data="{ isMobile: window.matchMedia('(pointer: coarse)').matches }"
                                @resize.window.debounce.100ms="isMobile = window.matchMedia('(pointer: coarse)').matches"
                                :class="isMobile
                                    ? (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto' : 'flex-nowrap overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden')
                                    : (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto' : 'md:flex-wrap md:overflow-visible items-center')"
                                class="flex flex-1 gap-1.5 p-2 transition-all scroll-smooth">

                            {{-- BOLD | ITALIC | STRIKE | UNDERLINE --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <x-layouts::app.editor-toolbar-btn command="toggleBold" activeName="bold" title="Tebal (Ctrl+B)" icon="bold" />
                                <x-layouts::app.editor-toolbar-btn command="toggleItalic" activeName="italic" title="Miring (Ctrl+I)" icon="italic" />
                                <x-layouts::app.editor-toolbar-btn command="toggleStrike" activeName="strike" title="Coretan (Ctrl+⇑+X)" icon="strikethrough" />
                                <x-layouts::app.editor-toolbar-btn command="toggleUnderline" activeName="underline" title="Garis Bawah (Ctrl+U)" icon="underline" />
                            </div>

                            {{-- FONT FAMILY (Disembunyikan di Mobile agar bersih) --}}
                            <div class="hidden md:flex items-center gap-4 p-1 bg-gray-50 border-l border-gray-200 shrink-0 rounded">
                                <select id="font-family-select" :value="getCurrentFont()" @change="changeFontFamily($event.target.value)"
                                    class="block w-48 px-3 py-1 text-sm bg-white border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-1 focus:ring-forest transition-colors">
                                    <option value="default" style="font-family: 'Plus Jakarta Sans', sans-serif;">Plus Jakarta Sans (Default)</option>
                                    <option value="Arial" style="font-family: Arial, sans-serif;">Arial</option>
                                    <option value="Jetbrains Mono" style="font-family: 'JetBrains Mono', monospace;">JetBrains Mono</option>
                                    <option value="Open Sans" style="font-family: 'Open Sans', sans-serif;">Open Sans</option>
                                    <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto</option>
                                    <option value="Times New Roman" style="font-family: 'Times New Roman', serif;">Times New Roman</option>
                                </select>
                            </div>

                            {{-- PILCROW --}}
                            <div class="shrink-0 flex items-center">
                                <x-layouts::app.editor-toolbar-btn command="toggleHiddenMarks()" activeName="showMarks" activeParams="{}" activeType="alpine" title="Tampilkan Tanda Baca Terselubung" icon="pilcrow" />
                            </div>

                            {{-- INDENTATION --}}
                            <div class="flex items-center gap-1 md:border-l md:border-zinc-300 md:dark:border-zinc-700 md:pl-2 shrink-0">
                                <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="left" activeParams="{ textAlign: 'left' }" activeType="textAlign" title="Rata Kiri" icon="align-left" />
                                <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="center" activeParams="{ textAlign: 'center' }" activeType="textAlign" title="Rata Tengah" icon="align-center" />
                                <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="right" activeParams="{ textAlign: 'right' }" activeType="textAlign" title="Rata Kanan" icon="align-right" />
                                <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="justify" activeParams="{ textAlign: 'justify' }" activeType="textAlign" title="Rata Kiri Kanan" icon="align-justify" />
                                <x-layouts::app.editor-toolbar-btn command="toggleIndent" activeName="paragraph" activeParams="{ indent: true }" activeType="default" title="Menjorokkan Baris (Tab)" icon="list-indent-increase" />
                            </div>

                            <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                            {{-- HEADINGs --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="1" activeParams="{ level: 1 }" activeType="heading" title="Heading 1" icon="heading-1" />
                                <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="2" activeParams="{ level: 2 }" activeType="heading" title="Heading 2" icon="heading-2" />
                                <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="3" activeParams="{ level: 3 }" activeType="heading" title="Heading 3" icon="heading-3" />
                            </div>

                            <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                            {{-- LISTS --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <x-layouts::app.editor-toolbar-btn command="toggleBulletList" activeName="" activeParams="{}" activeType="heading" title="Bullet list" icon="list" />
                                <x-layouts::app.editor-toolbar-btn command="toggleTaskList" activeName="taskList" title="Daftar Tugas" icon="list-todo" />
                                <x-layouts::app.editor-toolbar-btn command="none" activeName="number" activeParams="{ listStyle: 'number' }" activeType="orderedList" title="Daftar Angka" icon="list-tree">
                                    <span class="text-[10px] font-bold ml-0.5">1.</span>
                                </x-layouts::app.editor-toolbar-btn>
                                <x-layouts::app.editor-toolbar-btn command="none" activeName="alpha" activeParams="{ listStyle: 'alpha' }" activeType="orderedList" title="Daftar Kapital" icon="list-tree">
                                    <span class="text-[10px] font-bold ml-0.5">A.</span>
                                </x-layouts::app.editor-toolbar-btn>

                            </div>

                            <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                            {{-- QUOTES --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <x-layouts::app.editor-toolbar-btn command="toggleBlockquote" activeName="blockquote" title="Kutipan" icon="quote" />
                                <x-layouts::app.editor-toolbar-btn command="toggleCodeBlock" activeName="codeBlock" title="Blok Kode" icon="code-xml" />
                            </div>

                            {{-- TABLES DO NOT REMOVED--}}
                                    {{--
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
                                    --}}
                            <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                            {{-- MEDIA & LINK --}}
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="openLinkModal(); $dispatch('buka-modal-link');" :disabled="isUploading"
                                    :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                </button>
                                <button type="button" @click="insertMediaPlaceholder()" :disabled="isUploading"
                                    :class="checkButtonActive('mediaPlaceholder', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-image-plus'" class="h-4 w-4" stroke-width="2" />
                                </button>
                                {{-- <button type="button" wire:click="scanEditorImages" :disabled="isUploading" @click="$dispatch('buka-featured-modal')"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-view'" class="h-4 w-4" stroke-width="2" />
                                </button> --}}
                                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>


                            </div>
                            {{-- NOTIFY  BUTTONs --}}
                            {{-- <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="notifyTheUser('putangina','warning');" :disabled="isUploading"
                                    :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                                </button>
                                <button type="button" @click="notifyTheUser('karena sesungguhnya kemerdekaan itu adalah hak segala bangsa','info');" :disabled="isUploading"
                                    :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                                </button>
                                <button type="button" @click="notifyTheUser('faggotron assemble','success');" :disabled="isUploading"
                                    :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                                </button>
                                <button type="button" @click="notifyTheUser('i luv musc','error');" :disabled="isUploading"
                                    :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                                    <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                                </button>
                            </div> --}}
                        </div>

                        {{-- ================= AKSI KANAN TOOLBAR ================= --}}
                        <div class="flex items-center gap-1.5 p-2 pl-3 border-l border-zinc-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-800 z-10 shadow-[-4px_0_10px_rgba(0,0,0,0.02)] md:shadow-none">

                            {{-- 🌟 TOMBOL LAYAR PENUH (Sekarang aman di dalam scope editor) --}}
                            <button type="button" @click="isFullscreen = !isFullscreen"
                                class="p-1.5 text-zinc-500 hover:text-forest dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors cursor-pointer"
                                title="Layar Penuh">
                                <x-dynamic-component x-show="!isFullscreen" :component="'lucide-maximize'" class="h-5 w-5 md:h-4 md:w-4" stroke-width="2" />
                                <x-dynamic-component x-show="isFullscreen" :component="'lucide-minimize'" class="h-5 w-5 md:h-4 md:w-4" stroke-width="2" style="display: none;" />
                            </button>

                            {{-- TOMBOL EXPAND (KHUSUS HP) --}}
                            <button type="button" @click="expanded = !expanded"
                                class="md:hidden p-1.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
                                <x-dynamic-component x-show="!expanded" :component="'lucide-chevron-up'" class="h-5 w-5" stroke-width="2.5" />
                                <x-dynamic-component x-show="expanded" :component="'lucide-chevron-down'" class="h-5 w-5" stroke-width="2.5" style="display: none;" />
                            </button>

                        </div>

                        {{-- TOMBOL EXPAND (MENU PANEL NAIK DARI BAWAH KHUSUS HP) --}}
                        {{-- <button type="button" @click="expanded = !expanded"
                            class="md:hidden m-2 p-1.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 shrink-0 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <x-dynamic-component x-show="!expanded" :component="'lucide-chevron-up'" class="h-5 w-5" stroke-width="2.5" />
                            <x-dynamic-component x-show="expanded" :component="'lucide-chevron-down'" class="h-5 w-5" stroke-width="2.5" style="display: none;" />
                        </button> --}}

                    </div>
                </div>


                {{-- EDITOR's AREA --}}
                <div class="flex-1 flex flex-col min-h-0 relative bg-white dark:bg-zinc-900">

                    {{-- 🌌 ZONA DROP OVERLAY GLOBAL (SUNTIKAN PASIF SAH) --}}

                    <div
                        {{-- x-data="{ isLocalDrag: false }" --}}
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

                    {{-- UPLOADING IMAGES INDICATOR wire:loading --}}
                    <div x-show="isUploading" x-transition class="absolute left-1/2 top-4 -translate-x-1/2 z-40" style="display: none;">

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

                    {{-- 🌟 WORD COUNTER MELAYANG (FLOATING) --}}
                    <div class="absolute bottom-2 right-3 md:bottom-4 md:right-4 pointer-events-none z-10">
                        <div class="bg-zinc-100/90 dark:bg-zinc-800/90 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 text-[10px] md:text-xs px-2.5 py-1 rounded-md shadow-sm font-medium tracking-wide">
                            <span x-text="`${wordCount} kata`"></span>
                        </div>
                    </div>

                </div>

                <input type="file" x-ref="fileInput" accept="image/*" multiple class="hidden"  @change="handleMultipleImageUpload($event.target.files); $event.target.value = ''" />

            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm shadow cursor-pointer">
                    {{ __('Simpan Artikel') }}
                </button>
            </div>

            {{-- THuMBNAIL SELECTOR MODAL--}}
            <div x-data="{ isOpen: false }"
                @buka-featured-modal.window="isOpen = true"
                class="relative">

                {{-- ========================================================================= --}}
                {{-- 💻 LAYOUT MODAL THUMBNAIL: DESKTOP (md:flex)                              --}}
                {{-- ========================================================================= --}}
                <div x-show="isOpen"
                    class="hidden md:flex fixed inset-0 z-999 items-center justify-center overflow-x-hidden overflow-y-auto"
                    style="display: none;">

                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="isOpen = false"></div>

                    {{-- THUMBNAIL MODAL AREA --}}
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 w-full max-w-3xl max-h-[85vh] flex flex-col z-10 overflow-hidden">
                        {{-- Header Modal --}}
                        <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-white dark:bg-gray-900">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Pilih Gambar Sampul</h3>
                            <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-red-500 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Area Konten Gambar --}}
                        <div class="p-6 overflow-y-auto grid grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-950 flex-1 min-h-62.5">
                            {{-- KOTAK PREVIEW / UNGGAH UTAMA DESKTOP --}}
                            <div class="relative aspect-video rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 flex flex-col items-center justify-center text-center overflow-hidden group">
                                <input type="file" wire:model="photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" />

                                @if ($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif ($selected_image_url)
                                    <img src="{{ $selected_image_url }}" class="w-full h-full object-cover opacity-90 group-hover:opacity-50 transition-opacity">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                        <x-dynamic-component :component="'lucide-upload-cloud'" class="h-6 w-6 text-zinc-700 dark:text-zinc-300 drop-shadow-sm" />
                                        <span class="block text-[11px] font-bold text-zinc-700 dark:text-zinc-300 mt-1">Ganti Berkas</span>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center pointer-events-none p-2">
                                        <x-dynamic-component :component="'lucide-camera'" class="h-5 w-5 text-zinc-400 mb-1" />
                                        <span class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300">Klik / Seret Foto</span>
                                    </div>
                                @endif

                                <div wire:loading wire:target="photo" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-20">
                                    <span class="text-xs font-bold text-blue-600 animate-pulse">Memproses...</span>
                                </div>
                            </div>

                            {{-- LOOP GAMBAR DARI EDITOR --}}
                            @foreach($extracted_images as $imgUrl)
                                <div wire:click="selectImageFromEditor('{{ $imgUrl }}')"
                                    class="relative aspect-video rounded-xl overflow-hidden cursor-pointer border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600 ring-4 ring-blue-500/20' : 'border-transparent hover:border-zinc-400' }} transition-all">
                                    <img src="{{ $imgUrl }}" class="w-full h-full object-cover">

                                    @if($selected_image_url === $imgUrl)
                                        <div class="absolute top-1 right-1 bg-blue-600 text-white rounded-full p-0.5 shadow-md animate-scale-in">
                                            <x-dynamic-component :component="'lucide-check'" class="h-3 w-3" stroke-width="3" />
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer Modal --}}
                        <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end bg-gray-50 dark:bg-gray-900">
                            <button type="button" @click="isOpen = false" class="bg-forest hover:bg-forest text-white px-5 py-2 rounded-xl text-xs font-semibold shadow-sm transition cursor-pointer">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>


                {{-- ========================================================================= --}}
                {{-- 📱 LAYOUT MODAL THUMBNAIL: MOBILE (md:hidden)                              --}}
                {{-- ========================================================================= --}}
                <div x-show="isOpen"
                    class="flex md:hidden fixed inset-0 z-999 items-end justify-center"
                    style="display: none;">

                    {{-- Animasi Overlay: Fade In / Fade Out --}}
                    <div x-show="isOpen"
                        x-transition:enter="transition-opacity ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isOpen = false"></div>

                    {{-- Animasi Laci: Meluncur dari bawah (translate-y-full) ke atas (translate-y-0) --}}
                    <div x-show="isOpen"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="translate-y-full"
                        x-transition:enter-end="translate-y-0"
                        x-transition:leave="transition ease-in duration-200 transform"
                        x-transition:leave-start="translate-y-0"
                        x-transition:leave-end="translate-y-full"
                        class="bg-white dark:bg-gray-900 w-full rounded-t-2xl max-h-[80vh] flex flex-col z-10 overflow-hidden shadow-2xl relative">

                        {{-- Handle Drag Modal Mobile --}}
                        <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto my-3" @click="isOpen = false"></div>
                        <div class="p-4 overflow-y-auto space-y-4 bg-gray-50 dark:bg-gray-950 flex-1">
                            {{-- KOTAK PREVIEW / UNGGAH UTAMA MOBILE --}}
                            <div class="relative w-full aspect-video rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-gray-900 flex flex-col items-center justify-center overflow-hidden transition-all">
                                <input type="file" wire:model="photo" class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer" accept="image/*" />

                                @if ($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif ($selected_image_url)
                                    <img src="{{ $selected_image_url }}" class="w-full h-full object-cover">
                                    <div class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] px-2 py-1 rounded backdrop-blur-xs pointer-events-none">
                                        Ketuk untuk mengganti
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center pointer-events-none">
                                        <x-dynamic-component :component="'lucide-camera'" class="h-6 w-6 text-zinc-400 mb-1" />
                                        <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Unggah / Ambil Foto</span>
                                    </div>
                                @endif

                                <div wire:loading wire:target="photo" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-20">
                                    <span class="text-xs font-bold text-blue-600 animate-pulse">Memproses...</span>
                                </div>
                            </div>

                            {{-- DAFTAR PILIHAN GAMBAR UTUK MOBILE (YANG TADI HILANG) --}}
                            @if(count($extracted_images) > 0)
                                <div class="pt-2">
                                    <h4 class="text-xs font-bold text-zinc-400 dark:text-zinc-500 mb-2 tracking-wide uppercase">Pilih Gambar Dari Artikel:</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($extracted_images as $imgUrl)
                                            <div wire:click="selectImageFromEditor('{{ $imgUrl }}')"
                                                class="relative w-full aspect-video rounded-xl overflow-hidden cursor-pointer border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600 ring-2 ring-blue-500/10' : 'border-transparent' }} transition-all">
                                                <img src="{{ $imgUrl }}" class="w-full h-full object-cover">

                                                @if($selected_image_url === $imgUrl)
                                                    <div class="absolute top-1 right-1 bg-blue-600 text-white rounded-full p-0.5 shadow">
                                                        <x-dynamic-component :component="'lucide-check'" class="h-3 w-3" stroke-width="3" />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Tombol Aksi Mobile --}}
                        <div class="p-4 bg-white dark:bg-gray-900 border-t border-zinc-100 dark:border-zinc-800 shadow-inner">
                            <button type="button" @click="isOpen = false" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-xs font-bold text-center transition-colors cursor-pointer shadow-sm">
                                Terapkan Sampul
                            </button>
                        </div>
                    </div>
                </div>
            </div>


        </form>

    </div>
</div>
