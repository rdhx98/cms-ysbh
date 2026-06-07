<?php

use Livewire\Component;

new class extends Component
{
    public string $content = '';

    public function save()
    {
        $this->validate([
            'content' => 'required|min:10',
        ]);

        // Proses simpan ke MariaDB bisa ditaruh di sini

        session()->flash('message', 'Artikel berhasil disimpan!');
    }
};
?>

<div class="max-w-5xl mx-auto p-6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">

    <form wire:submit.prevent="save" class="space-y-6">

        <div
            x-data="setupEditor('content', $wire)"
            class="w-full border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative"
            wire:ignore
        >

            <div
                x-ref="bubbleMenuElement"
                class="flex items-center gap-1 bg-zinc-900 text-white rounded-lg p-1 shadow-xl border border-zinc-700 text-xs select-none"
            >
                <button type="button" @click="runCommand('toggleBold')" class="px-2 py-1 rounded font-bold hover:bg-zinc-800">B</button>
                <button type="button" @click="runCommand('toggleItalic')" class="px-2 py-1 rounded italic hover:bg-zinc-800">I</button>
                <button type="button" @click="runCommand('setLink')" class="px-2 py-1 rounded hover:bg-zinc-800">🔗 Link</button>
            </div>

            <div class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                <button type="button" @click="runCommand('toggleBold')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bold', updatedAt) }" class="px-3 py-1.5 rounded font-bold text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">B</button>
                <button type="button" @click="runCommand('toggleItalic')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('italic', updatedAt) }" class="px-3 py-1.5 rounded italic text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">I</button>
                <button type="button" @click="runCommand('toggleStrike')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('strike', updatedAt) }" class="px-3 py-1.5 rounded line-through text-sm cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">S</button>

                <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                <button type="button" @click="runCommand('toggleHeading', 1)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 1 }, updatedAt) }" class="px-2 py-1.5 rounded font-black text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H1</button>
                <button type="button" @click="runCommand('toggleHeading', 2)" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('heading', { level: 2 }, updatedAt) }" class="px-2 py-1.5 rounded font-extrabold text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">H2</button>

                <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                <button type="button" @click="runCommand('toggleBulletList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('bulletList', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">• List</button>
                <button type="button" @click="runCommand('toggleOrderedList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('orderedList', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">1. List</button>
                <button type="button" @click="runCommand('toggleTaskList')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('taskList', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">☑ Task</button>
                <button type="button" @click="runCommand('toggleBlockquote')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('blockquote', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs italic cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">“ Quote</button>
                <button type="button" @click="runCommand('toggleCodeBlock')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('codeBlock', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs font-mono cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">&lt;/&gt; Code</button>

                <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                <button type="button" @click="runCommand('setLink')" :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white': isActive('link', updatedAt) }" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">🔗 Link</button>
                <button type="button" @click="runCommand('insertTable')" class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">📊 +Table</button>

                <template x-if="isActive('table', updatedAt)">
                    <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded ml-2">
                        <button type="button" @click="runCommand('addColumnAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Col</button>
                        <button type="button" @click="runCommand('addRowAfter')" class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Row</button>
                        <button type="button" @click="runCommand('deleteTable')" class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                    </div>
                </template>
            </div>

            <div
                x-ref="editorElement"
                class="prose prose-zinc dark:prose-invert max-w-none p-6 min-h-[400px] dark:text-zinc-100 focus:outline-none"
            ></div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm shadow cursor-pointer">
                {{ __('Simpan Artikel') }}
            </button>
        </div>
    </form>
</div>

<style>
    /* Mengatur Teks Placeholder */
    .tiptap p.is-editor-empty:first-child::before {
        color: #a1a1aa;
        content: attr(data-placeholder);
        float: left;
        height: 0;
        pointer-events: none;
    }

    /* Mengatur Layout Checkbox Task List */
    .tiptap ul[data-type="taskList"] {
        list-style: none;
        padding: 0;
    }
    .tiptap ul[data-type="taskList"] li {
        display: flex;
        align-items: flex-start;
    }
    .tiptap ul[data-type="taskList"] li > label {
        flex: 0 0 auto;
        margin-right: 0.5rem;
        user-select: none;
    }
    .tiptap ul[data-type="taskList"] li > div {
        flex: 1 1 auto;
    }
    .tiptap ul[data-type="taskList"] input[type="checkbox"] {
        cursor: pointer;
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
        border: 1px solid #d1d5db;
    }
</style>

{{-- <div class="max-w-4xl mx-auto p-6">
    <form wire:submit.prevent="save" class="space-y-6">

        <div
            x-data="setupEditor('content', $wire)"
            class="w-full border border-zinc-300 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-900 shadow-sm"
            wire:ignore
        >
            <div class="flex flex-wrap items-center gap-1 bg-zinc-50 dark:bg-zinc-800 p-2 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10 select-none">

                <button
                    type="button"
                    @click="runCommand('toggleBold')"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('bold', updatedAt) }"
                    class="px-3 py-1.5 rounded font-bold text-sm cursor-pointer text-zinc-600 dark:text-zinc-400"
                >B</button>

                <button
                    type="button"
                    @click="runCommand('toggleItalic')"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('italic', updatedAt) }"
                    class="px-3 py-1.5 rounded italic text-sm cursor-pointer text-zinc-600 dark:text-zinc-400"
                >I</button>

                <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                <button
                    type="button"
                    @click="runCommand('toggleHeading', 1)"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('heading', { level: 1 }, updatedAt) }"
                    class="px-2 py-1.5 rounded font-black text-xs cursor-pointer text-zinc-600 dark:text-zinc-400"
                >H1</button>

                <button
                    type="button"
                    @click="runCommand('toggleHeading', 2)"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('heading', { level: 2 }, updatedAt) }"
                    class="px-2 py-1.5 rounded font-extrabold text-xs cursor-pointer text-zinc-600 dark:text-zinc-400"
                >H2</button>

                <div class="h-5 w-[1px] bg-zinc-300 dark:bg-zinc-600 mx-1"></div>

                <button
                    type="button"
                    @click="runCommand('toggleBulletList')"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('bulletList', updatedAt) }"
                    class="px-2.5 py-1.5 rounded text-xs font-medium cursor-pointer text-zinc-600 dark:text-zinc-400"
                >• List</button>

                <button
                    type="button"
                    @click="runCommand('toggleBlockquote')"
                    :class="{ 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:white': isActive('blockquote', updatedAt) }"
                    class="px-2.5 py-1.5 rounded text-xs italic cursor-pointer text-zinc-600 dark:text-zinc-400"
                >“ Quote</button>
            </div>

            <div
                x-ref="editorElement"
                class="prose prose-zinc dark:prose-invert max-w-none p-5 min-h-[350px] dark:text-zinc-100 focus:outline-none"
            ></div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2.5 bg-forest hover:bg-forest/90 text-white font-medium rounded-lg text-sm cursor-pointer">
                {{ __('Publish Article') }}
            </button>
        </div>
    </form>
</div> --}}
