{{-- BASIC BUBBLE --}}
<div x-ref="bubbleMenuElement"
    class="absolute invisible opacity-0 bg-zinc-100 dark:bg-zinc-800 text-forest p-1 rounded-lg shadow-xl border border-zinc-700/50 z-50 flex items-center gap-1">

    <x-editor.toolbar-button command="toggleBold" activeName="bold" title="Tebal (Ctrl+B)"
        icon="bold" />

    <x-editor.toolbar-button command="toggleItalic" activeName="italic"
        title="Miring (Ctrl+I)" icon="italic" />

    <x-editor.toolbar-button command="toggleStrike" activeName="strike"
        title="Coretan (Ctrl+S)" icon="strikethrough" />

    <x-editor.toolbar-button command="toggleUnderline" activeName="underline"
        title="Garis Bawah (Ctrl+⇑+X)" icon="underline" />
</div>
