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
import { Color } from '@tiptap/extension-color'
import { FontFamily } from '@tiptap/extension-font-family'
import { Underline } from '@tiptap/extension-underline'
import { OrderedList } from '@tiptap/extension-ordered-list'

import GlobalDragHandle from 'tiptap-extension-global-drag-handle'
import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'
import { DOMParser as ProseMirrorDOMParser } from '@tiptap/pm/model'

import { HiddenMarks } from './extensions/HiddenMarks.js'
import { LinkBackspaceHandler } from './extensions/LinkBackspaceHandler.js'
import { ParagraphIndent } from './extensions/ParagraphIndent.js'

// import { ChipGroup } from './node/ChipGroup.js'
// import { ContactItem } from './unverified/ContactItem.js'

import { Card } from './node/Card.js'
import { StepCard, StepNumber, StepContent } from './node/StepCard.js'
// import { TrailingNode } from './node/TrailingNode.js'
import { TransferCard } from './unverified/TransferCard.js'
import { MediaPlaceholder } from './node/MediaPlaceholder.js'
import { SectionBlock } from './node/SectionBlock.js'
import { Eyebrow } from './node/EyeBrow.js'

import { Column, ColumnBlock } from "./node/ColumnLayout.js";
import { FontSize } from "./node/FontSize.js";
import { Pill } from "./node/Pill.js";
import { ImageBlock } from "./node/ImageBlock.js";


const ALLOWED_FONTS = ['Arial', 'Fraunces', 'Times New Roman', 'Roboto', 'Jetbrains Mono', 'Open Sans', 'Plus Jakarta Sans'];

// Daftar ikon untuk dropdown pemilih icon Eyebrow di toolbar.
// Path SVG-nya harus tetap sinkron dengan ICONS di node/EyeBrow.js
const EYEBROW_ICONS = [
    { key: 'crosshair', label: 'Crosshair', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>` },
    { key: 'star', label: 'Star', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>` },
    { key: 'zap', label: 'Zap', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>` },
    { key: 'sparkles', label: 'Sparkles', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>` },
    { key: 'flag', label: 'Flag', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>` },
    { key: 'tag', label: 'Tag', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>` },
    { key: 'badge-check', label: 'Badge Check', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>` },
    { key: 'trending-up', label: 'Trending Up', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>` },
];
// Preset warna latar + border untuk dropdown Pill.
// borderColor: null berarti tanpa border (sesuai default lama Pill.js)
const PILL_COLOR_PRESETS = [
    { key: 'green', label: 'Hijau (default)', backgroundColor: '#E9F1EB', borderColor: null },
    { key: 'red', label: 'Merah', backgroundColor: '#FEE2E2', borderColor: '#FCA5A5' },
    { key: 'blue', label: 'Biru', backgroundColor: '#DBEAFE', borderColor: '#93C5FD' },
    { key: 'yellow', label: 'Kuning', backgroundColor: '#FEF9C3', borderColor: '#FDE68A' },
    { key: 'purple', label: 'Ungu', backgroundColor: '#F3E8FF', borderColor: '#D8B4FE' },
    { key: 'gray', label: 'Abu-abu', backgroundColor: '#F3F4F6', borderColor: '#D1D5DB' },
];

const lowlight = createLowlight(common)

// document.addEventListener('alpine:init', () => {
//     const original = window.Alpine.initTree;
//     window.Alpine.initTree = function (...args) {
//         console.trace('🚨 Alpine.initTree dipanggil ulang di sini:');
//         return original.apply(this, args);
//     };
// });


document.addEventListener('livewire:init', () => {
    const protectedKeys = ['tiptap-instance-permanen', 'tiptap-editor-shell'];
    const isProtected = (el) => el && el.getAttribute && protectedKeys.includes(el.getAttribute('wire:key'));

    Livewire.hook('morph.updating', ({ el, skip }) => {
        if (isProtected(el)) skip();
    });

    Livewire.hook('morph.removing', ({ el, skip }) => {
        if (isProtected(el)) skip();
    });
});

function posToDOMRect(view, from, to) {
    const start = view.coordsAtPos(from);
    const end = view.coordsAtPos(to, -1);
    return {
        top: Math.min(start.top, end.top),
        bottom: Math.max(start.bottom, end.bottom),
        left: Math.min(start.left, end.left),
        right: Math.max(start.right, end.right),
        width: Math.max(start.left, end.left) - Math.min(start.left, end.left),
        height: Math.max(start.bottom, end.bottom) - Math.min(start.top, end.top),
    };
}

