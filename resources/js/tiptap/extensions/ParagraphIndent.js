import { Extension } from '@tiptap/core'

export const ParagraphIndent = Extension.create({
    name: 'paragraphIndent',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph'],
                attributes: {
                    indent: {
                        default: null,
                        // 🌟 FIX INDENTASI WORD ONLINE: Cek apakah nilainya valid dan lebih besar dari 0
                        parseHTML: element => {
                            const style = element.style.textIndent || '';
                            const value = parseFloat(style);
                            // Jika ada text-indent dan nilainya lebih dari 0 (misal 24pt, 1cm, dll), anggap true.
                            // Jika 0pt atau tidak ada, berikan null (normal).
                            return (style && value > 0) ? true : null;
                        },
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
            // 🌟 TAMBAHKAN KODE BACKSPACE INI 🌟
            'Backspace': () => {
                const { selection } = this.editor.state;
                const { empty, $anchor } = selection;

                // Jika ada teks yang diblok, biarkan backspace menghapus teks tersebut
                if (!empty) return false;

                // Cek apakah kursor berada tepat di titik paling awal (offset 0) dari sebuah paragraf
                if ($anchor.parentOffset === 0 && this.editor.isActive('paragraph')) {
                    const isIndented = this.editor.getAttributes('paragraph').indent;

                    // Jika paragraf tersebut memiliki indentasi, hapus indentasinya saja
                    if (isIndented) {
                        return this.editor.commands.unsetIndent();
                    }
                }

                // Jika tidak ada indentasi, biarkan backspace bekerja normal (menghapus paragraf)
                return false;
            },
        }
    },
});
