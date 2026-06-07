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
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import Image from '@tiptap/extension-image'
import BubbleMenu from '@tiptap/extension-bubble-menu'

// Pustaka Pewarnaan Kode Sintaksis
import { createLowlight, common } from 'lowlight'
const lowlight = createLowlight(common)

document.addEventListener('alpine:init', () => {
    // KUNCI UTAMA: Amankan instance editor di luar objek reactive Alpine
    let _rawEditor = null;

    window.setupEditor = function (wireModelName, wireComponent) {
        return {
            updatedAt: Date.now(),

            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                // Buat instance murni tanpa terkena Proxy dari return Alpine
                _rawEditor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        StarterKit.configure({
                            codeBlock: false,
                            link: false,
                        }),

                        Link.configure({
                            openOnClick: false,
                            HTMLAttributes: { class: 'text-forest underline cursor-pointer' }
                        }),

                        Image.configure({
                            inline: false,
                            allowBase64: true,
                            HTMLAttributes: {
                                class: 'rounded-lg max-w-full my-4 mx-auto shadow-md transition-all block',
                            },
                        }).extend({
                            addAttributes() {
                                return {
                                    ...this.parent?.(),
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
                            HTMLAttributes: { class: 'flex items-center gap-3 my-2 list-none' }
                        }),

                        Placeholder.configure({
                            placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                            emptyEditorClass: 'is-editor-empty'
                        }),

                        CodeBlockLowlight.configure({
                            lowlight,
                            HTMLAttributes: { class: 'rounded-lg bg-zinc-950 text-zinc-100 p-4 font-mono text-xs my-4 overflow-x-auto' }
                        }),

                        BubbleMenu.configure({
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: { duration: 150, moveTransition: 'transform 0.1s ease-out' },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false
                                if (editor.isActive('image')) return false
                                return true
                            }
                        }),

                        BubbleMenu.extend({ name: 'imageBubbleMenu' }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: { placement: 'top', duration: 150, animation: 'fade' },
                            shouldShow: ({ editor }) => {
                                return editor.isActive('image')
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
                    if (!_rawEditor || _rawEditor.isFocused || newContent === _rawEditor.getHTML()) return
                    _rawEditor.commands.setContent(newContent || '', false)
                })
            },

            // ===== LOGIKA AMAN UPLOAD TANPA PROXY INTERFERENSI =====
            // async handleMultipleImageUpload(files) {
            //     const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))
            //     if (imageFiles.length === 0) return

            //     console.log(`[Tiptap Upload] Memulai antrean unggah untuk ${imageFiles.length} gambar.`);

            //     for (let i = 0; i < imageFiles.length; i++) {
            //         const file = imageFiles[i];
            //         console.log(`%c[Antrean ${i + 1}/${imageFiles.length}] Memproses file: ${file.name}`, 'color: #3b82f6; font-weight: bold;');

            //         try {
            //             await new Promise((resolve, reject) => {
            //                 wireComponent.upload('photo', file, resolve, reject);
            //             });

            //             const imageUrl = await wireComponent.uploadImage();

            //             if (imageUrl && _rawEditor) {
            //                 console.log(`%c   ✅ Berhasil diunggah! URL Server: ${imageUrl}`, 'color: #10b981; font-weight: bold;');

            //                 // Gunakan _rawEditor secara langsung untuk memintas Proxy Alpine
            //                 _rawEditor.chain()
            //                     .focus()
            //                     .setImage({ src: imageUrl })
            //                     .run();

            //                 this.updatedAt = Date.now();
            //                 console.log("Isi HTML Editor Saat Ini:", _rawEditor.getHTML());
            //             }
            //         } catch (error) {
            //             console.error(`❌ Gagal memproses gambar (${file.name}):`, error);
            //             alert(`Gagal mengunggah gambar [${file.name}].`);
            //             continue;
            //         }
            //     }
            // },

            // ===== PERBAIKAN LOGIKA ANTREAN DAN MOVEMENT KURSOR =====
            async handleMultipleImageUpload(files) {
                const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))
                if (imageFiles.length === 0) return

                console.log(`[Tiptap Upload] Memulai antrean unggah untuk ${imageFiles.length} gambar.`);

                for (let i = 0; i < imageFiles.length; i++) {
                    const file = imageFiles[i];

                    try {
                        // Tambahkan jeda sejenak agar request asinkron Livewire tidak tumpang tindih
                        await new Promise(resolve => setTimeout(resolve, 150));

                        await new Promise((resolve, reject) => {
                            wireComponent.upload('photo', file, resolve, reject);
                        });

                        const imageUrl = await wireComponent.uploadImage();

                        if (imageUrl && _rawEditor) {
                            // MASUKKAN GAMBAR, LALU BUAT PARAGRAF BARU DI BAWAHNYA, DAN PINDAHKAN FOKUS KURSOR KESANA
                            _rawEditor.chain()
                                .focus()
                                .setImage({ src: imageUrl })
                                .insertContent('<p></p>') // Membuat penampung baris baru agar loop berikutnya tidak menimpa gambar ini
                                .run();

                            // Paksa Alpine memperbarui state visual UI
                            this.updatedAt = Date.now();
                        }
                    } catch (error) {
                        console.error(`❌ Gagal memproses gambar (${file.name}):`, error);
                        continue;
                    }
                }
            },

            triggerFileSelect() {
                this.$refs.fileInput.click()
            },

            setImageAlignment(alignment) {
                if (!_rawEditor) return
                let marginClass = 'mx-auto block'
                if (alignment === 'left') marginClass = 'mr-auto ml-0 block'
                if (alignment === 'right') marginClass = 'ml-auto mr-0 block'

                _rawEditor.chain().focus().updateAttributes('image', { class: `rounded-lg max-w-full my-4 shadow-md transition-all ${marginClass}` }).run()
                this.updatedAt = Date.now()
            },

            setImageWidth(percentage) {
                if (!_rawEditor) return
                _rawEditor.chain().focus().updateAttributes('image', { style: `width: ${percentage}%` }).run()
                this.updatedAt = Date.now()
            },

            isActive(type, opts = {}) {
                this.updatedAt; // Memaksa re-evaluasi saat properti ini diakses
                return _rawEditor ? _rawEditor.isActive(type, opts) : false
            },

            runCommand(command, args = null) {
                if (!_rawEditor) return
                if (args !== null) {
                    _rawEditor.chain().focus()[command](args).run()
                } else {
                    _rawEditor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now()
            }
        }
    }
})
