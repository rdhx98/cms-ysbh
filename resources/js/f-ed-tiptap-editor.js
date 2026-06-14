import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Image from '@tiptap/extension-image'
import BubbleMenu from '@tiptap/extension-bubble-menu'
import Placeholder from '@tiptap/extension-placeholder'
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'

import { createLowlight, common } from 'lowlight'
const lowlight = createLowlight(common)

const CustomImage = Image.extend({
    name: 'image', // this new line
    group: 'block',
    inline: false,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            // Mengambil attribute bawaan asli (src, alt, title) agar tidak hilang
            ...this.parent?.(),

            // Override / Tambahkan attribute class kustom milikmu
            class: {
                default: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
                parseHTML: element => element.getAttribute('class'),
                renderHTML: attributes => ({ class: attributes.class })
            },
            // Override / Tambahkan attribute style kustom milikmu
            style: {
                default: 'width: 25%; display: block; margin-left: auto; margin-right: auto;',
                parseHTML: element => element.getAttribute('style'),
                renderHTML: attributes => attributes.style ? { style: attributes.style } : {}
            },
        }
        // return {
        //     // Ijinkan src bawaan
        //     src: {
        //         default: null,
        //         parseHTML: element => element.getAttribute('src'),
        //         renderHTML: attributes => attributes.src ? { src: attributes.src } : {}
        //     },
        //     // Ijinkan class CSS
        //     class: {
        //         default: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
        //         parseHTML: element => element.getAttribute('class'),
        //         renderHTML: attributes => ({ class: attributes.class })
        //     },
        //     // Ijinkan inline style untuk resize/alignment
        //     style: {
        //         default: 'width: 25%; display: block; margin-left: auto; margin-right: auto;',
        //         parseHTML: element => element.getAttribute('style'),
        //         renderHTML: attributes => attributes.style ? { style: attributes.style } : {}
        //     },
        // }
    },
})
// =========================================================================
// OLD DEFINISI CUSTOM IMAGE NODE (BLOCK NODE - AMAN BAGI SKEMA)
// =========================================================================
// const CustomImage = Image.extend({
//     group: 'block',
//     inline: false,
//     selectable: true,
//     draggable: true,

//     addAttributes() {
//         return {
//             ...this.parent?.(),
//             class: {
//                 default: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
//                 parseHTML: element => element.getAttribute('class'),
//                 renderHTML: attributes => ({ class: attributes.class })
//             },
//             style: {
//                 default: 'width: 25%; display: block; margin-left: auto; margin-right: auto;',
//                 parseHTML: element => element.getAttribute('style'),
//                 renderHTML: attributes => attributes.style ? { style: attributes.style } : {}
//             },
//         }
//     },
// })


