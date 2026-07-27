<?php

use Livewire\Component;

use Carbon\Carbon;

use App\Livewire\Traits\WithNotifications;

use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;

new class extends Component
{
    //
    use WithNotifications;
    public Post $article;

    public function mount($category, Post $post = null) {

        $this->article = $post;

        // (Opsional tapi disarankan) Validasi kesesuaian kategori di URL dengan database
        if ($post->category->slug !== $category) {
            abort(404, 'Artikel tidak ditemukan di kategori ini.');
        }


        // 1. JIKA ADA PARAMETER DI URL (Masuk Mode Edit)
        // if ($post) {
        //     $this->article = Post::where('slug', $post)->firstOrFail();
        // }
    }
    public function changeStatus($newStatus)
    {
        $this->article->status = $newStatus;
        $this->article->save();
        $this->notify('Status berhasil diubah.', 'success');
    }
    public function submitForReview()
    {
        // 1. Simpan dulu perubahan terakhirnya (meminjam logika saveArticle)
        // Pastikan Anda memanggil fungsi saveArticle agar gambar & tag ikut tersimpan.
        // $this->saveArticle($latestContent);

        // 2. Ambil artikel yang baru saja disave
        // $article = \App\Models\Post::find($this->article_id);

        if ($article) {
            // 3. Ubah statusnya menjadi pending
            $article->update([
                'status' => 'review'
            ]);

            // 4. Sinkronkan properti komponen
            $this->status = 'review';

            // 5. Catat log
            activity('article_updates')
                ->performedOn($article)
                ->causedBy(auth()->user())
                ->log('Artikel diajukan untuk review editor');

            // 6. Lempar notifikasi dan tendang kembali ke halaman daftar artikel
            $this->notifyFlash('Artikel berhasil diajukan! Menunggu review editor.', 'success');
            return $this->redirect(route('article.index'), navigate: true);
        }
    }
};
?>

