import { Mark, mergeAttributes } from '@tiptap/core';

export const Pill = Mark.create({
    name: 'pill',
    inclusive: false, // Penting agar style tidak meluber saat mengetik di ujung teks

    parseHTML() { return [{ tag: 'span.pill-wrapper' }]; },

    renderHTML({ HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                class: 'inline-flex items-center px-3 py-1 text-sm font-medium text-[#064F3B] bg-[#E9F1EB] rounded-full'
            }),
            0
        ];
    },

    addCommands() {
        return {
            togglePill: () => ({ commands }) => commands.toggleMark(this.name),
        };
    }
});