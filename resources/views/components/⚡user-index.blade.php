<?php

use Livewire\Attributes\Title;

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Livewire\Traits\WithNotifications;

new class extends Component
{
    Use WithNotifications;
    //
    public function mount(){
        $this->orderDirection = "desc";
        $this->orderColumn = "created_at";
    }
    public string $orderDirection;
    public string $orderColumn;

    #[Computed]
    public function userList()
    {
        $query = User::query();
        return $query->get();
    }

};
?>

{{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}
{{-- <div class="">
    --}}
<x-main-wrapper>
    <x-slot:title>{{ __('Manage Users') }}</x-slot:title>
    <!--HEADER CONTAINER -->
    <div class="mb-4  flex justify-between w-full items-center">
        <div class="left"></div>
        <div class="">
            <a href="{{ route('user.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                <x-dynamic-component :component="'lucide-user-plus'" class="w-4 h-4" />
                Buat Akun
            </a>
        </div>
    </div>

    <!-- TABLE CONTAINER -->
    <div class="overflow-x-auto overflow-y-auto  rounded-xl border max-h-full border-zinc-200 dark:border-zinc-700 w-full max-w-screen">
        <table class="w-full min-w-max text-left border-collapse">
            {{-- <thead class="bg-sbh-green text-xs text-white dark:bg-green-950 dark:text-green-300">
                <tr>
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Judul Artikel</th>
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Kategori</th>
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Status</th>
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider text-center">Kelola</th>
                </tr>
            </thead> --}}
            <thead class="bg-sbh-green text-xs text-white dark:bg-green-950 dark:text-green-300">
                <tr>
                    <!-- Header Judul -->
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">
                        <button wire:click="sortBy('name')" class="flex items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                            Nama
                            @if($orderColumn === 'name')
                                <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>

                    <!-- Tanggal -->
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">
                        <button wire:click="sortBy('created_at')" class="flex items-center justify-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                            handle
                            @if($orderColumn === 'created_at')
                                <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>

                    <!-- Header Kategori -->
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">
                        <button wire:click="sortBy('category')" class="flex items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                            E-mail
                            @if($orderColumn === 'category')
                                <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>

                    <!-- Header Status -->
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">
                        <button wire:click="sortBy('status')" class="flex items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                            Status
                            @if($orderColumn === 'status')
                                <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider text-center">
                        {{ __('roles') }}
                    </th>

                    <!-- Header Kelola (Tidak perlu sorting) -->
                    <th class="sticky top-0 z-10 lg:z-20 bg-forest dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider text-center">
                        Kelola
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">

                @forelse ($this->userList as $user)
                    @foreach (range(1, 1) as $i)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <td class="px-4 py-3.5 text-sm">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->job_title }}</div>
                            </td>
                            <td class="px-4 py-0 text-sm h-full align-middle">
                                <div class="flex gap-2 items-center justify-center h-full min-h-14">
                                    <div class="px-2 py-0.5 rounded text-xs font-medium bg-sage-soft text-foresty dark:bg-slate-800 dark:text-slate-300"> {{ $user->handle }} </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm">{{ $user->email }}</td>
                            {{-- Hapus mx-auto, ganti dengan text-center dan align-middle --}}
                            <td class="px-4 py-3.5 text-sm text-center align-middle">
                                @if($user->active)
                                    {{-- Tambahkan inline-block agar padding span dirender sempurna --}}
                                    <span class="inline-flex items-center gap-2 px-2.5 py-0.5 text-xs font-medium rounded-full bg-misty text-foresty border-2 border-foresty dark:bg-violet-950 dark:text-violet-300">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-forest"></span>Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 px-2.5 py-0.5text-xs font-medium rounded-full bg-coral-muted text-red-700 border-2 border-red-700 dark:bg-yellow-950 dark:text-yellow-300">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-700"></span>Nonaktif
                                    </span>
                                @endif
                            </td>
                            {{-- <td class="px-4 py-3.5 text-sm align-middle">
                                @if($user->active)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-misty text-foresty border-2 border-foresty dark:bg-violet-950 dark:text-violet-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-coral-muted text-red-700 border-2 border-red-700 dark:bg-yellow-950 dark:text-yellow-300">
                                        Nonaktif
                                    </span>
                                @endif
                            </td> --}}
                            <td class="px-4 py-3.5 text-sm align-middle">
                                <div class="flex items-center gap-4">
                                    @foreach ($user->roles as $role)

                                        @switch( $role->name )
                                            @case('admin')
                                                <div class="px-2 py-1 text-xs flex font-medium rounded-full bg-coral-muted text-red-700 border-2 border-red-700 dark:bg-yellow-950 dark:text-yellow-300 gap-1">
                                                    <x-dynamic-component component="lucide-crown" class="h-4 w-4" stroke-width="2.5" />
                                                    Admin
                                                </div>
                                                @break
                                            @case('editor')
                                                <div class="px-2 py-1 text-xs flex font-medium rounded-full bg-yellow-50 text-yellow-700 border-2 border-yellow-700 dark:bg-yellow-950 dark:text-yellow-300  gap-1">
                                                    <x-dynamic-component component="lucide-glasses" class="h-4 w-4" stroke-width="2.5" />
                                                    Editor
                                                </div>
                                                @break
                                            @case('writer')
                                                <div class="px-2 py-1 text-xs flex font-medium rounded-full bg-misty text-foresty border-2 border-forest dark:bg-yellow-950 dark:text-yellow-300  gap-1">
                                                    <x-dynamic-component component="lucide-feather" class="h-4 w-4" stroke-width="2.5" />
                                                    Writer
                                                </div>
                                                @break

                                            @default

                                        @endswitch

                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                {{-- BUTTONS CONTAINER --}}
                                <div class="flex justify-center items-center gap-2">

                                    {{-- <a wire:navigate href="{{ route('user.edit', $user) }}" class="group p-1.5 rounded-md text-white bg-forest/90 dark:bg-forest/80 relative cursor-pointer hover:bg-forest/70 transition-colors flex items-center justify-center">
                                        <flux:icon variant="solid" icon="pencil" class="size-3.5!" />
                                        <span class="z-30 absolute bottom-full left-0 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                            Sunting

                                            <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-4 left-2 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                            </svg>
                                        </span>
                                    </a> --}}

                                    <a wire:navigate href="{{ route('user.detail', $user) }}" class="group p-1.5 rounded-md bg-slate-600 text-white dark:bg-slate-800 relative cursor-pointer hover:bg-slate-700 transition-colors flex items-center justify-center">
                                        <flux:icon variant="solid" icon="eye" class="size-3.5!" />
                                        <span class="z-30 absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                            Detail
                                            <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-full left-0 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                            </svg>
                                        </span>
                                    </a>

                                    {{-- <button
                                        @click="deleteType = 'article'; deleteId = {{ $user->id }}; showDeleteModal = true"
                                        type="button" class="group p-1.5 rounded-md bg-red-600 text-white dark:bg-red-800 relative cursor-pointer hover:bg-red-700 transition-colors flex items-center justify-center">
                                        <flux:icon variant="solid" icon="trash" class="size-3.5!" />

                                        <span class="z-30 absolute bottom-full right-0 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                            Hapus

                                            <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-4 right-2 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                            </svg>
                                        </span>
                                    </button> --}}

                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    {{-- INI AKAN MUNCUL JIKA TIDAK ADA DATA ARTIKEL --}}
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon variant="outline" icon="document-text" class="size-8 text-zinc-400 mb-2" />
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Nu uh</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</x-main-wrapper>
{{-- </div> --}}
