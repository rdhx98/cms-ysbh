import collapse from '@alpinejs/collapse';
// import './bootstrap';

// 1. Import JavaScript dan CSS Tom Select
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

// 2. Ekspos ke window agar bisa diakses dari x-data di file Blade
window.TomSelect = TomSelect;

// Daftarkan plugin global Alpine di sini
Alpine.plugin(collapse);

// Import modul Tiptap Editor yang terpisah
import './tiptap-editor';
