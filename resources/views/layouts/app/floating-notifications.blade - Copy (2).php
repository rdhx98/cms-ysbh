@props([
    'position' => 'fixed',
    'mobileTop' => 'top-16',
])

<div {{ $attributes->merge(['class' => 'pointer-events-none z-50 ' . $position . ' inset-0']) }}
     x-data="{
        notifications: [],
        notifIdCounter: 0,

        addNotification(detail) {
            const id = ++this.notifIdCounter;

            // Memastikan kompatibilitas jika detail hanya berupa string
            const message = typeof detail === 'string' ? detail : detail.message;
            const type = detail.type || 'info'; // Default ke 'info'

            this.notifications.push({ id, message, type });

            // Hapus otomatis setelah 5 detik
            setTimeout(() => {
                this.removeNotification(id);
            }, 5000);
        },

        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
     }"
     {{-- Dengarkan event 'tampilkan-notifikasi' --}}
     @tampilkan-notifikasi.window="addNotification($event.detail)"
>
    {{-- <div class="hidden md:flex absolute top-0 right-0 bottom-0 w-72 bg-linear-to-l from-sage-soft/90 to-transparent flex-col-reverse p-4 gap-3">
        <template x-for="notif in notifications" :key="notif.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-10"
                 :class="{
                    'bg-red-50 border-red-200 text-red-700': notif.type === 'error',
                    'bg-green-50 border-green-200 text-green-700': notif.type === 'success',
                    'bg-amber-50 border-amber-200 text-amber-700': notif.type === 'warning',
                    'bg-blue-50 border-blue-200 text-blue-700': notif.type === 'info'
                 }"
                 class="border p-3 rounded-md shadow-sm pointer-events-auto text-sm flex items-start gap-2">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 mt-0.5 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight"></span>
            </div>
        </template>
    </div> --}}
    {{-- <div x-show="notifications.length > 0"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         style="display: none;" {{-- Mencegah kilatan (flicker) saat halaman pertama dimuat --
         class="hidden md:flex absolute top-0 right-0 bottom-0 w-80 bg-linear-to-l from-sage-soft to-transparent flex-col-reverse p-4 gap-3 z-10">

        <template x-for="notif in notifications" :key="notif.id">
            <div
                 {{-- Gunakan duration-500 (valid) dan delay-150 agar menunggu kolom gradien masuk dulu --
                 x-transition:enter="transition-all duration-500 delay-150 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-50 origin-bottom-right"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom-right"

                 {{-- Animasi keluar pakai duration-300 (valid) --
                 x-transition:leave="transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0 origin-bottom-right"
                 x-transition:leave-end="opacity-0 scale-50 translate-y-8 origin-bottom-right"

                 :class="{
                    'bg-red-500 text-white shadow-red-500/30': notif.type === 'error',
                    'bg-emerald-500 text-white shadow-emerald-500/30': notif.type === 'success',
                    'bg-amber-500 text-white shadow-amber-500/30': notif.type === 'warning',
                    'bg-blue-500 text-white shadow-blue-500/30': notif.type === 'info'
                 }"
                 class="px-4 py-2.5 rounded-3xl shadow-lg pointer-events-auto text-sm flex items-center gap-2 max-w-sm self-end">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight font-medium tracking-wide"></span>
            </div>
        </template>
         {{-- <template x-for="notif in notifications" :key="notif.id">
            <div x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] delay-75"
                 x-transition:enter-start="opacity-0 translate-y-12 scale-75 origin-bottom"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom"
                 x-transition:leave="transition-all duration-300 ease-in"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-90 -translate-y-4"

                 {{-- Desain Gelembung Solid ala iOS --
                 :class="{
                    'bg-red-500 text-white shadow-red-500/30': notif.type === 'error',
                    'bg-emerald-500 text-white shadow-emerald-500/30': notif.type === 'success',
                    'bg-amber-500 text-white shadow-amber-500/30': notif.type === 'warning',
                    'bg-blue-500 text-white shadow-blue-500/30': notif.type === 'info'
                 }"
                 class="px-4 py-2.5 rounded-3xl shadow-lg pointer-events-auto text-sm flex items-center gap-2 max-w-sm self-end">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight font-medium tracking-wide"></span>
            </div>
        </template> --}}
        {{-- <template x-for="notif in notifications" :key="notif.id">
            <div x-transition:enter="transition ease-out duration-300 delay-150"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-10"
                 :class="{
                    'bg-red-50 border-red-200 text-red-700': notif.type === 'error',
                    'bg-green-50 border-green-200 text-green-700': notif.type === 'success',
                    'bg-amber-50 border-amber-200 text-amber-700': notif.type === 'warning',
                    'bg-blue-50 border-blue-200 text-blue-700': notif.type === 'info'
                 }"
                 class="border p-3 rounded-md shadow-sm pointer-events-auto text-sm flex items-start gap-2">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 mt-0.5 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 mt-0.5 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight"></span>
            </div>
        </template>
    </div> --}}

    <div class="hidden md:block absolute top-0 right-0 bottom-0 w-80 z-10 pointer-events-none">

        <div x-show="notifications.length > 0"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             class="absolute inset-0 bg-gradient-to-l from-sage-soft to-transparent">
        </div>

        <div class="absolute inset-0 flex flex-col-reverse p-4 gap-3">
            <template x-for="notif in notifications" :key="notif.id">
                <div
                     x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                     x-transition:enter-start="opacity-0 translate-y-8 scale-50 origin-bottom-right"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom-right"
                     x-transition:leave="transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0 origin-bottom-right"
                     x-transition:leave-end="opacity-0 scale-50 translate-y-8 origin-bottom-right"

                     :class="{
                        'bg-red-500 text-white shadow-red-500/30': notif.type === 'error',
                        'bg-emerald-500 text-white shadow-emerald-500/30': notif.type === 'success',
                        'bg-amber-500 text-white shadow-amber-500/30': notif.type === 'warning',
                        'bg-blue-500 text-white shadow-blue-500/30': notif.type === 'info'
                     }"
                     class="px-4 py-2.5 rounded-3xl shadow-lg pointer-events-auto text-sm flex items-center gap-2 max-w-sm self-end">

                    <template x-if="notif.type === 'error'">
                        <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                    </template>
                    <template x-if="notif.type === 'success'">
                        <x-lucide-check-circle class="w-4 h-4 shrink-0" />
                    </template>
                    <template x-if="notif.type === 'warning'">
                        <x-lucide-alert-triangle class="w-4 h-4 shrink-0" />
                    </template>
                    <template x-if="notif.type === 'info'">
                        <x-lucide-info class="w-4 h-4 shrink-0" />
                    </template>

                    <span x-text="notif.message" class="leading-tight font-medium tracking-wide"></span>
                </div>
            </template>
        </div>
    </div>

    <div class="md:hidden absolute {{ $mobileTop }} left-0 right-0 px-4 flex flex-col gap-2 z-10 pointer-events-none">

        <template x-for="notif in notifications" :key="notif.id">
            <div
                 {{-- Animasi Masuk: Jatuh memantul membesar dari arah atas (toolbar) --}}
                 x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                 x-transition:enter-start="opacity-0 -translate-y-8 scale-50 origin-top"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-top"

                 {{-- Animasi Keluar: Tersedot menyusut kembali ke atas --}}
                 x-transition:leave="transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0 origin-top"
                 x-transition:leave-end="opacity-0 scale-50 -translate-y-8 origin-top"

                 {{-- Desain Gelembung (Sama persis dengan Desktop) --}}
                 :class="{
                    'bg-red-500 text-white shadow-red-500/30': notif.type === 'error',
                    'bg-emerald-500 text-white shadow-emerald-500/30': notif.type === 'success',
                    'bg-amber-500 text-white shadow-amber-500/30': notif.type === 'warning',
                    'bg-blue-500 text-white shadow-blue-500/30': notif.type === 'info'
                 }"
                 {{-- self-center membuatnya berada di tengah-tengah layar HP --}}
                 class="px-4 py-2.5 rounded-3xl shadow-lg pointer-events-auto text-sm flex items-center gap-2 max-w-sm self-center">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight font-medium tracking-wide"></span>
            </div>
        </template>

    </div>
    {{-- <div class="md:hidden absolute {{ $mobileTop }} left-0 right-0 px-4 flex flex-col gap-2">
        <template x-for="notif in notifications" :key="notif.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-4"
                 :class="{
                    'bg-red-50 border-red-200 text-red-700': notif.type === 'error',
                    'bg-green-50 border-green-200 text-green-700': notif.type === 'success',
                    'bg-amber-50 border-amber-200 text-amber-700': notif.type === 'warning',
                    'bg-blue-50 border-blue-200 text-blue-700': notif.type === 'info'
                 }"
                 class="border p-3 rounded-md shadow-md pointer-events-auto text-sm flex items-center gap-2">

                <template x-if="notif.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4 shrink-0" />
                </template>
                <template x-if="notif.type === 'info'">
                    <x-lucide-info class="w-4 h-4 shrink-0" />
                </template>

                <span x-text="notif.message" class="leading-tight"></span>
            </div>
        </template>
    </div> --}}


</div>
