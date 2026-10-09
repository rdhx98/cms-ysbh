// resources/js/app.js — daftarkan SEKALI. Listener di `document` TIDAK dilepas saat wire:navigate,
// jadi jangan menaruhnya di <script> halaman (akan menumpuk di tiap kunjungan).
import './visual-fx.js';
import collapse from '@alpinejs/collapse';
import sort from '@alpinejs/sort';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';



import { registerEditor } from './editor'
import { registerCanvas } from './canvas'                       // rilis 3: panel pratinjau (kanvas)
import { registerRich } from './rich-field'                     // rilis 31: Tiptap tunggal di panel Properti
import { SharedExtensions } from './mikro-tiptap.js'            // proyek Anda; harus `export const SharedExtensions` (lihat PERUBAHAN.md rilis 31)
document.addEventListener('alpine:init', () => {
  const api = registerEditor(window.Alpine)                     // mengembalikan { wireFieldFactory, extend }
  registerCanvas(window.Alpine)
  registerRich(window.Alpine, { ...api, extensions: SharedExtensions })
})
Alpine.plugin(collapse);
Alpine.plugin(sort);
window.TomSelect = TomSelect;
// Store `editor` hidup di seluruh navigasi: bersihkan agar tidak membawa fokus dari halaman sebelumnya.
document.addEventListener('livewire:navigated', () => {
  window.blockCollapseState = {}            // ingatan collapse milik wrapper
  window.Alpine?.store('editor')?.clear?.()
})

// Penjaga perubahan belum tersimpan. `store.editor.dirty` belum ada di kit; tambahkan di tahap T3.
document.addEventListener('livewire:navigate', (e) => {
  if (window.Alpine?.store('editor')?.dirty && !confirm('Ada perubahan yang belum disimpan. Tinggalkan halaman?')) e.preventDefault()
})
window.addEventListener('beforeunload', (e) => {
  if (window.Alpine?.store('editor')?.dirty) { e.preventDefault(); e.returnValue = '' }
})



//old bak
// import collapse from '@alpinejs/collapse';
// import sort from '@alpinejs/sort';
// import TomSelect from 'tom-select';
// import 'tom-select/dist/css/tom-select.css';

// window.TomSelect = TomSelect;

Alpine.plugin(collapse);
Alpine.plugin(sort);

// import './mikro-tiptap.js';
// import './visual-fx.js';

// import { registerEditor } from './editor'
// import { registerCanvas } from './canvas'
// document.addEventListener('alpine:init', () => { registerEditor(window.Alpine); registerCanvas(window.Alpine) })

