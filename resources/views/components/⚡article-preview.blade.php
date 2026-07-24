<?php

use Livewire\Component;

use Carbon\Carbon;

use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;

new class extends Component
{
    //
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
};
?>
{{--
<x-main-wrapper isPreview="true">
    <div class="">Header</div>
    <x-slot:title>{!! $article->title !!}</x-slot:title>
        {!! $article->content !!}
</x-main-wrapper> --}}
<x-main-wrapper isPreview="true">

    <x-slot:title>{!! $article->title !!}</x-slot:title>

    <!-- Ini akan masuk ke bagian Atas (Berdiri Sendiri) -->
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-forest">Pratinjau: {{ $article->title }}</h1>
            <a href="{{ route('article.index') }}" wire:navigate class="px-4 py-2 text-sm bg-white border border-forest rounded-lg hover:bg-forest hover:text-white transition-colors">
                Kembali
            </a>
        </div>
    </x-slot:header>

    <!-- Ini akan otomatis masuk ke bagian Bawah (di dalam <article class="tiptap">) -->
    {!! $article->content !!}

</x-main-wrapper>