<x-slot:title>{!! $article->title !!}</x-slot:title>
<x-main-wrapper isPreview="true" x-data="{ showConfirmationModal: false, targetStatus: '' }">


    <!-- WRAPPER UTAMA MODAL -->
    <div x-show="showConfirmationModal" 
        x-data="{
            statusData: {
                'draft': { title: 'Kembalikan ke Draf', desc: 'Artikel akan ditarik dan dikembalikan ke status draf (belum tayang).', color: 'text-zinc-600 dark:text-zinc-400', bg: 'bg-zinc-100 dark:bg-zinc-800' },
                'review': { title: 'Kirim untuk Ulasan', desc: 'Artikel akan dikirim ke editor untuk proses ulasan.', color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-100 dark:bg-blue-900/30' },
                'published': { title: 'Terbitkan Artikel', desc: 'Artikel akan langsung ditayangkan dan dapat dibaca oleh publik.', color: 'text-green-600 dark:text-green-400', bg: 'bg-green-100 dark:bg-green-900/30' },
                'scheduled': { title: 'Jadwalkan Artikel', desc: 'Artikel akan tayang secara otomatis pada waktu yang ditentukan.', color: 'text-purple-600 dark:text-purple-400', bg: 'bg-purple-100 dark:bg-purple-900/30' },
                'archived': { title: 'Arsipkan Artikel', desc: 'Artikel akan ditarik dari publik dan disimpan di arsip.', color: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-100 dark:bg-amber-900/30' },
                'rejected': { title: 'Tolak Artikel', desc: 'Artikel akan ditolak dan dikembalikan untuk direvisi.', color: 'text-red-600 dark:text-red-400', bg: 'bg-red-100 dark:bg-red-900/30' }
            }
        }"
        class="relative z-50" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true" 
        x-cloak>
        
        <!-- 1. LATAR HITAM / BACKDROP -->
        <div x-show="showConfirmationModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity"
            x-on:click.away=" showConfirmationModal = false;  setTimeout(() => { targetStatus = '' }, 200); ">
        </div>

        <!-- 2. KONTAINER POSISI MODAL -->
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <!-- 3. BADAN MODAL UTAMA -->
                <div x-show="showConfirmationModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    @click.away="showConfirmationModal = false"
                    class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 px-4 pb-4 pt-5 text-left shadow-xl transition-all w-full max-w-sm sm:my-8 sm:p-6">

                    <div>
                        <!-- Ikon Berubah Warna Dinamis -->
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full transition-colors duration-300"
                            x-bind:class="statusData[targetStatus]?.bg || 'bg-blue-100 dark:bg-blue-900/30'">
                            <flux:icon variant="outline" icon="arrow-path" class="h-6 w-6 transition-colors duration-300"
                                    x-bind:class="statusData[targetStatus]?.color || 'text-blue-600 dark:text-blue-400'" />
                        </div>

                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-bold leading-6 text-zinc-900 dark:text-white" 
                                id="modal-title"
                                x-text="statusData[targetStatus]?.title || 'Konfirmasi Perubahan'">
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400" 
                                x-text="statusData[targetStatus]?.desc || 'Apakah Anda yakin ingin mengubah status dokumen ini?'">
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="button"
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-forest px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-foresty transition-colors sm:w-auto"
                                x-on:click="
                                    $wire.changeStatus(targetStatus);
                                    showConfirmationModal = false;
                                ">
                            Ya, Lanjutkan
                        </button>
                        <button type="button"
                                x-on:click="
                                    showConfirmationModal = false; 
                                    setTimeout(() => { targetStatus = '' }, 200); 
                                "
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-300 shadow-sm ring-1 ring-inset ring-zinc-300 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors sm:w-auto">
                            Batal
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>


    <!-- STATUS CONFIRMATION MODAL -->
    {{-- <div x-show="showConfirmationModal" class="relative z-99" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>

        <!-- Backdrop -->
        <div x-show="showConfirmationModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity">
        </div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showConfirmationModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    @click.away="showConfirmationModal = false"
                    class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 px-4 pb-4 pt-5 text-left shadow-xl transition-all w-full max-w-sm sm:my-8 sm:p-6">

                    <div>
                        <!-- Ikon Info/Konfirmasi (Warna netral/biru, bukan merah) -->
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <flux:icon variant="outline" icon="arrow-path" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>

                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-bold leading-6 text-zinc-900 dark:text-white" id="modal-title">
                                Konfirmasi Perubahan Status
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Apakah Anda yakin ingin mengubah status dokumen ini menjadi
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 uppercase" x-text="targetStatus"></span>?
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row-reverse gap-3">

                        <!-- Tombol Konfirmasi -->
                        <button type="button"
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-forest px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-foresty transition-colors sm:w-auto"
                                x-on:click="
                                    $wire.changeStatus(targetStatus);
                                    showConfirmationModal = false;
                                ">
                            Ya, Ubah Status
                        </button>

                        <!-- Tombol Batal -->
                        <button type="button"
                                x-on:click="showConfirmationModal = false; targetStatus = ''; "
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-300 shadow-sm ring-1 ring-inset ring-zinc-300 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors sm:w-auto">
                            Batal
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <x-slot:header>
        <!-- Kiri: Judul & Badge Status -->
        <div class="flex justify-center items-center gap-3">
            {{-- <a href="{{ route('article.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                <x-dynamic-component :component="'lucide-arrow-left'" class="w-4 h-4" />
                <x-dynamic-component :component="'lucide-chevron-left'" class="w-4 h-4" />
                Kembali
            </a> --}}
            <a href="{{ route('article.index') }}" wire:navigate class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">

                <!-- Wrapper Ikon (Ukuran tetap agar teks tidak melompat) -->
                <div class="relative flex items-center justify-center w-4 h-4 overflow-hidden">

                    <!-- Chevron Left: Tampil secara default, geser ke kiri dan memudar saat hover -->
                    <div class="absolute transition-all duration-300 ease-out translate-x-0 opacity-100 group-hover:-translate-x-4 group-hover:opacity-0">
                        <x-dynamic-component :component="'lucide-chevron-left'" class="w-4 h-4" />
                        {{-- <x-dynamic-component :component="'lucide-arrow-left'" class="w-4 h-4" /> --}}
                    </div>

                    <!-- Arrow Left: Tersembunyi di kanan, geser ke tengah dan muncul saat hover -->
                    <div class="absolute transition-all duration-300 ease-out translate-x-4 opacity-0 group-hover:translate-x-0 group-hover:opacity-100">
                        <x-dynamic-component :component="'lucide-arrow-left'" class="w-4 h-4" />
                        {{-- <x-dynamic-component :component="'lucide-chevron-left'" class="w-4 h-4" /> --}}
                    </div>

                </div>
                {{ __('Back') }}

            </a>

            <h1 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 line-clamp-1">
                {!! $article->title !!}
            </h1>


            <!-- Contoh Badge Status -->
            <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $article->status_color }}">
                {{ strtoupper($article->status) }}
                @if ($article->status == 'scheduled')
                     @jadwal terbit
                @endif
            </span>
        </div>

        <!-- Kanan: Tombol Aksi Berdasarkan Role & Status -->
        <div class="flex items-center gap-2 ">

            <!-- LOGIKA UNTUK PENULIS -->
            @if(auth()->user()->hasRole('writer'))
                @if($article->status === 'draft' || $article->status === 'rejected')
                    <a href="{{ route('article.edit', ['category' => $article->category->slug ?? 'uncategorized', 'post'=> $article->slug]) }}" wire:navigate class="select-none cursor-pointer group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Edit
                    </a>
                    {{-- <a href="{{ route('article.edit', ['category' => $article->category->slug ?? 'uncategorized', 'post'=> $article->slug]) }}" class="btn-edit">Edit Artikel</a> --}}
                    <button x-on:click="targetStatus = 'review'; showConfirmationModal = true" type="button" class="select-none cursor-pointer group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-misty border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Ajukan Review
                    </button>
                @endif
            @endif

            <!-- LOGIKA UNTUK EDITOR / ADMIN -->
            @if(auth()->user()->hasanyrole('editor|admin'))
                @if($article->status === 'review')
                    <button x-on:click="targetStatus = 'draft'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Kembalikan ke Draft
                    </button>
                    <button x-on:click="targetStatus = 'rejected'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Tolak Total
                    </button>
                    <button x-on:click="targetStatus = 'published'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Terbitkan Sekarang
                    </button>
                @endif

                @if($article->status === 'scheduled')
                    <button x-on:click="targetStatus = 'published'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Terbitkan
                    </button>
                    <button x-on:click="targetStatus = 'draft'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Kembalikan ke Draft
                    </button>
                @endif

                @if($article->status === 'archived')
                    <button x-on:click="targetStatus = 'published'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Terbitkan
                    </button>
                @endif

                @if($article->status === 'published')
                    <button x-on:click="targetStatus = 'archived'; showConfirmationModal = true" type="button" class="select-none group cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border text-zinc-600 bg-misty border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                        Arsipkan
                    </button>
                @endif
            @endif

        </div>
    </x-slot:header>

    {!! $article->content !!}

</x-main-wrapper>
