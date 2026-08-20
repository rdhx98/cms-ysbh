import { Extension } from '@tiptap/core';

export const FontSize = Extension.create({
    name: 'fontSize',

    addOptions() {
        return {
            types: ['textStyle'],
        };
    },
    
    addGlobalAttributes() {
        return [
            {
                types: this.options.types,
                attributes: {
                    fontSize: {
                        default: null,
                        parseHTML: element => {
                            const raw = element.style.fontSize;
                            if (!raw) return null;

                            const match = raw.match(/^([\d.]+)(pt|px)$/i);
                            if (!match) return raw.replace(/['"]+/g, '') || null;

                            const [, num, unit] = match;

                            if (unit.toLowerCase() === 'pt') {
                                // Word & aplikasi desktop lain pakai satuan pt —
                                // konversi ke px biar konsisten dengan satuan toolbar Anda
                                return `${Math.round(parseFloat(num) * (96 / 72))}px`;
                            }

                            return `${parseFloat(num)}px`;
                        },
                        renderHTML: attributes => {
                            if (!attributes.fontSize) {
                                return {};
                            }
                            return {
                                style: `font-size: ${attributes.fontSize}`,
                            };
                        },
                    },
                },
            },
        ];
    },

    addCommands() {
        return {
            setFontSize: fontSize => ({ chain }) => {
                return chain()
                    .setMark('textStyle', { fontSize })
                    .run();
            },
            unsetFontSize: () => ({ chain }) => {
                return chain()
                    .setMark('textStyle', { fontSize: null })
                    .removeEmptyTextStyle()
                    .run();
            },
        };
    },
});