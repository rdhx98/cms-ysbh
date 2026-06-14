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

//addding this for
import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

const lowlight = createLowlight(common)

const HiddenMarks = Extension.create({
    name: 'hiddenMarks',

    addStorage() {
        return {
            visible: false,
        }
    },

    addCommands() {
        return {
            toggleHiddenMarks: () => ({ editor }) => {
                // 1. Flip the flag
                this.storage.visible = !this.storage.visible;

                // 2. Dispatch a no-op transaction to force decorations() to re-run
                const { state, view } = editor;
                view.dispatch(
                    state.tr
                        .setMeta('hiddenMarksTrigger', Date.now())
                        .setMeta('addToHistory', false)
                );

                return true;
            },
        }
    },

    addProseMirrorPlugins() {
        const extensionThis = this;

        return [
            new Plugin({
                key: new PluginKey('hiddenMarksPlugin'),

                // Let ProseMirror know it must re-run decorations when our meta key appears
                state: {
                    init() { return null; },
                    apply(tr, value) {
                        if (tr.getMeta('hiddenMarksTrigger') !== undefined) return tr.getMeta('hiddenMarksTrigger');
                        return value;
                    }
                },

                props: {
                    attributes() {
                        return extensionThis.storage.visible
                            ? { class: 'show-invisible-marks' }
                            : {};
                    },

                    decorations(state) {
                        if (!extensionThis.storage.visible) return DecorationSet.empty;

                        const decorations = [];
                        const { doc } = state;

                        doc.descendants((node, pos) => {
                            if (node.type.name === 'paragraph') {
                                const endPos = pos + node.nodeSize - 1;
                                decorations.push(
                                    Decoration.widget(endPos, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-para';
                                        span.textContent = '¶';
                                        span.style.cssText = `
                                            display: inline !important;
                                            position: static !important;
                                            white-space: nowrap !important;
                                            font-family: monospace !important;
                                            font-size: 0.75em !important;
                                            line-height: 1 !important;
                                            vertical-align: baseline !important;
                                            color: #10b981 !important;
                                            user-select: none !important;
                                            pointer-events: none !important;
                                            margin-left: 2px !important;
                                            float: none !important;
                                            padding: 0 !important;
                                            width: auto !important;
                                            height: auto !important;
                                            overflow: visible !important;
                                        `;
                                        return span;
                                    }, { side: 1, stopEvent: () => true })
                                );
                            }


                            // Switch from Decoration.node to Decoration.widget for hardBreak
                            if (node.type.name === 'hardBreak') {
                                const afterBreak = pos + node.nodeSize;
                                decorations.push(
                                    Decoration.widget(afterBreak, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-break';
                                        span.textContent = '↵';
                                        span.style.cssText = `
                                            display: inline !important;
                                            position: static !important;
                                            font-family: monospace !important;
                                            font-size: 0.75em !important;
                                            line-height: 1 !important;
                                            vertical-align: baseline !important;
                                            color: #c300ff !important;
                                            user-select: none !important;
                                            pointer-events: none !important;
                                            margin-left: 2px !important;
                                            float: none !important;
                                            padding: 0 !important;
                                            width: auto !important;
                                            height: auto !important;
                                            white-space: nowrap !important;
                                        `;
                                        return span;
                                    }, { side: -1, stopEvent: () => true })
                                );
                            }
                        });

                        return DecorationSet.create(doc, decorations);
                    }
                }
            })
        ];
    }
});


document.addEventListener('alpine:init', () => {
    // Simpan instance murni global agar terbebas dari Proxy Observer Alpine
    window.tiptapEditor = null;

    window.setupEditor = function (wireModelName, wireComponent) {
        return {
            updatedAt: Date.now(),
            uploadQueue: [],
            isUploading: false,
            showMarks: false,

            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                // Taruh ini di dalam fungsi init() Alpine komponen editor Anda
                window.addEventListener('unhandledrejection', (event) => {
                    // Cek apakah error disebabkan oleh kegagalan upload file Livewire
                    if (this.isUploading && event.reason && event.reason.message && event.reason.message.includes('JSON')) {
                        console.warn("%c[Sistem Penyelamat] Mendeteksi fatal crash pada Livewire Upload. Memulihkan antrean...", "color: #f59e0b; font-weight: bold;");

                        // Lewati file yang rusak/terlalu besar ini
                        event.preventDefault();

                        // Paksa reset input file Livewire dan jalankan antrean berikutnya
                        wireComponent.set('photo', null);

                        setTimeout(() => {
                            this.isUploading = false;
                            this.processNextInQueue();
                        }, 500); // Beri jeda setengah detik untuk pemulihan browser
                    }
                });

                window.tiptapEditor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        // StarterKit standar
                        StarterKit.configure({
                            codeBlock: false,
                            link: false
                        }),

                        // Biarkan mati saat pertama kali dimuat
                        HiddenMarks.configure({visible: false }),

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


            toggleHiddenMarks() {
                if (!window.tiptapEditor) return;

                // 1. Jalankan command internal Tiptap
                window.tiptapEditor.commands.toggleHiddenMarks();

                // 2. Sinkronkan tombol UI Alpine
                const hiddenMarksExt = window.tiptapEditor.extensionManager.extensions.find(
                    ext => ext.name === 'hiddenMarks'
                );
                if (hiddenMarksExt) {
                    this.showMarks = hiddenMarksExt.storage.visible;
                }

                // 3. Paksa ProseMirror gambar ulang class pembungkusnya
                const { state, view } = window.tiptapEditor;
                if (view) {
                    view.dispatch(state.tr.setMeta('hiddenMarksTrigger', Date.now()));
                }

                this.updatedAt = Date.now();
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
