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
import TextAlign from '@tiptap/extension-text-align'

import { createLowlight, common } from 'lowlight'
const lowlight = createLowlight(common)

document.addEventListener('alpine:init', () => {
    // Simpan instance murni global agar terbebas dari Proxy Observer Alpine
    window.tiptapEditor = null;

    window.setupEditor = function (wireModelName, wireComponent) {
        return {
            updatedAt: Date.now(),
            uploadQueue: [],
            isUploading: false,

            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                window.tiptapEditor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        // StarterKit standar
                        StarterKit.configure({
                            codeBlock: false,
                            link: false
                        }),

                        Link.configure({
                            openOnClick: false,
                            HTMLAttributes: { class: 'text-forest underline cursor-pointer' }
                        }),
                        // TAMBAHKAN EXTENSION TEXT ALIGN DI SINI
                        TextAlign.configure({
                            types: ['heading', 'paragraph'], // Terapkan pada teks & judul
                        }),

                        // UBAH JADI INLINE: TRUE AGAR BISA SEBARIS DENGAN TEKS
                        Image.configure({
                            inline: true, // <-- Ubah dari false ke true
                            allowBase64: true,
                        }).extend({
                            addAttributes() {
                                return {
                                    src: { default: null },
                                    alt: { default: null },
                                    title: { default: null },
                                    class: {
                                        default: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block',
                                        parseHTML: element => element.getAttribute('class'),
                                        renderHTML: attributes => ({ class: attributes.class })
                                    },
                                    style: {
                                        default: null,
                                        parseHTML: element => element.getAttribute('style'),
                                        renderHTML: attributes => attributes.style ? { style: attributes.style } : {}
                                    }
                                }
                            }
                        }),
                        // PERBAIKAN: Berikan kemampuan Image untuk membaca & menulis atribut class dan style
                        // Image.configure({
                        //     inline: false,
                        //     allowBase64: true,
                        // }).extend({
                        //     addAttributes() {
                        //         return {
                        //             src: {
                        //                 default: null,
                        //             },
                        //             alt: {
                        //                 default: null,
                        //             },
                        //             title: {
                        //                 default: null,
                        //             },
                        //             // Daftarkan class agar alignment (mx-auto, ml-0, dll) bisa disimpan
                        //             class: {
                        //                 default: 'rounded-lg max-w-full my-4 mx-auto shadow-md transition-all block cursor-pointer tiptap-uploaded-image',
                        //                 parseHTML: element => element.getAttribute('class'),
                        //                 renderHTML: attributes => ({ class: attributes.class })
                        //             },
                        //             // Daftarkan style agar persentase width (width: 50%) bisa disimpan
                        //             style: {
                        //                 default: null,
                        //                 parseHTML: element => element.getAttribute('style'),
                        //                 renderHTML: attributes => attributes.style ? { style: attributes.style } : {}
                        //             }
                        //         }
                        //     }
                        // }),

                        Table.configure({ resizable: true }),
                        TableRow,
                        TableCell,
                        TableHeader,
                        TaskList,
                        TaskItem,

                        Placeholder.configure({
                            placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                            emptyEditorClass: 'is-editor-empty'
                        }),

                        CodeBlockLowlight.configure({ lowlight }),

                        // BUBBLE MENU TEXT
                        BubbleMenu.configure({
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: { duration: 150, zIndex: 99 },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false
                                return !editor.isActive('image')
                            }
                        }),

                        // BUBBLE MENU GAMBAR
                        BubbleMenu.extend({ name: 'imageBubbleMenu' }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: { placement: 'top', duration: 150, zIndex: 99 },
                            shouldShow: ({ editor }) => editor.isActive('image')
                        })
                    ],
                    content: initialContent,

                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now()

                        // JANGAN kirim data ke Livewire jika sedang ada proses upload gambar di latar belakang
                        if (_this.isUploading) return;

                        // wireComponent.set(wireModelName, editor.getHTML(), false)
                        wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                    },
                    onSelectionUpdate() {
                        _this.updatedAt = Date.now()
                    }
                });

                this.$watch(`$wire.${wireModelName}`, (newContent) => {
                    // Cukup cek fokus dan kesamaan konten, jangan kunci dengan isUploading lagi
                    if (!window.tiptapEditor || window.tiptapEditor.isFocused || newContent === window.tiptapEditor.getHTML()) return
                    window.tiptapEditor.commands.setContent(newContent || '', false)
                })

            },

            handleMultipleImageUpload(files) {
                const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))
                if (imageFiles.length === 0) return

                console.log(`[Tiptap Upload] Memasukkan ${imageFiles.length} gambar ke dalam antrean.`);
                this.uploadQueue.push(...imageFiles);

                if (!this.isUploading) {
                    this.processNextInQueue();
                }
            },


            async processNextInQueue() {
                const _this = this;

                if (this.uploadQueue.length === 0) {
                    this.isUploading = false;
                    console.log(`[Tiptap Upload] Seluruh antrean selesai diproses!`);

                    // Kunci terakhir: Kirim data final yang sudah stabil ke Livewire
                    if (window.tiptapEditor) {
                        wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                    }
                    this.updatedAt = Date.now();
                    return;
                }

                this.isUploading = true;
                const nextFile = this.uploadQueue.shift();
                console.log(`%c[Antrean] Mengunggah fisik file ke Livewire: ${nextFile.name}`, 'color: #3b82f6; font-weight: bold;');

                wireComponent.upload('photo', nextFile, async () => {
                    console.log(`%c   -> Temp upload sukses. Menyimpan secara permanen...`, 'color: #9333ea;');

                    try {
                        const finalUrl = await wireComponent.uploadImage();

                        if (finalUrl && window.tiptapEditor) {
                            console.log(`%c   -> URL Diterima Tiptap Direct: ${finalUrl}`, 'color: #10b981; font-weight: bold;');

                            // 1. Ambil kendali fokus kembali ke posisi kursor terakhir pengguna
                            window.tiptapEditor.commands.focus();

                            // 2. Sisipkan Gambar TEPAT di posisi kursor aktif beserta spasi paragraf baru di bawahnya
                            window.tiptapEditor.chain()
                                .insertContent({
                                    type: 'image',
                                    attrs: {
                                        src: finalUrl,
                                        class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block'
                                    }
                                })
                                .insertContent('<p></p>') // Membuat baris baru kosong di bawah gambar agar ketikan tidak nyangkut
                                .run();

                            // 3. Gulirkan layar secara halus ke posisi kursor baru jika posisinya di bawah luar layar
                            setTimeout(() => {
                                window.tiptapEditor.commands.scrollIntoView();
                            }, 50);
                        }
                    } catch (err) {
                        console.error("Gagal mengeksekusi uploadImage di server:", err);
                    } finally {
                        _this.updatedAt = Date.now();

                        // Berikan sedikit jeda sebelum mengeksekusi antrean gambar berikutnya
                        setTimeout(() => {
                            _this.processNextInQueue();
                        }, 50);
                    }
                }, (err) => {
                    console.error("Gagal mengunggah ke temporary Livewire:", err);
                    _this.isUploading = false;
                    _this.processNextInQueue();
                });
            },

            // async processNextInQueue() {
            //     const _this = this;

            //     if (this.uploadQueue.length === 0) {
            //         this.isUploading = false;
            //         console.log(`[Tiptap Upload] Seluruh antrean selesai diproses!`);
            //         this.updatedAt = Date.now();
            //         return;
            //     }

            //     this.isUploading = true;
            //     const nextFile = this.uploadQueue.shift();
            //     console.log(`%c[Antrean] Mengunggah fisik file ke Livewire: ${nextFile.name}`, 'color: #3b82f6; font-weight: bold;');

            //     wireComponent.upload('photo', nextFile, async () => {
            //         console.log(`%c   -> Temp upload sukses. Menyimpan secara permanen...`, 'color: #9333ea;');

            //         try {
            //             const finalUrl = await wireComponent.uploadImage();

            //             if (finalUrl && window.tiptapEditor) {
            //                 console.log(`%c   -> URL Diterima Tiptap Direct: ${finalUrl}`, 'color: #10b981; font-weight: bold;');

            //                 // 1. Matikan isUploading sejenak agar $watch Alpine tidak memblokir incoming HTML dari Livewire
            //                 _this.isUploading = false;

            //                 // 2. Ambil HTML terbaru yang sudah dimodifikasi oleh PHP backend secara paksa
            //                 const freshHtmlFromLivewire = wireComponent.get(wireModelName);

            //                 // 3. Perbarui konten Tiptap secara langsung menggunakan data sah dari server
            //                 window.tiptapEditor.commands.setContent(freshHtmlFromLivewire, false);

            //                 // 4. Pindahkan kursor ke bagian paling akhir setelah gambar
            //                 window.tiptapEditor.commands.focus('end');

            //                 // 5. Gulirkan area kerja ke bawah
            //                 setTimeout(() => {
            //                     const editorElement = _this.$refs.editorElement;
            //                     if (editorElement) {
            //                         editorElement.scrollTo({
            //                             top: editorElement.scrollHeight,
            //                             behavior: 'smooth'
            //                         });
            //                     }
            //                 }, 50);
            //             }
            //         } catch (err) {
            //             console.error("Gagal mengeksekusi uploadImage di server:", err);
            //         } finally {
            //             _this.updatedAt = Date.now();

            //             // Beri sedikit jeda sebelum mengeksekusi antrean gambar berikutnya
            //             setTimeout(() => {
            //                 _this.processNextInQueue();
            //             }, 100);
            //         }
            //     }, (err) => {
            //         console.error("Gagal mengunggah ke temporary Livewire:", err);
            //         _this.isUploading = false;
            //         _this.processNextInQueue();
            //     });
            // },
            // async processNextInQueue() {
            //     const _this = this;

            //     if (this.uploadQueue.length === 0) {
            //         this.isUploading = false;

            //         // KITA HAPUS wireComponent.set dari sini karena backend PHP sudah menyimpannya dengan aman!
            //         console.log(`[Tiptap Upload] Seluruh antrean selesai diproses!`);
            //         this.updatedAt = Date.now();
            //         return;
            //     }

            //     this.isUploading = true;
            //     const nextFile = this.uploadQueue.shift();
            //     console.log(`%c[Antrean] Mengunggah fisik file ke Livewire: ${nextFile.name}`, 'color: #3b82f6; font-weight: bold;');

            //     wireComponent.upload('photo', nextFile, async () => {
            //         console.log(`%c   -> Temp upload sukses. Menyimpan secara permanen...`, 'color: #9333ea;');

            //         try {
            //             const finalUrl = await wireComponent.uploadImage();

            //             if (finalUrl && window.tiptapEditor) {
            //                 console.log(`%c   -> URL Diterima Tiptap Direct: ${finalUrl}`, 'color: #10b981; font-weight: bold;');

            //                 // Masukkan ke editor secara lokal untuk kenyamanan visual instant
            //                 window.tiptapEditor.commands.focus();
            //                 window.tiptapEditor.chain()
            //                     .setImage({ src: finalUrl })
            //                     .insertContent('<p></p>')
            //                     .run();

            //                 // Gulirkan editor ke bawah
            //                 const editorElement = _this.$refs.editorElement;
            //                 if (editorElement) {
            //                     editorElement.scrollTo({
            //                         top: editorElement.scrollHeight,
            //                         behavior: 'smooth'
            //                     });
            //                 }
            //             }
            //         } catch (err) {
            //             console.error("Gagal mengeksekusi uploadImage di server:", err);
            //         } finally {
            //             _this.updatedAt = Date.now();
            //             _this.processNextInQueue();
            //         }
            //     }, (err) => {
            //         console.error("Gagal mengunggah ke temporary Livewire:", err);
            //         _this.isUploading = false;
            //         _this.processNextInQueue();
            //     });
            // },
            // async processNextInQueue() {
            //     const _this = this;

            //     if (this.uploadQueue.length === 0) {
            //         // SELESAI MASALAH: Begitu seluruh antrean benar-benar habis, baru kita sinkronisasikan HTML final ke Livewire
            //         this.isUploading = false;

            //         if (window.tiptapEditor) {
            //             console.log(`%c[Antrean] Sinkronisasi HTML Final ke Livewire...`, 'color: #10b981; font-weight: bold;');
            //             wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
            //         }

            //         console.log(`[Tiptap Upload] Seluruh antrean selesai diproses!`);
            //         this.updatedAt = Date.now();
            //         return;
            //     }

            //     this.isUploading = true;
            //     const nextFile = this.uploadQueue.shift();
            //     console.log(`%c[Antrean] Mengunggah fisik file ke Livewire: ${nextFile.name}`, 'color: #3b82f6; font-weight: bold;');

            //     wireComponent.upload('photo', nextFile, async () => {
            //         console.log(`%c   -> Temp upload sukses. Menyimpan secara permanen...`, 'color: #9333ea;');

            //         try {
            //             const finalUrl = await wireComponent.uploadImage();

            //             if (finalUrl && window.tiptapEditor) {
            //                 console.log(`%c   -> URL Diterima Tiptap Direct: ${finalUrl}`, 'color: #10b981; font-weight: bold;');

            //                 window.tiptapEditor.commands.focus();

            //                 window.tiptapEditor.chain()
            //                     .setImage({ src: finalUrl })
            //                     .run();

            //                 // Berikan jeda agar DOM Prosemirror stabil mendahului siklus request Livewire
            //                 await new Promise(resolve => setTimeout(resolve, 100));

            //                 window.tiptapEditor.chain()
            //                     .insertContent('<p></p>')
            //                     .blur()
            //                     .focus()
            //                     .run();

            //                 const editorElement = _this.$refs.editorElement;
            //                 if (editorElement) {
            //                     editorElement.scrollTo({
            //                         top: editorElement.scrollHeight,
            //                         behavior: 'smooth'
            //                     });
            //                 }
            //             }
            //         } catch (err) {
            //             console.error("Gagal mengeksekusi uploadImage di server:", err);
            //         } finally {
            //             _this.updatedAt = Date.now();
            //             // Lanjut ke antrean berikutnya atau selesaikan proses upload
            //             _this.processNextInQueue();
            //         }
            //     }, (err) => {
            //         console.error("Gagal mengunggah ke temporary Livewire:", err);
            //         _this.isUploading = false;
            //         _this.processNextInQueue();
            //     });
            // },

            // async processNextInQueue() {
            //     if (this.uploadQueue.length === 0) {
            //         this.isUploading = false;
            //         console.log(`[Tiptap Upload] Seluruh antrean selesai diproses!`);
            //         return;
            //     }

            //     this.isUploading = true;
            //     const nextFile = this.uploadQueue.shift();
            //     console.log(`%c[Antrean] Mengunggah fisik file ke Livewire: ${nextFile.name}`, 'color: #3b82f6; font-weight: bold;');

            //     // Gunakan properti aslinya
            //     wireComponent.upload('photo', nextFile, async () => {
            //         console.log(`%c   -> Temp upload sukses. Menyimpan secara permanen...`, 'color: #9333ea;');

            //         try {
            //             const finalUrl = await wireComponent.uploadImage();

            //             if (finalUrl && window.tiptapEditor) {
            //                 console.log(`%c   -> URL Diterima Tiptap Direct: ${finalUrl}`, 'color: #10b981; font-weight: bold;');

            //                 // 1. Pastikan editor mendapatkan fokus utama kembali
            //                 window.tiptapEditor.commands.focus();

            //                 // 2. Sisipkan gambar menggunakan command rantai murni Prosemirror
            //                 window.tiptapEditor.chain()
            //                     .setImage({ src: finalUrl })
            //                     .run();

            //                 // 3. Paksa penambahan paragraf baru di bawahnya dan geser view
            //                 setTimeout(() => {
            //                     if (window.tiptapEditor) {
            //                         window.tiptapEditor.chain()
            //                             .insertContent('<p></p>')
            //                             .blur() // Trigger hilangkan fokus sejenak agar Alpine merefresh state
            //                             .focus() // Kembalikan fokus ke paragraf baru
            //                             .run();

            //                         // 4. Otomatis gulirkan (scroll) editor ke posisi paling bawah tempat gambar baru berada
            //                         const editorElement = _this.$refs.editorElement;
            //                         if (editorElement) {
            //                             editorElement.scrollTo({
            //                                 top: editorElement.scrollHeight,
            //                                 behavior: 'smooth'
            //                             });
            //                         }
            //                     }
            //                 }, 80); // Jeda dinaikkan ke 80ms untuk memberi waktu kalkulasi calc() CSS viewport
            //             }
            //         } catch (err) {
            //             console.error("Gagal mengeksekusi uploadImage di server:", err);
            //         } finally {
            //             this.updatedAt = Date.now();
            //             this.processNextInQueue();
            //         }
            //     }, (err) => {
            //         console.error("Gagal mengunggah ke temporary Livewire:", err);
            //         this.isUploading = false;
            //         this.processNextInQueue();
            //     });
            // },

            triggerFileSelect() { this.$refs.fileInput.click() },

            setImageAlignment(alignment) {
                if (!window.tiptapEditor) return

                // 1. Ambil atribut style yang saat ini sedang aktif di gambar tersebut
                const currentAttributes = window.tiptapEditor.getAttributes('image');
                const currentStyle = currentAttributes.style || '';

                // 2. Ekstrak nilai width (misal: "width: 25%") jika ada menggunakan Regex
                const widthMatch = currentStyle.match(/width:\s*\d+%/);
                const existingWidth = widthMatch ? widthMatch[0] + ';' : '';

                // 3. Tentukan gaya alignment baru
                let alignmentStyles = ''
                if (alignment === 'left') {
                    alignmentStyles = 'float: left; margin-right: 1.5rem; margin-bottom: 0.5rem; display: inline;'
                } else if (alignment === 'right') {
                    alignmentStyles = 'float: right; margin-left: 1.5rem; margin-bottom: 0.5rem; display: inline;'
                } else {
                    // Jika tengah (center), matikan float agar kembali berlagak seperti block
                    alignmentStyles = 'display: block; margin-left: auto; margin-right: auto; float: none;'
                }

                // 4. Gabungkan width lama dengan alignment baru
                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', {
                        style: `${existingWidth} ${alignmentStyles}`.trim()
                    })
                    .run()

                this.updatedAt = Date.now()
            },

            setImageWidth(percentage) {
                if (!window.tiptapEditor) return

                // 1. Ambil atribut style yang saat ini sedang aktif di gambar
                const currentAttributes = window.tiptapEditor.getAttributes('image');
                const currentStyle = currentAttributes.style || '';

                // 2. Bersihkan nilai width lama dari string style agar tidak double
                // Kita hilangkan bagian "width: X%;" dari string lama
                let remainingStyle = currentStyle.replace(/width:\s*\d+%;?/, '').trim();

                // 3. Gabungkan width baru dengan sisa gaya alignment yang ada
                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', {
                        style: `width: ${percentage}%; ${remainingStyle}`.trim()
                    })
                    .run()

                this.updatedAt = Date.now()
            },

            isActive(type, opts = {}) {
                this.updatedAt; // Trigger reaktivitas visual UI Alpine
                return window.tiptapEditor ? window.tiptapEditor.isActive(type, opts) : false;
            },

            runCommand(command, args = null) {
                if (!window.tiptapEditor) return
                if (args !== null) {
                    window.tiptapEditor.chain().focus()[command](args).run()
                } else {
                    window.tiptapEditor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now()
            }
        }
    }
})
