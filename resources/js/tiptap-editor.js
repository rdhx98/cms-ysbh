import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import Placeholder from '@tiptap/extension-placeholder'
import Image from '@tiptap/extension-image'
import BubbleMenu from '@tiptap/extension-bubble-menu'
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'

// Pustaka Pewarnaan Kode Sintaksis
import { createLowlight, common } from 'lowlight'
const lowlight = createLowlight(common)

document.addEventListener('alpine:init', () => {
    window.setupEditor = function (wireModelName, wireComponent) {
        let editor // Terisolasi aman dari Proxy Alpine demi mencegah mismatched transaction

        return {
            updatedAt: Date.now(),

            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                editor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        // MATIKAN fitur bawaan yang akan kita timpa secara manual
                        StarterKit.configure({
                            codeBlock: false, // Menghilangkan duplikasi 'codeBlock'
                            link: false, // Tambahkan ini HANYA jika peringatan 'link' masih membandel muncul, karena kita akan menggantinya dengan konfigurasi Link yang lebih lengkap di bawah
                        }),

                        Link.configure({
                            openOnClick: false,
                            HTMLAttributes: { class: 'text-forest underline cursor-pointer' }
                        }),

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

                        Table.configure({
                            resizable: true,
                            HTMLAttributes: { class: 'border-collapse table-auto w-full my-4 text-sm' }
                        }),
                        TableRow,
                        TableCell.configure({
                            HTMLAttributes: { class: 'border border-zinc-300 dark:border-zinc-700 p-2 relative' }
                        }),
                        TableHeader.configure({
                            HTMLAttributes: { class: 'border border-zinc-300 dark:border-zinc-700 p-2 bg-zinc-100 dark:bg-zinc-800 font-bold' }
                        }),

                        TaskList,
                        TaskItem.configure({
                            nested: true,
                            HTMLAttributes: {
                                class: 'flex items-center gap-3 my-2 list-none'
                            }
                        }),

                        Placeholder.configure({
                            placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                            emptyEditorClass: 'is-editor-empty'
                        }),

                        // Ini yang menggantikan peran codeBlock bawaan StarterKit secara sempurna:
                        CodeBlockLowlight.configure({
                            lowlight,
                            HTMLAttributes: { class: 'rounded-lg bg-zinc-950 text-zinc-100 p-4 font-mono text-xs my-4 overflow-x-auto' }
                        }),

                        // 1. BUBBLE MENU UTAMA (UNTUK TEKS BIASA)
                        BubbleMenu.configure({
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: {
                                duration: 150,
                                moveTransition: 'transform 0.1s ease-out'
                            },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false
                                if (editor.isActive('image')) return false // Sembunyikan jika yang diklik adalah gambar
                                return true
                            }
                        }),

                        // 2. BUBBLE MENU KUSTOM (UNTUK GAMBAR) -> Ganti nama agar tidak konflik
                        BubbleMenu.extend({
                            name: 'imageBubbleMenu', // <-- TRIKNYA DI SINI: Ubah nama internal ekstensinya
                        }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: {
                                placement: 'top',
                                duration: 150,
                                animation: 'fade',
                            },
                            shouldShow: ({ editor }) => {
                                return editor.isActive('image')
                            }
                        })
                    ],
                    content: initialContent,

                    // ====== IMPLEMENTASI FILE HANDLER GRATIS ======
                    editorProps: {
                        // Menangani file yang ditarik-lepas (Drag & Drop) ke dalam area mengetik
                        handleDrop(view, event, slice, moved) {
                            if (!moved && event.dataTransfer?.files?.[0]) {
                                _this.handleImageUpload(event.dataTransfer.files[0])
                                return true // Blokir aksi default drop bawaan browser
                            }
                            return false
                        },
                        // Menangani file gambar hasil salinan dari clipboard (Paste / Ctrl+V)
                        handlePaste(view, event) {
                            if (event.clipboardData?.files?.[0]) {
                                // _this.handleImageUpload(event.clipboardData.files[0])
                                _this.handleMultipleImageUpload(event.clipboardData.files)
                                return true // Blokir aksi default paste bawaan browser
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

            // Fungsi Logika Pengolah File Gambar
            // handleImageUpload(file) {
            //     if (!file.type.startsWith('image/')) {
            //         alert('Hanya file gambar yang diperbolehkan!')
            //         return
            //     }

            //     const reader = new FileReader()
            //     reader.onload = () => {
            //         // Masukkan string Base64 gambar langsung ke dalam posisi kursor aktif saat ini
            //         editor.chain().focus().setImage({ src: reader.result }).run()
            //     }
            //     reader.readAsDataURL(file)
            // },

            // ====== LOGIKA UPLOAD GAMBAR BARU (SERVER-SIDE) V2 ======
            // handleImageUpload(file) {
            //     if (!file.type.startsWith('image/')) {
            //         alert('Hanya file gambar yang diperbolehkan!')
            //         return
            //     }

            //     this.isUploading = true;

            //     // Menggunakan API upload bawaan Livewire
            //     wireComponent.upload(
            //         'photo', // Mengarah ke public $photo di server
            //         file,
            //         // Callback Berhasil
            //         async () => {
            //             // Jalankan method di backend untuk memproses penyimpanan dan mengambil URL publik
            //             const imageUrl = await wireComponent.uploadImage();

            //             if (imageUrl) {
            //                 // Masukkan URL gambar asli hasil upload ke posisi kursor editor
            //                 editor.chain().focus().setImage({ src: imageUrl }).run();
            //                 this.updatedAt = Date.now();
            //             }
            //             this.isUploading = false;
            //         },
            //         // Callback Gagal
            //         () => {
            //             alert('Gagal mengunggah gambar. Pastikan ukuran di bawah 2MB.');
            //             this.isUploading = false;
            //         }
            //     );
            // },

            // 3. LOGIKA UNTUK MENANGANI BEBERAPA GAMBAR SEKALIGUS (LOOPING MULTIPLE UPLOAD)
            async handleMultipleImageUpload(files) {
                const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))

                if (imageFiles.length === 0) return

                // Proses upload satu per satu secara asynchronous bergantian
                for (const file of imageFiles) {
                    try {
                        // Upload file ke temporary Livewire storage
                        await wireComponent.upload('photo', file)

                        // Eksekusi method backend untuk memindahkan ke public storage & dapatkan URL-nya
                        const imageUrl = await wireComponent.uploadImage()

                        if (imageUrl) {
                            // Masukkan gambar ke editor di posisi kursor aktif
                            editor.chain().focus().setImage({ src: imageUrl }).run()
                            this.updatedAt = Date.now()
                        }
                    } catch (error) {
                        alert(`Gagal mengunggah gambar: ${file.name}`)
                    }
                }
            },

            // Fungsi pembantu jika user ingin klik tombol toolbar (bukan drag/paste)
            triggerFileSelect() {
                this.$refs.fileInput.click();
            },
            // 4. KONTROL UKURAN & POSISI VIA JAVASCRIPT COMMANDS
            setImageAlignment(alignment) {
                // Tiptap Image secara default menggunakan textAlign atau manipulasi class Tailwind
                let marginClass = 'mx-auto block' // Default Center
                if (alignment === 'left') marginClass = 'mr-auto ml-0 block'
                if (alignment === 'right') marginClass = 'ml-auto mr-0 block'

                editor.chain().focus().updateAttributes('image', { class: `rounded-lg max-w-full my-4 shadow-md transition-all ${marginClass}` }).run()
                this.updatedAt = Date.now()
            },

            setImageWidth(percentage) {
                // Menyuntikkan style width langsung ke atribut gambar yang sedang aktif
                editor.chain().focus().updateAttributes('image', { style: `width: ${percentage}%` }).run()
                this.updatedAt = Date.now()

                // editor.chain().focus().updateAttributes('image', { style: `width: ${percentage}%` }).run()
                // this.updatedAt = Date.now()
            },

            // Pembantu pengecekan node aktif
            isNodeActive(type, attrs = {}) {
                return editor ? editor.isActive(type, attrs) : false
            },

            isActive(type, opts = {}) {
                return editor ? editor.isActive(type, opts) : false
            },

            runCommand(command, args = null) {
                if (!editor) return
                editor.view.focus()

                if (command === 'toggleHeading') {
                    editor.chain().focus().toggleHeading({ level: args }).run()
                } else if (command === 'setLink') {
                    const url = prompt('Masukkan URL tautan:', 'https://')
                    if (url) editor.chain().focus().setLink({ href: url }).run()
                } else if (command === 'insertTable') {
                    editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
                } else {
                    editor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now()
            }
        }
    }
})
