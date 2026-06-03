import collapse from '@alpinejs/collapse';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

// Tunggu sampai Livewire selesai menginisialisasi Alpine bawaannya
document.addEventListener('livewire:init', () => {
    const Alpine = window.Alpine;

    // Daftarkan plugin ke instance Alpine milik Livewire
    Alpine.plugin(collapse);

    // Daftarkan komponen TiptapEditor
    Alpine.data('tiptapEditor', (wireModel) => ({
        editor: null,
        updatedAt: Date.now(),

        init() {
            this.editor = new Editor({
                element: this.$refs.editorElement,
                extensions: [StarterKit],
                content: wireModel,

                onUpdate: ({ editor }) => {
                    wireModel = editor.getHTML();
                    this.updatedAt = Date.now();
                },
                onSelectionUpdate: () => {
                    this.updatedAt = Date.now();
                }
            });

            this.$watch('wireModel', (value) => {
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
});
