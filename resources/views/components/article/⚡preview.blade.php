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

    public function mount($post = null) {
        // 1. JIKA ADA PARAMETER DI URL (Masuk Mode Edit)
        if ($post) {
            $this->article = Post::where('slug', $post)->firstOrFail();
        }
    }
};
?>

<div class="w-full h-[calc(100vh-4rem)] flex flex-col pt-2 md:pt-0 bg-paper rounded-lg border-2 border-forest overflow-y-auto "  >
    {{-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama --}}
    {{-- {{!! $article->content !!}} --}}
    {{-- {{ $article->content }} --}}
    <article class="prose prose-lg prose-zinc h-full prose-img:rounded-xl prose-img:shadow-sm prose-a:text-forest prose-headings:font-display max-w-7xl mx-auto my-8">
        {!! $article->content !!}
    </article>
</div>
