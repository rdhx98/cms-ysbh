// columns-setup.example.js
// Cara pasang ColumnRow/ColumnSlot ke editor Tiptap yang sudah Anda bangun.
// Jangan lupa juga sertakan columns.css di halaman editor Anda.

import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { ColumnRow, ColumnSlot } from './Columns.js'

const editor = new Editor({
  element: document.querySelector('#editor'),
  extensions: [
    StarterKit,
    ColumnRow,
    ColumnSlot,
  ],
  content: '<p>Mulai menulis...</p>',
})

// Tombol toolbar untuk menyisipkan baris kolom baru (2 kolom kosong, lebar 50/50):
document.querySelector('#btn-insert-columns')?.addEventListener('click', () => {
  editor.chain().focus().insertColumnRow().run()
})

// Catatan pemakaian:
// - Tambah kolom: hover baris kolom, tombol "+" muncul di kiri/kanan (maks. 5 kolom).
// - Hapus kolom: hover salah satu kolom, tombol "x" muncul di pojok atasnya (min. 2 kolom).
// - Atur lebar: drag garis tipis di antara dua kolom - hanya memengaruhi
//   DUA kolom yang bersebelahan dengan garis itu, kolom lain tidak berubah.
// - Menyimpan & memuat ulang: editor.getHTML() menghasilkan
//   <div data-type="column-row" class="column-row">
//     <div data-type="column-slot" data-width="60" class="column-slot">...</div>
//     <div data-type="column-slot" data-width="40" class="column-slot">...</div>
//   </div>
//   - lebar tersimpan di atribut data-width, terbaca otomatis lagi saat dibuka ulang.