// =========================================================================
// INITIALISASI ALPINE JS COMPONENT
// =========================================================================
document.addEventListener('alpine:init', () => {
    window.tiptapEditor = null;

    window.setupEditor = function (wireModelName, wireComponent) {
        return {
            updatedAt: Date.now(),
            uploadQueue: [],
            isUploading: false,
            draftKey: 'cms_draft_artikel_ysbh',
            showMarks: false, // Memperbaiki error showMarks is not defined sebelumnya

            // --- REAKTIVITAS INDIKATOR TOMBOL STYLE GAMBAR ---
            imageHasStyle(searchString) {
                this.updatedAt;
                if (!window.tiptapEditor) return false;

                const attrs = window.tiptapEditor.getAttributes('image');
                const currentStyle = attrs?.style || '';

                if (!attrs || !attrs.style) {
                    if (searchString === '25%') return true;
                    if (searchString === 'center') return true;
                    return false;
                }

                if (searchString.includes('%')) {
                    return new RegExp(`width:\\s*${searchString.replace('%', '')}%`).test(currentStyle);
                }

                if (searchString === 'left') return currentStyle.includes('float: left');
                if (searchString === 'right') return currentStyle.includes('float: right');
                if (searchString === 'center') {
                    return currentStyle.includes('margin-left: auto') && !currentStyle.includes('float:');
                }

                return false;
            },

            // --- INISIALISASI UTAMA EDITOR ---
            init() {
                const _this = this;
                const initialContent = wireComponent.get(wireModelName) || '';

                console.log("[Tiptap-Alpine] Menginisialisasi editor komponen...");

                window.addEventListener('unhandledrejection', (event) => {
                    if (this.isUploading && event.reason?.message?.includes('JSON')) {
                        console.warn("[Sistem Penyelamat] Reset crash upload otomatis berjalan...");
                        event.preventDefault();
                        wireComponent.set('photo', null);
                        setTimeout(() => {
                            this.isUploading = false;
                            this.processNextInQueue();
                        }, 500);
                    }
                });

                window.tiptapEditor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        StarterKit.configure({ codeBlock: false, link: false }),
                        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-forest underline' } }),
                        TextAlign.configure({ types: ['heading', 'paragraph'] }),
                        Table.configure({ resizable: true }),
                        TableRow, TableCell, TableHeader, TaskList, TaskItem,
                        CodeBlockLowlight.configure({ lowlight }),
                        Placeholder.configure({
                            placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                            emptyEditorClass: 'is-editor-empty'
                        }),

                        CustomImage.configure({ allowBase64: true }),

                        // Bubble Menu Teks
                        BubbleMenu.configure({
                            pluginKey: 'textBubbleMenu',
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: { duration: 150, zIndex: 99 },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false;
                                return !editor.isActive('image');
                            }
                        }),

                        // Bubble Menu Gambar (Siklus kelas utilitas disesuaikan)
                        BubbleMenu.extend({ name: 'imageBubbleMenu' }).configure({
                            pluginKey: 'imageBubbleMenu',
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: {
                                placement: 'top',
                                duration: 150,
                                zIndex: 99,
                                appendTo: 'body',
                                onMount(instance) {
                                    const el = instance.reference;
                                    if (el) {
                                        el.classList.remove('hidden');
                                        el.style.display = 'flex';
                                    }
                                },
                                onShow(instance) {
                                    const el = instance.reference;
                                    if (el) {
                                        el.classList.remove('hidden');
                                        el.style.display = 'flex';
                                    }
                                },
                                onHidden(instance) {
                                    const el = instance.reference;
                                    if (el) {
                                        el.style.display = 'none';
                                        el.classList.add('hidden');
                                    }
                                }
                            },
                            shouldShow: ({ editor }) => editor.isActive('image')
                        })
                    ],
                    content: initialContent,
                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now();
                        if (_this.isUploading) return;
                        wireComponent.set(wireModelName, editor.getHTML(), false);
                    },
                    onSelectionUpdate() {
                        _this.updatedAt = Date.now();
                    }
                });

                this.$watch(`$wire.${wireModelName}`, (newContent) => {
                    if (!window.tiptapEditor || window.tiptapEditor.isFocused || newContent === window.tiptapEditor.getHTML()) return;
                    window.tiptapEditor.commands.setContent(newContent || '', false);
                });
            },

            // =========================================================================
            // CORE HANDLER UPLOAD GAMBAR (DENGAN LOG DETEKSI KETat)
            // =========================================================================
            handleMultipleImageUpload(files) {
                console.log("[Upload Log 1] Fungsi handleMultipleImageUpload terpicu!", files);

                if (!files || files.length === 0) {
                    console.warn("[Upload Log 1] File kosong atau tidak terbaca.");
                    return;
                }

                const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
                console.log("[Upload Log 2] File setelah difilter tipe gambar:", imageFiles);

                if (imageFiles.length === 0) {
                    console.warn("[Upload Log 2] Tidak ada file bertipe gambar terdeteksi.");
                    return;
                }

                // Inisialisasi antrean jika belum ada, lalu push data baru
                if (!this.uploadQueue) this.uploadQueue = [];
                this.uploadQueue.push(...imageFiles);
                console.log("[Upload Log 3] Isi Antrean Saat Ini:", this.uploadQueue);

                if (!this.isUploading) {
                    console.log("[Upload Log 4] Status isUploading FALSE, memulai pemrosesan antrean...");
                    this.processNextInQueue();
                } else {
                    console.log("[Upload Log 4] Status isUploading TRUE, menunggu giliran antrean berjalan.");
                }
            },

            async processNextInQueue() {
                console.log("[Antrean Log A] Menjalankan processNextInQueue...");

                if (!this.uploadQueue || this.uploadQueue.length === 0) {
                    console.log("[Antrean Log B] Antrean habis. Menghentikan proses upload.");
                    this.isUploading = false;
                    if (window.tiptapEditor) {
                        wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                    }
                    this.updatedAt = Date.now();
                    return;
                }

                this.isUploading = true;
                const nextFile = this.uploadQueue.shift();
                console.log("[Antrean Log C] Memproses file berikutnya:", nextFile.name);

                try {
                    const reader = new FileReader();

                    reader.onload = (e) => {
                        console.log(`[Antrean Log D] FileReader sukses membaca data base64 untuk file: ${nextFile.name}`);
                        const base64Url = e.target.result;

                        if (window.tiptapEditor) {
                            console.log("[Antrean Log E] Menginjeksikan gambar ke dalam Tiptap Editor...");

                            // 1. Amankan fokus editor
                            window.tiptapEditor.commands.focus();

                            // 2. Suntikkan langsung sebagai Objek Node (Solusi Telak untuk Block Node)
                            window.tiptapEditor.commands.insertContent({
                                type: 'image', // Mengacu pada name: 'image' di CustomImage kamu
                                attrs: {
                                    src: base64Url,
                                    alt: nextFile.name,
                                    title: nextFile.name,
                                    class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
                                    style: 'width: 25%; display: block; margin-left: auto; margin-right: auto;'
                                }
                            });

                            // 3. Buat baris kosong baru di bawahnya agar kursor turun
                            window.tiptapEditor.commands.insertContent('<p></p>');

                            console.log("[Antrean Log F] Gambar berhasil diinjeksikan.");
                            console.log("[Isi HTML Editor Saat Ini]:", window.tiptapEditor.getHTML());

                            setTimeout(() => {
                                if(window.tiptapEditor) window.tiptapEditor.commands.scrollIntoView();
                            }, 50);
                        }

                        this.updatedAt = Date.now();
                        setTimeout(() => { this.processNextInQueue(); }, 30);
                    };

                    // another f in OLD
                    // reader.onload = (e) => {
                    //     console.log(`[Antrean Log D] FileReader sukses membaca data base64 untuk file: ${nextFile.name}`);
                    //     const base64Url = e.target.result;

                    //     if (window.tiptapEditor) {
                    //         console.log("[Antrean Log E] Menginjeksikan gambar ke dalam Tiptap Editor...");

                    //         // Amankan focus editor terlebih dahulu
                    //         window.tiptapEditor.commands.focus();

                    //         // STRATEGI BARU: Injeksi string HTML mentah agar parser Tiptap membaca langsung lewat parseHTML bawaan
                    //         const htmlString = `<img src="${base64Url}" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block" style="width: 25%; display: block; margin-left: auto; margin-right: auto;">`;

                    //         // Jalankan perintah insert tunggal tanpa chaining panjang
                    //         window.tiptapEditor.commands.insertContent(htmlString);

                    //         // Tambahkan paragraf baru di bawahnya agar kursor turun
                    //         window.tiptapEditor.commands.insertContent('<p></p>');

                    //         console.log("[Antrean Log F] Gambar berhasil diinjeksikan.");
                    //         console.log("[Isi HTML Editor Saat Ini]:", window.tiptapEditor.getHTML());

                    //         setTimeout(() => {
                    //             if(window.tiptapEditor) window.tiptapEditor.commands.scrollIntoView();
                    //         }, 50);
                    //     }

                    //     this.updatedAt = Date.now();
                    //     setTimeout(() => { this.processNextInQueue(); }, 30);
                    // };

                    // another OLD
                    // reader.onload = (e) => {
                    //     console.log(`[Antrean Log D] FileReader sukses membaca data base64 untuk file: ${nextFile.name}`);
                    //     const base64Url = e.target.result;

                    //     if (window.tiptapEditor) {
                    //         console.log("[Antrean Log E] Menginjeksikan gambar ke dalam Tiptap Editor...");
                    //         window.tiptapEditor.commands.focus();

                    //         // MASUKKAN KONTEN DENGAN ATRIBUT YANG VALID SESUAI SKEMA DI ATAS
                    //         window.tiptapEditor.chain()
                    //             .insertContent({
                    //                 type: 'image',
                    //                 attrs: {
                    //                     src: base64Url,
                    //                     class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
                    //                     style: 'width: 25%; display: block; margin-left: auto; margin-right: auto;'
                    //                 }
                    //             })
                    //             .insertContent({ type: 'paragraph' }) // Membuat baris baru kosong di bawahnya
                    //             .run();
                    //             // .insertContent({
                    //             //     type: 'image',
                    //             //     attrs: {
                    //             //         src: base64Url,
                    //             //         class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block',
                    //             //         style: 'width: 25%; display: block; margin-left: auto; margin-right: auto;'
                    //             //     }
                    //             // })
                    //             // .insertContent({ type: 'paragraph' }) // Buat baris baru di bawahnya
                    //             // .run();

                    //         console.log("[Antrean Log F] Gambar berhasil diinjeksikan.");
                    //         console.log("[Isi HTML Editor Saat Ini]:", window.tiptapEditor.getHTML());

                    //         setTimeout(() => {
                    //             if(window.tiptapEditor) window.tiptapEditor.commands.scrollIntoView();
                    //         }, 50);
                    //     }

                    //     this.updatedAt = Date.now();
                    //     setTimeout(() => { this.processNextInQueue(); }, 30);
                    // };
                    // OLD Menggunakan arrow function agar context scope `this` menunjuk langsung ke objek Alpine
                    // reader.onload = (e) => {
                    //     console.log(`[Antrean Log D] FileReader sukses membaca data base64 untuk file: ${nextFile.name}`);
                    //     const base64Url = e.target.result;

                    //     if (window.tiptapEditor) {
                    //         console.log("[Antrean Log E] Menginjeksikan gambar ke dalam Tiptap Editor...");
                    //         window.tiptapEditor.commands.focus();

                    //         window.tiptapEditor.chain()
                    //             .insertContent({
                    //                 type: 'image',
                    //                 attrs: {
                    //                     src: base64Url,
                    //                     style: 'width: 25%; display: block; margin-left: auto; margin-right: auto;',
                    //                     class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image block'
                    //                 }
                    //             })
                    //             .insertContent({ type: 'paragraph' }) // Baris paragraf baru di bawah gambar
                    //             .run();

                    //         console.log("[Antrean Log F] Gambar berhasil diinjeksikan.");
                    //         console.log("[Isi HTML Editor Saat Ini]:", window.tiptapEditor.getHTML());

                    //         setTimeout(() => {
                    //             if(window.tiptapEditor) window.tiptapEditor.commands.scrollIntoView();
                    //         }, 50);
                    //     } else {
                    //         console.error("[Antrean Log E Error] window.tiptapEditor tidak aktif atau bernilai null!");
                    //     }

                    //     this.updatedAt = Date.now();
                    //     // Lanjut panggil antrean berikutnya
                    //     setTimeout(() => { this.processNextInQueue(); }, 30);
                    // };


                    reader.onerror = (err) => {
                        console.error("[Antrean Log FileReader Error]", err);
                        this.processNextInQueue();
                    };

                    reader.readAsDataURL(nextFile);

                } catch (err) {
                    console.error("[Antrean Log Catch Block Error]", err);
                    this.isUploading = false;
                    this.processNextInQueue();
                }
            },

            triggerFileSelect() {
                console.log("[UI Log] Memicu klik pada element file input...");
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.click();
                } else {
                    console.error("[UI Log Error] Element x-ref='fileInput' tidak ditemukan di HTML!");
                }
            },

            // --- AKSI STRUKTUR DISPLAY GAMBAR VIA BUTTON ---
            setImageAlignment(alignment) {
                if (!window.tiptapEditor) return;
                const { state } = window.tiptapEditor;
                const { from } = state.selection;

                const currentStyle = window.tiptapEditor.getAttributes('image').style || '';
                const widthMatch = currentStyle.match(/width:\s*\d+%/);
                const existingWidth = widthMatch ? widthMatch[0] : 'width: 25%';

                let alignmentStyles = 'display: block; margin-left: auto; margin-right: auto; float: none;';
                if (alignment === 'left') {
                    alignmentStyles = 'float: left; margin-right: 0.5rem; margin-bottom: 0.5rem; display: inline;';
                } else if (alignment === 'right') {
                    alignmentStyles = 'float: right; margin-left: 0.5rem; margin-bottom: 0.5rem; display: inline;';
                }

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', { style: `${existingWidth}; ${alignmentStyles}`.trim() })
                    .run();

                setTimeout(() => {
                    window.tiptapEditor.commands.setNodeSelection(from);
                    this.updatedAt = Date.now();
                }, 10);
            },

            setImageWidth(percentage) {
                if (!window.tiptapEditor) return;
                const { state } = window.tiptapEditor;
                const { from } = state.selection;

                const currentStyle = window.tiptapEditor.getAttributes('image').style || '';
                let remainingStyle = currentStyle.replace(/width:\s*\d+%;?/, '').trim();

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', { style: `width: ${percentage}%; ${remainingStyle}`.trim() })
                    .run();

                setTimeout(() => {
                    window.tiptapEditor.commands.setNodeSelection(from);
                    this.updatedAt = Date.now();
                }, 10);
            },

            isActive(type, opts = {}) {
                this.updatedAt;
                return window.tiptapEditor ? window.tiptapEditor.isActive(type, opts) : false;
            },

            runCommand(command, args = null) {
                if (!window.tiptapEditor) return;
                if (args !== null) {
                    window.tiptapEditor.chain().focus()[command](args).run();
                } else {
                    window.tiptapEditor.chain().focus()[command]().run();
                }
                this.updatedAt = Date.now();
            }
        }
    }
})
