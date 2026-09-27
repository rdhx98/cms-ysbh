<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed; 
use Livewire\Attributes\On;
use App\Models\MediaFolder;
use App\Models\Media;

new class extends Component
{
    use WithFileUploads;

    public $selectedMedia = []; 
    public $isModal = true; 
    public $forceModal = false; // 🌟 KUNCI PENCEGAH TAMPILAN GANDA

    public $currentFolderId = null;
    public $search = '';
    public $uploadFiles = []; 

    public $newFolderName = '';

    public $movingMediaId = null;
    public $movingToFolderId = 'root';
    
    public $targetEvent = null; 
    public $targetComponentId = null; 

    public function mount()
    {
        // 🌟 Logika Cerdas: Jika dipaksa jadi modal dari app.blade.php, abaikan route.
        if ($this->forceModal) {
            $this->isModal = true;
        } else {
            // Jika tidak, cek apakah ini diakses dari halaman penuh
            if (request()->routeIs('files.index') || request()->is('*files*')) {
                $this->isModal = false;
            }
        }
    }

    #[On('openFileManager')]
    public function openManager($targetEvent = 'mediaSelected', $targetComponentId = null)
    {
        $this->targetEvent = $targetEvent;
        $this->targetComponentId = $targetComponentId;
        $this->search = ''; 
        $this->selectedMedia = [];
        $this->dispatch('show-file-manager-modal');
    }

    public function selectMedia($mediaId, $mediaUrl)
    {
        $this->dispatch($this->targetEvent, [
            'id' => $mediaId,
            'url' => $mediaUrl,
            'componentId' => $this->targetComponentId
        ]);
        $this->dispatch('hide-file-manager-modal');
    }

    public function saveUploads()
    {
        $this->validate(['uploadFiles.*' => 'image|max:15360']);

        foreach ($this->uploadFiles as $file) {
            $path = $file->store('media/' . date('Y/m'), 'public');
            Media::create([
                'folder_id' => $this->currentFolderId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(), 
            ]);
        }

        $this->uploadFiles = [];
        $this->dispatch('swal', title: 'Berhasil diunggah!', icon: 'success');
    }

    public function createFolder()
    {
        $this->validate(['newFolderName' => 'required|string|max:40']);

        if ($this->currentDepth >= 3) {
            $this->dispatch('swal', title: 'Batas Maksimal!', text: 'Tidak bisa membuat folder lebih dalam dari 3 tingkat.', icon: 'error');
            return;
        }

        MediaFolder::create([
            'name' => $this->newFolderName,
            'parent_id' => $this->currentFolderId,
        ]);

        $this->newFolderName = '';
        $this->dispatch('swal', title: 'Folder Dibuat!', icon: 'success');
    }

    public function deleteMedia($mediaId)
    {
        $media = Media::find($mediaId);
        if (!$media) return;

        if ($media->isInUse()) {
            $this->dispatch('swal', title: 'Gagal', text: 'Gambar sedang digunakan di halaman/artikel lain!', icon: 'error');
            return;
        }

        $media->deleteWithFile();
        $this->dispatch('swal', title: 'Terhapus', icon: 'success');
    }

    public function bulkDelete()
    {
        $medias = Media::whereIn('id', $this->selectedMedia)->get();
        $deletedCount = 0;
        $inUseCount = 0;

        foreach ($medias as $media) {
            if ($media->isInUse()) {
                $inUseCount++;
            } else {
                $media->deleteWithFile();
                $deletedCount++;
            }
        }

        $this->selectedMedia = []; 
        if ($inUseCount > 0) {
            $this->dispatch('swal', title: 'Selesai', text: "$deletedCount terhapus. $inUseCount DILEWATI karena dipakai.", icon: 'warning');
        } else {
            $this->dispatch('swal', title: 'Selesai', text: "$deletedCount file terhapus.", icon: 'success');
        }
    }

    public function renameMedia($mediaId, $newName)
    {
        $media = Media::find($mediaId);
        if ($media && !empty(trim($newName))) {
            $media->update(['original_name' => trim($newName)]);
            $this->dispatch('swal', title: 'Nama Diubah!', icon: 'success');
        }
    }

    public function setMoveDestination($folderId)
    {
        $this->movingToFolderId = $folderId;
    }

    public function openMovePanel($mediaId = null)
    {
        $this->movingMediaId = $mediaId;
        
        if ($mediaId) {
            $media = Media::find($mediaId);
            $this->movingToFolderId = $media ? ($media->folder_id ?? 'root') : 'root';
        } else {
            $this->movingToFolderId = $this->currentFolderId ?? 'root';
        }
        
        $this->dispatch('show-move-panel');
    }

    public function executeMove()
    {
        $targetId = ($this->movingToFolderId === 'root' || $this->movingToFolderId === '') 
                    ? null 
                    : (int) $this->movingToFolderId; 

        if ($this->movingMediaId) {
            Media::where('id', $this->movingMediaId)->update(['folder_id' => $targetId]);
        } 
        elseif (!empty($this->selectedMedia)) {
            Media::whereIn('id', $this->selectedMedia)->update(['folder_id' => $targetId]);
        }
        
        $this->dispatch('swal', title: 'Berhasil dipindahkan!', icon: 'success');
        
        $this->movingMediaId = null;
        $this->selectedMedia = []; 
        $this->movingToFolderId = 'root';
        
        $this->dispatch('hide-move-panel');
    }

    #[Computed]
    public function activePathIds()
    {
        $ids = [];
        $temp = $this->currentFolderId;
        $breaker = 10;
        while ($temp && $breaker > 0) {
            $ids[] = $temp;
            $f = MediaFolder::find($temp);
            $temp = $f ? $f->parent_id : null;
            $breaker--;
        }
        return $ids;
    }

    #[Computed]
    public function currentDepth()
    {
        if (!$this->currentFolderId) return 0; 
        return count($this->activePathIds);
    }

    #[Computed]
    public function flatFolders()
    {
        $result = [['id' => 'root', 'name' => '🏠 Root (Semua Media)']];
        foreach ($this->rootFolders as $f) {
            $result[] = ['id' => $f->id, 'name' => '📁 ' . $f->name];
            foreach ($f->children as $c) {
                $result[] = ['id' => $c->id, 'name' => '— 📁 ' . $c->name];
                foreach ($c->children as $cc) {
                    $result[] = ['id' => $cc->id, 'name' => '—— 📁 ' . $cc->name];
                }
            }
        }
        return $result;
    }

    #[Computed]
    public function folders()
    {
        return MediaFolder::where('parent_id', $this->currentFolderId)->orderBy('name')->get();
    }

    #[Computed]
    public function mediaItems()
    {
        return Media::where('folder_id', $this->currentFolderId)
                    ->when($this->search, fn($q) => $q->where('original_name', 'like', "%{$this->search}%"))
                    ->latest()
                    ->get();
    }

    #[Computed]
    public function currentFolder()
    {
        return $this->currentFolderId ? MediaFolder::find($this->currentFolderId) : null;
    }

    #[Computed]
    public function rootFolders()
    {
        return MediaFolder::with('children.children')->whereNull('parent_id')->orderBy('name')->get();
    }
};
?>

