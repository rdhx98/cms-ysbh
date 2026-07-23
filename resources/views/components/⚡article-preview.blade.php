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

{{-- <div class="w-full h-[calc(100vh-4rem)] flex flex-col pt-2 md:pt-0 bg-paper rounded-lg border-2 border-forest overflow-y-auto overflow-x-hidden px-6 lg:px-12"  >
    <article class="prose prose-zinc h-full prose-img:rounded-xl prose-img:shadow-sm prose-a:text-forest prose-headings:font-display max-w-7xl mx-auto my-8">
        {!! $article->content !!}
    </article>
</div> --}}

<div class="w-full h-[calc(100vh-4rem)] flex flex-col pt-2 md:pt-0 bg-paper rounded-lg border-2 border-forest overflow-y-auto overflow-x-hidden px-6 lg:px-12">
    <x-slot:title>{!! $article->title !!}</x-slot:title>
    <!-- SEBELUMNYA: -->
    <!-- <article class="prose prose-zinc h-full prose-img:rounded-xl prose-img:shadow-sm prose-a:text-forest prose-headings:font-display max-w-7xl mx-auto my-8"> -->

    <!-- SESUDAHNYA: -->
    <article class="tiptap h-full max-w-7xl mx-auto my-8 [&_img]:rounded-xl [&_img]:shadow-sm [&_a]:text-forest">
        {!! $article->content !!}
    </article>
</div>
