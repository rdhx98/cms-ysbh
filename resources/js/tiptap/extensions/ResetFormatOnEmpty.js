import { Extension } from '@tiptap/core';

export const ResetFormatOnEmpty = Extension.create({
    name: 'resetFormatOnEmpty',

    // onUpdate memantau SETIAP perubahan yang terjadi di editor
    onUpdate({ editor }) {
    if (editor.isEmpty) {
        // Cek apakah ada konten tersembunyi (seperti spasi atau karakter zero-width)
        const text = editor.getText();
        
        // Jika teks berisi karakter "hantu" (spasi, newline, atau zero-width space)
        if (text.trim().length === 0) {
            // Bersihkan total semua atribut dan kembalikan ke paragraf suci
            editor.chain()
                .setContent('') // Reset isi ke string kosong
                .clearNodes()
                .unsetAllMarks()
                .focus()
                .run();
        }
    }
}
});