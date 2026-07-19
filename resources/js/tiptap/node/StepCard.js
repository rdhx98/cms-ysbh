import { Node, mergeAttributes } from '@tiptap/core'

// ==========================================
// 1. NODE ANGKA (ANAK PERTAMA)
// ==========================================
export const StepNumber = Node.create({
    name: 'stepNumber',
    content: 'inline*', // Hanya menerima teks sebaris (tidak bisa di-Enter)
    isolating: true,
    
    parseHTML() {
        return [{ tag: 'div[data-type="step-number"]' }]
    },
    
    renderHTML({ HTMLAttributes }) {
        // Kita menggunakan variabel CSS var(--num-bg) yang disuntikkan oleh Node Bapak
        return ['div', mergeAttributes(HTMLAttributes, {
            'data-type': 'step-number',
            class: 'flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-xl text-gray-900 font-bold text-lg focus:outline-none',
            style: 'background-color: var(--num-bg); text-align: center;'
        }), 0] // Angka 0 berarti ini adalah kanvas Tiptap
    },
})

// ==========================================
// 2. NODE KONTEN UTAMA (ANAK KEDUA)
// ==========================================
export const StepContent = Node.create({
    name: 'stepContent',
    content: 'block+', // Menerima Paragraf, Heading, dll
    isolating: true,
    
    parseHTML() {
        return [{ tag: 'div[data-type="step-content"]' }]
    },
    
    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, {
            'data-type': 'step-content',
            class: 'flex-1 w-full prose max-w-none focus:outline-none'
        }), 0] // Angka 0 berarti ini adalah kanvas Tiptap
    },
})

