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
import { StepCard } from './node//StepCard.js'
import { TransferCard } from './unverified/TransferCard.js'
import { MediaPlaceholder } from './node/MediaPlaceholder.js'
import { SectionBlock } from './node/SectionBlock.js'
import { Eyebrow } from './node/EyeBrow.js'

import { Column, ColumnBlock } from "./node/ColumnLayout.js";
import { FontSize } from "./node/FontSize.js";
import { Pill } from "./node/Pill.js";


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


const lowlight = createLowlight(common)

document.addEventListener('alpine:init', () => {
    const original = window.Alpine.initTree;
    window.Alpine.initTree = function (...args) {
        console.trace('🚨 Alpine.initTree dipanggil ulang di sini:');
        return original.apply(this, args);
    };
});


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
            isEyebrowIconOpen: false,
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

                // 🌟 SABUK PENGAMAN REVISI: Cek apakah elemen DOM INI sudah punya editor
                // Ini menyelamatkan ketikan saat Livewire transaksi/error validasi
                if (editorElement && editorElement.__tiptap) {
                    window.tiptapEditor = editorElement.__tiptap;
                    return;
                }
                const initialContent = wireComponent.get(wireModelName) || '';

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

                        GlobalDragHandle.configure({
                            dragHandleWidth: 32, // Lebar area deteksi hover (dalam px)
                            scrollTreshold: 100, // Kecepatan scroll saat drag mendekati tepi layar
                            customNodes: [
                                'card',
                                'chip-group',
                                'callout',
                                'pull-quote',
                                'stat-highlight',
                                'figure',
                                'cta-button',
                                'column',
                            ],
                        }),

                        Column,
                        ColumnBlock,
                        SectionBlock,
                        Eyebrow,
                        StepCard,
                        Card,
                        Pill,
                        // InfoCard,
                        FontSize,

                        // TransferCard,
                        // ContactItem,
                        // ChipGroup,

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
                            placeholder: 'Mulai menulis artikel hebat Anda di sini...',
                            emptyEditorClass: 'is-editor-empty'
                        }),

                        CodeBlockLowlight.configure({ lowlight }),

                        
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
                        handleKeyDown: (view, event) => {
                            const { state } = view;
                            const { selection, doc } = state;
                            
                            // 🌟 PERBAIKAN 1: Deteksi yang lebih kebal.
                            // ProseMirror terkadang menghitung ukuran node 2 angka lebih kecil/besar di ujung dokumen.
                            const isAllSelected = selection.from === 0 && selection.to >= doc.content.size - 2;

                            if (isAllSelected) {
                                const editor = window.tiptapEditor;
                                if (!editor) return false;

                                // 1. Jika menekan Backspace atau Delete
                                if (event.key === 'Backspace' || event.key === 'Delete') {
                                    event.preventDefault();
                                    
                                    // 🌟 PERBAIKAN 2: Opsi Nuklir (Reset Paksa HTML)
                                    // Ini akan menghancurkan semua atribut yang 'nyangkut'
                                    editor.commands.setContent('<p></p>');
                                    editor.commands.focus();
                                    
                                    return true;
                                }

                                // 2. Jika langsung mengetik huruf/angka untuk menimpa teks
                                if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                                    event.preventDefault();
                                    
                                    // Hancurkan dan langsung isi dengan huruf pertama
                                    editor.commands.setContent(`<p>${event.key}</p>`);
                                    // Pindahkan kursor ke ujung teks
                                    editor.commands.focus('end');
                                    
                                    return true;
                                }
                            }
                            
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
                    },

                    onUpdate({ editor }) {
                        _this.updatedAt = Date.now();

                        // Penghitungan kata manual
                        const text = editor.getText();
                        _this.wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;

                        if (_this.isUploading) return;

                    },
                    

                    onSelectionUpdate() {
                        _this.updatedAt = Date.now()
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
            // 3. Eksekusi Livewire
            // executeLivewireUpload(targetFile) {
            //     if (!navigator.onLine) {
            //         window.dispatchEvent(new CustomEvent('tampilkan-error', {
            //             detail: "Koneksi internet terputus! Silakan periksa jaringan Anda."
            //         }));
            //         this.removeDummyImage(targetFile.targetToken);
            //         this.processNextInQueue();
            //         return;
            //     }

            //     wireComponent.upload('photo', targetFile,
            //         async (uploadedUrl) => {
            //             try {
            //                 const finalUrl = await wireComponent.uploadImage();
            //                 if (finalUrl && window.tiptapEditor) {
            //                     this.replaceDummyWithImage(targetFile.targetToken, finalUrl, targetFile.name);
            //                 }
            //             } catch (error) {
            //                 console.error('[Upload Error]', error);
            //                 this.removeDummyImage(targetFile.targetToken);
            //             } finally {
            //                 // 🔑 Lanjut ke antrean berikutnya apa pun yang terjadi
            //                 this.processNextInQueue();
            //             }
            //         },
            //         (error) => {
            //             console.error('[Livewire Error]', error);
            //             window.dispatchEvent(new CustomEvent('tampilkan-error', {
            //                 detail: `Gagal mengunggah ${targetFile.name}. Periksa koneksi internet Anda.`
            //             }));
            //             this.removeDummyImage(targetFile.targetToken);

            //             // 🔑 Lanjut ke antrean berikutnya apa pun yang terjadi
            //             this.processNextInQueue();
            //         }
            //     );
            // },

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
            insertStepCard() {
                if (!window.tiptapEditor) return;
                window.tiptapEditor.chain().focus().insertStepCard({ number: '01' }).run();
                this.updatedAt = Date.now();
            },

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
        }
    }
})
