<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;

use Livewire\Component;
use Livewire\WithFileUploads;

use Spatie\Activitylog\Models\Activity;

use Illuminate\Validation\Rule;
use App\Livewire\Traits\WithNotifications; // Import trait-nya
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

new class extends Component {
    use WithFileUploads;
    use WithNotifications;


    public ?int $user_id = null;
    public ?int $article_id = null;
    public ?int $category_id = null;

    public array $tags = [] ;
    public string $title;
    public string $slug;
    public string $content;
    public string $featured_image;
    public string $status;
    public string $status_color;
    public string $published_at;
    public string $created_at;

    public $photo;
    public $editorPhoto;

    // Properti baru untuk mendukung fitur seleksi gambar
    public array $extracted_images = []; // Menyimpan daftar semua URL gambar dari editor
    public ?string $selected_image_url = null; // Menyimpan URL gambar yang dipilih penulis


    public function mount($category = null, $post = null) {
        // 1. JIKA ADA PARAMETER DI URL (Masuk Mode Edit)
        if ($post) {

            // 🌟 KUNCI 404: Cari artikel berdasarkan slug.
            // Jika slug asal-asalan dan tidak ada di database, sistem akan OTOMATIS berhenti dan merender halaman 404 Not Found.
            $artikel = \App\Models\Post::where('slug', $post)->firstOrFail();

            $this->article_id = $artikel->id;
            $this->title = $artikel->title;
            $this->slug = $artikel->slug;
            $this->content = $artikel->content;
            $this->category_id = $artikel->category_id;
            $this->user_id = $artikel->user_id;
            $this->status = $artikel->status;
            $this->status_color = $artikel->status_color;
            $this->created_at = $artikel->created_at->format('Y-m-d');

            // Ambil ID tags untuk TomSelect
            $this->tags = $artikel->tags->pluck('id')->toArray();

            // Atur gambar sampul
            $this->featured_image = $artikel->featured_image;
            $this->selected_image_url = asset('storage/articles/' . $artikel->featured_image);

            // Pindai gambar dari konten lama
            $this->scanEditorImages();
        }
        // 2. JIKA URL KOSONG / TANPA PARAMETER (Masuk Mode Tulis Baru)
        else {
            $this->created_at = now()->format('Y-m-d');
            $this->user_id = auth()->id() ?? 1; // Pastikan ada fallback user ID
            $this->content = '';
            $this->title = '';
            $this->slug = '';
            $this->featured_image = 'default.webp';
            $this->status = 'draft';
            $this->tags = [];
        }
    }


    public function scanEditorImages() {
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
    public function selectImageFromEditor($url) {
        $this->photo = null; // Batalkan file upload kustom jika ada
        $this->selected_image_url = $url;
        $this->featured_image = basename($url); // Ambil nama filenya saja untuk database
    }

    /**
     * Lifecycle hook Livewire: Otomatis berjalan ketika penulis mengunggah file kustom lewat input file
     */
    public function updatedPhoto() {
        $this->validate([
            'photo' => 'image|max:15360',
        ]);

        // Reset pilihan gambar dari editor karena penulis beralih ke upload kustom
        $this->selected_image_url = null;
        // featured_image sementara diisi nama file aslinya untuk pratinjau local temporaryUrl()
        // $this->featured_image = $this->photo->getClientOriginalName();
    }

    private function processAndTrimImages($htmlContent) {
        if (empty($htmlContent)) return $htmlContent;

        return preg_replace_callback(
            '/<img([^>]*)\ssrc="data:image\/([a-zA-Z0-9.+-]+);base64,([^"]+)"([^>]*)>/i',
            function ($m) {
                [$full, $before, $ext, $data, $after] = $m;
                $ext = $ext === 'jpeg' ? 'jpg' : $ext;
                try {
                    $filename = 'article-' . Str::uuid() . '.' . $ext;
                    Storage::disk('public')->put('articles/' . $filename, base64_decode($data));
                    $url = asset('storage/articles/' . $filename);
                    return '<img' . $before . ' src="' . $url . '" class="rounded-lg max-w-full my-2 inline-block tiptap-trimmed-image"' . $after . '>';
                } catch (\Exception $e) {
                    return '';
                }
            },
            $htmlContent
        );
    }


    protected function rules() {
        return [
            'category_id'       => 'required|numeric',
            'title'             => ['required', Rule::unique('posts')->ignore($this->article_id)],
            'slug'              => ['required', Rule::unique('posts')->ignore($this->article_id)],
            'tags'              => 'required|array|min:1',
            'content'           => 'required|min:5',
            'featured_image'    => 'required',
        ];
    }

    protected function messages() {
        return [
            // Format: 'nama_variabel.nama_rule' => 'Pesan kustom'

            'title.required' => __('Judul artikel tidak boleh dikosongkan.'),
            'title.unique'   => __('Judul ini sudah dipakai di artikel lain. Silakan buat yang berbeda.'),

            'category_id.required' => __('Anda belum memilih kategori artikel.'),

            'content.required' => __('Isi tulisan tidak boleh kosong.'),
            'content.min'      => __('Tulisan terlalu pendek, minimal 5 karakter.'),

            'tags.required' => __('Anda wajib menambahkan minimal satu tag.'),
            'tags.min'      => __('Minimal pilih satu tag dari daftar.'),

            'featured_image.required' => __('Tolong tentukan gambar sampul untuk artikel ini.'),
        ];
    }

    public function submitForReview($latestContent)
    {
        // 1. Simpan dulu perubahan terakhirnya (meminjam logika saveArticle)
        // Pastikan Anda memanggil fungsi saveArticle agar gambar & tag ikut tersimpan.
        $this->saveArticle($latestContent);

        // 2. Ambil artikel yang baru saja disave
        $article = \App\Models\Post::find($this->article_id);

        if ($article) {
            // 3. Ubah statusnya menjadi pending
            $article->update([
                'status' => 'review'
            ]);

            // 4. Sinkronkan properti komponen
            $this->status = 'review';

            // LOG ON MODEL
            // // 5. Catat log
            // activity('article_updates')
            //     ->performedOn($article)
            //     ->causedBy(auth()->user())
            //     ->log('Artikel diajukan untuk review editor Awgoooga');

            // 6. Lempar notifikasi dan tendang kembali ke halaman daftar artikel
            $this->notifyFlash('Artikel berhasil diajukan! Menunggu review editor.', 'success');
            return $this->redirect(route('article.index'), navigate: true);
        }
    }

    public function saveArticle($latestContent) {
        $this->content = $latestContent;
        // 1. Biarkan Carbon membaca tanggalnya secara otomatis
        // (Tidak peduli formatnya d/m/y, d-m-Y, atau Y-m-d)
        $tanggal = Carbon::parse($this->created_at);

        // 2. Tambahkan jam dan menit saat ini
        $tanggal->setTimeFrom(now());

        // 3. Simpan kembali ke dalam format yang aman untuk database
        $this->created_at = $tanggal->format('Y-m-d H:i:s');

        $this->content = $this->processAndTrimImages($this->content);
        $this->scanEditorImages();
        $this->slug = Str::slug($this->title);
        if (empty($this->status)) {
            $this->status = 'draft';
        }
        // 🔥 TAMBAHKAN BLOK INI: Proses jika penulis mengunggah gambar sampul khusus via Modal
        if ($this->photo) {
            $filename = 'cover-' . Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
            $this->photo->storeAs('articles', $filename, 'public');

            $this->featured_image = $filename;
            $this->selected_image_url = asset('storage/articles/' . $filename);

            // Kosongkan agar aman
            $this->photo = null;
        }

        // 4. FALLBACK DEFAULT URL: Jika ternyata penulis tidak upload foto kustom DAN editor murni teks saja
        // 🔥 UBAH FALLBACK: Hapus pengecekan empty($this->photo) karena sudah ditangani di atas
        if (empty($this->featured_image)) {
            $this->featured_image = 'default.webp';
            $this->selected_image_url = asset('storage/articles/default.webp');
        }
        // if (empty($this->featured_image) && empty($this->photo)) {
        //     // Ganti 'default.jpg' dengan nama file gambar default Anda yang ada di storage/public
        //     $this->featured_image = 'default.webp';
        //     $this->selected_image_url = asset('storage/articles/default.webp');
        // }

        $this->validate();

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
                            Log::info('[Tiptap Cleaner] Berhasil menghapus file usang dari storage: ' . $storagePath);
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
                'user_id' => $this->user_id, // Fallback ke properti jika auth kosong
                'category_id' => $this->category_id,
                'title' => $this->title,
                'slug' => $this->slug,
                'content' => $this->content, // HTML bersih, ringan, bebas base64
                'featured_image' => $this->featured_image,
                'status' => $this->status,
                // 'created_at' => $this->created_at,
            ],
        );
        $urlTelahBerubah = $article->wasRecentlyCreated || $article->wasChanged(['slug', 'category_id']);

        // Jika ini adalah artikel baru yang baru dibuat, ikat ID-nya ke properti komponen
        if (!isset($this->article_id)) {
            $this->article_id = $article->id;
        }

        // =======================================================
        // 🌟 TAHAP 4: PROSES PENYIMPANAN & PEMBUATAN TAGS BARU
        // =======================================================
        $finalTagIds = []; // Wadah untuk mengumpulkan semua ID Tag (baik lama maupun baru)

        if (!empty($this->tags)) {
            foreach ($this->tags as $tagInput) {
                // Cek apakah inputannya hanya berupa angka (berarti ID Tag lama)
                if (is_numeric($tagInput)) {
                    $finalTagIds[] = (int) $tagInput;
                }
                // Jika berupa teks/string (berarti Tag baru yang diketik user)
                else {
                    // Gunakan firstOrCreate agar tidak ada tag duplikat jika user salah ketik
                    // Pastikan Anda sudah mengimpor use App\Models\Tag; di atas
                    $newTag = \App\Models\Tag::firstOrCreate([
                        'name' => trim($tagInput),
                        'slug' => Str::slug($tagInput),
                    ]);

                    // Masukkan ID dari tag yang baru saja dibuat ke dalam wadah
                    $finalTagIds[] = $newTag->id;
                }
            }
        }

        // Hubungkan (sync) semua ID tag yang terkumpul ke artikel ini
        // Asumsinya Anda sudah punya relasi tags() di model Post Anda
        $article->tags()->sync($finalTagIds);

        if ($urlTelahBerubah) {
            $categorySlug = \Illuminate\Support\Facades\DB::table('categories')
                ->where('id', $this->category_id)
                ->value('slug') ?? 'umum';

            // ✅ Gunakan notifyFlash() buatan Anda sendiri!
            $this->notifyFlash('Artikel berhasil disimpan!', 'success');

            // Redirect ke halaman edit dengan URL yang baru
            return $this->redirect(route('article.edit', [
                'category' => $categorySlug,
                'post'     => $article->slug
            ]), navigate: true);
        }

        $this->notify('Artikel disimpan!', 'success');
        // session()->flash('message', 'Artikel berhasil disimpan!');
    }
    public function uploadImage() {
        $this->validate([
            'photo' => 'image|max:15360',
        ]);

        $tempPath = $this->photo->getRealPath();
        $extension = strtolower($this->photo->getClientOriginalExtension());
        $filename = 'article-' . uniqid() . '.webp';
        $savePath = storage_path('app/public/articles/' . $filename);

        if ($extension === 'gif' && class_exists('\Imagick')) {
            try {
                \Intervention\Image\Laravel\Facades\Image::withDriver(new \Intervention\Image\Drivers\Imagick\Driver())
                    ->read($tempPath)
                    ->scale(width: 1000)
                    ->toWebp(70)
                    ->save($savePath);

                // 🔥 TAMBAHKAN INI: Bersihkan sisa file agar tidak mengganggu validasi Save
                $this->photo = null;

                return asset('storage/articles/' . $filename);
            } catch (\Exception $e) {
                Log::warning('Gagal kompresi GIF di backend, menggunakan file asli: ' . $e->getMessage());
            }
        }

        $path = $this->photo->store('articles', 'public');

        // 🔥 TAMBAHKAN INI JUGA DI JALUR FALLBACK
        $this->photo = null;

        return asset('storage/' . $path);
    }
    public function getAuditTrailProperty()
    {
        if (!isset($this->article_id)) {
            return collect(); // Jika artikel baru/belum disave, kosongkan
        }

        return Activity::forSubject(\App\Models\Post::find($this->article_id))
            ->with('causer') // Ambil data siapa yang melakukan aksi
            ->latest()
            ->get();
    }
};
?>
<x-slot:title>{{ __('Write Article') }}</x-slot:title>
<div class="w-full h-[calc(100vh-4rem)] flex-1 min-h-0 gap-2 overflow-hidden flex flex-col md:flex-row pt-2 md:pt-0">

    {{-- Gunakan wire:submit="save" yang merupakan standar Livewire 3 --}}
    <form
        wire:submit="save" @submit.capture="flushEditorSync()" x-data="setupEditor('content', $wire)" @buka-modal-link.window="isLinkOpen = true"
        class="flex flex-col w-full bg-zinc-50 dark:bg-zinc-950 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all duration-300 ease-in-out">

        {{-- HEADER, META, BUTTONS, TOOLBARS --}}
        <div class="flex-none w-full bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 z-40">
            {{-- META's --}}
            <div x-data="{ isMetaOpen: false, title: @entangle('title'), categoryId: @entangle('category_id'), selectedTags: @entangle('tags'), createdAt: @entangle('created_at') }"
                class="w-full pt-4 pb-3 px-4 md:px-4 flex flex-col md:flex-row md:items-center justify-between gap-4">

                {{-- STATUS CONTAINER --}}
                <div class="relative flex flex-row justify-start items-center w-full gap-4 max-w-4xl">
                    @if(!empty($article_id))
                        <span class="mx-1 px-2 py-1 text-xs font-medium rounded-full border-2 {{ $status_color }}">
                            {{ ucfirst($status) }}
                        </span>
                    @endif
                    @error('title')
                        {{-- Teks error dibuat absolute agar melayang di bawah tanpa mendorong elemen lain --}}
                        <span class="absolute -top-2 left-3 text-xs text-red-500 font-semibold tracking-wide whitespace-nowrap">
                            {{ $message }}
                        </span>
                    @enderror
                    <input type="text" x-model="title" placeholder="Judul Artikel..." class="w-full p-2.5 text-2xl md:text-3xl font-bold bg-transparent outline-none focus:outline-none focus:ring-0 border-0 border-b-2 border-zinc-200 focus:border-zinc-400 dark:border-zinc-800 dark:focus:border-zinc-600 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-600 transition-colors" />

                </div>

                {{-- <div x-data="{ errors: false }"
                    x-on:livewire-upload-error.window="errors = true"
                    @if($errors->any()) x-init="setTimeout(() => $dispatch('notify', { message: 'Gagal menyimpan! Periksa form Anda.', type: 'error' }), 100)" @endif>
                </div> --}}

                {{-- BUTTONS --}}
                <div class="flex items-center justify-end md:justify-between gap-2 shrink-0 md:ml-4">
                    @php $canEdit =  in_array($status, ['draft', null]); @endphp

                    @if(empty($article_id) || $status === 'draft')
                        {{-- COVER MODAL BUTTON --}}
                        <button type="button" wire:click="scanEditorImages" @click="$dispatch('buka-featured-modal')"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                            title="Pilih Gambar Sampul">
                            <x-dynamic-component :component="'lucide-image'" class="h-5 w-5 origin-center group-hover:animate-blocks" stroke-width="2" />
                            <span class="hidden 2xl:inline">Sampul Artikel</span>
                            <span class="md:hidden">Sampul</span>
                        </button>

                        {{-- META MODAL BUTTON --}}
                        <button type="button" @click="isMetaOpen = true" 
                            class="relative group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none" 
                            title="Pengaturan Artikel">

                            <x-dynamic-component :component="'lucide-file-sliders'" class="h-5 w-5 origin-center group-hover:animate-tag" stroke-width="2" />
                            <span class="hidden 2xl:inline">Pengaturan Dokumen</span>
                            <span class="md:hidden">Pengaturan</span>

                            {{-- 🚨 INDIKATOR DOT MERAH ERROR 🚨 --}}
                            @if($errors->hasAny(['category_id', 'tags']))
                                {{-- Wrapper absolute diletakkan di luar jangkauan flex --}}
                                <span class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-zinc-900 z-10">
                                    !
                                    {{-- Efek ping/denyut opsional agar lebih menarik perhatian --}}
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                </span>
                            @endif
                        </button>

                        {{-- META MODAL BUTTON --}}
                        {{-- <button type="button" @click="isMetaOpen = true" class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none" title="Pengaturan Artikel">

                            <x-dynamic-component :component="'lucide-file-sliders'" class="h-5 w-5 origin-center group-hover:animate-tag" stroke-width="2" />
                            <span class="hidden 2xl:inline">Pengaturan Dokumen</span>
                            <span class="md:hidden">Pengaturan</span> --}}

                            {{-- 🚨 INDIKATOR DOT MERAH ERROR 🚨 --}}
                            {{-- Wrapper absolute diletakkan di luar jangkauan flex --}}
                            {{-- Efek ping/denyut opsional agar lebih menarik perhatian --}}
                            {{-- @if($errors->hasAny(['category_id', 'tags']))
                                <span class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-zinc-900 z-10">
                                    !
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                </span>
                            @endif
                        </button> --}}

                        @if(!empty($article_id) && $status === 'draft')
                            {{-- REVIEW MODAL BUTTON --}}
                            <button type="button"
                                x-on:click="$dispatch('buka-modal-review')"
                                wire:loading.attr="disabled"
                                class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">
                                
                                <span class="flex items-center justify-center gap-2" wire:loading.remove wire:target="submitForReview">
                                    <x-dynamic-component :component="'lucide-send'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                    <span class="hidden 2xl:block"> Ajukan Review </span>
                                    {{-- <span class="md:hidden"> Ajukan </span> --}}
                                </span>

                                <div wire:loading.flex wire:target="submitForReview" class="flex-row items-center justify-center gap-2">
                                    <span>Mengirim...</span>
                                </div>
                            </button>
                        @endif
                    
                        {{-- SAVE BUTTON --}}
                        <button type="button"
                            x-on:click="if(window.tiptapEditor) { $wire.saveArticle(window.tiptapEditor.getHTML()) }"
                            wire:loading.attr="disabled"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">

                            <!-- PENTING: Ubah wire:target menjadi saveArticle -->
                            <span class="flex items-center justify-center gap-2" >
                                <x-dynamic-component :component="'lucide-save'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                <span wire:loading.remove wire:target="saveArticle" class="hidden 2xl:block"> {{ __('Simpan Artikel') }} </span>
                                <span class="md:hidden"> {{ __('Simpan') }} </span>
                            </span>

                            <div  class="hidden 2xl:block flex-row items-center justify-center gap-2">
                                <span wire:loading.flex wire:target="saveArticle">Memproses...</span>
                            </div>
                        </button>
                    @endif


                    <!-- 🎛️ TOMBOL TOGGLE PANEL AUDIT (Desktop & Mobile) -->
                    <button type="button" @click="isAuditOpen = !isAuditOpen"
                        class="group inline-flex items-center gap-2 p-2 text-sm font-semibold border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                        :title="isAuditOpen ? 'Tutup Panel Audit' : 'Buka Panel Audit'"
                        :class="isAuditOpen ? 'bg-foresty text-goldy' : 'bg-white text-zinc-600'" >
                        
                        {{-- 🔥 Ganti class menjadi group-hover:animate-tick --}}
                        <x-dynamic-component :component="'lucide-clock-fading'" class="h-5 w-5 origin-center group-hover:animate-tick" stroke-width="2" />
                        
                        <span class="hidden 2xl:inline" x-text="isAuditOpen ? 'Tutup Audit' : 'Riwayat Audit'"></span>
                    </button>

                </div>

                {{-- ================== UNIVERSAL MODAL / BOTTOM SHEET ================== --}}
                {{-- Trik Tailwind: items-end untuk HP (Bottom Sheet), md:items-center untuk Desktop (Modal Tengah) --}}
                <template x-teleport="body">
                    <div x-show="isMetaOpen"
                        class="fixed inset-0 z-999 flex items-end justify-center md:items-center p-0 md:p-4"
                        style="display: none;">

                        {{-- Overlay Backdrop --}}
                        <div x-show="isMetaOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isMetaOpen = false"></div>

                        {{-- Konten Modal --}}
                        {{-- Meluncur dari bawah di HP, Fade & Scale di Desktop --}}
                        <div x-show="isMetaOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95" x-transition:enter-end="translate-y-0 md:opacity-100 md:scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 md:opacity-100 md:scale-100" x-transition:leave-end="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95" class="relative bg-white dark:bg-zinc-900 w-full md:w-125 rounded-t-2xl md:rounded-2xl h-[85vh] md:h-[90vh] flex flex-col overflow-hidden shadow-2xl z-10">

                            {{-- Handle Drag (Hanya terlihat di Mobile) --}}
                            <div class="md:hidden w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto my-3" @click="isMetaOpen = false"></div>

                            {{-- Header Modal (Desktop) --}}
                            <div
                                class="hidden md:flex p-4 border-b border-zinc-100 dark:border-zinc-800 items-center justify-between">
                                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-wide">
                                    Pengaturan Meta Dokumen</h3>
                                <button type="button" @click="isMetaOpen = false"
                                    class="text-zinc-400 hover:text-red-500 transition cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Konten Input Modal --}}
                            <div class="p-5 md:p-6 overflow-y-auto space-y-5 bg-zinc-50 dark:bg-zinc-950 flex-1">
                                <div class="md:hidden">
                                    <h4
                                        class="text-xs font-bold text-zinc-400 dark:text-zinc-500 mb-2 tracking-wide uppercase">
                                        Detail Dokumen</h4>
                                </div>

                                {{-- Input Tanggal --}}
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal
                                        Dibuat</label>
                                    <input type="date" x-model="createdAt" class="w-full p-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-forest/20 focus:border-forest text-zinc-800 dark:text-zinc-200" />

                                </div>

                                {{-- Kategori --}}
                                <div class="space-y-1.5" wire:ignore x-data="{
                                    tom: null,
                                    init() {
                                        this.tom = new window.TomSelect(this.$refs.catInput, {
                                            create: false,
                                            placeholder: 'Pilih Kategori...'
                                        });
                                        this.tom.setValue(this.categoryId, true);
                                        this.tom.on('change', val => this.categoryId = val);
                                    } }">
                                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kategori</label>
                                    <select x-ref="catInput">
                                        <option value="">Pilih Kategori...</option>
                                        @foreach (\App\Models\Category::all() as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category_id')
                                    <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror

                                {{-- Tags --}}
                                <div class="space-y-1.5" wire:ignore x-data="{
                                    tom: null,
                                    init() {
                                        this.tom = new window.TomSelect(this.$refs.tagInput, {
                                            create: true,
                                            plugins: ['remove_button'],
                                            maxItems: null,
                                            placeholder: 'Ketik atau Cari Tags...'
                                        });

                                        // Set nilai awal saat halaman dimuat menggunakan data dari Livewire
                                        this.tom.setValue(@js($tags));

                                        // 🌟 KUNCI: Tembak data langsung ke PHP backend setiap ada perubahan
                                        this.tom.on('change', val => {
                                            let tagsArray = [];

                                            if (val) {
                                                // Pastikan datanya benar-benar Array sebelum dikirim
                                                tagsArray = Array.isArray(val) ? val : val.split(',');
                                            }

                                            // Gunakan $wire.set() untuk meng-overwrite variabel $this->tags di PHP
                                            $wire.set('tags', tagsArray);
                                        });
                                    } }">
                                    <label
                                        class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tags</label>
                                    <select x-ref="tagInput" multiple>
                                        @foreach (\App\Models\Tag::all() as $tag)
                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tags')
                                    <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror

                            </div>

                            {{-- Tombol Tutup Modal --}}
                            <div
                                class="p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <button type="button" @click="isMetaOpen = false"
                                    class="w-full bg-forest hover:bg-forest/90 text-white py-2.5 rounded-xl text-sm font-bold text-center transition-colors cursor-pointer shadow-sm">
                                    Terapkan & Selesai
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        </div>

        {{-- EDITOR WAS HERE --}}
        <x-editor :editable="$canEdit"/>
    </form>

    @include('components.editor.modal-audit')
    @include('components.editor.modal-thumbnail')
    @include('components.editor.modal-review')
</div>