{{-- ========================================================== --}}
{{-- 🌟 ROOT ELEMENT UTAMA 🌟 --}}
{{-- ========================================================== --}}
<div>
    
    {{-- 🌟 PEMBUNGKUS LUAR (KONDISIONAL) 🌟 --}}
    @if($isModal)
        <!-- 🌟 PERBAIKAN MODAL BURAM: Pisahkan Backdrop & Kotak Konten -->
        <div x-data="{ isOpen: false }"
             x-on:show-file-manager-modal.window="isOpen = true"
             x-on:hide-file-manager-modal.window="isOpen = false"
             x-show="isOpen"
             style="display: none;"
             class="fixed inset-0 z-[100] flex items-center justify-center">
             
             <!-- Backdrop Hitam (Klik ini untuk menutup modal) -->
             <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="isOpen = false; $dispatch('hide-file-manager-modal')"></div>

             <!-- Kotak Putih Konten -->
             <div class="relative z-10 flex h-[85vh] w-[90vw] max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
    @else
        <x-main-wrapper>
             <!-- Kotak Halaman Penuh -->
             <div class="relative flex h-[calc(100vh-100px)] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-200">
    @endif
            
            <!-- HEADER FILE MANAGER -->
            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 shrink-0">
                <div class="flex items-center gap-3">
                    <x-dynamic-component component="lucide-folder-open" class="h-6 w-6 text-foresty" stroke-width="2.5" />
                    <h2 class="text-lg font-bold text-gray-800">Media Manager</h2>
                </div>
                @if($isModal)
                    <button type="button" @click="isOpen = false; $dispatch('hide-file-manager-modal')" class="rounded-full p-2 text-gray-400 hover:bg-gray-200 hover:text-gray-600 outline-none">
                        <x-dynamic-component component="lucide-x" class="h-5 w-5" />
                    </button>
                @endif
            </div>

            <!-- MAIN LAYOUT (2 KOLOM: Kiri & Tengah) -->
            <div class="flex flex-1 min-h-0">
                
                <!-- KOLOM KIRI: Navigasi Pohon Folder (Tree View) -->
                <div class="w-64 shrink-0 flex-col border-r border-gray-200 bg-gray-50 p-4 overflow-y-auto scrollbar-thin hidden md:flex">
                    <button wire:click="$set('currentFolderId', null)" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold hover:bg-gray-200 transition-colors outline-none {{ !$this->currentFolderId ? 'bg-sage-soft text-foresty' : 'text-gray-700' }}">
                        <x-dynamic-component component="lucide-home" class="h-4 w-4" /> Folder Utama
                    </button>
                    <div class="my-4 border-t border-gray-200"></div>
                    <span class="text-xs text-gray-400 font-semibold px-3 uppercase tracking-widest mb-2 block">Struktur Direktori</span>
                    
                    <div class="space-y-0.5">
                        <!-- LEVEL 1 (Root Folders) -->
                        @foreach($this->rootFolders as $rf)
                            <div x-data="{ expanded: {{ in_array($rf->id, $this->activePathIds) ? 'true' : 'false' }} }">
                                <div class="flex items-center group">
                                    <button type="button" @click="expanded = !expanded" class="p-1 text-gray-400 hover:text-foresty outline-none" x-show="{{ $rf->children->count() ? 'true' : 'false' }}">
                                        <x-dynamic-component component="lucide-chevron-right" class="h-3 w-3 transition-transform duration-200" x-bind:class="expanded ? 'rotate-90' : ''" />
                                    </button>
                                    <div class="p-1" x-show="{{ $rf->children->count() ? 'false' : 'true' }}"><div class="w-3"></div></div>
                                    
                                    <button wire:click="$set('currentFolderId', {{ $rf->id }})" class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-semibold hover:bg-gray-200 text-left transition-colors outline-none {{ $this->currentFolderId === $rf->id ? 'bg-sage-soft text-foresty' : 'text-gray-600' }}">
                                        <x-dynamic-component component="lucide-folder" class="h-3.5 w-3.5 shrink-0" /> 
                                        <span class="truncate" title="{{ $rf->name }}">{{ $rf->name }}</span>
                                    </button>
                                </div>

                                <!-- LEVEL 2 (Sub Folders) -->
                                <div x-show="expanded" x-collapse class="pl-4 ml-2 mt-0.5 border-l border-gray-200 space-y-0.5" x-cloak>
                                    @foreach($rf->children as $child)
                                        <div x-data="{ expanded2: {{ in_array($child->id, $this->activePathIds) ? 'true' : 'false' }} }">
                                            <div class="flex items-center group">
                                                <button type="button" @click="expanded2 = !expanded2" class="p-1 text-gray-400 hover:text-foresty outline-none" x-show="{{ $child->children->count() ? 'true' : 'false' }}">
                                                    <x-dynamic-component component="lucide-chevron-right" class="h-3 w-3 transition-transform duration-200" x-bind:class="expanded2 ? 'rotate-90' : ''" />
                                                </button>
                                                <div class="p-1" x-show="{{ $child->children->count() ? 'false' : 'true' }}"><div class="w-3"></div></div>
                                                
                                                <button wire:click="$set('currentFolderId', {{ $child->id }})" class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium hover:bg-gray-200 text-left transition-colors outline-none {{ $this->currentFolderId === $child->id ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-600' }}">
                                                    <x-dynamic-component component="lucide-folder" class="h-3.5 w-3.5 shrink-0 {{ $this->currentFolderId === $child->id ? 'text-foresty' : 'text-gray-400' }}" /> 
                                                    <span class="truncate" title="{{ $child->name }}">{{ $child->name }}</span>
                                                </button>
                                            </div>

                                            <!-- LEVEL 3 (Sub-Sub Folders) -->
                                            <div x-show="expanded2" x-collapse class="pl-4 ml-2 mt-0.5 border-l border-gray-200 space-y-0.5" x-cloak>
                                                @foreach($child->children as $subchild)
                                                    <div class="flex items-center group">
                                                        <div class="p-1"><div class="w-3"></div></div>
                                                        <button wire:click="$set('currentFolderId', {{ $subchild->id }})" class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-medium hover:bg-gray-200 text-left transition-colors outline-none {{ $this->currentFolderId === $subchild->id ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-500' }}">
                                                            <x-dynamic-component component="lucide-folder" class="h-3 w-3 shrink-0 {{ $this->currentFolderId === $subchild->id ? 'text-foresty' : 'text-gray-300' }}" /> 
                                                            <span class="truncate" title="{{ $subchild->name }}">{{ $subchild->name }}</span>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- KOLOM TENGAH: Grid File & Folder -->
                <div x-data="{ isDragging: false }"
                     x-on:dragover.prevent="isDragging = true"
                     x-on:dragleave.prevent="isDragging = false"
                     x-on:drop.prevent="
                         isDragging = false;
                         if ($event.dataTransfer.files.length > 0) {
                             $wire.uploadMultiple('uploadFiles', $event.dataTransfer.files, () => {
                                 $wire.saveUploads();
                             });
                         }
                     "
                     class="flex flex-1 flex-col bg-white min-w-0 relative">
                    
                    <!-- Drag & Drop Overlay -->
                    <div x-show="isDragging" x-transition.opacity.duration.300ms x-cloak class="absolute inset-0 z-50 m-4 flex items-center justify-center rounded-2xl border-4 border-dashed border-foresty bg-emerald-50/90 backdrop-blur-sm pointer-events-none">
                        <div class="flex flex-col items-center rounded-2xl bg-white px-8 py-6 shadow-xl">
                            <x-dynamic-component component="lucide-upload-cloud" class="mb-3 h-14 w-14 animate-bounce text-foresty" stroke-width="2" />
                            <span class="text-xl font-extrabold text-gray-800">Lepaskan File di Sini</span>
                            <span class="mt-2 rounded bg-sage-soft px-2 py-1 text-xs font-bold uppercase tracking-wider text-foresty">
                                Unggah ke: <span x-text="$wire.currentFolderId ? 'Folder Aktif' : 'Semua Media'"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Toolbar Atas Grid -->
                    <div class="flex flex-wrap items-center justify-between border-b border-gray-100 px-4 py-3 gap-3">
                        <div class="flex items-center gap-2">
                            @if($this->currentFolder)
                                <button wire:click="$set('currentFolderId', {{ $this->currentFolder->parent_id ?? 'null' }})" class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 outline-none transition-colors">
                                    <x-dynamic-component component="lucide-arrow-left" class="h-4 w-4" />
                                </button>
                                <span class="font-bold text-sm text-gray-700 truncate max-w-[150px]">{{ $this->currentFolder->name }}</span>
                            @else
                                <span class="font-bold text-sm text-gray-700">Semua Media</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-3">
                            @if($this->currentDepth < 2)
                            <form wire:submit.prevent="createFolder" class="relative items-center hidden sm:flex">
                                <input type="text" wire:model="newFolderName" placeholder="Nama folder baru..." required class="w-36 sm:w-40 rounded-l-lg border-gray-200 pl-3 pr-2 text-xs focus:border-foresty focus:ring-foresty shadow-sm h-8">
                                <button type="submit" class="bg-sage-soft text-foresty hover:bg-foresty hover:text-white px-2 rounded-r-lg border border-l-0 border-gray-200 shadow-sm transition-colors h-8">
                                    <x-dynamic-component component="lucide-plus" class="h-4 w-4" />
                                </button>
                            </form>
                            @endif

                            <div class="relative">
                                <x-dynamic-component component="lucide-search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari media..." class="w-full sm:w-48 rounded-lg border-gray-200 pl-9 text-xs focus:border-foresty focus:ring-foresty shadow-sm">
                            </div>
                            
                            <!-- Tombol Unggah -->
                            <label class="relative cursor-pointer bg-foresty hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm flex items-center gap-2 transition-colors">
                                <x-dynamic-component component="lucide-upload-cloud" class="h-4 w-4 shrink-0" />
                                <span class="hidden sm:inline">Unggah</span>
                                <input type="file" wire:model.live="uploadFiles" multiple class="hidden" accept="image/*" x-on:change="$wire.saveUploads()">
                            </label>
                        </div>
                    </div>

                    <!-- Loading Progress -->
                    <div wire:loading wire:target="uploadFiles" class="w-full bg-blue-50 px-4 py-2 text-xs font-bold text-blue-600 flex items-center gap-2 border-b border-blue-100">
                        <x-dynamic-component component="lucide-loader-2" class="h-4 w-4 animate-spin" />
                        Sedang memproses unggahan, mohon tunggu...
                    </div>

                    <!-- 🌟 FLOATING BULK ACTION BAR 🌟 -->
                    <div x-show="$wire.selectedMedia.length > 0" x-cloak x-transition.opacity
                         class="absolute bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-4 rounded-full bg-gray-900 px-6 py-3 shadow-2xl text-white">
                        <span class="text-sm font-bold bg-white/20 px-3 py-1 rounded-full"><span x-text="$wire.selectedMedia.length"></span> Dipilih</span>
                        <div class="h-6 w-px bg-gray-600"></div>
                        
                        <!-- Tombol Pindah Massal -->
                        <button type="button" 
                            wire:click="openMovePanel(null)" 
                            class="flex items-center gap-2 text-sm font-semibold hover:text-blue-400 transition-colors">
                            <x-dynamic-component component="lucide-folder-symlink" class="h-4 w-4" /> Pindahkan
                        </button>
                        
                        <button type="button" wire:click="bulkDelete" wire:confirm="Hapus semua file terpilih secara permanen? File yang sedang dipakai artikel akan aman." class="flex items-center gap-2 text-sm font-semibold text-red-400 hover:text-red-300 transition-colors">
                            <x-dynamic-component component="lucide-trash-2" class="h-4 w-4" /> Hapus
                        </button>
                        <button type="button" wire:click="$set('selectedMedia', [])" class="ml-2 rounded-full p-1 hover:bg-gray-700 transition-colors">
                            <x-dynamic-component component="lucide-x" class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Grid Konten -->
                    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin bg-gray-50/50">
                        @if($this->folders->isEmpty() && $this->mediaItems->isEmpty())
                            <div class="flex h-full flex-col items-center justify-center text-gray-400">
                                <x-dynamic-component component="lucide-folder-open" class="h-12 w-12 mb-3 opacity-20" />
                                <p class="text-sm font-bold">Folder Kosong</p>
                                <p class="text-xs">Seret & lepas file ke sini untuk mengunggah.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 pb-20">
                                
                                <!-- Render Folders -->
                                @foreach($this->folders as $folder)
                                    <button wire:click="$set('currentFolderId', {{ $folder->id }})" class="group flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-foresty hover:shadow-md outline-none">
                                        <x-dynamic-component component="lucide-folder" class="h-10 w-10 text-foresty/40 group-hover:text-foresty transition-colors" fill="currentColor" />
                                        <span class="w-full truncate text-center text-xs font-bold text-gray-700">{{ $folder->name }}</span>
                                    </button>
                                @endforeach

                                <!-- Render Media -->
                                @foreach($this->mediaItems as $media)
                                    <div class="group relative flex flex-col overflow-visible rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-foresty hover:ring-2 hover:ring-foresty/20" x-data="{ openMenu: false }">
                                        
                                        <!-- Kotak Gambar & Pilih -->
                                        <div class="relative aspect-square w-full bg-gray-100 flex items-center justify-center overflow-hidden rounded-t-xl">
                                            
                                            <!-- Checkbox Multi-Select -->
                                            <div class="absolute top-2 left-2 z-20" :class="$wire.selectedMedia.length > 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 transition-opacity'">
                                                <input type="checkbox" wire:model.live="selectedMedia" value="{{ $media->id }}" class="h-5 w-5 rounded border-gray-300 text-foresty focus:ring-foresty shadow-sm cursor-pointer">
                                            </div>

                                            @if($media->isImage())
                                                <img src="{{ $media->url() }}" alt="{{ $media->original_name }}" class="h-full w-full object-cover">
                                            @else
                                                <x-dynamic-component component="lucide-file-text" class="h-10 w-10 text-gray-400" />
                                            @endif

                                            <!-- Tombol Gunakan (Hanya di mode Modal Pop-up) -->
                                            @if($isModal)
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100">
                                                <button type="button" wire:click="selectMedia({{ $media->id }}, '{{ $media->url() }}')" class="rounded-lg bg-foresty px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-emerald-700 transition-colors outline-none cursor-pointer">
                                                    Gunakan Gambar
                                                </button>
                                            </div>
                                            @endif
                                        </div>

                                        <!-- Detail Teks & Menu Kebab -->
                                        <div class="p-2 flex items-start justify-between gap-1 border-t border-gray-50">
                                            <div class="min-w-0">
                                                <p class="truncate text-[11px] font-bold text-gray-700" title="{{ $media->original_name }}">{{ $media->original_name }}</p>
                                                <p class="text-[9px] text-gray-400 font-semibold">{{ round($media->size / 1024, 1) }} KB</p>
                                            </div>
                                            <button type="button" @click.prevent="openMenu = !openMenu" class="shrink-0 p-1 text-gray-400 hover:text-foresty hover:bg-sage-soft rounded transition-colors outline-none">
                                                <x-dynamic-component component="lucide-more-vertical" class="h-4 w-4" />
                                            </button>
                                        </div>

                                        <!-- MENU KEBAB AKSI -->
                                        <div x-show="openMenu" @click.outside="openMenu = false" x-transition x-cloak class="absolute right-2 top-auto bottom-8 z-50 w-36 rounded-lg border border-gray-200 bg-white py-1 shadow-xl">
                                            <button type="button" @click="let n = prompt('Ganti nama file:', '{{ addslashes($media->original_name) }}'); if(n) { $wire.renameMedia({{ $media->id }}, n); } openMenu = false;" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 hover:text-foresty transition-colors">
                                                <x-dynamic-component component="lucide-pencil" class="h-3.5 w-3.5" /> Ganti Nama
                                            </button>
                                            <button type="button" @click="openMenu = false; $wire.openMovePanel({{ $media->id }})" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition-colors outline-none">
                                                <x-dynamic-component component="lucide-folder-symlink" class="h-3.5 w-3.5" /> Pindah Folder
                                            </button>
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <button type="button" wire:click="deleteMedia({{ $media->id }})" wire:confirm="Hapus gambar ini secara permanen?" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors">
                                                <x-dynamic-component component="lucide-trash-2" class="h-3.5 w-3.5" /> Hapus
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div> {{-- Tutup Kotak Konten Utama --}}

    {{-- 🌟 PENUTUP BUNGKUSAN LUAR (KONDISIONAL) 🌟 --}}
    @if($isModal)
        </div> <!-- Tutup Div Backdrop Pop-up -->
    @else
        </x-main-wrapper>
    @endif

    <!-- ======================================================== -->
    <!-- 🌟 SLIDE-OVER PANEL: PINDAH FOLDER (Di luar container overflow) 🌟 -->
    <!-- ======================================================== -->
    <div x-data="{
            isMoveOpen: false,
            searchQuery: '',
            init() {
                window.addEventListener('show-move-panel', () => { this.isMoveOpen = true; this.searchQuery = ''; });
                window.addEventListener('hide-move-panel', () => { this.isMoveOpen = false; });
            }
        }"
        x-show="isMoveOpen"
        class="fixed inset-0 z-[110] overflow-hidden"
        x-cloak
    >
        <div class="absolute inset-0 overflow-hidden">
            <div x-show="isMoveOpen" x-transition.opacity.duration.300ms @click="isMoveOpen = false; $wire.set('movingMediaId', null)" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                <div x-show="isMoveOpen"
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-md bg-white shadow-2xl flex flex-col"
                     @click.stop
                >
                    <div class="bg-foresty px-4 py-6 sm:px-6 shrink-0">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <x-dynamic-component component="lucide-folder-symlink" class="h-5 w-5" /> Pindah Folder
                            </h2>
                            <button type="button" @click="isMoveOpen = false; $wire.set('movingMediaId', null)" class="rounded-md text-emerald-200 hover:text-white outline-none cursor-pointer">
                                <x-dynamic-component component="lucide-x" class="h-6 w-6" />
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-emerald-100">Cari dan pilih direktori tujuan.</p>
                    </div>

                    <div class="flex flex-1 flex-col bg-gray-50 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 bg-white shrink-0">
                            <div class="relative">
                                <x-dynamic-component component="lucide-search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                <input type="text" x-model="searchQuery" placeholder="Cari nama folder..." class="w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-foresty focus:ring-foresty shadow-sm">
                            </div>
                        </div>

                        <!-- Daftar Folder Scrollable -->
                        <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
                            <div class="space-y-1 rounded-xl border border-gray-200 bg-white p-2 shadow-sm">
                                
                                @foreach($this->flatFolders as $ff)
                                    <!-- 🌟 KITA UBAH LABEL & RADIO MENJADI BUTTON MURNI 🌟 -->
                                    <button type="button" 
                                        wire:click="setMoveDestination('{{ $ff['id'] }}')"
                                        x-data="{ name: '{{ strtolower(addslashes($ff['name'])) }}' }"
                                        x-show="searchQuery === '' || name.includes(searchQuery.toLowerCase())"
                                        class="w-full flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition-colors text-left outline-none"
                                        :class="$wire.movingToFolderId == '{{ $ff['id'] }}' ? 'bg-sage-soft text-foresty shadow-inner' : 'text-gray-700 hover:bg-gray-100'"
                                    >
                                        <!-- Ikon Lingkaran (Sebagai Pengganti Radio Button Asli) -->
                                        <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition-all"
                                             :class="$wire.movingToFolderId == '{{ $ff['id'] }}' ? 'border-[5px] border-foresty bg-white' : 'border-gray-300 bg-white'">
                                        </div>
                                        
                                        <span class="text-sm font-semibold whitespace-pre truncate">{{ $ff['name'] }}</span>
                                    </button>
                                @endforeach

                            </div>
                        </div>
                    </div>

                    <!-- FOOTER: TOMBOL AKSI PANEL PINDAH -->
                    <div class="flex shrink-0 justify-end gap-3 px-4 py-4 bg-white border-t border-gray-200">
                        <button type="button" @click="isMoveOpen = false; $wire.set('movingMediaId', null)" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 outline-none transition-colors cursor-pointer">
                            Batal
                        </button>
                        
                        <!-- 🌟 PASTIKAN INI ADALAH wire:click 🌟 -->
                        <button type="button" wire:click="executeMove" class="inline-flex justify-center rounded-lg bg-foresty px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-emerald-800 outline-none transition-colors cursor-pointer">
                            Pindahkan File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>