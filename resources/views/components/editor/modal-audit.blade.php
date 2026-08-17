{{-- 🌟 1. OUTER WRAPPER: absolute inset-0 agar melayang sempurna di atas form --}}
<div x-show="isAuditOpen" x-cloak class="absolute inset-0 z-99 pointer-events-none overflow-hidden">
    
    <!-- 🌑 BACKDROP OVERLAY GELAP -->
    {{-- 🌟 2. pointer-events-auto ditambahkan agar bisa diklik untuk menutup laci --}}
    <div x-show="isAuditOpen" 
        x-transition.opacity 
        @click="isAuditOpen = false"
        class="absolute inset-0 bg-zinc-900/20 backdrop-blur-sm pointer-events-auto">
    </div>

    <!-- 📜 PANEL RIWAYAT AUDIT (Meluncur dari Kanan) -->
    <div x-show="isAuditOpen"
        x-transition:enter="transition transform ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute top-0 right-0 h-full w-full max-w-sm md:max-w-md bg-white dark:bg-zinc-950 shadow-2xl border-l border-zinc-200 dark:border-zinc-800 flex flex-col rounded-l-2xl pointer-events-auto">

        <!-- Header Panel Audit -->
        <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-zinc-50 dark:bg-zinc-900/50 shrink-0 rounded-tl-2xl">
            <!-- Indikator Handle kecil khas laci mobile di tengah -->
            <div class="md:hidden w-10 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full absolute left-1/2 -translate-x-1/2 top-1.5"></div>

            <h3 class="text-xs font-bold text-zinc-700 dark:text-zinc-300 tracking-wide uppercase mt-2 md:mt-0"> 
                {{ __('ui.modal_audit.title') }} 
            </h3>
            <button type="button" @click="isAuditOpen = false" class="text-zinc-400 hover:text-red-500 transition-colors cursor-pointer p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Area List Riwayat (Scrollable) -->
        <div class="flex-1 overflow-y-auto w-full p-4">
            <div class="flow-root min-h-full">
                <ul class="-mb-8">
                    @forelse($this->auditTrail as $log)
                        @php
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
                                default             => 'bg-amber-500', 
                            };
                        @endphp

                        <li>
                            <div class="relative px-2 pb-8">
                                {{-- Garis vertikal penghubung --}}
                                @if (!$loop->last)
                                    <span class="absolute top-4 left-2 ml-4 h-full w-0.5 bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                                @endif

                                <div class="relative flex space-x-4 items-start">
                                    {{-- Ikon Titik Timeline --}}
                                    <div>
                                        <span class="h-8 w-8 mt-1 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-zinc-950 border-2 {{ $iconColor }}">
                                            @switch($log->description)
                                                @case('created')
                                                    <x-dynamic-component :component="'lucide-sticky-note-plus'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('updated_general')
                                                    <x-dynamic-component :component="'lucide-cassette-tape'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('deleted')
                                                    <x-dynamic-component :component="'lucide-trash-2'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_draft')
                                                    <x-dynamic-component :component="'lucide-scroll-text'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_review')
                                                    <x-dynamic-component :component="'lucide-view'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_published')
                                                    <x-dynamic-component :component="'lucide-globe-check'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_scheduled')
                                                    <x-dynamic-component :component="'lucide-clock-check'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_archived')
                                                    <x-dynamic-component :component="'lucide-file-archive'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @case('status_rejected')
                                                    <x-dynamic-component :component="'lucide-shredder'" class="h-4 w-4 origin-center" stroke-width="2.5" />
                                                    @break
                                                @default
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                            @endswitch
                                        </span>
                                    </div>

                                    {{-- Informasi Detail Log --}}
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-2">
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ __('audit-modal.' . $log->description) }}
                                            </p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                Oleh: <span class="font-bold text-zinc-700 dark:text-zinc-300">
                                                    {{ optional($log->causer)->name ?? 'Sistem' }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="text-right text-xs whitespace-nowrap text-zinc-400 dark:text-zinc-500 pt-0.5">
                                            <time datetime="{{ $log->created_at }}">
                                                {{ $log->created_at->diffForHumans() }}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center py-10">
                            <x-dynamic-component component="lucide-ghost" class="w-10 h-10 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" />
                            <p class="text-sm font-medium text-zinc-500">{{ __('ui.modal_audit.no_trail') }}</p>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>