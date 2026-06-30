@props([
    'position' => 'fixed', // Default 'fixed' untuk penggunaan global di seluruh halaman
    'mobileTop' => 'top-16', // Jarak dari atas untuk UI Mobile (sesuaikan dengan tinggi header/toolbar Anda)
])

{{-- Wrapper transparan yang membungkus area notifikasi --}}
<div {{ $attributes->merge(['class' => 'pointer-events-none z-50 ' . $position . ' inset-0']) }}
     x-data="{
        errors: [],
        errorIdCounter: 0,

        addError(message) {
            const id = ++this.errorIdCounter;
            this.errors.push({ id, message });

            // Hapus otomatis setelah 5 detik
            setTimeout(() => {
                this.removeError(id);
            }, 5000);
        },

        removeError(id) {
            this.errors = this.errors.filter(err => err.id !== id);
        }
     }"
     @tampilkan-error.window="addError($event.detail)"
>
    <div class="hidden md:flex absolute top-0 right-0 bottom-0 w-72 bg-gradient-to-l from-sage-soft/40 to-transparent flex-col-reverse p-4 gap-3">
        <template x-for="error in errors" :key="error.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-10"
                 class="bg-red-50 border border-red-200 text-red-600 p-3 rounded-md shadow-sm pointer-events-auto text-sm flex items-start gap-2">
                <x-lucide-alert-circle class="w-4 h-4 mt-0.5 shrink-0" />
                <span x-text="error.message"></span>
            </div>
        </template>
    </div>

    <div class="md:hidden absolute {{ $mobileTop }} left-0 right-0 px-4 flex flex-col gap-2">
        <template x-for="error in errors" :key="error.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-4"
                 class="bg-red-50 border border-red-200 text-red-600 p-3 rounded-md shadow-md pointer-events-auto text-sm flex items-center gap-2">
                <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                <span x-text="error.message"></span>
            </div>
        </template>
    </div>
</div>
