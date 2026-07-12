// editor-setup.example.js
// Cara pasang kedua node di atas ke editor Tiptap yang sudah Anda bangun.
// Sesuaikan bagian import Editor/StarterKit dengan setup Anda saat ini
// (vanilla, Vue, React, atau lewat Alpine.js component).

import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import GlobalDragHandle from 'tiptap-extension-global-drag-handle'
import { InfoCard } from './InfoCard.js'
import { ChipGroup } from './ChipGroup.js'
import { Callout } from './Callout.js'
import { StatHighlight } from './StatHighlight.js'
import { Figure } from './Figure.js'
import { PullQuote } from './PullQuote.js'
import { CtaButton } from './CtaButton.js'
import { Column, ColumnList } from './Columns.js'
import { ColumnDropZone } from './ColumnDropZone.js'

const editor = new Editor({
  element: document.querySelector('#editor'),
  extensions: [
    StarterKit,
    InfoCard,
    ChipGroup,
    Callout,
    StatHighlight,
    Figure,
    PullQuote,
    CtaButton,
    Column,
    ColumnList,
    ColumnDropZone,

    // Satu plugin drag untuk SEMUA blok - bawaan StarterKit (paragraf, heading,
    // list) otomatis kebaca; untuk 7 node custom di atas, daftarkan lewat
    // customNodes dengan nilai data-type PERSIS seperti yang dipakai di
    // masing-masing file (lihat renderHTML masing-masing node).
    GlobalDragHandle.configure({
      dragHandleWidth: 20,
      scrollTreshold: 100,
      customNodes: [
        'info-card',
        'chip-group',
        'callout',
        'pull-quote',
        'stat-highlight',
        'figure',
        'cta-button',
        'column-list', // supaya columnList juga bisa di-drag sebagai satu kesatuan
      ],
    }),
  ],
  content: '<p>Mulai menulis...</p>',
})

// --- Tombol untuk komponen artikel ---
document.querySelector('#btn-insert-callout-tip')?.addEventListener('click', () => {
  editor.chain().focus().setCallout({ color: 'gold' }).run()
})
document.querySelector('#btn-insert-stat')?.addEventListener('click', () => {
  editor.chain().focus().setStatHighlight([{ value: '8.650', label: 'Kelambu anti-malaria dibagikan' }]).run()
})
document.querySelector('#btn-insert-figure')?.addEventListener('click', () => {
  editor.chain().focus().setFigure().run()
})
document.querySelector('#btn-insert-quote')?.addEventListener('click', () => {
  editor.chain().focus().setPullQuote({ attribution: 'Sri Wahyuni, Kader Posyandu · Sikka, NTT' }).run()
})
document.querySelector('#btn-insert-cta')?.addEventListener('click', () => {
  editor.chain().focus().setCtaButton({ text: 'Donasi Sekarang', url: '#donasi', style: 'coral' }).run()
})

// --- Toolbar sederhana ---
// Cara paling gampang untuk mulai: tombol biasa yang memanggil command custom.
// (Slash-command ala Notion bisa menyusul kalau pola dasar ini sudah nyaman dipakai.)

document.querySelector('#btn-insert-partner-card')?.addEventListener('click', () => {
  editor.chain().focus().setInfoCard({ icon: 'building', tag: 'Pemerintah Daerah', color: 'forest' }).run()
})

document.querySelector('#btn-insert-region-chips')?.addEventListener('click', () => {
  editor.chain().focus().setChipGroup({ label: 'Beban Tinggi', color: 'coral', chips: ['Mimika', 'Nabire'] }).run()
})

// --- Menyimpan & memuat ulang ---
// editor.getHTML() akan menghasilkan markup yang SAMA PERSIS dengan yang dipakai
// di halaman publik (class .partner-card, .region-card, dst.) - jadi tidak perlu
// konversi/mapping tambahan saat menyimpan ke database atau menampilkannya kembali.
//
// const savedHtml = editor.getHTML()
//
// Saat memuat ulang konten lama ke editor (mis. buka form edit Program),
// parseHTML() pada kedua node di atas akan membaca ulang attrs dari HTML
// tersimpan secara otomatis - tidak butuh format penyimpanan khusus.
