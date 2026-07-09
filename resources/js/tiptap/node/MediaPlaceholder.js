import { Node, mergeAttributes } from '@tiptap/core'

export const MediaPlaceholder = Node.create({
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
