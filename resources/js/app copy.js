import collapse from '@alpinejs/collapse';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

document.addEventListener('livewire:init', () => {
    // Gunakan Livewire.Alpine secara eksplisit agar lebih konsisten di Livewire v3
    const Alpine = Livewire.Alpine;

    // Daftarkan plugin collapse
    Alpine.plugin(collapse);

    // Daftarkan komponen TiptapEditor
    // KUNCI: Kita minta nama properti properti entangle Livewire-nya (misal: 'content')
    Alpine.data('tiptapEditor', (propertyName) => ({
        editor: null,
        updatedAt: Date.now(),

        init() {
            // 1. Ambil nilai awal dari Livewire component secara aman
            const initialContent = this.$wire.get(propertyName) || '';

            this.editor = new Editor({
                element: this.$refs.editorElement,
                extensions: [StarterKit],
                content: initialContent,

                onUpdate: ({ editor }) => {
                    // 2. KUNCI FIX: Kirim data hasil ketikan ke Livewire menggunakan $wire.set()
                    this.$wire.set(propertyName, editor.getHTML());
                    this.updatedAt = Date.now();
                },
                onSelectionUpdate: () => {
                    this.updatedAt = Date.now();
                }
            });

            // 3. KUNCI FIX: Awasi perubahan dari backend Livewire (misal jika data di-reset/diubah dari server)
            this.$watch(`$wire.${propertyName}`, (value) => {
                if (this.editor && value !== this.editor.getHTML()) {
                    this.editor.commands.setContent(value, false);
                }
            });
        },

        isActive(type, attributes = {}) {
            this.updatedAt;
            return this.editor ? this.editor.isActive(type, attributes) : false;
        },

        runCommand(command, ...args) {
            if (this.editor) {
                this.editor.chain().focus()[command](...args).run();
            }
        }
    }));

    console.log('✅ Alpine Plugins & Tiptap Component berhasil disuntikkan!');
});
