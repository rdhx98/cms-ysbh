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
import { Node, mergeAttributes } from '@tiptap/core'

import { TextStyle } from '@tiptap/extension-text-style'
import { FontFamily } from '@tiptap/extension-font-family'
import { Underline } from '@tiptap/extension-underline'
import { OrderedList } from '@tiptap/extension-ordered-list'

import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'
import { DOMParser as ProseMirrorDOMParser } from '@tiptap/pm/model'



const ALLOWED_FONTS = ['Arial', 'Times New Roman', 'Roboto', 'Jetbrains Mono', 'Open Sans', 'Plus Jakarta Sans'];

const lowlight = createLowlight(common)

const LinkBackspaceHandler = Extension.create({
    name: 'linkBackspaceHandler',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('linkBackspacePlugin'),
                props: {
                    handleKeyDown(view, event) {
                        if (event.key !== 'Backspace') return false;

                        const { state } = view;
                        const { selection, tr } = state;
                        const { $from, empty } = selection;

                        if (!empty) return false;

                        let linkMark = state.schema.marks.link ? $from.marks().find(m => m.type.name === 'link') : null;

                        if (!linkMark && state.schema.marks.link) {
                            linkMark = $from.nodeBefore ? $from.nodeBefore.marks.find(m => m.type.name === 'link') : null;
                        }

                        if (linkMark) {
                            const linkType = state.schema.marks.link;
                            let start = $from.pos;
                            let end = $from.pos;

                            // Melacak batas awal link
                            while (start > 0 && state.doc.rangeHasMark(start - 1, start, linkType)) {
                                start--;
                            }

                            // Melacak batas akhir link
                            while (end < state.doc.content.size && state.doc.rangeHasMark(end, end + 1, linkType)) {
                                end++;
                            }

                            if (start < end) {
                                // --- ✅ PERBAIKAN DI SINI ---
                                // 1. Hapus teks link dari dokumen
                                // 2. Gunakan removeStoredMark agar ketikan setelahnya tidak bermark link
                                view.dispatch(
                                    tr.delete(start, end)
                                      .removeStoredMark(linkType)
                                );
                                return true;
                            }
                        }

                        return false;
                    },
                }
            })
        ];
    }
});

const HiddenMarks = Extension.create({
    name: 'hiddenMarks',

    addStorage() {
        return {
            visible: false,
        }
    },
    // --- ➕ INJECT CSS OTOMATIS LEWAT JS ---
    onCreate() {
        if (!document.getElementById('tiptap-hidden-marks-styles')) {
            const style = document.createElement('style');
            style.id = 'tiptap-hidden-marks-styles';
            style.textContent = `
                .tiptap-invisible-space {
                    position: relative !important;
                }
                .tiptap-invisible-space::before {
                    content: "·" !important;
                    position: absolute !important;
                    left: 50% !important;

                    /* --- 🛠️ ADJUSTMENT POSISI VERTIKAL DI SINI --- */
                    top: 55% !important; /* Diturunkan sedikit dari 50% ke 55% atau 58% */
                    transform: translate(-50%, -35%) !important; /* Menyeimbangkan posisi Y */

                    /* Alternatif jika ingin mengecilkan ukuran titik agar lebih estetik */
                    font-size: 1.1em !important;
                    line-height: 0 !important;

                    color: #a1a1aa !important; /* Zinc-400, tipis dan tidak mencolok */
                    font-weight: 900 !important;
                    pointer-events: none !important;
                    user-select: none !important;
                }
            `;
            document.head.appendChild(style);
        }
    },

    addCommands() {
        return {
            toggleHiddenMarks: () => ({ editor }) => {
                this.storage.visible = !this.storage.visible;

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
                            // 1. PERBAIKAN PARAGRAF (¶) - DIKUNCI AGAR TIDAK TURUN top: -0.35em !important; /* Tarik ke atas secara murni dari koordinat relatifnya */
                            if (node.type.name === 'paragraph') {
                                const endPos = pos + node.nodeSize - 1;
                                decorations.push(
                                    Decoration.widget(endPos, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-para';
                                        span.textContent = '¶';
                                        span.style.cssText = `
                                            display: inline-block !important;
                                            width: 0 !important;
                                            height: 0 !important;
                                            line-height: 0 !important;
                                            overflow: visible !important;

                                            /* --- 🛠️ RACIKAN BARU PENYEIMBANG POSISI --- */
                                            vertical-align: middle !important; /* Gunakan middle sebagai jangkar pusat teks */
                                            position: relative !important;


                                            font-family: var(--font-mono) !important;
                                            font-size: 0.85em !important;
                                            color: #10b981 !important;
                                            user-select: none !important;
                                            pointer-events: none !important;
                                            margin-left: 4px !important;
                                            top: -0.25em !important;
                                        `;
                                        return span;
                                    }, { side: 1, stopEvent: () => true })
                                );
                            }

                            // 2. PERBAIKAN HARD BREAK (↵) - DIKUNCI AGAR SEJAJAR TEKS
                            if (node.type.name === 'hardBreak') {
                                decorations.push(
                                    Decoration.widget(pos, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-break';
                                        span.textContent = '↵';
                                        span.style.cssText = `
                                            display: inline-block !important;
                                            width: 0 !important;
                                            height: 0 !important;
                                            line-height: 0 !important;
                                            overflow: visible !important;

                                            /* Kunci posisi vertikal */
                                            vertical-align: baseline !important;
                                            transform: translateY(-0.05em) !important; /* Dorong mikro ke atas jika ikut ketarik turun */

                                            font-family: var(--font-mono) !important;
                                            font-size: 0.85em !important;
                                            color: #c300ff !important;
                                            user-select: none !important;
                                            pointer-events: none !important;
                                            margin-left: 2px !important;
                                        `;
                                        return span;
                                    }, { side: -1, stopEvent: () => true })
                                );
                            }


                            // 3. DETEKSI SPASI (·) - MENGGUNAKAN INLINE DECORATION
                            if (node.isText) {
                                const text = node.text;
                                let index = text.indexOf(' ');

                                while (index !== -1) {
                                    const startPos = pos + index;
                                    const endPos = startPos + 1;

                                    decorations.push(
                                        Decoration.inline(startPos, endPos, {
                                            class: 'tiptap-invisible-space',
                                        })
                                    );

                                    // Cari spasi berikutnya di dalam text node yang sama
                                    index = text.indexOf(' ', index + 1);
                                }
                            }
                        });

                        return DecorationSet.create(doc, decorations);
                    }
                }
            }),
        ];
    }
});