document.addEventListener('alpine:init', () => {

    const original = window.Alpine.initTree;
    window.Alpine.initTree = function (...args) {
        console.trace('🚨 Alpine.initTree dipanggil ulang di sini:');
        return original.apply(this, args);
    };

    // Simpan instance murni global agar terbebas dari Proxy Observer Alpine
    window.tiptapEditor = null;

    window.setupEditor = function (wireModelName, wireComponent, translations = {}) {
        return {
            translations: translations,
            updatedAt: Date.now(),
            uploadQueue: [],
            isUploading: false,
            showMarks: false,

            linkInputUrl: '',
            linkInputText: '',
            hasSelection: false,
            isLinkOpen: false,
            isEyebrowIconOpen: false,
            isPillColorOpen: false,
            customPillBg: '#E9F1EB',
            customPillBorder: '#000000',
            pillBorderEnabled: false,
            wordCount: 0,
            isLocalDrag: false,
            syncTimeout: null,
            isFullscreen: false,



            // Fungsi helper untuk mengecek status
            shouldDisable() {
                return this.isUploading;
            },
            // Panggil ini saat proses upload gambar dimulai/selesai
            toggleUploadState(state) {
                this.isUploading = state;
            },

            init() {

                if
                (
                    window.tiptapEditor &&
                    !window.tiptapEditor.isDestroyed &&
                    window.tiptapEditor.view &&
                    document.body.contains(window.tiptapEditor.view.dom)
                ) {
                    console.log('[setupEditor] Editor sudah ada & sehat, lewati pembuatan instance baru.');
                    return;
                }

                const _this = this;
                const editorElement = this.$refs.editorElement;
                const initialContent = wireComponent.get(wireModelName) || '';
                const initialDiv = document.getElementById('initialContent');
                const startingHTML = initialDiv ? initialDiv.innerHTML : '';

                this.$watch('$wire.title', (newTitle) => {
                    this.syncTitleToEditor(newTitle);
                });


                // 🌟 SABUK PENGAMAN REVISI: Cek apakah elemen DOM INI sudah punya editor
                // Ini menyelamatkan ketikan saat Livewire transaksi/error validasi
                if (editorElement && editorElement.__tiptap) {
                    window.tiptapEditor = editorElement.__tiptap;
                    return;
                }

                // 🌟 SABUK PENGAMAN: Hentikan proses jika HTML berantakan
                if (!editorElement) {
                    console.error("🚨 Alpine kehilangan jejak x-ref='editorElement'! Periksa tag </div> di file Blade kamu.");
                    return;
                }

                window.addEventListener('offline', () => {
                    window.dispatchEvent(new CustomEvent('tampilkan-error', {
                        detail: "Koneksi internet terputus. Mohon periksa jaringan Anda!"
                    }));
                });

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

                const isEditable = editorElement.getAttribute('data-editable') === 'true';

                window.tiptapEditor = new Editor({
                    element: this.$refs.editorElement,
                    editable: isEditable,
                    extensions: [
                        // StarterKit standar
                        StarterKit.configure({ codeBlock: false, link: false, underline: false, orderedList:false, }),

                        GlobalDragHandle.configure({
                            dragHandleWidth: 32, // Lebar area deteksi hover (dalam px)
                            scrollTreshold: 100, // Kecepatan scroll saat drag mendekati tepi layar
                            customNodes: [ 'card', 'chip-group', 'callout', 'pull-quote', 'stat-highlight', 'figure', 'cta-button', 'column', ],
                        }),

                        Column,
                        ColumnBlock,
                        SectionBlock,
                        Eyebrow,
                        StepCard,
                        StepNumber,
                        StepContent,
                        // TrailingNode,
                        Card,
                        Pill,
                        ImageBlock,
                        FontSize,

                        HiddenMarks.configure({visible: false }),

                        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-forest underline cursor-pointer' } }),

                        LinkBackspaceHandler,
                        ParagraphIndent,

                        // TAMBAHKAN EXTENSION TEXT ALIGN DI SINI
                        TextAlign.configure({
                            types: ['heading', 'paragraph'], // Terapkan pada teks & judul
                        }),

                        // UBAH JADI INLINE: TRUE AGAR BISA SEBARIS DENGAN TEKS
                        Image.configure({
                            inline: true, // <-- Ubah dari false ke true
                            allowBase64: false,
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
                                    },
                                    // 🌟 TAMBAHKAN INI: Izinkan TipTap membaca token pelacak kursor paste
                                    'data-token': {
                                        default: null,
                                        parseHTML: element => element.getAttribute('data-token'),
                                        renderHTML: attrs => attrs['data-token'] ? { 'data-token': attrs['data-token'] } : {}
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
                        emptyEditorClass: 'is-editor-empty',
                        placeholder: ({ node, pos, editor }) => {
                            // 1. Placeholder untuk Angka (Karena dia Node resmi sekarang, Placeholder Tiptap bisa bekerja padanya!)
                            if (node.type.name === 'stepNumber') return '01';

                            // 2. Cek apakah kursor berada di dalam Step Card
                            const $pos = editor.state.doc.resolve(pos);
                            let isInStepCard = false;

                            for (let i = $pos.depth; i > 0; i--) {
                                if ($pos.node(i).type.name === 'stepCard') {
                                    isInStepCard = true;
                                    break;
                                }
                            }

                            if (isInStepCard) {
                                if (node.type.name === 'heading') return 'Judul langkah...';
                                if (node.type.name === 'paragraph') return 'Deskripsi langkah...';
                            }

                            if (node.type.name === 'heading') return 'Ketik judul...';

                            // return 'Mulai menulis artikel hebat Anda di sini...';
                            return translations.default || 'Mulai menulis artikel hebat Anda di sini...';
                        }
                    }),

                        // Placeholder.configure({
                        //     placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                        //     emptyEditorClass: 'is-editor-empty'
                        // }),

                        CodeBlockLowlight.configure({ lowlight }),


                        BubbleMenu.configure({
                            element: this.$refs.bubbleMenuElement,
                            tippyOptions: { duration: 150, zIndex: 99 },
                            shouldShow: ({ editor, from, to }) => {
                                if (from === to) return false;
                                if (editor.isActive('imageBlock')) return false; // 🌟 TAMBAHAN: jangan tabrakan dengan bubble menu gambar
                                return !editor.isActive('image') && !editor.isActive('mediaPlaceholder');
                            }
                        }),

                        BubbleMenu.extend({ name: 'imageBubbleMenu' }).configure({
                            element: this.$refs.imageBubbleMenu,
                            tippyOptions: {
                                duration: 150,
                                zIndex: 99,
                                hideOnClick: false,
                                getReferenceClientRect: () => {
                                    if (window.activeImageBlockRef?.el) {
                                        return window.activeImageBlockRef.el.getBoundingClientRect();
                                    }
                                    const { view, state } = window.tiptapEditor;
                                    const { from, to } = state.selection;
                                    return posToDOMRect(view, from, to);
                                },
                                onShow: (instance) => {
                                    const captionPos = window.activeImageBlockRef?.captionPosition;
                                    instance.setProps({ placement: captionPos === 'top' ? 'bottom' : 'top' });
                                },
                            },
                            shouldShow: ({ state }) => {
                                const { selection } = state;
                                // 🌟 KUNCI: hanya true kalau ini betul-betul NodeSelection gambar/imageBlock,
                                // BUKAN kursor teks yang kebetulan ada di dalam caption
                                return !!(selection.node && (selection.node.type.name === 'imageBlock' || selection.node.type.name === 'image'));
                            },
                        }),


                        TextStyle, // Wajib diisi karena FontFamily bergantung pada TextStyle
                        Color,
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

                    editorProps: {
                        // handleKeyDown: (view, event) => {
                        //     const { state } = view;
                        //     const { selection, doc } = state;

                        //     // 🌟 TAMBAHAN: Kembalikan kursor ke default setelah menekan Enter di Judul (H1)
                        //     if (event.key === 'Enter' && !event.shiftKey) {
                        //         const { $from, empty } = selection;

                        //         // Cek apakah kursor berada di dalam Heading 1 dan tidak ada teks yang di-blok
                        //         if (empty && $from.parent.type.name === 'heading' && $from.parent.attrs.level === 1) {

                        //             // Pastikan kursor berada persis di ujung paling kanan (akhir teks judul)
                        //             const isAtEnd = $from.parentOffset === $from.parent.content.size;

                        //             if (isAtEnd) {
                        //                 const editor = window.tiptapEditor;
                        //                 if (editor) {
                        //                     event.preventDefault(); // Cegah Enter bawaan Tiptap

                        //                     // Rentetan aksi otomatis ala Tiptap
                        //                     editor.chain()
                        //                         .splitBlock()          // 1. Buat baris baru ke bawah
                        //                         .setNode('paragraph')  // 2. Pastikan jadi paragraf biasa
                        //                         .clearNodes()          // 3. Hapus gaya text-align (Rata tengah/kanan)
                        //                         .unsetAllMarks()       // 4. Hapus warna, bold, italic, dll
                        //                         .run();

                        //                     return true; // Beri tahu browser bahwa aksi sudah selesai
                        //                 }
                        //             }
                        //         }
                        //     }

                        //     // 🌟 PERBAIKAN 1: Deteksi yang lebih kebal.
                        //     // ProseMirror terkadang menghitung ukuran node 2 angka lebih kecil/besar di ujung dokumen.
                        //     // 🌟 PERBAIKAN 1: Deteksi seleksi seluruh teks (Ctrl+A)
                        //     const isAllSelected = selection.from === 0 && selection.to >= doc.content.size - 2;

                        //     if (isAllSelected) {
                        //         const editor = window.tiptapEditor;
                        //         if (!editor) return false;

                        //         // 💡 KUNCI UX: Ambil judul yang masih selamat di kolom input (Livewire)
                        //         const savedTitle = wireComponent.get('title') || '';

                        //         // 1. Jika menekan Backspace atau Delete
                        //         if (event.key === 'Backspace' || event.key === 'Delete') {
                        //             event.preventDefault();

                        //             // 🌟 SMART RESET: Hancurkan isi artikel, tapi kembalikan Judul (H1) dan buat 1 paragraf kosong!
                        //             editor.commands.setContent(`<h1>${savedTitle}</h1><p></p>`);

                        //             // Pindahkan kursor secara cerdas:
                        //             if (savedTitle) {
                        //                 editor.commands.focus('end'); // Jika ada judul, kursor siap nulis isi artikel di paragraf bawah
                        //             } else {
                        //                 editor.commands.focus('start'); // Jika judul juga kosong, kursor di H1 atas
                        //             }

                        //             return true;
                        //         }

                        //         // 2. Jika langsung mengetik huruf/angka untuk menimpa
                        //         if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                        //             event.preventDefault();

                        //             // Selamatkan judul, taruh huruf baru yang diketik di paragraf bawah
                        //             editor.commands.setContent(`<h1>${savedTitle}</h1><p>${event.key}</p>`);
                        //             editor.commands.focus('end');

                        //             return true;
                        //         }
                        //     }

                        //     return false;
                        // },
                        handleKeyDown: (view, event) => {
                            const { state } = view;
                            const { selection, doc } = state;

                            // =====================================================================
                            // 🌟 1. PENANGANAN ENTER DI JUDUL (H1 UTAMA - BARIS PERTAMA)
                            // =====================================================================
                            if (event.key === 'Enter' && !event.shiftKey) {
                                const { $from, empty } = selection;

                                // KUNCI PENGAMAN: Pastikan kursor benar-benar ada di baris pertama (Index 0)
                                // dan berada di level utama dokumen (Depth 1), bukan di dalam SectionBlock/Card!
                                const isFirstNode = $from.depth === 1 && $from.index(0) === 0;

                                if (empty && $from.parent.type.name === 'heading' && $from.parent.attrs.level === 1 && isFirstNode) {

                                    const isAtEnd = $from.parentOffset === $from.parent.content.size;

                                    if (isAtEnd) {
                                        const editor = window.tiptapEditor;
                                        if (editor) {
                                            event.preventDefault(); // Cegah Enter bawaan Tiptap

                                            // PERBAIKAN: Gunakan insertContentAt alih-alih splitBlock!
                                            // Ini menaruh kotak <p> murni tanpa mewariskan gaya aneh dari H1
                                            const insertPos = $from.after();

                                            editor.chain()
                                                .insertContentAt(insertPos, { type: 'paragraph' })
                                                .setTextSelection(insertPos + 1) // Pindah kursor ke <p> baru
                                                .scrollIntoView()
                                                .run();

                                            return true;
                                        }
                                    }
                                }
                            }

                            // =====================================================================
                            // 🌟 2. PENANGANAN CTRL+A (SELECT ALL) LALU HAPUS/KETIK BARU
                            // =====================================================================
                            // Deteksi apakah pengguna menyeleksi seluruh teks (Toleransi 2 angka ukuran node)
                            const isAllSelected = selection.from === 0 && selection.to >= doc.content.size - 2;

                            if (isAllSelected) {
                                const editor = window.tiptapEditor;
                                if (!editor) return false;

                                // Ambil judul yang masih selamat dari komponen Livewire
                                const savedTitle = wireComponent.get('title') || '';

                                // SKENARIO A: Menekan tombol Backspace atau Delete
                                if (event.key === 'Backspace' || event.key === 'Delete') {
                                    event.preventDefault();

                                    // Reset bersih: Tulis ulang Judul + Paragraf kosong di bawahnya
                                    editor.commands.setContent(`<h1>${savedTitle}</h1><p></p>`);

                                    if (savedTitle) {
                                        editor.commands.focus('end'); // Jika judul ada, kursor siap nulis di bawah
                                    } else {
                                        editor.commands.focus('start'); // Jika judul kosong, kursor di H1 atas
                                    }

                                    return true;
                                }

                                // SKENARIO B: Langsung mengetik huruf/angka baru menimpa semua teks
                                if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                                    event.preventDefault();

                                    // Reset bersih: Tulis ulang Judul + Taruh huruf yang diketik di paragraf bawah
                                    editor.commands.setContent(`<h1>${savedTitle}</h1><p>${event.key}</p>`);
                                    editor.commands.focus('end');

                                    return true;
                                }
                            }

                            // Biarkan aksi keyboard lainnya ditangani oleh ProseMirror bawaan
                            return false;
                        },
                        handleDragOver: (view, event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            return true;
                        },

                        handleDrop: (view, event, slice, moved) => {
                            if (moved) return false;

                            const files = event.dataTransfer ? event.dataTransfer.files : [];
                            let imageFound = false;

                            // Cek apakah ada file gambar di dalam drop
                            for (const file of files) {
                                if (file.type.startsWith('image/')) {
                                    imageFound = true;
                                    break;
                                }
                            }

                            if (imageFound) {
                                // Hentikan propagasi agar Alpine @drop di HTML tidak ikut terpancing (mencegah duplikat)
                                event.preventDefault();
                                event.stopPropagation();

                                const editorWrapper = document.querySelector('[x-data*="setupEditor"]');
                                const alpineData = editorWrapper && window.Alpine ? window.Alpine.$data(editorWrapper) : null;

                                if (alpineData) {
                                    // 🌟 KUNCI: Lempar file ke fungsi Alpine secara eksklusif
                                    alpineData.handleMultipleImageUpload(files);
                                }
                                return true;
                            }

                            return false;
                        },

                        handlePaste(view, event, slice) {
                            const items = (event.clipboardData || event.originalEvent.clipboardData).items;
                            let imageFound = false;
                            let filesToUpload = [];

                            // Kumpulkan semua file gambar yang di-paste
                            for (const item of items) {
                                if (item.type.indexOf('image') === 0) {
                                    const file = item.getAsFile();
                                    if (file) {
                                        imageFound = true;
                                        filesToUpload.push(file);
                                    }
                                }
                            }

                            if (imageFound) {
                                event.preventDefault();
                                event.stopPropagation();

                                const editorWrapper = document.querySelector('[x-data*="setupEditor"]');
                                const alpineData = editorWrapper && window.Alpine ? window.Alpine.$data(editorWrapper) : null;

                                if (alpineData && filesToUpload.length > 0) {
                                    // 🌟 Lempar juga paste file fisik ke satu pintu utama
                                    alpineData.handleMultipleImageUpload(filesToUpload);
                                }
                                return true;
                            }

                            return false;
                        },

                        transformPastedHTML(html) {
                            if (!html) return html;

                            const editorWrapper = document.querySelector('[x-data*="setupEditor"]');
                            const alpineData = editorWrapper && window.Alpine ? window.Alpine.$data(editorWrapper) : null;

                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            const images = doc.querySelectorAll('img, v\\:imagedata, o\\:Graphic, v\\:shape img');
                            let base64ImageCount = 0;

                            images.forEach((img, index) => {
                                let src = img.getAttribute('src') || img.getAttribute('v:src') || '';

                                const isBase64 = src.startsWith('data:image/');
                                const isIncompatible = src.startsWith('file:///') ||
                                                    src.startsWith('blob:') ||
                                                    html.includes('msohtmlclip') ||
                                                    img.tagName.toLowerCase().includes('imagedata') ||
                                                    (src !== '' && !isBase64 && !src.startsWith('http') && !src.startsWith('https'));

                                if (isBase64 && alpineData) {
                                    base64ImageCount++;
                                    const uniqueToken = `pasted-token-${Date.now()}-${index}`;

                                    try {
                                        const parts = src.split(',');
                                        const mime = parts[0].match(/:(.*?);/)[1];
                                        const bstr = atob(parts[1]);
                                        let n = bstr.length;
                                        const u8arr = new Uint8Array(n);

                                        while (n--) {
                                            u8arr[n] = bstr.charCodeAt(n);
                                        }

                                        const extension = mime.split('/')[1] || 'png';
                                        const file = new File([u8arr], `pasted-inline-${index}.${extension}`, { type: mime });

                                        file.targetToken = uniqueToken;
                                        alpineData.uploadQueue.push(file);

                                        // 🌟 KUNCI 1: Gunakan 'title' untuk menitipkan token, pasti lolos sensor TipTap!
                                        img.outerHTML = `<img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/1x1.png" title="${uniqueToken}" alt="⏳ Mengunggah..." class="animate-pulse bg-zinc-200 rounded-lg aspect-video w-1/4 inline-block my-2" />`;

                                    } catch (e) {
                                        console.error('[Base64 Extractor] Gagal memparsing gambar inline:', e);
                                    }
                                } else if (isIncompatible) {
                                    const placeholderDom = doc.createElement('div');
                                    placeholderDom.setAttribute('data-type', 'media-placeholder');
                                    placeholderDom.className = 'media-placeholder-zone';

                                    const innerDiv = doc.createElement('div');
                                    innerDiv.className = 'placeholder-content';

                                    const span = doc.createElement('span');
                                    span.className = 'placeholder-text';
                                    span.innerText = `Tarik & lepas gambar ke sini atau `;

                                    const button = doc.createElement('button');
                                    button.setAttribute('type', 'button');
                                    button.className = 'placeholder-btn';
                                    button.setAttribute('onclick', 'window.triggerLocalFilePicker()');
                                    button.innerText = 'Cari Berkas';

                                    innerDiv.appendChild(span);
                                    innerDiv.appendChild(button);
                                    placeholderDom.appendChild(innerDiv);

                                    if (img.parentNode) {
                                        img.parentNode.replaceChild(placeholderDom, img);
                                    } else {
                                        img.outerHTML = placeholderDom.outerHTML;
                                    }
                                }
                            });

                            if (base64ImageCount > 0 && alpineData) {
                                alpineData.isUploading = true;
                                setTimeout(() => { alpineData.processNextInQueue(); }, 150);
                            }

                            doc.body.querySelectorAll('[style]').forEach(el => {
                                const style = el.getAttribute('style');
                                if (!style) return;

                                // Buang font-family biasa DAN varian mso-*-font-family milik Word
                                const cleanedStyle = style
                                    .replace(/(?:mso-[a-z-]*-)?font-family\s*:[^;]+;?/gi, '')
                                    .trim();

                                if (cleanedStyle) {
                                    el.setAttribute('style', cleanedStyle);
                                } else {
                                    el.removeAttribute('style');
                                }
                            });

                            // Buang juga tag <font face="..."> gaya lama (masih sering muncul dari HTML lawas)
                            doc.body.querySelectorAll('font[face]').forEach(el => el.removeAttribute('face'));

                            // Buang blok <style> yang sering disisipkan Word (definisi class Mso*)
                            doc.body.querySelectorAll('style').forEach(el => el.remove());

                            return doc.body.innerHTML;
                        },
                    },

                    content: initialContent,

                    onCreate: ({ editor }) => {
                        // 🌟 PERBAIKAN: Gunakan penghitung manual seperti di onUpdate
                        const text = editor.getText();
                        _this.wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;


                        // 🔥 IKAT INSTANCE: Simpan Tiptap ke dalam elemen HTML-nya secara fisik
                        editorElement.__tiptap = editor;

                        // 🌟 PERBAIKAN 2: Ambil langsung dari komponen Livewire
                        setTimeout(() => {
                            const judulAwal = wireComponent.get('title') || '';
                            _this.syncTitleToEditor(judulAwal);
                        }, 100);
                    },

                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now();

                        // Penghitungan kata manual
                        const text = editor.getText();
                        _this.wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;

                        const firstNode = editor.state.doc.firstChild;
                        if (firstNode && firstNode.type.name === 'heading' && firstNode.attrs.level === 1) {
                            const h1Text = firstNode.textContent;

                            // 🌟 PERBAIKAN 3: Gunakan wireComponent untuk mengecek dan menyimpan data
                            if (wireComponent.get('title') !== h1Text) {
                                wireComponent.set('title', h1Text, false); // false = jangan trigger network request/loading
                            }
                        }

                        if (_this.isUploading) return;
                        clearTimeout(_this.syncTimeout);
                        _this.syncTimeout = setTimeout(() => {
                            if (window.tiptapEditor) {
                                wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                            }
                        }, 500);

                    },


                    onSelectionUpdate() {
                        _this.updatedAt = Date.now()
                    },
                    onTransaction: () => {
                        _this.updatedAt = Date.now();
                    }
                });

                // 🌟 TAMBAHAN: Sabuk pengaman agar editor tidak kosong setelah tombol 'Simpan' ditekan
                window.addEventListener('article-saved', (event) => {
                    if (window.tiptapEditor && event.detail.newContent) {
                        window.tiptapEditor.commands.setContent(event.detail.newContent, false);
                    }
                });

                Alpine.effect(() => {
                    const loading = _this.isUploading;
                    const editorElement = _this.$refs.editorElement;

                    if (editorElement) {
                        // Toggle class untuk CSS
                        editorElement.classList.toggle('tiptap-locked', loading);

                        // 🌟 PAKSA INLINE STYLE AGAR MENANG MELAWAN SEMUA CSS
                        if (loading) {
                            editorElement.style.setProperty('cursor', 'wait', 'important');
                        } else {
                            editorElement.style.removeProperty('cursor');
                        }
                    }
                });

                // 🌟 OPTIMASI: Jangan cegat keydown secara global jika tidak sedang upload!
                const blockEvent = (event) => {
                    if (_this.isUploading) {
                        // Jika pengguna menekan tombol ketik saat sedang upload, baru kita kunci
                        if (event.type === 'keydown') {
                            // Izinkan tombol navigasi (panah, backspace) agar tidak beku total
                            const allowedKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Backspace'];
                            if (allowedKeys.includes(event.key)) return;
                        }
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        return false;
                    }
                };

                // Pasang listener secara pasif agar tidak merusak frame rate ketikan browser
                editorElement.addEventListener('paste', blockEvent, { capture: true, passive: false });
                editorElement.addEventListener('keydown', blockEvent, { capture: true, passive: false });
                editorElement.addEventListener('drop', blockEvent, { capture: true, passive: false });


            },

            // Tambahkan ini sejajar/di bawah fungsi init() atau fungsi lainnya
            destroy() {
                if (this.$refs.editorElement && this.$refs.editorElement.__tiptap) {
                    this.$refs.editorElement.__tiptap.destroy();
                    delete this.$refs.editorElement.__tiptap;
                }
                window.tiptapEditor = null;
            },

            // 🌟 FUNGSI BARU: Sinkronisasi Teks ke Editor Tanpa Merusak Warna/Format
            syncTitleToEditor(newTitle) {
                if (!window.tiptapEditor) return;

                const { state, view } = window.tiptapEditor;
                const tr = state.tr;
                const firstNode = state.doc.firstChild;

                // Pastikan teks aman, jika null jadikan string kosong
                const safeTitle = newTitle || '';

                if (firstNode && firstNode.type.name === 'heading' && firstNode.attrs.level === 1) {
                    // Jika teksnya sudah sama, hentikan (mencegah infinite loop)
                    if (firstNode.textContent === safeTitle) return;

                    // Mengganti Teks di DALAM H1 dengan kalkulasi posisi node ProseMirror
                    // Angka 1 adalah awal teks di dalam node pertama.
                    const from = 1;
                    const to = firstNode.nodeSize - 1;

                    if (safeTitle) {
                        tr.replaceWith(from, to, state.schema.text(safeTitle));
                    } else {
                        tr.delete(from, to); // Kosongkan H1 jika input dihapus
                    }
                    view.dispatch(tr);
                } else {
                    // Jika belum ada H1 sama sekali, ciptakan H1 baru di baris paling atas
                    if (safeTitle) {
                        const h1 = state.schema.nodes.heading.create({ level: 1 }, state.schema.text(safeTitle));
                        tr.insert(0, h1);
                        view.dispatch(tr);
                    }
                }
            },

            // 🌟 FUNGSI BARU: Mendorong teks dari Input ke dalam Editor tanpa merusak gaya (style)
            // syncTitleToEditor(newTitle) {
            //     if (!window.tiptapEditor) return;

            //     const { state, view } = window.tiptapEditor;
            //     const tr = state.tr;
            //     const firstNode = state.doc.firstChild;

            //     // Pastikan teks aman (tidak null)
            //     const safeTitle = newTitle || '';

            //     if (firstNode && firstNode.type.name === 'heading' && firstNode.attrs.level === 1) {
            //         // Jika teks sudah sama, hentikan proses untuk mencegah infinite loop
            //         if (firstNode.textContent === safeTitle) return;

            //         // KUNCI: Kita hanya mengganti Teks di DALAM H1,
            //         // sehingga atribut warna dan posisi H1 tetap dipertahankan!
            //         const from = 1;
            //         const to = firstNode.nodeSize - 1;

            //         if (safeTitle) {
            //             tr.replaceWith(from, to, state.schema.text(safeTitle));
            //         } else {
            //             tr.delete(from, to); // Kosongkan isi H1 jika input dihapus
            //         }
            //         view.dispatch(tr);
            //     } else {
            //         // Jika belum ada H1 di paling atas, buat H1 baru
            //         if (safeTitle) {
            //             const h1 = state.schema.nodes.heading.create({ level: 1 }, state.schema.text(safeTitle));
            //             tr.insert(0, h1);
            //             view.dispatch(tr);
            //         }
            //     }
            // },

            // 1. Pintu Masuk File
            handleMultipleImageUpload(files) {
                if (files.length === 0) return;

                const wasUploading = this.isUploading;
                this.isUploading = true;
                this.isLocalDrag = false;

                if (typeof this.isDragging !== 'undefined') {
                    this.isDragging = false;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                const MAX_SIZE_MB = 15;
                let errorMessages = [];

                Array.from(files).forEach(file => {
                    const isTooBig = file.size > MAX_SIZE_MB * 1024 * 1024;
                    const isInvalidType = !allowedTypes.includes(file.type);

                    if (isInvalidType) {
                        errorMessages.push(`Format ${file.name} tidak didukung.`);
                    } else if (isTooBig) {
                        errorMessages.push(`${file.name} terlalu besar (>15MB).`);
                    } else {
                        this.uploadQueue.push(file);
                    }
                });

                if (errorMessages.length > 0) {
                    window.dispatchEvent(new CustomEvent('tampilkan-error', {
                        detail: errorMessages.join(' | ')
                    }));
                }

                if (this.uploadQueue.length > 0 && !wasUploading) {
                    this.processNextInQueue();
                } else if (this.uploadQueue.length === 0) {
                    this.isUploading = false;
                }
            },

            // 2. Mesin Antrean
            async processNextInQueue() {
                // 🔑 KUNCI PENYELAMAT: isUploading hanya boleh dimatikan di sini
                if (this.uploadQueue.length === 0) {
                    this.isUploading = false;

                    if (window.tiptapEditor) {
                        const currentHTML = window.tiptapEditor.getHTML();

                        // 🌟 PERBAIKAN: Jangan pernah kirim teks kosong ke Livewire setelah upload
                        if (currentHTML !== '<p></p>' && currentHTML !== '') {
                            wireComponent.set(wireModelName, currentHTML, false);
                        }
                    }
                    this.updatedAt = Date.now();
                    return;
                }
                // if (this.uploadQueue.length === 0) {
                //     this.isUploading = false;
                //     if (window.tiptapEditor) {
                //         wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                //     }
                //     this.updatedAt = Date.now();
                //     return;
                // }

                this.isUploading = true;
                const originalFile = this.uploadQueue.shift();

                try {
                    let finalFile;

                    // 🌟 PERBAIKAN GIF: Biarkan GIF lewat apa adanya!
                    // Jangan ubah namanya jadi .webp di sini, agar backend Livewire Anda
                    // (yang mengecek if $extension === 'gif') bisa menjalankan Imagick.
                    if (originalFile.type === 'image/gif') {
                        finalFile = originalFile;
                    } else {
                        finalFile = await this.convertToWebp(originalFile);
                    }

                    if (originalFile.targetToken) {
                        finalFile.targetToken = originalFile.targetToken;
                    }

                    this.executeLivewireUpload(finalFile);
                } catch (compressError) {
                    console.error('[Processor Error]', compressError);
                    this.executeLivewireUpload(originalFile);
                }
            },

            //upload with direct routing
            async executeLivewireUpload(targetFile) {
                if (!navigator.onLine) {
                    window.dispatchEvent(new CustomEvent('tampilkan-error', {
                        detail: "Koneksi internet terputus! Silakan periksa jaringan Anda."
                    }));
                    this.removeDummyImage(targetFile.targetToken);
                    this.processNextInQueue();
                    return;
                }

                const formData = new FormData();
                formData.append('image', targetFile);

                try {
                    const response = await fetch('/editor/upload-image', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) throw new Error(`Upload gagal (status ${response.status})`);

                    const data = await response.json();
                    if (!data.url) throw new Error('Response tidak berisi URL gambar');

                    if (window.tiptapEditor) {
                        this.replaceDummyWithImage(targetFile.targetToken, data.url, targetFile.name);
                    }
                } catch (error) {
                    console.error('[Upload Error]', error);
                    window.dispatchEvent(new CustomEvent('tampilkan-error', {
                        detail: `Gagal mengunggah ${targetFile.name}. Periksa koneksi internet Anda.`
                    }));
                    this.removeDummyImage(targetFile.targetToken);
                } finally {
                    this.processNextInQueue();
                }
            },

            // 4. Helper Pemrosesan & Pembersihan
            removeDummyImage(token) {
                if (!token || !window.tiptapEditor) return;
                window.tiptapEditor.state.doc.descendants((node, pos) => {
                    if (node.type.name === 'image' && node.attrs.title === token) {
                        window.tiptapEditor.chain().setNodeSelection(pos).deleteSelection().run();
                        return false;
                    }
                });
            },

            replaceDummyWithImage(token, finalUrl, fileName) {
                // 🌟 SOLUSI: Gunakan JSON Node, bukan HTML String.
                // Ini lebih aman karena langsung didefinisikan sebagai object node Tiptap.
                const imageNode = {
                    type: 'image',
                    attrs: {
                        src: finalUrl,
                        alt: fileName,
                        title: fileName,
                        class: 'rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block',
                        style: 'width: 25%; display: block !important; margin-left: auto !important; margin-right: auto !important; margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; float: none !important;'
                    }
                };

                // KASUS 1: Upload via tombol MediaPlaceholder (tidak pakai token)
                if (!token) {
                    // Gunakan .insertContent dengan object agar Tiptap mencoba menyelesaikan skema
                    window.tiptapEditor.chain().deleteSelection().insertContent(imageNode).run();
                    return;
                }

                // KASUS 2: Upload via Drag & Drop / Paste (mengganti token placeholder)
                const findToken = () => {
                    let foundPos = null;
                    window.tiptapEditor.state.doc.descendants((node, pos) => {
                        if (node.type.name === 'image' && node.attrs.title === token) {
                            foundPos = pos; return false;
                        }
                    });
                    return foundPos;
                };

                let actualPos = findToken();
                if (actualPos !== null) {
                    // Gunakan tr.replaceWith untuk mengganti node dummy dengan imageNode secara langsung (bypass parser)
                    const tr = window.tiptapEditor.state.tr.replaceWith(
                        actualPos,
                        actualPos + 1,
                        window.tiptapEditor.schema.node('image', imageNode.attrs)
                    );
                    window.tiptapEditor.view.dispatch(tr);
                } else {
                    // Fallback
                    let retries = 0;
                    const interval = setInterval(() => {
                        actualPos = findToken();
                        if (actualPos !== null) {
                            clearInterval(interval);
                            const tr = window.tiptapEditor.state.tr.replaceWith(
                                actualPos,
                                actualPos + 1,
                                window.tiptapEditor.schema.node('image', imageNode.attrs)
                            );
                            window.tiptapEditor.view.dispatch(tr);
                        } else if (retries > 10) {
                            clearInterval(interval);
                            window.tiptapEditor.chain().insertContent(imageNode).run();
                        }
                        retries++;
                    }, 150);
                }
                if (actualPos !== null) {
                    const tr = window.tiptapEditor.state.tr.replaceWith(
                        actualPos,
                        actualPos + 1,
                        window.tiptapEditor.schema.node('image', imageNode.attrs)
                    );
                    window.tiptapEditor.view.dispatch(tr);

                    // 🔥 TAMBAHKAN INI: Memaksa Tiptap menyegarkan tampilan
                    window.tiptapEditor.view.updateState(window.tiptapEditor.state);
                }
            },


            flushEditorSync() {
                clearTimeout(this.syncTimeout);
                if (window.tiptapEditor) {
                    wireComponent.set(wireModelName, window.tiptapEditor.getHTML(), false);
                }
            },
            convertToWebp(file) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);

                    reader.onload = (event) => {
                        const img = new window.Image();
                        img.src = event.target.result;

                        img.onload = () => {
                            let width = img.naturalWidth || img.width;
                            let height = img.naturalHeight || img.height;

                            if (!width || !height) return resolve(file);

                            const MAX_DIMENSION = 1920;
                            if (width > MAX_DIMENSION || height > MAX_DIMENSION) {
                                const ratio = Math.min(MAX_DIMENSION / width, MAX_DIMENSION / height);
                                width = Math.round(width * ratio);
                                height = Math.round(height * ratio);
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            canvas.toBlob((blob) => {
                                if (!blob || blob.size === 0) return resolve(file);
                                const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                                resolve(new File([blob], newFileName, { type: "image/webp", lastModified: Date.now() }));
                            }, "image/webp", 0.90);
                        };
                        img.onerror = () => resolve(file);
                    };
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

            insertInfoCard() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain()
                    .focus()
                    .setInfoCard({ icon: 'building', tag: 'Mitra', color: 'forest' })
                    .run();

                this.updatedAt = Date.now(); // Trigger reaktivitas Alpine
            },

            insertChipGroup() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain()
                    .focus()
                    .setChipGroup({ label: 'Beban Sedang', color: 'gold', chips: ['Kabupaten A'] })
                    .run();

                this.updatedAt = Date.now();
            },

            // 🌟 HELPER BARU: deteksi tipe node gambar yang sedang aktif
            getActiveImageType() {
                if (!window.tiptapEditor) return null;
                if (window.tiptapEditor.isActive('imageBlock')) return 'imageBlock';
                if (window.tiptapEditor.isActive('image')) return 'image';
                return null;
            },

            isImageAlignActive(alignment) {
                this.updatedAt;
                const targetType = this.getActiveImageType();
                if (!targetType) return false;

                const attrs = window.tiptapEditor.getAttributes(targetType);
                const style = attrs.style || '';

                if (alignment === 'left') return style.includes('float: left') || style.includes('float:left');
                if (alignment === 'right') return style.includes('float: right') || style.includes('float:right');
                if (alignment === 'center') return style.includes('display: block') || style.includes('margin-left: auto');

                return false;
            },

            isImageWidthActive(width) {
                this.updatedAt;
                const targetType = this.getActiveImageType();
                if (!targetType) return false;

                const attrs = window.tiptapEditor.getAttributes(targetType);
                const style = attrs.style || '';

                return style.includes(`width: ${width}%`) || style.includes(`width:${width}%`);
            },

            deleteSelectedImage() {
                if (!this.getActiveImageType()) return;
                window.tiptapEditor.chain().focus().deleteSelection().run();
                this.updatedAt = Date.now();
            },

            // TESTING
            setImageWidth(width) {
                const targetType = this.getActiveImageType();
                if (!targetType) return;

                const { selection } = window.tiptapEditor.state;
                const currentPosition = selection.from;

                const currentAttributes = window.tiptapEditor.getAttributes(targetType);
                const currentStyle = currentAttributes.style || '';

                // 🌟 Tentukan format nilai width berdasarkan tipe node
                // CLAUDE, WORKING WITH MINOR BUG
                const widthValue = targetType === 'imageBlock'
                    ? `calc(${width / 100} * min(100%, 64rem)) !important`
                    : `${width}% !important`;

                // GEMINI TESTING : BUGGING THE isImageWidth
                // const widthValue = `calc(${width / 100} * min(100%, 64rem)) !important`;

                // 🔧 Tambahkan flag 'g' di akhir regex agar SEMUA deklarasi width lama terhapus bersih
                const cleanedStyle = currentStyle.replace(/width:\s*[^;]+;?/g, '').trim();

                // Gabungkan style baru dengan sisa style yang sudah dibersihkan
                let newStyle = `width: ${widthValue};`;
                if (cleanedStyle) {
                    newStyle += ` ${cleanedStyle}`;
                }

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes(targetType, { style: newStyle })
                    .setNodeSelection(currentPosition)
                    .run();

                this.updatedAt = Date.now();
            },
            // current
            // setImageWidth(width) {
            //     const targetType = this.getActiveImageType();
            //     if (!targetType) return;

            //     const { selection } = window.tiptapEditor.state;
            //     const currentPosition = selection.from;

            //     const currentAttributes = window.tiptapEditor.getAttributes(targetType);
            //     const currentStyle = currentAttributes.style || '';

            //     // 🌟 Untuk imageBlock: hitung lebar terhadap kolom kertas (min(100%, 64rem)),
            //     // BUKAN terhadap .tiptap yang sebenarnya selebar penuh area krem
            //     const widthValue = targetType === 'imageBlock'
            //         ? `calc(${width / 100} * min(100%, 64rem)) !important`
            //         : `${width}% !important`;

            //     const cleanedStyle = currentStyle.replace(/width:\s*[^;]+;?/, '').trim(); // 🔧 regex diperluas, tadinya cuma cocok pola "25%"
            //     const newStyle = `width: ${widthValue}; ${cleanedStyle}`.trim();

            //     window.tiptapEditor.chain()
            //         .focus()
            //         .updateAttributes(targetType, { style: newStyle })
            //         .setNodeSelection(currentPosition)
            //         .run();

            //     this.updatedAt = Date.now();
            // },



            setImageAlignment(alignment) {
                const targetType = this.getActiveImageType();
                if (!targetType) return;

                const { selection } = window.tiptapEditor.state;
                const currentPosition = selection.from;

                const currentAttributes = window.tiptapEditor.getAttributes(targetType);
                const currentStyle = currentAttributes.style || '';

                // const widthMatch = currentStyle.match(/width:\s*\d+%/);
                // const existingWidth = widthMatch ? widthMatch[0] + ' !important;' : '';

                const widthMatch = currentStyle.match(/width:\s*[^;]+;?/);
                let existingWidth = widthMatch ? widthMatch[0].trim() : '';

                // Pastikan diakhiri dengan titik koma agar tidak merusak struktur CSS saat digabung dengan alignment
                if (existingWidth && !existingWidth.endsWith(';')) {
                    existingWidth += ';';
                }

                // 🌟 Meniru manual posisi kolom 64rem yang biasanya otomatis lewat margin:auto —
                // karena elemen yang di-float tidak bisa pakai margin:auto (selalu dianggap 0 oleh browser)
                const columnInset = 'max(0px, calc((100% - 64rem) / 2))';
                console.log(columnInset);
                let alignmentStyles = '';
                if (alignment === 'left') {
                    alignmentStyles = `float: left !important; display: inline-block !important; margin-left: ${columnInset} !important; margin-right: 1rem !important; margin-top: 0.25rem !important; margin-bottom: 0.5rem !important;`;
                } else if (alignment === 'right') {
                    alignmentStyles = `float: right !important; display: inline-block !important; margin-right: ${columnInset} !important; margin-left: 1rem !important; margin-top: 0.25rem !important; margin-bottom: 0.5rem !important;`;
                } else {
                    alignmentStyles = 'display: block !important; margin-left: auto !important; margin-right: auto !important; margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; float: none !important;';
                }

                window.tiptapEditor.chain()
                    .focus()
                    .updateAttributes(targetType, { style: `${existingWidth} ${alignmentStyles}`.trim() })
                    .setNodeSelection(currentPosition)
                    .run();

                this.updatedAt = Date.now();
            },

            isImageAlignActive(alignment) {
                this.updatedAt;
                const targetType = this.getActiveImageType();
                if (!targetType) return false;

                const attrs = window.tiptapEditor.getAttributes(targetType);
                const style = attrs.style || '';

                if (alignment === 'left') return style.includes('float: left') || style.includes('float:left');
                if (alignment === 'right') return style.includes('float: right') || style.includes('float:right');
                if (alignment === 'center') return style.includes('display: block') || style.includes('margin-left: auto');

                return false;
            },

            // TEST
            isImageWidthActive(width) {
                this.updatedAt; // Trigger reactivity Vue
                const targetType = this.getActiveImageType();
                if (!targetType) return false;

                const attrs = window.tiptapEditor.getAttributes(targetType);
                const style = attrs.style || '';

                if (targetType === 'imageBlock') {
                    // Cek apakah style mengandung format calc() persis seperti yang kita set
                    const expectedCalc = `calc(${width / 100} * min(100%, 64rem))`;
                    return style.includes(expectedCalc);
                }

                // Untuk gambar biasa, cek format persen
                return style.includes(`width: ${width}%`) || style.includes(`width:${width}%`);
            },
            // WORKING
            // isImageWidthActive(width) {
            //     this.updatedAt;
            //     const targetType = this.getActiveImageType();
            //     if (!targetType) return false;

            //     const attrs = window.tiptapEditor.getAttributes(targetType);
            //     const style = attrs.style || '';

            //     if (targetType === 'imageBlock') {
            //         return style.includes(`calc(${width / 100} * min(100%, 64rem))`);
            //     }
            //     return style.includes(`width: ${width}%`) || style.includes(`width:${width}%`);
            // },

            deleteSelectedImage() {
                if (!this.getActiveImageType()) return;
                window.tiptapEditor.chain().focus().deleteSelection().run();
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
                if (this.shouldDisable()) return;
                if (!window.tiptapEditor) return;
                if (args !== null) {
                    window.tiptapEditor.chain().focus()[command](args).run()
                } else {
                    window.tiptapEditor.chain().focus()[command]().run()
                }
                this.updatedAt = Date.now();
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
                this.toggleUploadState(false); //enable tombol lagi
                editor.chain().focus().setImage({ src: cloudUrl }).run();
            },

            notifyTheUser(message, type) {
                console.log('faggotron released', message, type);
                window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
                    // error, success, warning, info
                    detail: {
                        message: message || '404',
                        type: type || 'success'
                    }
                }));
            },
            // insertStepCard() {
            //     if (!window.tiptapEditor) return;
            //     window.tiptapEditor.chain().focus().insertStepCard({ number: '01' }).run();
            //     this.updatedAt = Date.now();
            // },

            // TESTIN
            addStep() {
                if (!window.tiptapEditor) {
                    console.error('Editor belum siap dimuat.');
                    return;
                };

                window.tiptapEditor.chain().focus().insertStepCard({
                    number: '', // KOSONGKAN INI agar placeholder '01' muncul
                    numBgColor: '#f3f4f6',
                    cardBgColor: '#ffffff'
                }).run();
            },

            // WORKING
            // addStep() {
            //     // Anda bisa melempar parameter (opsional) atau membiarkannya kosong
            //     // if (!window.tiptapEditor) return;
            //     if (!window.tiptapEditor) {
            //         console.error('Editor belum siap dimuat.');
            //         return;
            //     }
            //     window.tiptapEditor.chain().focus().insertStepCard({
            //         number: '01', // Diubah menjadi 01
            //         numBgColor: '#f3f4f6',
            //         cardBgColor: '#ffffff'
            //     }).run()
            // },

            insertSectionBlock() {
                if (!window.tiptapEditor) return;

                // 🛑 CEGAH KLIK TOMBOL: Cek apakah kursor sedang berada di dalam Section
                if (window.tiptapEditor.isActive('sectionBlock')) {
                    this.notifyTheUser('Tidak bisa menambahkan Section di dalam Section!', 'warning');
                    return; // Batalkan eksekusi
                }

                window.tiptapEditor.chain().focus().setSectionBlock().run();
                this.updatedAt = Date.now();
            },
            setFontSize(size) {
                if (!window.tiptapEditor) return;

                if (size === 'default') {
                    window.tiptapEditor.chain().focus().unsetFontSize().run();
                } else {
                    window.tiptapEditor.chain().focus().setFontSize(size).run();
                }
                this.updatedAt = Date.now();
            },

            getCurrentFontSize() {
                this.updatedAt; // Trigger reaktivitas Alpine
                if (!window.tiptapEditor) return 'default';

                const attributes = window.tiptapEditor.getAttributes('textStyle');
                return attributes.fontSize || 'default';
            },

            // ➕ EYEBROW ICON PICKER
            eyebrowIcons: EYEBROW_ICONS,

            toggleEyebrowIconMenu() {
                this.isEyebrowIconOpen = !this.isEyebrowIconOpen;
            },

            selectEyebrowIcon(icon) {
                if (!window.tiptapEditor) return;

                // Kalau kursor sudah di dalam node eyebrow, cukup ganti attribute icon-nya.
                // Kalau belum, ubah blok saat ini jadi node eyebrow baru dengan icon terpilih.
                if (window.tiptapEditor.isActive('eyebrow')) {
                    window.tiptapEditor.chain().focus().setEyebrowIcon(icon).run();
                } else {
                    window.tiptapEditor.chain().focus().setEyebrow(icon).run();
                }

                this.isEyebrowIconOpen = false;
                this.updatedAt = Date.now();
            },

            getCurrentEyebrowIcon() {
                this.updatedAt; // Trigger reaktivitas Alpine saat kursor/seleksi berubah
                if (!window.tiptapEditor) return EYEBROW_ICONS[0].key;

                const attributes = window.tiptapEditor.getAttributes('eyebrow');
                return attributes.icon || EYEBROW_ICONS[0].key;
            },

            getEyebrowIconSVG(key) {
                const found = EYEBROW_ICONS.find((item) => item.key === key);
                return found ? found.svg : EYEBROW_ICONS[0].svg;
            },
            // ➕ PILL COLOR PICKER
            pillColorPresets: PILL_COLOR_PRESETS,

            togglePillColorMenu() {
                // Saat dibuka, sinkronkan input custom color dengan warna pill
                // yang sedang aktif di kursor (kalau ada), biar tidak "reset" ke default
                if (!this.isPillColorOpen && window.tiptapEditor) {
                    const attrs = window.tiptapEditor.getAttributes('pill');
                    this.customPillBg = attrs.backgroundColor || '#E9F1EB';
                    this.customPillBorder = attrs.borderColor || '#000000';
                    this.pillBorderEnabled = !!attrs.borderColor;
                }
                this.isPillColorOpen = !this.isPillColorOpen;
            },

            selectPillPreset(preset) {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain().focus().setPillColor({
                    backgroundColor: preset.backgroundColor,
                    borderColor: preset.borderColor || null,
                }).run();

                this.isPillColorOpen = false;
                this.updatedAt = Date.now();
            },

            applyCustomPillColor() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain().focus().setPillColor({
                    backgroundColor: this.customPillBg,
                    borderColor: this.pillBorderEnabled ? this.customPillBorder : null,
                }).run();

                this.updatedAt = Date.now();
            },

            removePill() {
                if (!window.tiptapEditor) return;

                window.tiptapEditor.chain().focus().unsetPill().run();
                this.isPillColorOpen = false;
                this.updatedAt = Date.now();
            },

            getCurrentPillSwatch() {
                this.updatedAt; // Trigger reaktivitas Alpine
                if (!window.tiptapEditor) return '#E9F1EB';

                const attrs = window.tiptapEditor.getAttributes('pill');
                return attrs.backgroundColor || '#E9F1EB';
            },
            createImageCaption() {
                this.updatedAt; // Trigger reaktivitas Alpine
                if (!window.tiptapEditor) return;
                window.tiptapEditor.chain().focus().addImageCaption().run();
            },
            toggleCaptionPosition() {
                if (!window.tiptapEditor) return;
                window.tiptapEditor.chain().focus().toggleCaptionPosition().run();
                this.updatedAt = Date.now();
            },
            removeCurrentImageCaption() {
                if (!window.tiptapEditor) return;
                window.tiptapEditor.chain().focus().removeImageCaption().run();
                this.updatedAt = Date.now();
            },
            isImageCaptionActive() {
                this.updatedAt;
                return !!window.tiptapEditor?.isActive('imageBlock');
            },
            isCaptionPositionTop() {
                this.updatedAt;
                if (!window.tiptapEditor?.isActive('imageBlock')) return false;
                return window.tiptapEditor.getAttributes('imageBlock').captionPosition === 'top';
            },
        }
    }
})
