<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithNotifications;

new class extends Component
{
    use WithFileUploads;
    use WithNotifications;

    public string $title;
    public ?int $page_id = null;
    public string $created_at;

    public function getAuditTrailProperty()
    {
        if (!isset($this->page_id)) {
            return collect(); // Jika artikel baru/belum disave, kosongkan
        }

        return Activity::forSubject(\App\Models\Page::find($this->page_id))
            ->with('causer') // Ambil data siapa yang melakukan aksi
            ->latest()
            ->get();
    }
};
?>

{{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}
<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title> 
<div class="w-full md:h-[calc(100vh-4rem)] flex-1 min-h-0 gap-2 overflow-hidden flex flex-col md:flex-row pt-2 md:pt-0">
    <form
        x-data="setupEditor(
            'content',
            $wire,
            {
                step_number: '01',
                step_heading: '{{ __('ui.editor.step_heading') }}',
                step_description: '{{ __('ui.editor.step_description') }}',
                heading: '{{ __('matata') }}',
                default: '{{ __('hakuna') }}'
                {{-- heading: '{{ __('ui.editor.heading') }}', --}}
                {{-- default: '{{ __('ui.placeholder.editor') }}' --}}
            }
        )"
        wire:submit="save" @submit.capture="flushEditorSync()"
        {{-- @buka-modal-link.window="isLinkOpen = true" --}}
        @buka-modal-link.window=" isLinkOpen = true; linkInputText = $event.detail.text || '';"
        class="flex flex-col w-full bg-zinc-50 dark:bg-zinc-950 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-800 shadow-sm transition-all duration-300 ease-in-out">

        
        {{-- HEADER, META, BUTTONS, TOOLBARS --}}
        <div class="flex-none w-full bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 z-40">
            {{-- META's --}}
            <div 
            x-data="{ 
                isMetaOpen: false, 
                title: @entangle('title'), 
                {{-- categoryId: @entangle('category_id'), 
                selectedTags: @entangle('tags'),  --}}
                createdAt: @entangle('created_at') }"
                class="w-full pt-4 pb-3 px-4 md:px-4 flex flex-col md:flex-row md:items-center justify-between gap-4">

                {{-- STATUS CONTAINER --}}
                <div class="relative flex flex-row justify-start items-center w-full gap-4 max-w-4xl">
                    {{-- @if(!empty($article_id))
                        <span class="mx-1 px-2 py-1 text-xs font-medium rounded-full border-2 {{ $status_color }}">
                            {{ ucfirst($status) }}
                        </span>
                    @endif
                    @error('title')
                        <!-- Teks error dibuat absolute agar melayang di bawah tanpa mendorong elemen lain -->
                        <span class="absolute -top-2 left-3 text-xs text-red-500 font-semibold tracking-wide whitespace-nowrap">
                            {{ $message }}
                        </span>
                    @enderror --}}
                    <input type="text" x-model="title" placeholder="{{ __('ui.articles.title') }}" class="w-full p-2.5 text-2xl md:text-3xl font-bold bg-transparent outline-none focus:outline-none focus:ring-0 border-0 border-b-2 border-zinc-200 focus:border-zinc-400 dark:border-zinc-800 dark:focus:border-zinc-600 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-600 transition-colors" />

                </div>

                {{-- BUTTONS --}}
                <div class="flex items-center justify-end md:justify-between gap-2 shrink-0 md:ml-4">
                    {{-- @php $canEdit =  in_array($status, ['draft', null]); @endphp --}}

                    {{-- @if(empty($article_id) || $status === 'draft') --}}
                        {{-- COVER MODAL BUTTON --}}
                        <button type="button" wire:click="scanEditorImages" @click="$dispatch('buka-featured-modal')"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                            title="{{ __('ui.tip.cover') }}">
                            <x-dynamic-component :component="'lucide-image'" class="h-5 w-5 origin-center group-hover:animate-blocks" stroke-width="2" />
                            <span class="hidden 2xl:inline">{{ __('ui.button.cover') }}</span>
                        </button>

                        {{-- META MODAL BUTTON --}}
                        <button type="button" @click="isMetaOpen = true"
                            class="relative group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                            title="{{ __('ui.tip.properties') }}">

                            <x-dynamic-component :component="'lucide-file-sliders'" class="h-5 w-5 origin-center group-hover:animate-tag" stroke-width="2" />
                            <span class="hidden 2xl:inline">{{ __('ui.button.properties') }}</span>

                            {{-- 🚨 INDIKATOR DOT MERAH ERROR 🚨 --}}
                            {{-- @if($errors->hasAny(['category_id', 'tags']))
                                <!-- Wrapper absolute diletakkan di luar jangkauan flex -->
                                <span class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-zinc-900 z-10">
                                    !
                                    <!-- Efek ping/denyut opsional agar lebih menarik perhatian -->
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                </span>
                            @endif --}}
                        </button>

                        @if(!empty($article_id) && $status === 'draft')
                            {{-- REVIEW MODAL BUTTON --}}
                            <button type="button"
                                x-on:click="$dispatch('buka-modal-review')"
                                wire:loading.attr="disabled"
                                title="{{ __('ui.tip.review') }}"
                                class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">

                                <span class="flex items-center justify-center gap-2" wire:loading.remove wire:target="submitForReview">
                                    <x-dynamic-component :component="'lucide-send'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                    <span class="hidden 2xl:block"> {{ __('ui.button.review') }} </span>
                                    {{-- <span class="md:hidden"> Ajukan </span> --}}
                                </span>

                                <div wire:loading.flex wire:target="submitForReview" class="flex-row items-center justify-center gap-2">
                                    <span>{{ __('ui.button.sending') }}...</span>
                                </div>
                            </button>
                        @endif

                        {{-- SAVE BUTTON --}}
                        <button type="button"
                            x-on:click="if(window.tiptapEditor) { $wire.saveArticle(window.tiptapEditor.getHTML()) }"
                            wire:loading.attr="disabled"
                            :title="$wire.article_id ? '{{ __('ui.tip.save') }}' : '{{ __('ui.tip.create') }}'"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">

                            <!-- PENTING: Ubah wire:target menjadi saveArticle -->
                            <span class="flex items-center justify-center gap-2" >
                                <x-dynamic-component :component="'lucide-save'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                <span wire:loading.remove wire:target="saveArticle" class="hidden 2xl:block" x-text="$wire.article_id ? '{{ __('ui.button.save') }}' : '{{ __('ui.button.create') }}'"></span>
                            </span>

                            <div  class="hidden 2xl:block flex-row items-center justify-center gap-2">
                                <span wire:loading.flex wire:target="saveArticle">{{ __('ui.button.saving') }}...</span>
                            </div>
                        </button>
                    {{-- @endif --}}


                    <!-- 🎛️ TOMBOL TOGGLE PANEL AUDIT (Desktop & Mobile) -->
                    <button type="button" @click="isAuditOpen = !isAuditOpen"
                        class="group inline-flex items-center gap-2 p-2 text-sm font-semibold border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                        :title="isAuditOpen ? '{{ __('ui.tip.audit_trail_close') }}' : '{{ __('ui.tip.audit_trail_open') }}'"
                        :class="isAuditOpen ? 'bg-foresty text-goldy' : 'bg-white text-zinc-600'" >

                        {{-- 🔥 Ganti class menjadi group-hover:animate-tick --}}
                        <x-dynamic-component :component="'lucide-clock-fading'" class="h-5 w-5 origin-center group-hover:animate-tick" stroke-width="2" />

                        <span class="hidden 2xl:inline" x-text="isAuditOpen ? '{{ __('ui.button.close') }}' : '{{ __('ui.button.audit_trail') }}'"></span>
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
                                {{-- <div class="space-y-1.5" wire:ignore x-data="{
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
                                </div> --}}

                                {{-- @error('category_id')
                                    <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror --}}

                                {{-- Tags --}}
                                {{-- <div class="space-y-1.5" wire:ignore x-data="{
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
                                </div> --}}
                                {{-- @error('tags')
                                    <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror --}}

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

        {{-- <x-editor :editable="$canEdit"/> --}}
        <x-editor/>
        <livewire:link-selector />
        @include('components.editor.modal-audit')
        {{-- @include('components.editor.modal-thumbnail') --}}
    </form>
</div>