const ParagraphIndent = Extension.create({
    name: 'paragraphIndent',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph'],
                attributes: {
                    indent: {
                        default: null,
                        parseHTML: element => element.style.textIndent ? true : null,
                        renderHTML: attributes => {
                            if (!attributes.indent) return {}
                            return { style: 'text-indent: 2rem;' }
                        },
                    },
                },
            },
        ]
    },

    addCommands() {
        return {
            toggleIndent: () => ({ commands, editor }) => {
                const isIndented = editor.getAttributes('paragraph').indent
                return commands.updateAttributes('paragraph', { indent: isIndented ? null : true })
            },
            unsetIndent: () => ({ commands }) => {
                return commands.updateAttributes('paragraph', { indent: null })
            },
        }
    },

    addKeyboardShortcuts() {
        return {
            'Tab': () => {
                if (this.editor.isActive('bulletList') || this.editor.isActive('orderedList')) {
                    return false
                }
                if (this.editor.isActive('paragraph')) {
                    return this.editor.commands.toggleIndent()
                }
                return false
            },
            'Shift-Tab': () => {
                if (this.editor.isActive('bulletList') || this.editor.isActive('orderedList')) {
                    return false
                }
                if (this.editor.isActive('paragraph')) {
                    return this.editor.commands.unsetIndent()
                }
                return false
            },
        }
    },
});

