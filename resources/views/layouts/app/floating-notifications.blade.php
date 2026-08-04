@props([
    'position' => 'fixed',
    'mobileTop' => 'top-16',
])

<div {{ $attributes->merge(['class' => 'pointer-events-none z-50 ' . $position . ' inset-0']) }}
     x-data="{
        notifications: [],
        notifIdCounter: 0,

        {{-- addNotification(detail) {
            const id = ++this.notifIdCounter;
            const message = typeof detail === 'string' ? detail : detail.message;
            const type = detail.type || 'info';

            // Induk HANYA menambah data. Logika waktu diserahkan ke gelembung.
            this.notifications.push({ id, message, type });
        }, --}}
        addNotification(detail) {
            // 1. Ekstrak objek jika Livewire membungkusnya dalam Array
            let data = Array.isArray(detail) ? detail[0] : detail;

            // 2. Cegah error jika parameter kosong
            if (!data) return;

            const id = ++this.notifIdCounter;

            // 3. Ambil pesan dan tipe dengan aman
            const message = typeof data === 'string' ? data : data.message;
            const type = data.type || 'info';

            // 4. Jika pesan tetap tidak ada, batalkan agar tidak error
            if (!message) {
                console.warn('Format notifikasi tidak dikenali:', detail);
                return;
            }

            // Induk HANYA menambah data. Logika waktu diserahkan ke gelembung.
            this.notifications.push({ id, message, type });
        },

        removeNotification(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
     }"
     @tampilkan-notifikasi.window="addNotification($event.detail)"
>
    <div class="hidden md:block absolute top-0 right-0 bottom-0 w-80 z-10 pointer-events-none">

        <div x-show="notifications.length > 0"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-x-10"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-10"
             class="absolute inset-0 bg-gradient-to-l from-sage-soft to-transparent">
        </div>

        <div class="absolute inset-0 flex flex-col-reverse p-4 gap-3">
            <template x-for="notif in notifications" :key="notif.id">

                {{-- 🌟 LOGIKA GELEMBUNG MANDIRI --}}
                <div x-data="{ show: false }"
                     x-init="
                        // 1. Munculkan dengan animasi setelah render
                        $nextTick(() => show = true);

                        // 2. Sembunyikan dengan animasi setelah 4 detik
                        setTimeout(() => show = false, 4000);

                        // 3. Hapus bersih dari memori setelah animasi keluar selesai (4.5 detik)
                        setTimeout(() => removeNotification(notif.id), 4500);
                     "
                     x-show="show"
                     x-transition:enter="transition-all transform ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-8 scale-75 origin-bottom-right"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom-right"
                     x-transition:leave="transition-all transform ease-in duration-300"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100 origin-bottom-right"
                     x-transition:leave-end="opacity-0 translate-y-8 scale-75 origin-bottom-right"

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

            {{-- 🌟 LOGIKA GELEMBUNG MANDIRI (Versi Mobile) --}}
            <div x-data="{ show: false }"
                 x-init="
                    $nextTick(() => show = true);
                    setTimeout(() => show = false, 4000);
                    setTimeout(() => removeNotification(notif.id), 4500);
                 "
                 x-show="show"
                 x-transition:enter="transition-all transform ease-out duration-500"
                 x-transition:enter-start="opacity-0 -translate-y-8 scale-75 origin-top"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-top"
                 x-transition:leave="transition-all transform ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100 origin-top"
                 x-transition:leave-end="opacity-0 -translate-y-8 scale-75 origin-top"

                 :class="{
                    'bg-red-500 text-white shadow-red-500/30': notif.type === 'error',
                    'bg-emerald-500 text-white shadow-emerald-500/30': notif.type === 'success',
                    'bg-amber-500 text-white shadow-amber-500/30': notif.type === 'warning',
                    'bg-blue-500 text-white shadow-blue-500/30': notif.type === 'info'
                 }"
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
</div>