// ==========================================
// 3. NODE PEMBUNGKUS (BAPAK)
// ==========================================
export const StepCard = Node.create({
    name: 'stepCard',
    group: 'block',
    // ATURAN KETAT: Bapak harus berisi persis 1 Angka dan 1 Konten secara berurutan
    content: 'stepNumber stepContent', 
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            numBgColor: { default: '#f3f4f6' },
            cardBgColor: { default: '#ffffff' },
        }
    },

    addCommands() {
        return {
            insertStepCard: (options) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: {
                        numBgColor: options?.numBgColor || '#f3f4f6',
                        cardBgColor: options?.cardBgColor || '#ffffff',
                    },
                    // Struktur baku saat tombol dipanggil
                    content: [
                        { type: 'stepNumber' }, // Kosong agar placeholder '01' muncul
                        { 
                            type: 'stepContent',
                            content: [
                                { type: 'heading', attrs: { level: 3 } },
                                { type: 'paragraph' }
                            ]
                        }
                    ]
                })
            },
        }
    },

    parseHTML() {
        return [{ tag: 'div[data-type="step-card"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { 'data-type': 'step-card' }), 0]
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            let currentNode = node
            
            const dom = document.createElement('div')
            dom.classList.add('step-card-wrapper', 'relative', 'mb-6', 'mt-6')
            
            // Suntikkan warna angka sebagai CSS Variable ke parent, agar Anak ke-1 bisa membacanya
            dom.style.setProperty('--num-bg', currentNode.attrs.numBgColor)

            // --- MENU KONTROL (Sama seperti sebelumnya) ---
            const controlsWrapper = document.createElement('div')
            controlsWrapper.classList.add('absolute', '-top-3', '-right-2', 'z-10')
            controlsWrapper.contentEditable = 'false'

            const triggerBtn = document.createElement('button')
            triggerBtn.type = 'button'
            triggerBtn.innerHTML = `
                <svg class="w-5 h-5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
            `
            triggerBtn.classList.add('bg-white', 'border', 'border-gray-200', 'rounded-md', 'p-1', 'shadow-sm', 'hover:bg-gray-50', 'cursor-pointer')

            const menu = document.createElement('div')
            menu.classList.add('hidden', 'absolute', 'top-full', 'right-0', 'mt-1', 'bg-white', 'border', 'border-gray-200', 'rounded-lg', 'shadow-md', 'p-1.5', 'items-center', 'gap-2')

            triggerBtn.addEventListener('click', (e) => {
                e.preventDefault()
                menu.classList.toggle('hidden')
                menu.classList.toggle('flex')
            })

            document.addEventListener('mousedown', (e) => {
                if (!controlsWrapper.contains(e.target)) {
                    menu.classList.add('hidden')
                    menu.classList.remove('flex')
                }
            })

            const numBgWrapper = document.createElement('div')
            numBgWrapper.title = "Warna Latar Angka"
            numBgWrapper.classList.add('flex', 'items-center', 'gap-1', 'text-xs', 'text-gray-500', 'cursor-pointer')
            const numBgInput = document.createElement('input')
            numBgInput.type = 'color'
            numBgInput.value = currentNode.attrs.numBgColor
            numBgInput.classList.add('w-6', 'h-6', 'p-0', 'border-0', 'rounded-md', 'cursor-pointer', 'bg-transparent')
            numBgWrapper.innerHTML = `<span class="px-1">#️⃣</span>`
            numBgWrapper.appendChild(numBgInput)

            numBgInput.addEventListener('input', (e) => {
                if (typeof getPos === 'function') {
                    editor.commands.command(({ tr }) => {
                        tr.setNodeMarkup(getPos(), undefined, { ...currentNode.attrs, numBgColor: e.target.value })
                        return true
                    })
                }
            })

            const cardBgWrapper = document.createElement('div')
            cardBgWrapper.title = "Warna Latar Kartu"
            cardBgWrapper.classList.add('flex', 'items-center', 'gap-1', 'text-xs', 'text-gray-500', 'cursor-pointer', 'border-l', 'pl-2')
            const cardBgInput = document.createElement('input')
            cardBgInput.type = 'color'
            cardBgInput.value = currentNode.attrs.cardBgColor
            cardBgInput.classList.add('w-6', 'h-6', 'p-0', 'border-0', 'rounded-md', 'cursor-pointer', 'bg-transparent')
            cardBgWrapper.innerHTML = `<span class="px-1">📄</span>`
            cardBgWrapper.appendChild(cardBgInput)

            cardBgInput.addEventListener('input', (e) => {
                if (typeof getPos === 'function') {
                    editor.commands.command(({ tr }) => {
                        tr.setNodeMarkup(getPos(), undefined, { ...currentNode.attrs, cardBgColor: e.target.value })
                        return true
                    })
                }
            })

            const deleteBtn = document.createElement('button')
            deleteBtn.type = 'button'
            deleteBtn.innerHTML = `
                <svg class="w-4 h-4 text-red-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            `
            deleteBtn.title = 'Hapus Kartu'
            deleteBtn.classList.add('hover:bg-red-50', 'p-1.5', 'rounded-md', 'border-l', 'ml-1')
            // deleteBtn.addEventListener('click', (e) => {
            //     e.preventDefault()
            //     if (typeof getPos === 'function') {
            //         editor.chain().focus().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run()
            //     }
            // })
            // deleteBtn.addEventListener('click', (e) => {
            //     e.preventDefault()
            //     if (typeof getPos === 'function') {
            //         // Gunakan Transaksi ProseMirror (tr) langsung untuk menghancurkan node beserta seluruh anaknya sekaligus
            //         editor.commands.command(({ tr }) => {
            //             tr.delete(getPos(), getPos() + node.nodeSize)
            //             return true
            //         })
                    
            //         // (Opsional) Kembalikan fokus kursor ke editor setelah kartu terhapus
            //         editor.commands.focus()
            //     }
            // })
            deleteBtn.addEventListener('click', (e) => {
                // 1. Cegah klik "bocor" ke editor yang bisa memindahkan kursor secara tak sengaja
                e.preventDefault()
                e.stopPropagation() 

                if (typeof getPos === 'function') {
                    // 2. Pilih node Bapak secara keseluruhan, lalu hapus seleksi tersebut
                    editor.chain()
                        .focus()
                        .setNodeSelection(getPos()) // Memilih kotak kartu ini secara utuh
                        .deleteSelection()          // Membumihanguskan apapun yang sedang dipilih
                        .run()
                }
            })

            menu.append(numBgWrapper, cardBgWrapper, deleteBtn)
            controlsWrapper.append(triggerBtn, menu)
            // --- AKHIR MENU KONTROL ---

            // --- LAYOUT UTAMA KARTU SEBAGAI CONTENT DOM ---
            const contentDOM = document.createElement('div')
            // Kita jadikan Layout Kartu ini sebagai Area Edit (contentDOM)
            // Tiptap secara ajaib akan memasukkan "Anak 1 (Angka)" dan "Anak 2 (Konten)" berdampingan di dalam div ini berkat class flex!
            contentDOM.classList.add('flex', 'flex-col', 'sm:flex-row', 'items-start', 'p-6', 'rounded-2xl', 'shadow-sm', 'gap-4', 'sm:gap-6', 'border', 'border-gray-100')
            contentDOM.style.backgroundColor = currentNode.attrs.cardBgColor

            dom.append(controlsWrapper, contentDOM)

            return {
                dom,
                contentDOM, // Keajaibannya terjadi di sini!
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'stepCard') return false
                    currentNode = updatedNode
                    
                    contentDOM.style.backgroundColor = currentNode.attrs.cardBgColor
                    dom.style.setProperty('--num-bg', currentNode.attrs.numBgColor)
                    
                    return true
                },
                ignoreMutation: (mutation) => {
                    if (!contentDOM.contains(mutation.target)) return true
                    return false
                }
            }
        }
    }
})