const MediaPlaceholder = Node.create({
    name: 'mediaPlaceholder',
    group: 'block',
    atom: true,
    selectable: true, // 💡 KUNCI 2: Izinkan user mengklik/memilih blok ini agar tahu fokusnya ada di sini
    draggable: false,  // Jaga agar slot placeholder tidak sengaja tergeser saat mau di-drop

    parseHTML() {
        return [{ tag: 'div[data-type="media-placeholder"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, { 'data-type': 'media-placeholder', class: 'media-placeholder-zone' }),
            [
                'div', { class: 'placeholder-content' },
                ['span', { class: 'placeholder-text' }, 'Tarik & lepas gambar ke sini atau '],
                // Gunakan fungsi pembuka modal/picker yang Anda miliki
                ['button', { type: 'button', class: 'placeholder-btn', onclick: 'window.triggerLocalFilePicker()' }, 'Cari Berkas']
            ]
        ]
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

            linkInputUrl: '',
            linkInputText: '',
            hasSelection: false,
            isLinkOpen: false,


            init() {
                const _this = this
                const initialContent = wireComponent.get(wireModelName) || ''

                window.triggerLocalFilePicker = () => {
                    if (_this.$refs.fileInput) {
                        _this.$refs.fileInput.click();
                    }
                };

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
                            link: false,
                            underline: false,
                            orderedList:false,
                        }),

                        // Biarkan mati saat pertama kali dimuat
                        HiddenMarks.configure({visible: false }),

                        Link.configure({
                            openOnClick: false,
                            HTMLAttributes: { class: 'text-forest underline cursor-pointer' }
                        }),

                        LinkBackspaceHandler,
                        ParagraphIndent,

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
                                    src: {
                                        default: null,
                                        parseHTML: element => element.getAttribute('src'),
                                        renderHTML: attributes => attributes.src ? { src: attributes.src } : {}
                                    },
                                    alt: {
                                        default: null,
                                        parseHTML: element => element.getAttribute('alt'),
                                        renderHTML: attributes => attributes.alt ? { alt: attributes.alt } : {}
                                    },
                                    title: {
                                        default: null,
                                        parseHTML: element => element.getAttribute('title'),
                                        renderHTML: attributes => attributes.title ? { title: attributes.title } : {}
                                    },
                                    class: {
                                        default: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block',
                                        parseHTML: element => element.getAttribute('class') || element.className,
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

                        TaskList.configure({
                        HTMLAttributes: {
                            class: 'not-prose list-none pl-0 my-4 space-y-2',
                        },
                        }),
                        TaskItem.configure({
                        HTMLAttributes: {
                            class: [
                            // 1. Container utama (<li>) dibuat flex dan sejajar vertikal di tengah baris
                            'flex items-center my-1',

                            // 2. Styling wrapper checkbox (<label>)
                            // Kita beri h-5 (20px) agar punya ruang tinggi yang konsisten
                            '[&>label]:flex [&>label]:items-center [&>label]:h-5 [&>label]:mr-3 [&>label]:select-none [&>label]:cursor-pointer [&>label]:flex-shrink-0',

                            // 3. Styling input checkbox asli
                            '[&>label>input]:w-4 [&>label>input]:h-4 [&>label>input]:rounded [&>label>input]:border-gray-300 [&>label>input]:text-blue-600',

                            // 4. Styling konten teks (<div>)
                            // leading-5 (20px) disamakan dengan h-5 milik label agar garis tengahnya (horizontal) benar-benar sejajar
                            '[&>div]:m-0 [&>div]:leading-5 [&>div]:flex-1',

                            // 5. Efek coret saat dicentang
                            'data-[checked=true]:[&>div]:line-through data-[checked=true]:[&>div]:text-gray-400'
                            ].join(' '),
                        },
                            nested: true,
                        }),

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
                                if (from === to) return false;
                                return !editor.isActive('image')&& !editor.isActive('mediaPlaceholder');
                                // return !editor.isActive('image')
                            }
                        }),
                        BubbleMenu.extend({ name: 'imageBubbleMenu' }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: {
                                placement: 'top',
                                duration: 150,
                                zIndex: 99,
                                hideOnClick: false, // Jaga Tippy agar tidak menutup saat area menu diklik
                            },
                            // 💡 KUNCI EMAS: Paksa menu tetap TRUE selama seleksi saat ini atau kursor adalah sebuah IMAGE
                            shouldShow: ({ editor, state, from, to, view }) => {
                                // Cek apakah elemen yang sedang dipilih oleh user saat ini adalah gambar
                                return editor.isActive('image');
                            }
                        }),
                        TextStyle, // Wajib diisi karena FontFamily bergantung pada TextStyle
                        FontFamily.extend({
                            parseHTML() {
                                return [
                                    {
                                        style: 'font-family',
                                        getAttrs: value => {
                                            // Bersihkan tanda kutip jika ada (misal: "Inter" menjadi Inter)
                                            const cleanedFont = value.replace(/['"]/g, '').split(',')[0].trim();

                                            // Jika font yang di-paste ada di daftar ALLOWED_FONTS, izinkan.
                                            if (ALLOWED_FONTS.includes(cleanedFont)) {
                                                return { fontFamily: cleanedFont };
                                            }

                                            // KUNCI UTAMA: Jika tidak ada di daftar, return false agar inline style font tersebut DIHAPUS
                                            // Teks akan otomatis menggunakan Font Default dari editor/website Anda.
                                            return false;
                                        },
                                    },
                                ];
                            },
                        }),
                        Underline, // Ekstensi untuk font family
                        OrderedList.configure({
                            HTMLAttributes: {
                                class: 'list-ordered-default',
                            },
                        }).extend({
                            addAttributes() {
                                return {
                                    listStyle: {
                                        default: 'number',
                                        // Membaca jenis list dari class HTML saat load dari DB atau saat di-paste
                                        parseHTML: element => element.classList.contains('list-alpha-upper') ? 'alpha' : 'number',
                                        // Merender class ke HTML berdasarkan pilihan di toolbar
                                        renderHTML: attributes => {
                                            if (attributes.listStyle === 'alpha') {
                                                return { class: 'list-alpha-upper' }
                                            }
                                            return { class: 'list-ordered-number' }
                                        },
                                    },
                                }
                            },
                        }),
                        MediaPlaceholder,
                    ],

                    // 🛡️ PROSEMIRROR CORE INTERCEPTION HANDLER
                    // =========================================================================
                    // 🛡️ PROSEMIRROR CORE INTERCEPTION HANDLER (ANTI-TAB BARU)
                    // =========================================================================
                    editorProps: {
                        // 1. CEGAT SAAT FILE BERADA DI ATAS EDITOR
                        handleDragOver: (view, event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            return true;
                        },

                        // 2. CEGAT MUTLAK SAAT FILE DILEPAS (DROP)
                        handleDrop: (view, event, slice, moved) => {
                            if (moved) return false;

                            // Paksa browser berhenti melakukan aksi bawaan sejak baris pertama
                            event.preventDefault();
                            event.stopPropagation();

                            // Ambil data transfer file biner dari desktop
                            const files = event.dataTransfer ? event.dataTransfer.files : [];
                            let imageFound = false;

                            for (const file of files) {
                                if (file.type.startsWith('image/')) {
                                    imageFound = true;

                                    // Masukkan file langsung ke antrean upload Alpine via referensi aman _this
                                    _this.uploadQueue.push(file);
                                }
                            }

                            if (imageFound) {
                                // Nyalakan state loading dan jalankan mesin upload Livewire Herd Anda
                                _this.isUploading = true;
                                _this.processNextInQueue();

                                return true; // Selesai ditangani secara kustom, hentikan ProseMirror bubble-up
                            }

                            return false;
                        },

                        transformPastedHTML(html) {
                        // transformPastedHTML: (html) => {
                            if (!html) return html;

                            const _this = this; // Pastikan referensi ke scope Alpine/TipTap aman
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const images = doc.querySelectorAll('img, v\\:imagedata, o\\:Graphic');

                            let localImageCount = 0;
                            let base64ImageCount = 0;

                            images.forEach((img) => {
                                const src = img.getAttribute('src') || '';

                                // 🚀 SKENARIO A: Jika terdeteksi gambar Base64 (Word Online / GDocs)
                                if (src.startsWith('data:image/')) {
                                    base64ImageCount++;

                                    try {
                                        // 1. Konversi string Base64 menjadi File Biner asli di browser frontend
                                        const parts = src.split(',');
                                        const mime = parts[0].match(/:(.*?);/)[1];
                                        const bstr = atob(parts[1]);
                                        let n = bstr.length;
                                        const u8arr = new Uint8Array(n);

                                        while (n--) {
                                            u8arr[n] = bstr.charCodeAt(n);
                                        }

                                        const extension = mime.split('/')[1] || 'png';
                                        const file = new File([u8arr], `pasted-inline-${Date.now()}-${base64ImageCount}.${extension}`, { type: mime });

                                        // 2. Suntikkan file biner ringkas ini langsung ke antrean upload WebP Anda!
                                        _this.uploadQueue.push(file);

                                        // 3. Sulap tag img lama agar tidak membawa data teks raksasa ke database
                                        // Kita ganti dengan placeholder sementara yang ringan sampai proses upload antrean selesai
                                        const tempPlaceholder = doc.createElement('div');
                                        tempPlaceholder.setAttribute('data-type', 'media-placeholder');
                                        tempPlaceholder.className = 'media-placeholder-zone animate-pulse';
                                        tempPlaceholder.innerText = `⏳ Mengompres & Mengunggah Gambar...`;

                                        if (img.parentNode) {
                                            img.parentNode.replaceChild(tempPlaceholder, img);
                                        }
                                    } catch (e) {
                                        console.error('[Base64 Extractor] Gagal memparsing gambar inline:', e);
                                    }
                                }

                                // 🚀 SKENARIO B: Jika link terkunci di drive komputer lokal pembaca (file:///)
                                else if (src.startsWith('file:///') || src.includes('msohtmlclip') || src.trim() === '') {
                                    localImageCount++;

                                    const placeholderDom = doc.createElement('div');
                                    placeholderDom.setAttribute('data-type', 'media-placeholder');
                                    placeholderDom.className = 'media-placeholder-zone';
                                    placeholderDom.setAttribute('data-alt-fallback', img.getAttribute('alt') || `Gambar Lokal ${localImageCount}`);

                                    if (img.parentNode) {
                                        img.parentNode.replaceChild(placeholderDom, img);
                                    }
                                }
                            });

                            // Jika ada gambar Base64 yang berhasil dicegat, langsung jalankan mesin antrean upload Anda
                            if (base64ImageCount > 0) {
                                console.log(`[CMS Interceptor] Berhasil mencegat ${base64ImageCount} Base64 raksasa & melemparnya ke WebP Upload Queue.`);
                                _this.isUploading = true;
                                // Jalankan function antrean upload milik Anda
                                setTimeout(() => { _this.processNextInQueue(); }, 50);
                                return doc.body.innerHTML;
                            }

                            if (localImageCount > 0) {
                                console.log(`[CMS Fallback] Berhasil menyulap ${localImageCount} gambar file:/// menjadi mediaPlaceholder.`);
                                return doc.body.innerHTML;
                            }

                            return html;
                        },

                        handlePaste: (view, event, slice) => {
                            // Ambil data dari clipboard item
                            const items = (event.clipboardData || event.originalEvent.clipboardData).items;
                            let imageFound = false;

                            for (const item of items) {
                                // Cek apakah item yang di-paste adalah file gambar fisik/tangkapan layar
                                if (item.type.indexOf('image') === 0) {
                                    const file = item.getAsFile();

                                    if (file) {
                                        imageFound = true;

                                        // Paksa browser berhenti merender string Base64 bawaannya
                                        event.preventDefault();
                                        event.stopPropagation();

                                        // Masukkan gambar langsung ke antrean upload Alpine yang sama dengan drop
                                        _this.uploadQueue.push(file);
                                    }
                                }
                            }

                            if (imageFound) {
                                // Picu mesin antrean upload Livewire Anda untuk berjalan
                                _this.isUploading = true;
                                _this.processNextInQueue();

                                return true; // Sukses ditangani, instruksikan ProseMirror agar tidak meloloskan teks Base64
                            }

                            return false; // Jika yang di-paste teks biasa, biarkan berjalan normal
                        },




                        // Pastikan fungsi handlePaste Anda mengizinkan proses bawaan berjalan
                        // handlePaste: (view, event) => {
                        //     const clipboardData = event.clipboardData || window.clipboardData;
                        //     if (!clipboardData) return false;

                        //     const htmlData = clipboardData.getData('text/html');
                        //     const items = clipboardData.items;

                        //     // Jalur screenshot biner murni (Ctrl + V gambar langsung dari Snipping Tool / OS)
                        //     if (!htmlData) {
                        //         let imageFound = false;
                        //         for (const item of items) {
                        //             if (item.kind === 'file' && item.type.startsWith('image/')) {
                        //                 const file = item.getAsFile();
                        //                 if (file) {
                        //                     imageFound = true;
                        //                     _this.uploadQueue.push(file);
                        //                 }
                        //             }
                        //         }
                        //         if (imageFound) {
                        //             _this.isUploading = true;
                        //             _this.processNextInQueue();
                        //             return true;
                        //         }
                        //     }

                        //     return false; // Biarkan core TipTap memproses HTML hasil filter dari transformPastedHTML
                        // }

                    },
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
                const _this = this;

                // 💡 KUNCI PENYEMBUH: Paksa matikan overlay dragging saat file resmi mendarat (drop)
                if (typeof this.isDragging !== 'undefined') {
                    this.isDragging = false;
                }
                // Jika Anda menggunakan variabel pendukung lain di Alpine untuk overlay, matikan juga di sini:
                // _this.isLocalDrag = false;

                // Daftar MIME Type gambar yang sah
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                let hasInvalidFile = false;
                let invalidFileNames = [];

                Array.from(files).forEach(file => {
                    if (allowedTypes.includes(file.type)) {
                        _this.uploadQueue.push(file);
                    } else {
                        hasInvalidFile = true;
                        invalidFileNames.push(file.name);
                    }
                });

                if (hasInvalidFile) {
                    alert(`⚠️ Format Berkas Tidak Didukung!\n\nEditor ini hanya menerima media gambar (JPG, PNG, WebP, GIF).\n\nBerkas berikut otomatis ditolak:\n- ${invalidFileNames.join('\n- ')}`);
                }

                if (_this.uploadQueue.length > 0 && !_this.isUploading) {
                    _this.processNextInQueue();
                }
            },

            async processNextInQueue() {
                const _this = this;

                if (this.uploadQueue.length === 0) {
                    this.isUploading = false;
                    if (window.tiptapEditor) {
                        wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                    }
                    this.updatedAt = Date.now();
                    return;
                }

                this.isUploading = true;
                const originalFile = this.uploadQueue.shift();

                // 💡 JALUR KHUSUS GIF: Jika file adalah GIF, langsung bypass ke server
                if (originalFile.type === 'image/gif') {
                    console.log(`[GIF Route] Mengirim GIF asli ke server untuk kompresi backend.`);
                    _this.executeLivewireUpload(originalFile, _this);
                    return;
                }

                console.log(`[WebP Compressor] Memproses otomatis: ${originalFile.name}`);

                // Fungsi konversi WebP di sisi Frontend Browser
                const convertToWebp = (file) => {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = (event) => {
                            const img = new window.Image(); // ◄ Aman dari bentrokan nama import
                            img.src = event.target.result;
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                let width = img.width;
                                let height = img.height;

                                if (width > 1200) {
                                    height = Math.round((height * 1200) / width);
                                    width = 1200;
                                }

                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);

                                canvas.toBlob((blob) => {
                                    const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                                    const compressedFile = new File([blob], newFileName, {
                                        type: "image/webp",
                                        lastModified: Date.now()
                                    });
                                    resolve(compressedFile);
                                }, "image/webp", 0.75);
                            };
                        };
                    });
                };

                try {
                    const webpFile = await convertToWebp(originalFile);
                    console.log(`[WebP Compressor] Berhasil!`);

                    // Gunakan _this agar aman dari error scope 'is not a function'
                    _this.executeLivewireUpload(webpFile, _this);

                } catch (compressError) {
                    console.error('[WebP Compressor] Gagal kompresi, fallback ke file asli:', compressError);
                    _this.executeLivewireUpload(originalFile, _this);
                }
            },


            // 💡 HELPER FUNCTION: Pastikan blok paling bawah tertulis 'finally' diganti sementara karena masalah gambar disisipkan ke tempat yang tidak tepat
            executeLivewireUpload(targetFile, _this) {
                wireComponent.upload('photo', targetFile, async (uploadedUrl) => {
                    try {
                        const finalUrl = await wireComponent.uploadImage();

                        if (finalUrl) {
                            if (window.tiptapEditor) {
                                window.tiptapEditor.commands.focus();

                                window.tiptapEditor.chain()
                                    .deleteSelection()
                                    .insertContent({
                                        type: 'image',
                                        attrs: {
                                            src: finalUrl,
                                            alt: targetFile.name,
                                            title: targetFile.name,
                                            style: 'width: 25%; display: block !important; margin-left: auto !important; margin-right: auto !important; margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; float: none !important;',
                                            class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block'
                                        }
                                    })
                                    .insertContent('<p></p>')
                                    .run();

                                setTimeout(() => { window.tiptapEditor.commands.scrollIntoView(); }, 50);
                            }
                        }
                    } catch (error) {
                        console.error('[Upload Error] Gagal memproses di backend:', error);
                    } finally { // ◄ ✅ PASTIKAN TERTULIS 'finally', BUKAN 'final'
                        _this.processNextInQueue();
                    }
                }, (error) => {
                    console.error('[Livewire Error] Gagal upload temporary:', error);
                    _this.processNextInQueue();
                });
            },

            triggerFileSelect() { this.$refs.fileInput.click() },

            insertMediaPlaceholder() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain()
                    .focus()
                    .insertContent({ type: 'mediaPlaceholder' })
                    .run();
                this.updatedAt = Date.now();
            },

            setImageAlignment(alignment) {
                if (!window.tiptapEditor || !window.tiptapEditor.isActive('image')) return;

                // Ambil posisi koordinat gambar sebelum diperbarui agar seleksi tidak hilang
                const { selection } = window.tiptapEditor.state;
                const currentPosition = selection.from;

                const currentAttributes = window.tiptapEditor.getAttributes('image');
                const currentStyle = currentAttributes.style || '';

                // Pertahankan ukuran persentase yang sudah disetel sebelumnya
                const widthMatch = currentStyle.match(/width:\s*\d+%/);
                const existingWidth = widthMatch ? widthMatch[0] + ';' : '';

                let alignmentStyles = '';
                if (alignment === 'left') {
                    // Rata Kiri: Float kiri, rata atas dengan teks (margin-top kecil), margin kanan proporsional (1rem)
                    alignmentStyles = 'float: left; margin-right: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;';
                } else if (alignment === 'right') {
                    // Rata Kanan: Float kanan, rata atas dengan teks (margin-top kecil), margin kiri proporsional (1rem)
                    alignmentStyles = 'float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;';
                } else {
                    // Rata Tengah: Menjadi blok mandiri, jeda vertikal sumbu Y yang manis dan simetris (12px / 0.75rem)
                    alignmentStyles = 'display: block !important; margin-left: auto !important; margin-right: auto !important; margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; float: none !important;';
                }

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', {
                        style: `${existingWidth} ${alignmentStyles}`.trim()
                    })
                    .setNodeSelection(currentPosition) // Kunci kembali posisi bubble menu
                    .run();

                this.updatedAt = Date.now();
            },

            isImageAlignActive(alignment) {
                this.updatedAt; // Trigger reaktivitas visual UI
                if (!window.tiptapEditor || !window.tiptapEditor.isActive('image')) return false;

                const attrs = window.tiptapEditor.getAttributes('image');
                const style = attrs.style || '';

                if (alignment === 'left') return style.includes('float: left') || style.includes('float:left');
                if (alignment === 'right') return style.includes('float: right') || style.includes('float:right');
                if (alignment === 'center') return style.includes('display: block') || style.includes('margin-left: auto');

                return false;
            },
            // HELPER: Cek status persentase ukuran gambar aktif secara kustom lewat pencarian style teks
            isImageWidthActive(width) {
                this.updatedAt; // Trigger reaktivitas visual UI
                if (!window.tiptapEditor || !window.tiptapEditor.isActive('image')) return false;

                const attrs = window.tiptapEditor.getAttributes('image');
                const style = attrs.style || '';

                // Mencari string "width: 25%" atau "width:25%" di dalam inline-style node gambar
                return style.includes(`width: ${width}%`) || style.includes(`width:${width}%`);
            },

            // AKTIVITAS HAPUS NODE: Menghapus gambar dari editor tanpa merusak sejarah Undo/Redo
            deleteSelectedImage() {
                if (!window.tiptapEditor || !window.tiptapEditor.isActive('image')) return;

                // Hapus node gambar yang sedang dipilih/aktif
                window.tiptapEditor.chain().focus().deleteSelection().run();
                this.updatedAt = Date.now();
            },

            setImageWidth(width) {
                if (!window.tiptapEditor || !window.tiptapEditor.isActive('image')) return;

                // Ambil posisi koordinat gambar yang sedang dipilih saat ini sebelum dia diperbarui
                const { selection } = window.tiptapEditor.state;
                const currentPosition = selection.from;

                const currentAttributes = window.tiptapEditor.getAttributes('image');
                const currentStyle = currentAttributes.style || '';

                const cleanedStyle = currentStyle.replace(/width:\s*\d+%;?/, '').trim();
                const newStyle = `width: ${width}%; ${cleanedStyle}`.trim();

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes('image', { style: newStyle })
                    // 💡 KUNCI EMAS: Paksa Tiptap menyeleksi kembali posisi gambar tersebut agar bubble menu tidak kabur
                    .setNodeSelection(currentPosition)
                    .run();

                this.updatedAt = Date.now();
            },

            isActive(type, opts = {}) {
                this.updatedAt; // Trigger reaktivitas visual UI Alpine
                return window.tiptapEditor ? window.tiptapEditor.isActive(type, opts) : false;
            },

            toggleHeading(level) {
                if (!window.tiptapEditor) return;

                // 1. Jalankan perintah toggle heading dari Tiptap
                window.tiptapEditor.chain().focus().toggleHeading({ level: level }).run();

                // 2. Memicu reaktivitas komponen Alpine
                this.updatedAt = Date.now();
            },

            isHeadingActive(level) {
                if (!window.tiptapEditor) return false;

                // Trik pemicu reaktivitas:
                // Setiap kali onUpdate / onSelectionUpdate memperbarui _this.updatedAt,
                // fungsi isHeadingActive() ini akan dieksekusi ulang secara otomatis oleh Alpine.
                this.updatedAt;

                return window.tiptapEditor.isActive('heading', { level: level });
            },

            runCommand(command, args = null) {
                if (!window.tiptapEditor) return
                if (args !== null) {
                    window.tiptapEditor.chain().focus()[command](args).run()
                } else {
                    window.tiptapEditor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now()
            },
            openLinkModal() {
                if (!window.tiptapEditor) return;

                const { state } = window.tiptapEditor;
                const { from, to } = state.selection;

                // Cek apakah pengguna sedang memblok teks (selection tidak kosong)
                this.hasSelection = from !== to;

                if (this.hasSelection) {
                    // Jika ada teks diblok, ambil teks tersebut untuk dijadikan default Link Text
                    this.linkInputText = state.doc.textBetween(from, to, ' ');
                    this.linkInputUrl = window.tiptapEditor.getAttributes('link').href || '';
                } else {
                    // Jika kursor kosong, reset input
                    this.linkInputText = '';
                    this.linkInputUrl = '';
                }
            },

            submitLink() {
                if (!window.tiptapEditor) return;

                let url = this.linkInputUrl.trim();
                let text = this.linkInputText.trim();

                // AKSI HANCURKAN: Jika text (title) dikosongkan oleh user, hancurkan link/hapus kontennya
                if (text === '') {
                    if (this.hasSelection) {
                        window.tiptapEditor.chain().focus().deleteSelection().run();
                    }
                    this.clearLinkInputs();
                    return;
                }

                // Jika teks ada tapi URL kosong, anggap user hanya ingin memasukkan teks biasa tanpa link
                if (url === '') {
                    if (this.hasSelection) {
                        window.tiptapEditor.chain().focus().extendMarkRange('link').unsetLink().run();
                    } else {
                        window.tiptapEditor.chain().focus().insertContent(text).insertContent(' ').run();
                    }
                    this.clearLinkInputs();
                    return;
                }

                // Validasi skema URL otomatis
                if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !/^tel:/i.test(url)) {
                    url = `https://${url}`;
                }

                if (this.hasSelection) {
                    // KONDISI A: User memblok teks
                    window.tiptapEditor.chain()
                        .focus()
                        .extendMarkRange('link')
                        .insertContent({
                            type: 'text',
                            text: text,
                            marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }]
                        })
                        .unsetMark('link') // 👈 Amankan kursor setelahnya
                        .insertContent(' ') // 👈 Masukkan spasi otomatis murni teks biasa
                        .run();
                } else {
                    // KONDISI B: Kursor kosongan
                    window.tiptapEditor.chain()
                        .focus()
                        .insertContent({
                            type: 'text',
                            text: text,
                            marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }]
                        })
                        .unsetMark('link') // 👈 Matikan mark link pada kursor
                        .insertContent(' ') // 👈 Masukkan spasi otomatis murni teks biasa
                        .run();
                }

                this.clearLinkInputs();
            },

            clearLinkInputs() {
                this.linkInputUrl = '';
                this.linkInputText = '';
                this.hasSelection = false;
                this.updatedAt = Date.now();
            },

            unsetLink() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain()
                    .focus()
                    .extendMarkRange('link')
                    .unsetLink()
                    .insertContent(' ') // Tetap beri spasi setelah mencopot link
                    .run();

                this.clearLinkInputs();
            },
            // ➕ TAMBAHKAN DUA METHOD BARU INI DI SINI
            changeFontFamily(fontName) {
                if (!window.tiptapEditor) return;

                if (fontName === 'default') {
                    window.tiptapEditor.chain().focus().unsetFontFamily().run();
                } else {
                    window.tiptapEditor.chain().focus().setFontFamily(fontName).run();
                }
                this.updatedAt = Date.now();
            },

            getCurrentFont() {
                this.updatedAt; // Trigger reaktivitas Alpine saat seleksi teks berubah
                if (!window.tiptapEditor) return 'default';

                // Mengambil font-family aktif dari teks tempat kursor berada
                const attributes = window.tiptapEditor.getAttributes('textStyle');
                return attributes.fontFamily || 'default';
            },

            // ➕ TAMBAHKAN HELPER METHOD LIST INI:
            toggleCustomOrderedList(type) {
                if (!window.tiptapEditor) return;

                // Jika list belum aktif, buat list baru dengan jenis yang dipilih
                if (!window.tiptapEditor.isActive('orderedList')) {
                    window.tiptapEditor.chain().focus().toggleOrderedList().updateAttributes('orderedList', { listStyle: type }).run();
                } else {
                    // Jika list sudah aktif, ubah tipenya saja secara dinamis
                    window.tiptapEditor.chain().focus().updateAttributes('orderedList', { listStyle: type }).run();
                }
                this.updatedAt = Date.now();
            },

            toggleHiddenMarks() {
                if (!window.tiptapEditor) return;

                // 1. Balik nilai state Alpine terlebih dahulu
                this.showMarks = !this.showMarks;

                // 2. Jalankan command Tiptap
                window.tiptapEditor.commands.toggleHiddenMarks();

                // 3. Paksa ProseMirror gambar ulang class pembungkusnya
                const { state, view } = window.tiptapEditor;
                if (view) {
                    view.dispatch(state.tr.setMeta('hiddenMarksTrigger', Date.now()));
                }

                // 🛠️ KUNCI 1: Paksa pembaruan state reaktif Alpine secara instan
                this.updatedAt = Date.now();

                console.log('Status showMarks saat ini:', this.showMarks);
            },
            checkButtonActive(name, params = {}, type = 'default') {
                // Pemicu Reaktivitas Alpine
                const forceReactiveUpdate = this.updatedAt > 0;

                if (!window.tiptapEditor || !forceReactiveUpdate) return false;

                switch (type) {
                    case 'heading':
                        return window.tiptapEditor.isActive('heading', { level: parseInt(name) });

                    case 'textAlign':
                        const currentAlign = window.tiptapEditor.getAttributes('paragraph').textAlign
                                          || window.tiptapEditor.getAttributes('heading').textAlign;
                        if (!currentAlign) {
                            return params.textAlign === 'left';
                        }
                        return currentAlign === params.textAlign;

                    case 'default':
                    default:
                        // 💡 SOLUSI EMAS: Jika params tidak memiliki kunci objek sama sekali (objek kosong {}),
                        // jalankan isActive() hanya dengan mengirimkan namanya saja tanpa gangguan objek kosong!
                        if (Object.keys(params).length === 0) {
                            return window.tiptapEditor.isActive(name);
                        }

                        // Jika params memiliki isi atribut (seperti kustom indent { indent: true })
                        return window.tiptapEditor.isActive(name, params);
                }
            },

            onUploadSuccess(responseFile, cloudUrl) {
                const editor = window.tiptapEditor;
                if (!editor) return;

                // Masukkan langsung ke posisi kursor aktif saat ini (karena kursor tidak lagi dipaksa melompat)
                editor.chain().focus().setImage({ src: cloudUrl }).run();
            }
        }
    }
})
