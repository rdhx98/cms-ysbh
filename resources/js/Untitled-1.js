// ... impor ekstensi lainnya tetap sama di atas ...
import Image from '@tiptap/extension-image'
import BubbleMenu from '@tiptap/extension-bubble-menu'

document.addEventListener('alpine:init', () => {
    window.setupEditor = function (wireModelName, wireComponent) {
        let editor

        return {
            updatedAt: Date.now(),

            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                editor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        StarterKit.configure({ codeBlock: false, link: false }),
                        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-forest underline' } }),

                        // ===== PERBAIKAN: EKSTENSI GAMBAR KUSTOM TANPA DUPLIKASI =====
                        Image.extend({
                            name: 'customImage', // <-- Beri nama unik agar tidak bentrok dengan ekstensi 'image' bawaan
                            addAttributes() {
                                return {
                                    ...this.parent?.(),
                                    src: { default: null },
                                    alt: { default: null },
                                    title: { default: null },
                                    style: {
                                        default: null,
                                        parseHTML: element => element.getAttribute('style'),
                                        renderHTML: attributes => {
                                            if (!attributes.style) return {}
                                            return { style: attributes.style }
                                        }
                                    }
                                }
                            }
                        }).configure({
                            inline: false,
                            HTMLAttributes: {
                                class: 'rounded-lg max-w-full my-4 mx-auto shadow-md transition-all'
                            },
                        }),

                        Table.configure({ resizable: true }),
                        TableRow, TableCell, TableHeader, TaskList, TaskItem, Placeholder, CodeBlockLowlight,

                        // Bubble Menu Utama untuk Teks
                        BubbleMenu.configure({
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: { duration: 150, moveTransition: 'transform 0.1s ease-out' },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false
                                if (editor.isActive('customImage')) return false // Sesuaikan ke nama baru
                                return true
                            }
                        }),

                        // Bubble Menu Kustom untuk Gambar
                        BubbleMenu.extend({
                            name: 'imageBubbleMenu',
                        }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: { placement: 'top', duration: 150, animation: 'fade' },
                            shouldShow: ({ editor }) => {
                                return editor.isActive('customImage') // Sesuaikan ke nama baru
                            }
                        })
                    ],
                    content: initialContent,
                    editorProps: {
                        handleDrop(view, event, slice, moved) {
                            if (!moved && event.dataTransfer?.files?.length > 0) {
                                _this.handleMultipleImageUpload(event.dataTransfer.files)
                                return true
                            }
                            return false
                        },
                        handlePaste(view, event) {
                            if (event.clipboardData?.files?.length > 0) {
                                _this.handleMultipleImageUpload(event.clipboardData.files)
                                return true
                            }
                            return false
                        }
                    },
                    onCreate() { _this.updatedAt = Date.now() },
                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now()
                        wireComponent.set(wireModelName, editor.getHTML(), false)
                    },
                    onSelectionUpdate() { _this.updatedAt = Date.now() }
                })

                this.$watch(`$wire.${wireModelName}`, (newContent) => {
                    if (!editor || editor.isFocused || newContent === editor.getHTML()) return
                    editor.commands.setContent(newContent || '', false)
                })
            },

            // ===== PERBAIKAN: ANTRIAN UPLOAD SECARA SINKRON =====
            async handleMultipleImageUpload(files) {
                const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))
                if (imageFiles.length === 0) return

                for (const file of imageFiles) {
                    try {
                        // Memaksa JavaScript menunggu Livewire benar-benar selesai mengunggah file sementara
                        await new Promise((resolve, reject) => {
                            wireComponent.upload('photo', file, resolve, reject)
                        })

                        // Eksekusi pemindahan ke public storage dan ambil URL-nya
                        const imageUrl = await wireComponent.uploadImage()

                        if (imageUrl) {
                            // Suntikkan gambar langsung ke posisi kursor, lalu paksa Tiptap fokus ulang
                            editor.chain().focus().setImage({ src: imageUrl }).run()
                            this.updatedAt = Date.now()
                        }
                    } catch (error) {
                        console.error("Gagal mengunggah:", error)
                        alert(`Gagal mengunggah gambar: ${file.name}`)
                    }
                }
            },

            triggerFileSelect() {
                this.$refs.fileInput.click()
            },

            setImageAlignment(alignment) {
                let marginClass = 'mx-auto block'
                if (alignment === 'left') marginClass = 'mr-auto ml-0 block'
                if (alignment === 'right') marginClass = 'ml-auto mr-0 block'

                editor.chain().focus().updateAttributes('customImage', { class: `rounded-lg max-w-full my-4 shadow-md transition-all ${marginClass}` }).run()
                this.updatedAt = Date.now()
            },

            setImageWidth(percentage) {
                editor.chain().focus().updateAttributes('customImage', { style: `width: ${percentage}%` }).run()
                this.updatedAt = Date.now()
            },

            isActive(type, opts = {}) {
                // Karena nama ekstensinya berubah, kita arahkan pengecekan ke 'customImage'
                if (type === 'image') type = 'customImage'
                return editor ? editor.isActive(type, opts) : false
            },

            runCommand(command, args = null) {
                if (!editor) return
                if (args !== null) {
                    editor.chain().focus()[command](args).run()
                } else {
                    editor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now()
            }
        }
    }
})
