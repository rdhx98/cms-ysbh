import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'

document.addEventListener('alpine:init', () => {
    // Kita buat fungsi global baru sesuai dokumentasi resmi
    window.setupEditor = function (wireModelName, wireComponent) {
        // KUNCI EMAS: let editor di luar return agar TERBEBAS dari Proxy Alpine!
        let editor

        return {
            updatedAt: Date.now(),

            init() {
                const _this = this

                // Ambil nilai awal langsung dari Livewire secara aman
                const initialContent = wireComponent.get(wireModelName) || ''

                editor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [StarterKit],
                    content: initialContent,

                    onCreate() {
                        _this.updatedAt = Date.now()
                    },
                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now()
                        // Salin HTML ke Livewire secara pasif tanpa memicu re-render (silent update)
                        wireComponent.set(wireModelName, editor.getHTML(), false)
                    },
                    onSelectionUpdate() {
                        _this.updatedAt = Date.now()
                    }
                })

                // Mengawasi jika ada perubahan dari luar (misal form di-reset oleh backend PHP)
                this.$watch(`$wire.${wireModelName}`, (newContent) => {
                    if (!editor) return
                    if (newContent === editor.getHTML()) return

                    // Jangan over-write jika user sedang fokus mengetik
                    if (editor.isFocused) return

                    if (newContent === '' || newContent === null || newContent === undefined) {
                        editor.commands.setContent('', false)
                        return
                    }

                    editor.commands.setContent(newContent, false)
                })
            },

            // Fungsi helper yang mengeksekusi langsung variabel 'editor' lokal yang murni
            isActive(type, opts = {}) {
                if (!editor) return false
                return editor.isActive(type, opts)
            },

            runCommand(command, args = null) {
                if (!editor) return

                editor.view.focus()

                if (command === 'toggleHeading') {
                    editor.chain().focus().toggleHeading({ level: args }).run()
                } else {
                    editor.chain().focus()[command]().run()
                }

                this.updatedAt = Date.now()
            }
        }
    }
})
