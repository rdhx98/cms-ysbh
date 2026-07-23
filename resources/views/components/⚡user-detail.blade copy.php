<?php

use Livewire\Component;
use App\Models\User;

use App\Livewire\Traits\WithNotifications; // Import trait-nya
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

new class extends Component
{
    use WithNotifications;

    public User $user;

    // Deklarasi wire:model
    public $name;
    public $handle;
    public $email;
    public $job_title;
    public array $roles = [];
    public bool $active; // Harus bool agar Alpine.js Toggle tidak error!
    
    public function mount(User $user)
    {
        $this->user = $user;
        
        // Isi form dengan data user saat ini
        $this->name = $user->name;
        $this->handle = $user->handle;
        $this->email = $user->email;
        $this->job_title = $user->job_title;
        
        // Pastikan active murni boolean
        $this->active = (bool) $user->active; 
        
        // Mengambil Role dari Spatie (Ambil role pertama, karena 1 user 1 role)
        // Jika belum punya role, set default ke 'writer'
        // $this->role = $user->roles->first()->name ?? 'writer';
        $this->roles = $user->roles->pluck('name')->toArray();
        if (empty($this->roles)) {
            $this->roles = ['writer'];
        }
        // dd($this->roles);
    }

    public function save()
    {
        // 1. Validasi Data
        $this->validate([
            'name'      => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'active'    => 'boolean',
            'handle'    => 'required|string|max:255|unique:users,handle,' . $this->user->id,
            'email'     => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'roles'     => 'required|array|min:1',
            'roles.*'   => 'in:writer,editor,admin',
            // 'role'      => 'required|in:writer,editor,admin',
            
            // Validasi Unique yang mengabaikan ID user saat ini (agar bisa di-save tanpa error)
        ], [
            'roles.required' => 'Pengguna harus memiliki minimal 1 peran.',
        ]);

        // 2. Update tabel users
        $this->user->update([
            'name'      => $this->name,
            'handle'    => $this->handle,
            'email'     => $this->email,
            'job_title' => $this->job_title,
            'active'    => $this->active,
        ]);

        // 3. Update Role Spatie
        // Gunakan syncRoles agar role lama dihapus dan diganti yang baru secara otomatis
        $this->user->syncRoles([$this->roles]);

        // 4. Redirect kembali ke halaman daftar user
        // return $this->redirect(route('user.index'), navigate: true);
        $this->dispatch('close-edit-mode');
        $this->notify('Perubahan Disimpan', 'success');
    }
    public function deleteUser()
    {
        // PROTEKSI 2: Yang menghapus HARUS punya role admin
        if (!auth()->user()->hasRole('admin')) {
            // abort(403, 'Anda tidak memiliki wewenang untuk menghapus akun.');
            $this->notify('Anda tidak memiliki otoritas admin!', 'error');
            return;
        }

        // PROTEKSI 3: Yang dihapus TIDAK BOLEH memiliki role admin
        if ($this->user->hasRole('admin')) {
            $this->notify('Tidak bisa menghapus akun yang memiliki peran admin!', 'error');
            return;
        }

        // Jika lolos proteksi, hapus user
        $this->user->delete();
        // $this->notify('Akun dihapus', 'success');
        $this->notifyFlash('Akun dihapus', 'success');
        return $this->redirect(route('user.index'), navigate: true);
    }
    
    public function cancel()
    {
        $this->mount($this->user);
        $this->resetValidation();
    }
};
?>

