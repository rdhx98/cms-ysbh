<div x-show="isAuditOpen" class="relative z-50 pointer-events-none">
    <!-- 🌑 BACKDROP OVERLAY GELAP (Hanya muncul di Mobile) -->
    <div x-show="isAuditOpen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="isAuditOpen = false"
            class="fixed inset-0 bg-black/50 backdrop-blur-xs md:hidden z-40"
            style="display: none;">
    </div>

    <!-- 📜 PANEL RIWAYAT AUDIT -->
    <div x-show="isAuditOpen"
            x-transition:enter="transition transform ease-out duration-300"
            x-transition:enter-start="translate-y-full md:translate-y-0 md:opacity-0 md:translate-x-12"
            x-transition:enter-end="translate-y-0 md:opacity-100 md:translate-x-0"
            x-transition:leave="transition transform ease-in duration-200"
            x-transition:leave-start="translate-y-0 md:opacity-100 md:translate-x-0"
            x-transition:leave-end="translate-y-full md:translate-y-0 md:opacity-0 md:translate-x-12"
            class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl md:static md:w-80 md:rounded-xl h-[60vh] md:h-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex flex-col overflow-hidden shadow-2xl md:shadow-sm shrink-0 pointer-events-none"
            style="display: none;">

        <!-- Header Panel Audit -->
        <div class="p-3 mb-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-zinc-50 dark:bg-zinc-900/50 shrink-0 relative pointer-events-auto">
            <!-- Indikator Handle kecil khas laci mobile di tengah -->
            <div class="md:hidden w-10 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full absolute left-1/2 -translate-x-1/2 top-1.5"></div>

            <h3 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 tracking-wide uppercase mt-1 md:mt-0">Riwayat Audit Dokumen</h3>
            <button type="button" @click="isAuditOpen = false" class="text-zinc-400 hover:text-red-500 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- List Riwayat Audit -->
        {{-- <div class="flex-1 p-4 overflow-y-auto space-y-4">
            <ul class="-mb-8">
                <li>
                    <div class="relative pb-6">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                        <div class="relative flex space-x-3 items-center">
                            <div>
                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-900 bg-emerald-500 text-white">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 flex justify-between space-x-4">
                                <div>
                                    <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">updated</p>
                                    <p class="text-[11px] text-zinc-500">Oleh: <span class="font-semibold">Ruth Charlota</span></p>
                                </div>
                                <div class="text-right text-[10px] text-zinc-400">3 hours ago</div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div> --}}
        <div class="flow-root min-h-full overflow-y-auto pointer-events-auto">
            <ul class="-mb-8 ">
                {{-- 🔥 Lakukan looping pada variabel data log Anda --}}
                {{-- @forelse($this->auditTrail as $log)
                    @php
                        // $isCreated = str_contains(strtolower($log->description), 'dibuat');
                        // $isReview = str_contains(strtolower($log->description), 'review');
                        // $isDeleted = str_contains(strtolower($log->description), 'dihapus');
                        // $isDrafted = str_contains(strtolower($log->description), 'dikembalikan');

                        $isCreated = $log->description === 'created';
                        $isDeleted = $log->description === 'deleted';
                        $isUpdated = $log->description === 'updated_general';

                        $isDraft = $log->description === 'status_draft';
                        $isReview = $log->description === 'status_review';
                        $isPublished = $log->description === 'status_published';
                        $isScheduled = $log->description === 'status_scheduled';
                        $isRejected = $log->description === 'status_rejected';
                        $isReview = $log->description === 'status_archived';
                    @endphp
                    <li>
                        <div class="relative px-4 pb-6">
                            {{-- Garis vertikal penghubung --}
                            @if (!$loop->last)
                                <span class="absolute top-4 left-4 ml-4  h-full w-0.5 bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                            @endif

                            <div class="relative flex space-x-3 items-center">
                                {{-- Icon Ikon Titik Timeline --}}
                                <div>
                                    {{-- <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-900 bg-emerald-500 text-white">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span> --}
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-900 text-white
                                        @if($isCreated) bg-emerald-500
                                        @elseif($isReview) bg-blue-500
                                        @elseif($isDeleted) bg-red-500
                                        @else bg-amber-500 @endif">

                                        {{-- Icon Dinamis --}
                                        @if($isCreated)
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        @elseif($isReview)
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                        @else
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                        @endif
                                    </span>
                                </div>

                                {{-- Informasi Detail Log --}
                                <div class="min-w-0 flex-1 flex justify-between space-x-4">
                                    <div>
                                        {{-- Menampilkan deskripsi log (contoh: "updated" atau "created") --}
                                        <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100 capitalize">
                                            {{ $log->description }}
                                        </p>

                                        {{-- Menampilkan nama pengguna yang melakukan aksi --}
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Oleh: <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                                {{ optional($log->causer)->name ?? 'Sistem' }}
                                            </span>
                                        </p>
                                    </div>

                                    {{-- Waktu Log (Menggunakan Carbon untuk format ramah manusia) --}
                                    <div class="text-right text-[10px] whitespace-nowrap text-zinc-400 dark:text-zinc-500">
                                        <time datetime="{{ $log->created_at }}">
                                            {{ $log->created_at->diffForHumans() }}
                                        </time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-center py-4 text-xs text-zinc-400">
                        Belum ada riwayat audit untuk artikel ini.
                    </li>
                @endforelse --}}
                @forelse($this->auditTrail as $log)
                    @php
                        // 1. Gunakan match() PHP 8 untuk menentukan warna (Jauh lebih ringkas dari switch!)
                        $iconColor = match($log->description) {
                            'created'           => 'bg-emerald-50 text-emerald-700 border-emerald-700',
                            'updated_general'   => 'bg-emerald-50 text-emerald-900 border-emerald-900',
                            'deleted'           => 'bg-red-50 text-red-700 border-red-700',
                            'status_draft'      => 'bg-violet-50 text-violet-700 border-violet-700',
                            'status_review'     => 'bg-yellow-50 text-yellow-700 border-yellow-700',
                            'status_published'  => 'bg-green-50 text-green-700 border-green-700',
                            'status_scheduled'  => 'bg-orange-50 text-orange-700 border-orange-700',
                            'status_archived'   => 'bg-blue-50 text-blue-700 border-blue-700',
                            'status_rejected'   => 'bg-red-50 text-red-700 border-red-700',
                            default => 'bg-amber-500', // Untuk updated_general, status_draft, dll
                        };
                    @endphp

                    <li>
                        <div class="relative px-4 pb-6">
                            {{-- Garis vertikal penghubung --}}
                            @if (!$loop->last)
                                <span class="absolute top-4 left-4 ml-4 h-full w-0.5 bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                            @endif

                            <div class="relative flex space-x-3 items-center">
                                {{-- Icon Ikon Titik Timeline --}}
                                <div>
                                    {{-- Panggil variabel warna secara langsung di class --}}
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-900  border-2 {{ $iconColor }}">

                                        {{-- 2. Gunakan @switch Blade untuk merender ikon yang berbeda --}}
                                        @switch($log->description)
                                        {{-- <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> --}}
                                            @case('created')
                                                <x-dynamic-component :component="'lucide-sticky-note-plus'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('updated_general')
                                                <x-dynamic-component :component="'lucide-cassette-tape'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('deleted')
                                                <x-dynamic-component :component="'lucide-trash-2'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_draft')
                                                <x-dynamic-component :component="'lucide-scroll-text'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_review')
                                                <x-dynamic-component :component="'lucide-view'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_published')
                                                <x-dynamic-component :component="'lucide-globe-check'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_scheduled')
                                                <x-dynamic-component :component="'lucide-clock-check'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_archived')
                                                <x-dynamic-component :component="'lucide-file-archive'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break
                                            @case('status_rejected')
                                                <x-dynamic-component :component="'lucide-shredder'" class="h-5 w-5 origin-center" stroke-width="2"  />
                                                @break


                                            @default
                                                {{-- Ikon pensil/edit untuk status default (update, dll) --}}
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                        @endswitch
                                    </span>
                                </div>

                                {{-- Informasi Detail Log --}}
                                <div class="min-w-0 flex-1 flex justify-between space-x-4">
                                    <div>
                                        {{-- Menggunakan file terjemahan yang sudah kita buat sebelumnya --}}
                                        <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100 ">
                                            {{ __('audit.' . $log->description) }}
                                        </p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Oleh: <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                                {{ optional($log->causer)->name ?? 'Sistem' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="text-right text-[10px] whitespace-nowrap text-zinc-400 dark:text-zinc-500">
                                        <time datetime="{{ $log->created_at }}">
                                            {{ $log->created_at->diffForHumans() }}
                                        </time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-center py-4 text-xs text-zinc-400">
                        Belum ada riwayat audit untuk artikel ini.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
{{-- <div x-show="isAuditOpen" class="relative z-50">

    <!-- 🌑 BACKDROP OVERLAY GELAP -->
    <div x-show="isAuditOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isAuditOpen = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-xs md:hidden"
         style="display: none;">
    </div>

    <!-- 📜 PANEL RIWAYAT AUDIT (Drawer dari Bawah di Mobile, Sidebar Kanan di Desktop) -->
    <div x-show="isAuditOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-10"
         x-transition:enter-end="translate-y-0 md:translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 md:translate-x-0"
         x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-10"
         class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl md:static md:w-80 md:rounded-xl h-[60vh] md:h-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex flex-col overflow-hidden shadow-2xl md:shadow-sm shrink-0"
         style="display: none;">

        <!-- Header Panel Audit -->
        <div class="p-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-zinc-50 dark:bg-zinc-900/50 shrink-0 relative">
            <!-- Indikator Handle kecil khas laci mobile di tengah -->
            <div class="md:hidden w-10 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full absolute left-1/2 -translate-x-1/2 top-1.5"></div>

            <h3 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 tracking-wide uppercase mt-1 md:mt-0">Riwayat Audit Dokumen</h3>
            <button type="button" @click="isAuditOpen = false" class="text-zinc-400 hover:text-red-500 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- List Riwayat Audit -->
        <div class="flex-1 p-4 overflow-y-auto space-y-4">
            <ul class="-mb-8">
                <li>
                    <div class="relative pb-6">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                        <div class="relative flex space-x-3 items-center">
                            <div>
                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-900 bg-emerald-500 text-white">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 flex justify-between space-x-4">
                                <div>
                                    <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">updated</p>
                                    <p class="text-[11px] text-zinc-500">Oleh: <span class="font-semibold">Ruth Charlota</span></p>
                                </div>
                                <div class="text-right text-[10px] text-zinc-400">3 hours ago</div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div> --}}