<div class="bg-white rounded-lg w-full  md:max-w-none flex flex-col items-center justify-center p-4 flex-1 grow">
    <x-slot:title>{{ __('Users Detail').' | '.auth()->user()->name }}</x-slot:title>
    <!-- If you do not have a consistent goal in life, you can not live it in a consistent way. - Marcus Aurelius -->
    
    {{-- MAIN --}}
    <div x-data="{showDeleteModal: false}"  class="w-full min-w-0 max-w-7xl max-h-[calc(93vh)] h-full flex flex-col justify-start items-center">
        <div class="overflow-x-auto overflow-y-auto max-h-full border-zinc-200 dark:border-zinc-700 w-full max-w-screen">
            <!-- Header Halaman -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-forest dark:text-zinc-100">Detail Pengguna</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Lihat detail dan kelola informasi akun staf.</p>
                </div>
                
                <!-- Tombol Kembali -->
                <a href="{{ route('user.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                    <x-dynamic-component :component="'lucide-arrow-left'" class="w-4 h-4" />
                    Kembali ke Daftar
                </a>
            </div>

            <!-- Grid Layout Utama -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- 👈 KOLOM KIRI: KARTU IDENTITAS (col-span-4) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Kartu Profil Utama -->
                    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 p-6 flex flex-col items-center text-center shadow-sm relative overflow-hidden">
                        
                        <!-- Ornamen Latar Belakang -->
                        <div class="absolute top-0 left-0 right-0 h-24 bg-sage-soft dark:bg-zinc-800/50"></div>

                        <!-- Avatar -->
                        <div class="relative mt-8 mb-4">
                            <div class="w-24 h-24 rounded-full bg-misty border-4 border-white dark:border-zinc-900 flex items-center justify-center text-3xl font-bold text-forest shadow-md z-10 relative">
                                <!-- Inisial dinamis -->
                                {{ $user->initials() ?? 'User' }}
                            </div>
                        </div>

                        <!-- Info Singkat -->
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $user->name ?? 'Nama Staf' }}</h3>
                        <p class="text-sm font-medium text-goldy dark:text-amber-400 mb-4">{{ '@' . ($user->handle ?? 'handle') }}</p>

                        <!-- Lencana Status & Peran -->
                        <div class="flex flex-wrap justify-center gap-2 w-full border-t border-zinc-100 dark:border-zinc-800 pt-4">
                            @if($user->active ?? true)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-misty text-foresty border border-foresty/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-forest"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-coral-muted text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Nonaktif
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-zinc-100 text-zinc-600 border border-zinc-200">
                                <x-dynamic-component :component="'lucide-briefcase'" class="w-3 h-3" />
                                {{ $user->job_title ?? 'Staf Yayasan' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 👉 KOLOM KANAN: FORMULIR DETAIL & EDIT (col-span-8) -->
                <div class="lg:col-span-8">
                    <form wire:submit="save" x-data="{ isEditing: false }" @close-edit-mode.window="isEditing = false" class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                        
                        <!-- Header Form -->
                        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex justify-between items-center">
                            <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                                <x-dynamic-component :component="'lucide-user-cog'" class="w-5 h-5 text-forest" />
                                Informasi Akun
                            </h3>
                        </div>

                        <!-- Area Input Data -->
                        <div class="p-6 space-y-6">
                            
                            <!-- Baris 1: Nama Lengkap -->
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Nama Lengkap</label>
                                <input x-bind:disabled="!isEditing" type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-forest/20 focus:border-forest transition-colors shadow-sm text-sm" placeholder="Masukkan nama lengkap...">
                                @error('name') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Baris 2: Handle & Email (Berdampingan) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Username (Handle)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-zinc-400 font-medium">@</span>
                                        <input x-bind:disabled="!isEditing" type="text" wire:model="handle" class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-forest/20 focus:border-forest transition-colors shadow-sm text-sm" placeholder="username">
                                    </div>
                                    @error('handle') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Alamat Email</label>
                                    <input x-bind:disabled="!isEditing" type="email" wire:model="email" class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-forest/20 focus:border-forest transition-colors shadow-sm text-sm" placeholder="email@yayasan.org">
                                    @error('email') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="h-px w-full bg-zinc-100 dark:bg-zinc-800 my-4"></div>
                            
                            <!-- ROLES WORKIN -->
                            <div x-data="{ selectedRoles: @entangle('roles') }">
                                <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">
                                    Akses Wewenang
                                </label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 transition-opacity duration-300"
                                    x-bind:class="!isEditing ? 'pointer-events-none opacity-80' : ''">
                                    
                                    <!-- KARTU 1: WRITER -->
                                    <label class="relative block group" x-bind:class="isEditing ? 'cursor-pointer' : ''">
                                        <input x-bind:disabled="!isEditing" type="checkbox" wire:model="roles" value="writer" class="peer sr-only">
                                        
                                        <div class="h-full p-4 border-2 rounded-2xl bg-white dark:bg-zinc-900 transition-all duration-200"
                                            x-bind:class="selectedRoles.includes('writer') ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10' : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'">
                                            
                                            <div class="flex items-center gap-2 mb-1.5">
                                                {{-- KUNCI PERBAIKAN: Gunakan x-bind:class pada x-dynamic-component --}}
                                                <x-dynamic-component :component="'lucide-feather'" 
                                                    class="w-5 h-5 transition-colors"
                                                    x-bind:class="selectedRoles.includes('writer') ? 'text-foresty' : 'text-zinc-400 group-hover:text-foresty/70'" />
                                                
                                                <span class="font-bold transition-colors"
                                                    x-bind:class="selectedRoles.includes('writer') ? 'text-foresty' : 'text-zinc-800 dark:text-zinc-200'">
                                                    Writer
                                                </span>
                                            </div>
                                            
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed select-none">
                                                Dapat membuat dan mengelola draf artikel miliknya sendiri.
                                            </p>
                                        </div>
                                        
                                        <div class="absolute top-4 right-4 transition-opacity"
                                            x-bind:class="selectedRoles.includes('writer') ? 'opacity-100 text-foresty' : 'opacity-0 text-zinc-400'">
                                            <x-dynamic-component :component="'lucide-circle-check'" class="w-5 h-5 fill-foresty/10" />
                                        </div>
                                    </label>

                                    <!-- KARTU 2: EDITOR -->
                                    <label class="relative block group" x-bind:class="isEditing ? 'cursor-pointer' : ''">
                                        <input x-bind:disabled="!isEditing" type="checkbox" wire:model="roles" value="editor" class="peer sr-only">
                                        
                                        <div class="h-full p-4 border-2 rounded-2xl bg-white dark:bg-zinc-900 transition-all duration-200"
                                            x-bind:class="selectedRoles.includes('editor') ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10' : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'">
                                            
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <x-dynamic-component :component="'lucide-clipboard-check'" 
                                                    class="w-5 h-5 transition-colors"
                                                    x-bind:class="selectedRoles.includes('editor') ? 'text-foresty' : 'text-zinc-400 group-hover:text-foresty/70'" />
                                                
                                                <span class="font-bold transition-colors"
                                                    x-bind:class="selectedRoles.includes('editor') ? 'text-foresty' : 'text-zinc-800 dark:text-zinc-200'">
                                                    Editor
                                                </span>
                                            </div>
                                            
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed select-none">
                                                Dapat menyunting semua artikel dan menyetujui publikasi.
                                            </p>
                                        </div>
                                        
                                        <div class="absolute top-4 right-4 transition-opacity"
                                            x-bind:class="selectedRoles.includes('editor') ? 'opacity-100 text-foresty' : 'opacity-0 text-zinc-400'">
                                            <x-dynamic-component :component="'lucide-circle-check'" class="w-5 h-5 fill-foresty/10" />
                                        </div>
                                    </label>

                                    <!-- KARTU 3: ADMIN -->
                                    <label class="relative block group" x-bind:class="isEditing ? 'cursor-pointer' : ''">
                                        <input x-bind:disabled="!isEditing" type="checkbox" wire:model="roles" value="admin" class="peer sr-only">
                                        
                                        <div class="h-full p-4 border-2 rounded-2xl bg-white dark:bg-zinc-900 transition-all duration-200"
                                            x-bind:class="selectedRoles.includes('admin') ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10' : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'">
                                            
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <x-dynamic-component :component="'lucide-shield-check'" 
                                                    class="w-5 h-5 transition-colors"
                                                    x-bind:class="selectedRoles.includes('admin') ? 'text-foresty' : 'text-zinc-400 group-hover:text-foresty/70'" />
                                                
                                                <span class="font-bold transition-colors"
                                                    x-bind:class="selectedRoles.includes('admin') ? 'text-foresty' : 'text-zinc-800 dark:text-zinc-200'">
                                                    Admin
                                                </span>
                                            </div>
                                            
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed select-none">
                                                Akses penuh ke semua pengaturan sistem dan pengguna.
                                            </p>
                                        </div>
                                        
                                        <div class="absolute top-4 right-4 transition-opacity"
                                            x-bind:class="selectedRoles.includes('admin') ? 'opacity-100 text-foresty' : 'opacity-0 text-zinc-400'">
                                            <x-dynamic-component :component="'lucide-circle-check'" class="w-5 h-5 fill-foresty/10" />
                                        </div>
                                    </label>

                                </div>

                                <!-- Kotak Info Dinamis -->
                                <!-- <div x-show="selectedRoles.length > 0 && isEditing" x-cloak
                                    x-transition:/enter="transition ease-out duration-300"
                                    x-transition:/enter-start="opacity-0 -translate-y-2"
                                    x-transition:/enter-end="opacity-100 translate-y-0"
                                    class="mt-3 p-4 bg-sage-soft/30 dark:bg-zinc-800/50 rounded-xl border border-foresty/20 relative overflow-hidden">
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-forest"></div>
                                    <div class="ml-2 flex items-start gap-3 select-none">
                                        <x-dynamic-component :component="'lucide-info'" class="w-5 h-5 text-forest shrink-0 mt-0.5" />
                                        <div class="text-sm space-y-1.5">
                                            <p x-show="selectedRoles.includes('writer')" class="text-zinc-700 dark:text-zinc-300"><strong class="text-forest">Writer:</strong> Mengelola draf pribadi.</p>
                                            <p x-show="selectedRoles.includes('editor')" class="text-zinc-700 dark:text-zinc-300"><strong class="text-forest">Editor:</strong> Menyunting dan mempublikasi semua artikel.</p>
                                            <p x-show="selectedRoles.includes('admin')" class="text-zinc-700 dark:text-zinc-300"><strong class="text-forest">Admin:</strong> Akses penuh konfigurasi sistem.</p>
                                        </div>
                                    </div>
                                </div> -->
                                
                                @error('roles') <span class="text-xs text-red-500 mt-2 block font-semibold">{{ $message }}</span> @enderror
                            </div>



                            <!-- Baris 3: Jabatan & Peran Sistem (Berdampingan) -->
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Jabatan (Organisasi)</label>
                                    <input x-bind:disabled="!isEditing" type="text" wire:model="job_title" class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-forest/20 focus:border-forest transition-colors shadow-sm text-sm" placeholder="Misal: Program Officer KIA">
                                    @error('job_title') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Baris 4: Toggle Status Aktif -->
                            <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-2xl border border-zinc-200 dark:border-zinc-800 transition-opacity"
                                :class="!isEditing ? 'opacity-70' : ''">
                                <div>
                                    <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Status Akun Aktif</h4>
                                    <p class="text-xs text-zinc-500 mt-0.5">Jika dimatikan, pengguna ini tidak akan bisa login ke dalam CMS.</p>
                                </div>
                                
                                <!-- Toggle Switch Alpine -->
                                <!-- KUNCI: Hapus "cursor-pointer" statis, ganti dengan binding dinamis -->
                                <label x-data="{ checked: @entangle('active') }" 
                                    class="relative inline-flex items-center"
                                    :class="isEditing ? 'cursor-pointer' : 'cursor-default pointer-events-none'">
                                    
                                    <input x-bind:disabled="!isEditing" type="checkbox" x-model="checked" class="sr-only peer">
                                    
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-forest"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Footer Area (Action Buttons) -->
                        <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex justify-end gap-3">
                            <button x-show="!isEditing" x-cloak type="button" 
                                @click="showDeleteModal = true"
                                class="px-5 py-2.5 text-sm font-bold text-foresty bg-coral-muted hover:bg-red-700 hover:text-goldy rounded-xl shadow-md transition-colors flex items-center gap-2 cursor-pointer select-none">
                                <x-dynamic-component :component="'lucide-shredder'" class="w-4 h-4" />
                                <span wire:loading.remove wire:target="save">Hapus Pengguna</span>
                                <span wire:loading wire:target="save">Menghapus...</span>
                            </button>
                            <button x-show="isEditing" x-cloak type="submit" class="px-5 py-2.5 text-sm font-bold bg-misty text-foresty hover:text-goldy  hover:bg-forest  rounded-xl shadow-md transition-colors flex items-center gap-2 cursor-pointer select-none">
                                <x-dynamic-component :component="'lucide-save'" class="w-4 h-4" />
                                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                            <button x-show="isEditing" x-cloak @click="isEditing = false" type="button" wire:click="cancel" 
                            class="px-5 py-2.5 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 bg-zinc-100 rounded-xl transition-colors cursor-pointer select-none">
                                Batal
                            </button>
                            <button type="button" 
                                x-show="!isEditing" 
                                @click="isEditing = true"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-forest bg-sage-soft/30 hover:bg-sage-soft rounded-lg transition-colors border border-forest/10 select-none cursor-pointer">
                                <x-dynamic-component :component="'lucide-pencil'" class="w-4 h-4" />
                                Edit Profil
                            </button>
                        </div>
                        
                    </form>
                </div>

                {{-- DELETE MODAL --}}
                <div x-show="showDeleteModal" class="relative z-99" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak >
                    <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
                    ></div>

                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.away="showDeleteModal = false" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 px-4 pb-4 pt text-left shadow-xl transition-all w-full max-w-sm sm:my-8 sm:p-6" >
                                <div>
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                        <flux:icon variant="outline" icon="exclamation-triangle" class="h-6 w-6 text-terracotta dark:text-red-400" />
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <h3 class="text-base font-bold leading-6 text-zinc-900 dark:text-white" id="modal-title">
                                            Hapus Pengguna
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                                Apakah Anda yakin ingin pengguna ini? Data yang sudah dihapus tidak dapat dikembalikan.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row-reverse gap-3">
                                    <button
                                        type="button"
                                        class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-sage-soft px-3 py-2 text-sm font-semibold text-forest shadow-sm hover:bg-red-600 hover:text-white transition-colors sm:w-auto"
                                        @click=" $wire.deleteUser(); showDeleteModal = false; ">
                                        Ya, Hapus
                                    </button>
                                    <button
                                        type="button"

                                        @click = "showDeleteModal = false"
                                        class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-300 shadow-sm ring-1 ring-inset ring-zinc-300 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors sm:w-auto">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- end of modal wrapper-->

            </div>
        </div>
    </div>
</div>