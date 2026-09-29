<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\MediaFolder;
use App\Models\Media;
use App\Livewire\Traits\WithNotifications;
use Livewire\WithPagination;

new class extends Component {
  use WithFileUploads, WithNotifications, WithPagination;

  public $selectedMedia = [];
  public $isModal = true;
  public $forceModal = false;

  public $currentFolderId = null;
  public $search = "";
  public $uploadFiles = [];

  public $newFolderName = "";

  public $isMovePanelOpen = false;
  public $movingMediaId = null;
  public $movingToFolderId = "root";

  public $targetEvent = null;
  public $targetComponentId = null;
  public $allowedFileType = 'all';

  // Variabel Panel Informasi
  public $isInfoPanelOpen = false;
  public $selectedItemId = null;
  public $selectedItemType = null;
  public $activeAltText = "";

  public function mount()
  {
    //dd('Batas Upload: ' . ini_get('upload_max_filesize'), 'Batas Post: ' . ini_get('post_max_size'));
    if ($this->forceModal) {
      $this->isModal = true;
    } else {
      if (request()->routeIs("files.index") || request()->is("*files*")) {
        $this->isModal = false;
      }
    }
  }
  #[On("openFileManager")]
  public function openManager(
    $targetEvent = "mediaSelected",
    $targetComponentId = null,
    $allowedFileType = "all"
  ) {
    if (is_array($targetEvent)) {
      $payload = $targetEvent;
      $targetEvent = $payload['targetEvent'] ?? 'mediaSelected';
      $targetComponentId = $payload['targetComponentId'] ?? null;
      $allowedFileType = $payload['allowedFileType'] ?? 'all';
    }
    $this->targetEvent = $targetEvent;
    $this->targetComponentId = $targetComponentId;
    $this->allowedFileType = $allowedFileType;
    $this->search = "";
    $this->selectedMedia = [];
    $this->dispatch("show-file-manager-modal");
  }

  public function selectMedia($mediaId, $mediaUrl)
  {
    $this->dispatch($this->targetEvent, [
      "id" => $mediaId,
      "url" => $mediaUrl,
      "componentId" => $this->targetComponentId,
    ]);
    $this->dispatch("hide-file-manager-modal");
  }

  public function updatedCurrentFolderId()
  {
    $this->dispatch("folder-navigated", path: $this->activePathIds);
  }

  public function openFolder($folderId = null)
  {
    $this->currentFolderId =
      $folderId === "null" || $folderId === "" ? null : $folderId;

    // Reset pilihan dan tutup panel
    $this->selectedMedia = [];
    $this->closeInfoPanel();

    $this->dispatch("folder-navigated", path: $this->activePathIds);
  }

  public function openFolderDoubleClick($folderId)
  {
    $this->openFolder($folderId);
    $this->closeInfoPanel();
  }

  // ==========================================
  // LOGIKA PANEL INFORMASI & SELEKSI
  // ==========================================

  // public function selectItem($id, $type)
  // {
  //   $this->selectedItemId = $id;
  //   $this->selectedItemType = $type;
  //   $this->isInfoPanelOpen = true;

  //   if ($type === "media") {
  //     $media = Media::find($id);
  //     $this->activeAltText = $media->alt_text ?? "";
  //   }
  // }
  public function selectItem($id, $type)
  {
    $this->selectedItemId = $id;
    $this->selectedItemType = $type;
    // $this->isInfoPanelOpen = true;

    if ($type === "media") {
      $media = Media::find($id);
      $this->activeAltText = $media->alt_text ?? "";
    } elseif ($type === "folder") {
      // 🌟 PERBAIKAN: Bersihkan keranjang seleksi file saat folder diklik!
      // Ini akan secara otomatis menghapus centang pada gambar di layar.
      $this->selectedMedia = [];
    }
    $this->dispatch('item-selected');
  }

  public function closeInfoPanel()
  {
    // $this->isInfoPanelOpen = false;
    // $this->selectedItemId = null;

    $this->selectedMedia = []; // 🌟 Kosongkan pemilih file jamak/tunggal
    $this->selectedItemId = null; // 🌟 Kosongkan ID item aktif
    $this->selectedItemType = null;

    $this->dispatch('close-info-panel');
  }

  public function updatedActiveAltText($value)
  {
    if ($this->selectedItemType === "media" && $this->selectedItemId) {
      $media = Media::find($this->selectedItemId);
      if ($media) {
        $media->update(["alt_text" => $value]);
        $this->logActivity(
          "updated",
          $media,
          "Mengubah teks alternatif (Alt Text) gambar.",
        );
      }
    }
  }

  // ==========================================
  // INTEGRASI SPATIE ACTIVITY LOG
  // ==========================================

  protected function logActivity($eventName, $model, $description)
  {
    // Menyuntikkan log menggunakan Spatie Activitylog helper
    activity("media_manager")
      ->performedOn($model)
      ->causedBy(auth()->user())
      ->event($eventName)
      ->log($description);
  }


  public function saveUploads()
  {
    if (empty($this->uploadFiles)) {
        return;
    }
    // 🌟 Atur aturan dan pesan error secara dinamis
    // $mimes = 'file|mimes:jpg,jpeg,png,webp,gif,svg,pdf|max:25600';
    // $errorMessage = 'Format ditolak: Hanya menerima Gambar atau PDF.';

    // if ($this->allowedFileType === 'image') {
    //     $mimes = 'file|mimes:jpg,jpeg,png,webp,gif,svg|max:15360';
    //     $errorMessage = 'Format ditolak: Form ini HANYA menerima Gambar.';
    // } elseif ($this->allowedFileType === 'pdf') {
    //     $mimes = 'file|mimes:pdf|max:15360';
    //     $errorMessage = 'Format ditolak: Form ini HANYA menerima PDF.';
    // }

    // $validator = \Illuminate\Support\Facades\Validator::make(
    //     ['uploadFiles' => $this->uploadFiles],
    //     ['uploadFiles.*' => 'file|mimes:jpg,jpeg,png,webp,gif,svg,pdf|max:15360'],
    //     [
    //         'uploadFiles.*.mimes' => 'Format ditolak: Hanya menerima Gambar atau PDF.',
    //         'uploadFiles.*.max' => 'Ukuran ditolak: Maksimal 15MB per berkas.',
    //     ]
    // );
    // 🌟 Atur aturan dan pesan error secara dinamis
    $mimes = 'file|mimes:jpg,jpeg,png,webp,gif,svg,pdf|max:25600'; // 25 MB
    $errorMessage = 'Format ditolak: Hanya menerima Gambar atau PDF.';

    if ($this->allowedFileType === 'image') {
        $mimes = 'file|mimes:jpg,jpeg,png,webp,gif,svg|max:25600';
        $errorMessage = 'Format ditolak: Form ini HANYA menerima Gambar.';
    } elseif ($this->allowedFileType === 'pdf') {
        $mimes = 'file|mimes:pdf|max:25600';
        $errorMessage = 'Format ditolak: Form ini HANYA menerima PDF.';
    }

    $validator = \Illuminate\Support\Facades\Validator::make(
        ['uploadFiles' => $this->uploadFiles],
        // 🌟 PERBAIKAN: Gunakan variabel $mimes di sini, JANGAN di-hardcode!
        ['uploadFiles.*' => $mimes],
        [
            'uploadFiles.*.mimes' => $errorMessage,
            'uploadFiles.*.max' => 'Ukuran ditolak: Maksimal 25MB per berkas.',
        ]
    );

    if ($validator->fails()) {
        $this->uploadFiles = []; // Hapus file dari memori sementara
        $this->notify($validator->errors()->first(), 'error');
        return;
    }

    $count = 0;
    foreach ($this->uploadFiles as $file) {
      $path = $file->store("media/" . date("Y/m"), "public");

      $media = Media::create([
        "folder_id" => $this->currentFolderId,
        "disk" => "public",
        "path" => $path,
        "original_name" => $file->getClientOriginalName(),
        "mime_type" => $file->getMimeType(),
        "size" => $file->getSize(),
        "uploaded_by" => auth()->id(),
      ]);

      $this->logActivity(
        "created",
        $media,
        "Mengunggah file baru: {$media->original_name}",
      );
      $count++;
    }

    $this->uploadFiles = [];
    $this->notify("$count berkas berhasil diunggah!", "success");
  }

  public function createFolder()
  {
    $this->validate(["newFolderName" => "required|string|max:40"]);

    if ($this->currentDepth >= 3) {
      $this->notify(
        __("Tidak bisa membuat folder lebih dalam dari 3 tingkat."),
        "warning",
      );
      return;
    }

    $folder = MediaFolder::create([
      "name" => $this->newFolderName,
      "parent_id" => $this->currentFolderId,
    ]);

    $this->logActivity(
      "created",
      $folder,
      "Membuat direktori folder baru: {$folder->name}",
    );

    $this->newFolderName = "";
    $this->notify(__("Folder Dibuat"), "success");
  }

  public function deleteMedia($mediaId)
  {
    $media = Media::find($mediaId);
    if (!$media) {
      return;
    }

    if ($media->isInUse()) {
      $this->notify(__("Media sedang digunakan pada halaman aktif!"), "error");
      return;
    }

    $this->logActivity(
      "deleted",
      $media,
      "Menghapus file secara permanen: {$media->original_name}",
    );

    $media->deleteWithFile();
    $this->closeInfoPanel();
    $this->notify(__("Terhapus"), "success");
  }

  public function bulkDelete()
  {
    $medias = Media::whereIn("id", $this->selectedMedia)->get();
    $deletedCount = 0;
    $inUseCount = 0;

    foreach ($medias as $media) {
      if ($media->isInUse()) {
        $inUseCount++;
      } else {
        $this->logActivity(
          "deleted",
          $media,
          "Menghapus file massal: {$media->original_name}",
        );
        $media->deleteWithFile();
        $deletedCount++;
      }
    }

    $this->selectedMedia = [];
    if ($inUseCount > 0) {
      $this->notify(
        "$deletedCount terhapus. $inUseCount DILEWATI karena dipakai artikel.",
        "success",
      );
    } else {
      $this->notify("$deletedCount file terhapus.", "success");
    }
  }

  public function renameMedia($mediaId, $newName)
  {
    $media = Media::find($mediaId);
    if ($media && !empty(trim($newName))) {
      $oldName = $media->original_name;
      $media->update(["original_name" => trim($newName)]);

      $this->logActivity(
        "updated",
        $media,
        "Mengubah nama file dari '{$oldName}' menjadi '{$newName}'",
      );
      $this->notify("Nama diubah!", "success");
    }
  }

  // ==========================================
  // LOGIKA PEMINDAHAN FOLDER
  // ==========================================

  public function openMovePanel($mediaId = null)
  {
    $this->movingMediaId = $mediaId;

    if ($mediaId) {
      $media = Media::find($mediaId);
      $this->movingToFolderId = $media ? $media->folder_id ?? "root" : "root";
    } else {
      $this->movingToFolderId = $this->currentFolderId ?? "root";
    }

    $this->isMovePanelOpen = true;
  }

  public function closeMovePanel()
  {
    $this->movingMediaId = null;
    $this->movingToFolderId = "root";
    $this->isMovePanelOpen = false;
  }

  // public function updatedSelectedMedia($value)
  // {
  //   if (count($value) > 0) {
  //     $this->isInfoPanelOpen = true;
  //     $this->selectedItemType = "media";
  //     // Set ID pertama sebagai referensi jika hanya 1 yang dipilih
  //     $this->selectedItemId = count($value) === 1 ? $value[0] : null;
  //   } else {
  //     if ($this->selectedItemType === "media") {
  //       $this->closeInfoPanel();
  //     }
  //   }
  // }
  public function updatedSelectedMedia($value)
  {
    if (count($value) > 0) {
      // $this->isInfoPanelOpen = true;
      $this->selectedItemType = "media";

      if (count($value) === 1) {
        // Set ID pertama sebagai referensi dan tarik Alt Text
        $this->selectedItemId = $value[0];
        $media = Media::find($value[0]);
        $this->activeAltText = $media ? $media->alt_text ?? "" : "";
      } else {
        $this->selectedItemId = null;
        $this->activeAltText = "";
      }
      $this->dispatch('item-selected');
    } else {
      if ($this->selectedItemType === "media") {
        $this->closeInfoPanel();
      }
    }
  }

  // NEW
  // 🌟 3. Fungsi pencari jalur lengkap (Breadcrumbs)
  public function getFullPath($folderId)
  {
    if (!$folderId) {
      return "Folder Utama";
    }
    $path = [];
    $current = MediaFolder::find($folderId);
    while ($current) {
      array_unshift($path, $current->name);
      $current = MediaFolder::find($current->parent_id);
    }
    return "Folder Utama / " . implode(" / ", $path);
  }

  // 🌟 4. Perbarui data Computed Panel Informasi
  #[Computed]
  public function selectedDetails()
  {
    $mediaCount = count($this->selectedMedia);

    // Jika lebih dari 1 file dipilih
    if ($mediaCount > 1) {
      return [
        "type" => "multiple",
        "count" => $mediaCount,
        "name" => $mediaCount . " File Dipilih",
      ];
    }

    // Jika persis 1 file dipilih (paksa ambil data)
    if ($mediaCount === 1) {
      $media = Media::with("uploader")->find($this->selectedMedia[0]);
      if ($media) {
        return [
          "id" => $media->id,
          "type" => "media",
          "is_image" => $media->isImage(),
          "url" => $media->url(),
          "name" => $media->original_name,
          "path" => $this->getFullPath($media->folder_id), // Jalur lengkap
          "size" => round($media->size / 1024) . " KB",
          "dimensions" => $media->isImage() ? "Otomatis" : "-",
          "created_at" => $media->created_at->translatedFormat("d M Y"),
          "uploader" => $media->uploader
            ? $media->uploader->name
            : "Administrator",
          "is_used" => $media->isInUse(),
        ];
      }
    }

    // Jika folder yang diklik
    if ($this->selectedItemId && $this->selectedItemType === "folder") {
      $folder = MediaFolder::find($this->selectedItemId);
      if ($folder) {
        return [
          "id" => $folder->id,
          "type" => "folder",
          "name" => $folder->name,
          "path" => $this->getFullPath($folder->parent_id), // Jalur lengkap
          "size" => "-",
          "created_at" => $folder->created_at->translatedFormat("d M Y"),
          "uploader" => "Sistem",
          "count" => $folder->media()->count() . " File",
          "is_used" => false,
        ];
      }
    }

    return null;
  }
  //end of new

  public function executeMove()
  {
    $targetId =
      $this->movingToFolderId === "root" || $this->movingToFolderId === ""
        ? null
        : $this->movingToFolderId;

    if ($this->movingMediaId) {
      $media = Media::find($this->movingMediaId);
      if ($media) {
        $media->update(["folder_id" => $targetId]);
        $this->logActivity(
          "moved",
          $media,
          "Memindahkan file {$media->original_name} ke folder lain.",
        );
      }
    } elseif (!empty($this->selectedMedia)) {
      $medias = Media::whereIn("id", $this->selectedMedia)->get();
      foreach ($medias as $media) {
        $media->update(["folder_id" => $targetId]);
        $this->logActivity(
          "moved",
          $media,
          "Memindahkan file massal: {$media->original_name}.",
        );
      }
    }

    $this->notify(__("Berhasil dipindahkan!"), "success");

    $this->closeMovePanel();
    $this->selectedMedia = [];
  }

  // ==========================================
  // DATA BACA (COMPUTED)
  // ==========================================

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
    if (!$this->currentFolderId) {
      return 0;
    }
    return count($this->activePathIds);
  }

  #[Computed]
  public function flatFolders()
  {
    $result = [["id" => "root", "name" => "🏠 Root (Semua Media)"]];
    foreach ($this->rootFolders as $f) {
      $result[] = ["id" => $f->id, "name" => "📁 " . $f->name];
      foreach ($f->children as $c) {
        $result[] = ["id" => $c->id, "name" => "— 📁 " . $c->name];
        foreach ($c->children as $cc) {
          $result[] = ["id" => $cc->id, "name" => "—— 📁 " . $cc->name];
        }
      }
    }
    return $result;
  }

  // #[Computed]
  // public function folders()
  // {
  //   return MediaFolder::where("parent_id", $this->currentFolderId)
  //     ->orderBy("name")
  //     ->get();
  // }

  // #[Computed]
  // public function mediaItems()
  // {
  //   return Media::where("folder_id", $this->currentFolderId)
  //     ->when(
  //       $this->search,
  //       fn($q) => $q->where("original_name", "like", "%{$this->search}%"),
  //     )
  //     ->latest()
  //     ->paginate(40); // 🌟 3. Ubah ->get() menjadi ->paginate()
  // }
  #[Computed]
  public function folders()
  {
    return MediaFolder::query()
      ->when(
        $this->search,
        fn($q) => $q->where("name", "like", "%{$this->search}%"), // Pencarian Global
        fn($q) => $q->where("parent_id", $this->currentFolderId) // Jika kosong, tampilkan folder aktif
      )
      ->orderBy("name")
      ->get();
  }

  #[Computed]
  public function mediaItems()
  {
    return Media::query()
      ->when(
        $this->search,
        fn($q) => $q->where("original_name", "like", "%{$this->search}%"), // Pencarian Global
        fn($q) => $q->where("folder_id", $this->currentFolderId) // Jika kosong, tampilkan file aktif
      )
      ->when($this->allowedFileType === 'image', fn($q) => $q->where('mime_type', 'like', 'image/%'))
      ->when($this->allowedFileType === 'pdf', fn($q) => $q->where('mime_type', 'application/pdf'))
      ->latest()
      ->paginate(40);
  }

  #[Computed]
  public function currentFolder()
  {
    return $this->currentFolderId
      ? MediaFolder::find($this->currentFolderId)
      : null;
  }

  #[Computed]
  public function rootFolders()
  {
    return MediaFolder::with("children.children")
      ->whereNull("parent_id")
      ->orderBy("name")
      ->get();
  }

};
?>

{{-- ========================================================== --}}
{{-- 🌟 ROOT ELEMENT UTAMA 🌟 --}}
{{-- ========================================================== --}}
<x-slot:title>
  File Manager
</x-slot:title>

<div
  class="{{ $isModal ? '' : 'h-[calc(100vh-4rem)] min-h-[600px] flex flex-col' }}"
>
  {{-- BUNGKUSAN DINAMIS: Terlindungi x-data untuk Morphdom --}}
  <div
    wire:key="fm-main-wrapper"
    x-data="{
      isOpen: {{ $isModal ? 'false' : 'true' }},
      isInfoOpen: false
    }"
    @item-selected.window="if (window.innerWidth >= 768) { setTimeout(() => { isInfoOpen = true }, 50); }"
    @close-info-panel.window="isInfoOpen = false"
    class="{{ $isModal ? 'fixed inset-0 z-[100] flex items-center justify-center' : 'relative w-full h-full flex flex-col' }}"



    @if ($isModal)
      x-on:show-file-manager-modal.window="isOpen = true"
      x-on:hide-file-manager-modal.window="isOpen = false"
      x-show="isOpen"
      x-cloak
    @endif
  >
    <!-- BACKDROP GELAP (Hanya Modal) -->
    @if ($isModal)
      <div
        wire:key="fm-backdrop"
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        x-on:click="
          isOpen = false;
          $dispatch('hide-file-manager-modal');
        "
      ></div>
    @endif

    <div
      class="{{ $isModal ?
      'relative z-10 flex h-[85vh] w-[90vw] max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl'
      :
      'relative flex flex-1 w-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-200' }}"
    >
      @if ($isModal)
        <!-- HEADER -->
        <div
          class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-gray-100 px-6 py-4"
        >
          <div class="flex items-center gap-3 ">
            <x-dynamic-component
              component="lucide-folder-open"
              class="text-foresty h-6 w-6"
              stroke-width="2.5"
            />
            <h2 class="text-lg font-bold text-gray-800">Media Manager</h2>
          </div>
          @if ($isModal)
            <button
              wire:key="fm-close-btn"
              type="button"
              x-on:click="
                isOpen = false;
                $dispatch('hide-file-manager-modal');
              "
              class="cursor-pointer rounded-full p-2 text-gray-400 outline-none hover:bg-gray-200 hover:text-gray-600"
            >
              <x-dynamic-component component="lucide-x" class="h-5 w-5" />
            </button>
          @endif
        </div>
      @endif

      <div class="flex min-h-0 flex-1">
        <!-- KOLOM KIRI: Pohon Folder -->
        <div
          class="hidden w-64 shrink-0 scrollbar-thin flex-col overflow-y-auto border-r border-gray-200 bg-gray-50 p-4 md:flex"
        >
          <button
            wire:click="openFolder(null)"
            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold hover:bg-gray-200 transition-colors outline-none cursor-pointer {{ !$this->currentFolderId ? 'bg-sage-soft text-foresty' : 'text-gray-700' }}"
          >
            <x-dynamic-component component="lucide-home" class="h-4 w-4" />
            Folder Utama
          </button>
          <div class="my-4 border-t border-gray-200"></div>
          <span
            class="mb-2 block px-3 text-xs font-semibold tracking-widest text-gray-400 uppercase"
            >Struktur Direktori</span
          >

          <div class="space-y-0.5">
            @foreach ($this->rootFolders as $rf)
              <div
                wire:key="tree-root-{{ $rf->id }}"
                x-data="{ expanded: {{ in_array($rf->id, $this->activePathIds) ? 'true' : 'false' }} }"
                @folder-navigated.window="if (Object.values($event.detail.path || {}).includes({{ $rf->id }})) expanded = true"
              >
                <div class="group flex items-center">
                  <button
                    type="button"
                    @click="expanded = !expanded"
                    class="hover:text-foresty cursor-pointer p-1 text-gray-400 outline-none"
                    x-show="{{ $rf->children->count() ? 'true' : 'false' }}"
                  >
                    <!-- 🌟 x-bind:class -->
                    <x-dynamic-component
                      component="lucide-chevron-right"
                      class="h-3 w-3 transition-transform duration-200"
                      x-bind:class="{ 'rotate-90': expanded }"
                    />
                  </button>
                  <div
                    class="p-1"
                    x-show="{{ $rf->children->count() ? 'false' : 'true' }}"
                  >
                    <div class="w-3"></div>
                  </div>

                  <button
                    wire:click="openFolder({{ $rf->id }})"
                    class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-semibold hover:bg-gray-200 text-left transition-colors outline-none cursor-pointer {{ $this->currentFolderId === $rf->id ? 'bg-sage-soft text-foresty' : 'text-gray-600' }}"
                  >
                    <x-dynamic-component
                      component="lucide-folder"
                      class="h-3.5 w-3.5 shrink-0"
                    />
                    <span
                      class="truncate"
                      title="{{ $rf->name }}"
                      >{{ $rf->name }}</span
                    >
                  </button>
                </div>

                <!-- Level 2 -->
                <div
                  x-show="expanded"
                  x-collapse
                  class="mt-0.5 ml-2 space-y-0.5 border-l border-gray-200 pl-4"
                  x-cloak
                >
                  @foreach ($rf->children as $child)
                    <div
                      wire:key="tree-child-{{ $child->id }}"
                      x-data="{ expanded2: {{ in_array($child->id, $this->activePathIds) ? 'true' : 'false' }} }"
                      @folder-navigated.window="if (Object.values($event.detail.path || {}).includes({{ $child->id }})) expanded2 = true"
                    >
                      <div class="group flex items-center">
                        <button
                          type="button"
                          @click="expanded2 = !expanded2"
                          class="hover:text-foresty cursor-pointer p-1 text-gray-400 outline-none"
                          x-show="{{ $child->children->count() ? 'true' : 'false' }}"
                        >
                          <!-- 🌟 x-bind:class -->
                          <x-dynamic-component
                            component="lucide-chevron-right"
                            class="h-3 w-3 transition-transform duration-200"
                            x-bind:class="{ 'rotate-90': expanded2 }"
                          />
                        </button>
                        <div
                          class="p-1"
                          x-show="{{ $child->children->count() ? 'false' : 'true' }}"
                        >
                          <div class="w-3"></div>
                        </div>

                        <button
                          wire:click="openFolder({{ $child->id }})"
                          class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium hover:bg-gray-200 text-left transition-colors outline-none cursor-pointer {{ $this->currentFolderId === $child->id ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-600' }}"
                        >
                          <x-dynamic-component
                            component="lucide-folder"
                            class="h-3.5 w-3.5 shrink-0 {{ $this->currentFolderId === $child->id ? 'text-foresty' : 'text-gray-400' }}"
                          />
                          <span
                            class="truncate"
                            title="{{ $child->name }}"
                            >{{ $child->name }}</span
                          >
                        </button>
                      </div>

                      <!-- Level 3 -->
                      <div
                        x-show="expanded2"
                        x-collapse
                        class="mt-0.5 ml-2 space-y-0.5 border-l border-gray-200 pl-4"
                        x-cloak
                      >
                        @foreach ($child->children as $subchild)
                          <div
                            wire:key="tree-sub-{{ $subchild->id }}"
                            class="group flex items-center"
                          >
                            <div class="p-1"><div class="w-3"></div></div>
                            <button
                              wire:click="openFolder({{ $subchild->id }})"
                              class="flex-1 flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-medium hover:bg-gray-200 text-left transition-colors outline-none cursor-pointer {{ $this->currentFolderId === $subchild->id ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-500' }}"
                            >
                              <x-dynamic-component
                                component="lucide-folder"
                                class="h-3 w-3 shrink-0 {{ $this->currentFolderId === $subchild->id ? 'text-foresty' : 'text-gray-300' }}"
                              />
                              <span
                                class="truncate"
                                title="{{ $subchild->name }}"
                                >{{ $subchild->name }}</span
                              >
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
        <div
          x-data="{
            isDragging: false,
            isUploading: false,
            selected: @entangle('selectedMedia').live,
            lastClicked: null,

          handleSelect(e, id, isCheckbox = false) {
        id = String(id);
        let items = Array.from(document.querySelectorAll('.media-card')).map(el => el.dataset.id);

        // 1. SHIFT + KLIK (Seleksi Rentang)
        if (e.shiftKey && this.lastClicked) {
            window.getSelection().removeAllRanges();
            let start = items.indexOf(this.lastClicked);
            let end = items.indexOf(id);

            if (start !== -1 && end !== -1) {
                let min = Math.min(start, end);
                let max = Math.max(start, end);
                let range = items.slice(min, max + 1);

                let isSelecting = !this.selected.includes(id);
                let newSelected = new Set(this.selected);

                range.forEach(i => isSelecting ? newSelected.add(i) : newSelected.delete(i));
                this.selected = Array.from(newSelected);
            }
        }
        // 2. CTRL/CMD + KLIK -ATAU- KLIK TEPAT DI CHECKBOX (Seleksi Jamak / Toggle)
        else if (e.ctrlKey || e.metaKey || isCheckbox) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(i => i !== id); // Hapus dari pilihan
            } else {
                this.selected.push(id); // Tambah ke pilihan
            }
        }
        // 3. KLIK BIASA PADA KARTU (Seleksi Tunggal / Reset)
        else {
            this.selected = [id]; // Timpa semua pilihan sebelumnya dengan file ini saja
        }

        this.lastClicked = id;
    },

    // 🌟 FUNGSI SENTRAL UPLOAD DENGAN VALIDASI KLIEN
        prosesUnggahan(files) {
            if (!files || files.length === 0) return;

            let validFiles = [];
            let maxSize = 25 * 1024 * 1024; // Batas 15 MB dalam Bytes

            // Loop untuk memvalidasi setiap file satu per satu
            for (let i = 0; i < files.length; i++) {
                let file = files[i];

                // 1. Validasi Ukuran (Langsung tolak jika kebesaran)
                if (file.size > maxSize) {
                    $wire.notify(`Ditolak: Ukuran '${file.name}' terlalu besar (Maks 15MB).`, 'error');
                    continue; // Lewati file ini
                }

                // 2. Validasi Ekstensi/Tipe (Pengamanan tambahan untuk Drag & Drop)
                let allowed = $wire.allowedFileType;
                if (allowed === 'image' && !file.type.startsWith('image/')) {
                    $wire.notify(`Ditolak: Form ini hanya menerima Gambar.`, 'error');
                    continue;
                }
                if (allowed === 'pdf' && file.type !== 'application/pdf') {
                    $wire.notify(`Ditolak: Form ini hanya menerima PDF.`, 'error');
                    continue;
                }

                // Jika lolos semua validasi, masukkan ke antrean
                validFiles.push(file);
            }

            // Jika tidak ada file yang valid setelah disaring, hentikan proses!
            if (validFiles.length === 0) return;

            // Mulai animasi loading
            this.isUploading = true;

            // Hanya unggah file yang sudah teruji valid!
            $wire.uploadMultiple(
                'uploadFiles',
                validFiles,
                async () => {
                    await $wire.saveUploads();
                    this.isUploading = false;
                },
                () => {
                    this.isUploading = false;
                    $wire.notify('Koneksi terputus atau ditolak oleh server.', 'error');
                }
            );
        },


            handlePaste(e) {
              if (e.target.tagName === 'INPUT' && e.target.type === 'text') return;

              let items = (e.clipboardData || window.clipboardData).items;
              let files = [];

              for (let i = 0; i < items.length; i++) {
                let type = items[i].type;
                if (items[i].kind === 'file' && (type.startsWith('image/') || type === 'application/pdf')) {
                  files.push(items[i].getAsFile());
                } else if (items[i].kind === 'file') {
                  $wire.notify('Format diabaikan: Hanya mendukung Gambar dan PDF.', 'warning');
                }
              }

              if (files.length > 0) {
                this.prosesUnggahan(files);

                //$wire.uploadMultiple(
                //  'uploadFiles', files,
                //  () => { isUploading = false; $wire.saveUploads(); },
                //  () => { isUploading = false; $wire.notify('Unggahan gagal. Periksa koneksi atau pastikan ukuran tak melebihi batas server.', 'error'); }
                //);
              }
            },

            handleSelectAll(e) {
              if (
                !['INPUT', 'TEXTAREA'].includes(e.target.tagName) &&
                (e.ctrlKey || e.metaKey) &&
                e.key.toLowerCase() === 'a'
              ) {
                e.preventDefault();
                // Mengambil semua ID dari kartu media di layar
                this.selected = Array.from(document.querySelectorAll('.media-card')).map((el) => el.dataset.id);
              }
            }
          }"
          x-on:dragover.prevent="isDragging = true"
          x-on:dragleave.prevent="isDragging = false"

          x-on:drop.prevent="
            isDragging = false;prosesUnggahan($event.dataTransfer.files);
            //if ($event.dataTransfer.files.length > 0) {
            //  isUploading = true;
            //  $wire.uploadMultiple(
            //    'uploadFiles',
            //    $event.dataTransfer.files,
            //    () => { $wire.saveUploads(); },
            //    () => { $wire.notify('Unggahan terputus. Pastikan berkas adalah file (bukan folder) dan ukurannya wajar.', 'error'); }
            //  );
            //}
          "

          x-on:paste.window="handlePaste($event)"
          @keydown.window="handleSelectAll($event)"
          class="relative flex min-w-0 flex-1 flex-col bg-white"
        >
          <!-- Drag & Drop Overlay -->
          <div
            wire:key="upload-overlay"
            x-show="isDragging"
            x-transition.opacity.duration.300ms
            x-cloak
            class="border-foresty pointer-events-none absolute inset-0 z-50 m-4 flex items-center justify-center rounded-2xl border-4 border-dashed bg-emerald-50/90 backdrop-blur-sm"
          >
            <div
              class="flex flex-col items-center rounded-2xl bg-white px-8 py-6 shadow-xl"
            >
              <x-dynamic-component
                component="lucide-upload-cloud"
                class="text-foresty mb-3 h-14 w-14 animate-bounce"
                stroke-width="2"
              />
              <span class="text-xl font-extrabold text-gray-800"
                >Lepaskan File di Sini</span
              >
              <span
                class="bg-sage-soft text-foresty mt-2 rounded px-2 py-1 text-xs font-bold tracking-wider uppercase"
              >
                Unggah ke:
                <span
                  x-text="
                    $wire.currentFolderId ? 'Folder Aktif' : 'Semua Media'
                  "
                ></span>
              </span>
            </div>
          </div>

          <!-- Toolbar Atas Grid -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3"
          >
            <div wire:key="toolbar-left" class="flex items-center gap-2">
              @if ($this->currentFolder)
                <div
                  wire:key="toolbar-has-folder"
                  class="flex items-center gap-2"
                >
                  <button
                    wire:click="openFolder({{ $this->currentFolder->parent_id ? $this->currentFolder->parent_id : 'null' }})"
                    class="cursor-pointer rounded-lg bg-gray-100 p-1.5 text-gray-600 transition-colors outline-none hover:bg-gray-200"
                  >
                    <x-dynamic-component
                      component="lucide-arrow-left"
                      class="h-4 w-4"
                    />
                  </button>
                  <span
                    class="max-w-[150px] truncate text-sm font-bold text-gray-700"
                    >{{
                      $this->currentFolder
                        ->name
                    }}</span
                  >
                </div>
              @else
                <span
                  wire:key="toolbar-root-folder"
                  class="text-sm font-bold text-gray-700"
                  >Semua Media</span
                >
              @endif
            </div>

            <div class="flex items-center gap-3">
              @if ($this->currentDepth < 2)
                <form
                  wire:key="form-create-folder"
                  wire:submit.prevent="createFolder"
                  class="relative hidden items-center sm:flex"
                >
                  <input
                    type="text"
                    wire:model="newFolderName"
                    placeholder="Nama folder baru..."
                    required
                    class="focus:border-sage-soft focus:ring-sage-soft h-8 w-36 rounded-l-lg border-gray-200 pr-2 pl-3 text-xs shadow-sm sm:w-40"
                  />
                  <button
                    type="submit"
                    class="bg-sage-soft text-foresty hover:bg-foresty h-8 cursor-pointer rounded-r-lg border border-l-0 border-gray-200 px-2 shadow-sm transition-colors hover:text-white"
                  >
                    <x-dynamic-component
                      component="lucide-plus"
                      class="h-4 w-4"
                    />
                  </button>
                </form>
              @endif

              <div class="relative">
                <x-dynamic-component
                  component="lucide-search"
                  class="absolute top-2.5 left-3 h-4 w-4 text-gray-400"
                />
                <input
                  type="text"
                  wire:model.live.debounce.500ms="search"
                  placeholder="Cari media..."
                  class="focus:border-sage-soft focus:ring-sage-soft w-full rounded-lg border-gray-200 h-8 pl-9 text-xs shadow-sm sm:w-48"
                />
              </div>

              <label
                class="bg-foresty relative flex cursor-pointer items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-800"
              >
                <x-dynamic-component
                  component="lucide-upload-cloud"
                  class="h-4 w-4 shrink-0"
                />
                <span class="hidden sm:inline">Unggah</span>
                <input
                  type="file"
                  multiple
                  class="hidden"
                  {{-- accept="image/*,application/pdf" --}}
                  x-bind:accept="
                    $wire.allowedFileType === 'image' ? 'image/*' :
                    ($wire.allowedFileType === 'pdf' ? 'application/pdf' : 'image/*,application/pdf')
                  "
                  x-on:change="
                    prosesUnggahan($event.target.files);
                    $event.target.value = '';

                    //if ($event.target.files.length > 0) {
                    //  $wire.uploadMultiple(
                    //    'uploadFiles',
                    //    $event.target.files,
                    //    () => { isUploading = false; $wire.saveUploads(); $event.target.value = ''; },
                    //    () => { isUploading = false; $wire.notify('Gagal mengunggah berkas. Mungkin ukurannya melebihi batas server.', 'error'); $event.target.value = ''; }
                    //  );
                    //}
                  "
                />
              </label>
            </div>
          </div>

          <div
            x-show="isUploading"
            x-transition
            class="fixed bottom-8 right-8 z-[120] flex items-center gap-4 rounded-2xl bg-gray-900/95 px-6 py-4 shadow-2xl backdrop-blur-sm"
            x-cloak
          >
            <x-dynamic-component
              component="lucide-loader-2"
              class="h-6 w-6 animate-spin text-emerald-400"
            />
            <div class="flex flex-col">
              <span class="text-sm font-bold text-white">Mengunggah File...</span>
              <span class="text-xs font-medium text-gray-400">Mohon jangan tutup jendela ini.</span>
            </div>
          </div>

          <!-- FLOATING BULK ACTION BAR -->
          <div
            wire:key="bulk-action-bar"
            x-show="$wire.selectedMedia.length > 0"
            x-cloak
            x-transition.opacity
            class="absolute bottom-6 left-1/2 z-40 flex -translate-x-1/2 items-center gap-4 rounded-full bg-gray-900 px-6 py-3 text-white shadow-2xl"
          >
            <span class="rounded-full bg-white/20 px-3 py-1 text-sm font-bold"
              ><span x-text="$wire.selectedMedia.length"></span> Dipilih</span
            >


            <button
              type="button"
              wire:click="$set('selectedMedia', [])"
              class="ml-2 cursor-pointer rounded-full p-1 transition-colors outline-none hover:bg-gray-700"
            >
              <x-dynamic-component component="lucide-x" class="h-5 w-5" />
            </button>
          </div>

          <!-- Grid Konten -->
          <div
            wire:key="fm-media-scroll-area"
            class="flex-1 scrollbar-thin overflow-y-auto bg-gray-50/50 p-4"
          >
            @if ($this->folders->isEmpty() && $this->mediaItems->isEmpty())
              <div
                wire:key="fm-empty-state"
                class="flex h-full flex-col items-center justify-center text-gray-400"
              >
                <x-dynamic-component
                  component="lucide-folder-open"
                  class="mb-3 h-12 w-12 opacity-20"
                />
                <p class="text-sm font-bold">Folder Kosong</p>
                <p class="text-xs">Seret & lepas, atau <span class="text-foresty font-extrabold">CTRL+V</span> file ke sini untuk mengunggah.</p>
              </div>
            @else
              <div
                wire:key="fm-grid-state"
                class="grid gap-4 pb-20 grid-cols-[repeat(auto-fill,minmax(130px,1fr))] sm:grid-cols-[repeat(auto-fill,minmax(176px,1fr))]"
              >
                <!-- 🌟 RENDER FOLDER (Perilaku Ala Google Drive) -->
                @foreach ($this->folders as $folder)
                  <div wire:key="grid-folder-{{ $folder->id }}"
                       x-data="{ openMenu: false }"
                       class="media-card group hover:border-foresty relative flex flex-col overflow-visible rounded-xl border-2 bg-white shadow-sm transition-all"
                       x-bind:class="$wire.selectedItemId == {{ $folder->id }} && $wire.selectedItemType == 'folder' ? 'border-foresty ring-2 ring-foresty/20' : 'border-gray-200'"
                  >
                      <!-- Klik Area Besar -->
                      <div @click="if (window.innerWidth < 768) { $wire.openFolder({{ $folder->id }}) } else { $wire.selectItem({{ $folder->id }}, 'folder') }"
                           @dblclick="if (window.innerWidth >= 768) { $wire.openFolder({{ $folder->id }}) }"
                           class="cursor-pointer relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-t-xl bg-gray-50">
                          <x-dynamic-component component="lucide-folder" class="text-foresty/40 group-hover:text-foresty h-12 w-12 transition-colors" fill="currentColor" />
                      </div>

                      <div class="flex items-start justify-between gap-1 border-t border-gray-50 p-2">
                          <div class="min-w-0 cursor-pointer flex-1"
                               @click="if (window.innerWidth < 768) { $wire.openFolder({{ $folder->id }}) } else { $wire.selectItem({{ $folder->id }}, 'folder') }"
                               @dblclick="if (window.innerWidth >= 768) { $wire.openFolder({{ $folder->id }}) }">
                              <p class="truncate text-[11px] font-bold text-gray-700" title="{{ $folder->name }}">{{ $folder->name }}</p>
                              <p class="text-[9px] font-semibold text-gray-400">Folder</p>
                          </div>

                          <!-- Tombol Titik Tiga -->
                          <button type="button" @click.prevent.stop="openMenu = !openMenu" class="hover:text-foresty hover:bg-sage-soft shrink-0 cursor-pointer rounded p-1 text-gray-400 transition-colors outline-none">
                              <x-dynamic-component component="lucide-more-vertical" class="h-4 w-4" />
                          </button>
                      </div>

                      <!-- Menu Dropdown Folder -->
                      <div x-show="openMenu" @click.outside="openMenu = false" x-transition x-cloak class="absolute top-auto right-2 bottom-8 z-50 w-36 rounded-lg border border-gray-200 bg-white py-1 shadow-xl">
                          <button type="button" @click="$wire.selectItem({{ $folder->id }}, 'folder'); isInfoOpen = true; openMenu = false" class="hover:text-foresty flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors outline-none hover:bg-gray-100">
                              <x-dynamic-component component="lucide-info" class="h-3.5 w-3.5" /> Lihat Detail
                          </button>
                          <div class="my-1 border-t border-gray-100"></div>
                          <button type="button" @click="let n = prompt('Ganti nama folder:', '{{ addslashes($folder->name) }}'); if(n) { $wire.renameFolder({{ $folder->id }}, n); } openMenu = false;" class="hover:text-foresty flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors outline-none hover:bg-gray-100">
                              <x-dynamic-component component="lucide-pencil" class="h-3.5 w-3.5" /> Ganti Nama
                          </button>
                          <div class="my-1 border-t border-gray-100"></div>
                          <button type="button" wire:click="deleteFolder({{ $folder->id }})" wire:confirm="Hapus folder ini? Pastikan kosong." class="flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-bold text-red-500 transition-colors outline-none hover:bg-red-50 hover:text-red-700">
                              <x-dynamic-component component="lucide-trash-2" class="h-3.5 w-3.5" /> Hapus
                          </button>
                      </div>
                  </div>
                @endforeach

                <!-- RENDER MEDIA -->
                @foreach ($this->mediaItems as $media)
                  <div
                    wire:key="grid-media-{{ $media->id }}"
                    data-id="{{ $media->id }}"
                    x-data="{ openMenu: false }"
                    @click.stop="handleSelect($event, '{{ $media->id }}')"
                    class="media-card group hover:border-foresty relative flex cursor-pointer flex-col overflow-visible rounded-xl border-2 bg-white shadow-sm transition-all"
                    x-bind:class="selected.includes('{{ $media->id }}') ? 'border-foresty ring-2 ring-foresty/20' : 'border-gray-200'"
                  >
                    <div
                    x-on:click="
                          $wire.selectItem({{ $media->id }}, 'media');
                          @if($isModal)
                              if (window.innerWidth < 768) {
                                  setTimeout(() => { isInfoOpen = true }, 50);
                              }
                          @endif
                      "
                      class="relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-t-xl bg-gray-100"
                    >
                      <!-- 🌟 Checkbox (Tombol klik khusus Multi-Select) -->
                      <div
                        class="absolute top-2 left-2 z-20"
                        x-bind:class="
                          selected.length > 0
                            ? 'opacity-100'
                            : 'opacity-0 group-hover:opacity-100 transition-opacity'
                        "
                      >
                        <input
                          type="checkbox"
                          :value="'{{ $media->id }}'"
                          :checked="selected.includes('{{ $media->id }}')"
                          @click.stop="handleSelect($event, '{{ $media->id }}', true)"
                          class="media-checkbox text-foresty focus:ring-foresty h-5 w-5 cursor-pointer rounded border-gray-300 shadow-sm"
                        />
                      </div>

                      <!-- Preview Gambar/Ikon -->
                      @if ($media->isImage())
                        <img
                          wire:key="img-preview-{{ $media->id }}"
                          src="{{ $media->url() }}"
                          alt="{{ $media->original_name }}"
                          draggable="false"
                          class="h-full w-full object-cover select-none"
                        />
                      @else
                        <x-dynamic-component
                          wire:key="icon-preview-{{ $media->id }}"
                          component="lucide-file-text"
                          class="h-10 w-10 text-gray-400"
                        />
                      @endif

                      <!-- Lapisan Tombol (Khusus Modal) -->
                      {{-- @if ($isModal)
                        <div
                          class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
                        >
                          <button
                            type="button"
                            wire:click.stop="selectMedia({{ $media->id }}, '{{ $media->url() }}')"
                            class="bg-foresty cursor-pointer rounded-lg px-4 py-2 text-xxs font-bold text-white shadow-md transition-colors outline-none hover:bg-emerald-700"
                          >
                            Gunakan Berkas
                          </button>
                        </div>
                      @endif --}}
                      @if ($isModal)
                        <!-- 🌟 PERBAIKAN: Gunakan hidden md:flex agar tombol ini Lenyap di HP -->
                        <div class="absolute inset-0 hidden md:flex items-center justify-center bg-black/40 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100">
                          <button type="button" wire:click.stop="selectMedia({{ $media->id }}, '{{ $media->url() }}')" class="bg-foresty cursor-pointer rounded-lg px-4 py-2 text-xs font-bold text-white shadow-md transition-colors outline-none hover:bg-emerald-700">
                            Pilih Berkas
                          </button>
                        </div>
                      @endif
                    </div>

                    <!-- Detail Nama & Ukuran -->
                    <div
                      class="flex items-start justify-between gap-1 border-t border-gray-50 p-2"
                    >
                      <div class="min-w-0">
                        <p
                          class="truncate text-[11px] font-bold text-gray-700"
                          title="{{ $media->original_name }}"
                        >
                          {{ $media->original_name }}
                        </p>
                        <p class="text-[9px] font-semibold text-gray-400">
                          {{
                            round(
                              $media->size / 1024,
                              1,
                            )
                          }} KB
                        </p>
                      </div>

                      <!-- Tombol Titik Tiga (Menu Kebab) -->
                      <button
                        type="button"
                        @click.prevent.stop="openMenu = !openMenu"
                        class="hover:text-foresty hover:bg-sage-soft shrink-0 cursor-pointer rounded p-1 text-gray-400 transition-colors outline-none"
                      >
                        <x-dynamic-component
                          component="lucide-more-vertical"
                          class="h-4 w-4"
                        />
                      </button>
                    </div>

                    <!-- Menu Dropdown -->
                    <div
                      x-show="openMenu"
                      @click.outside="openMenu = false"
                      x-transition
                      x-cloak
                      class="absolute top-auto right-2 bottom-8 z-50 w-36 rounded-lg border border-gray-200 bg-white py-1 shadow-xl"
                    >

                      <!-- 🌟 TOMBOL GUNAKAN DI MENU KEBAB (Khusus Modal) -->
                      @if ($isModal)
                        <button
                          type="button"
                          wire:click="selectMedia({{ $media->id }}, '{{ $media->url() }}')"
                          class="text-foresty flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-bold transition-colors outline-none hover:bg-emerald-50"
                        >
                          <x-dynamic-component component="lucide-check-circle" class="h-3.5 w-3.5" />
                          Gunakan
                        </button>
                        <div class="my-1 border-t border-gray-100"></div>
                      @endif
                      <!-- 🌟 TOMBOL LIHAT DETAIL (Hanya terlihat di Mobile) -->
                      <button
                        type="button"
                        @click="
                          isInfoOpen = true;
                          openMenu = false;
                        "
                        class="hover:text-foresty flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors outline-none hover:bg-gray-100 md:hidden"
                      >
                        <x-dynamic-component
                          component="lucide-info"
                          class="h-3.5 w-3.5"
                        />
                        Lihat Detail
                      </button>
                      <div
                        class="my-1 border-t border-gray-100 md:hidden"
                      ></div>
                      <button
                        type="button"
                        x-on:click="let n = prompt('Ganti nama file:', '{{ addslashes($media->original_name) }}'); if(n) { $wire.renameMedia({{ $media->id }}, n); } openMenu = false;"
                        class="hover:text-foresty flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors outline-none hover:bg-gray-100"
                      >
                        <x-dynamic-component
                          component="lucide-pencil"
                          class="h-3.5 w-3.5"
                        />
                        Ganti Nama
                      </button>
                      <button
                        type="button"
                        wire:click="openMovePanel({{ $media->id }})"
                        x-on:click="openMenu = false"
                        class="flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors outline-none hover:bg-gray-100 hover:text-blue-600"
                      >
                        <x-dynamic-component
                          component="lucide-folder-symlink"
                          class="h-3.5 w-3.5"
                        />
                        Pindah Folder
                      </button>
                      <div class="my-1 border-t border-gray-100"></div>
                      <button
                        type="button"
                        wire:click="deleteMedia({{ $media->id }})"
                        wire:confirm="Hapus gambar ini secara permanen?"
                        class="flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-xs font-bold text-red-500 transition-colors outline-none hover:bg-red-50 hover:text-red-700"
                      >
                        <x-dynamic-component
                          component="lucide-trash-2"
                          class="h-3.5 w-3.5"
                        />
                        Hapus
                      </button>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- 🌟 KOLOM KANAN: PANEL INFORMASI (Terintegrasi Sejajar) 🌟 -->
        <!-- ======================================================== -->

        <!-- Backdrop Khusus Mobile (Menghilang di PC) -->

        <div
          x-show="isInfoOpen"
          x-transition.opacity
          x-on:click="isInfoOpen = false; setTimeout(() => { $wire.closeInfoPanel() }, 300)"
          class="absolute inset-0 z-[60] cursor-pointer bg-black/40 md:hidden"
          x-cloak
        ></div>

        <!-- Panel Info: Laci di Bawah (Mobile) & Kolom Statis di Kanan (PC) -->
        <!-- 🌟 PERBAIKAN 2: Hapus transition-transform agar w-0 ke w-80 bisa berjalan mulus -->
        <div
          x-show="isInfoOpen"
          class="absolute inset-x-0 bottom-0 z-[70] flex h-[85vh] flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl md:relative md:inset-auto md:z-auto md:h-auto md:shrink-0 md:rounded-none md:border-l md:border-gray-200"
          x-transition:enter="transition-all ease-out duration-300"
          x-transition:enter-start="translate-y-full md:translate-y-0 md:w-0 md:opacity-0"
          x-transition:enter-end="translate-y-0 md:w-80 md:opacity-100"
          x-transition:leave="transition-all ease-in duration-200"
          x-transition:leave-start="translate-y-0 md:w-80 md:opacity-100"
          x-transition:leave-end="translate-y-full md:translate-y-0 md:w-0 md:opacity-0"
          x-cloak
        >
          @if ($this->selectedDetails)
            <!-- Pembungkus Lebar Tetap agar konten tidak menyusut jelek saat dianimasikan -->
            <div class="flex h-full w-full flex-col md:w-80">
              <!-- 🌟 PERBAIKAN 3: KONDISI FILE JAMAK (MULTIPLE) DIKEMBALIKAN -->
              @if ($this->selectedDetails["type"] === "multiple")
                <div
                  class="flex flex-1 flex-col items-center justify-center p-6 text-center"
                >

                  <button
                    type="button"
                    x-on:click="isInfoOpen = false; setTimeout(() => { $wire.closeInfoPanel() }, 300)"
                    class="absolute top-4 right-4 z-10 cursor-pointer rounded-full bg-gray-100 p-1.5 text-gray-500 shadow-sm outline-none hover:text-gray-800"
                  >
                    <x-dynamic-component component="lucide-x" class="h-5 w-5" />
                  </button>

                  <div class="relative mb-6 h-24 w-24">
                    <div
                      class="bg-sage-soft absolute inset-0 scale-105 -rotate-6 rounded-2xl opacity-50"
                    ></div>
                    <div
                      class="absolute inset-0 scale-95 rotate-3 rounded-2xl bg-emerald-100 opacity-75"
                    ></div>
                    <div
                      class="bg-foresty absolute inset-0 flex items-center justify-center rounded-2xl shadow-lg"
                    >
                      <span class="text-3xl font-black text-white">{{
                        $this->selectedDetails[
                          "count"
                        ]
                      }}</span>
                    </div>
                  </div>
                  <h2 class="mb-2 text-xl font-bold text-gray-800">
                    Item Dipilih
                  </h2>
                  <p class="mb-8 text-sm text-gray-500">Pilih aksi massal untuk file-file ini.</p>

                  <div class="mt-auto w-full space-y-3">
                    <button
                      type="button"
                      wire:click="openMovePanel"
                      class="bg-sage-soft/40 text-foresty hover:bg-sage-soft flex w-full cursor-pointer items-center justify-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition-colors outline-none"
                    >
                      <x-dynamic-component
                        component="lucide-folder-symlink"
                        class="h-4 w-4"
                      />
                      Pindahkan Semua
                    </button>
                    <button
                      type="button"
                      wire:click="bulkDelete"
                      wire:confirm="Hapus {{ $this->selectedDetails['count'] }} file secara permanen?"
                      class="flex w-full cursor-pointer items-center justify-center gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-600 transition-colors outline-none hover:bg-red-100"
                    >
                      <x-dynamic-component
                        component="lucide-trash-2"
                        class="h-4 w-4"
                      />
                      Hapus Semua
                    </button>
                  </div>
                </div>

                <!-- 🌟 KONDISI FILE TUNGGAL & FOLDER -->
              @else
                <!-- HEADER PREVIEW -->
                <div
                  class="relative flex shrink-0 items-center justify-center border-b border-gray-200 bg-gray-50 p-4"
                >
                  <button
                    type="button"
                    x-on:click="isInfoOpen = false; setTimeout(() => { $wire.closeInfoPanel() }, 300)"
                    class="absolute top-4 right-4 z-10 cursor-pointer rounded-full bg-white/80 p-1.5 text-gray-500 shadow-sm backdrop-blur-sm outline-none hover:text-gray-800"
                  >
                    <x-dynamic-component component="lucide-x" class="h-5 w-5" />
                  </button>

                  @if ($this->selectedDetails["type"] === "media" &&
                    $this->selectedDetails["is_image"])
                    <img
                      src="{{ $this->selectedDetails['url'] }}"
                      class="h-48 w-full rounded-xl object-cover shadow-sm"
                      alt="Preview"
                    />
                  @elseif ($this->selectedDetails["type"] === "folder")
                    <x-dynamic-component
                      component="lucide-folder"
                      class="text-foresty/40 my-8 h-24 w-24"
                    />
                  @else
                    <x-dynamic-component
                      component="lucide-file-text"
                      class="my-8 h-24 w-24 text-gray-400"
                    />
                  @endif
                </div>

                <!-- DETAIL METADATA -->
                <div class="flex-1 scrollbar-thin overflow-y-auto p-5">
                  <h2
                    class="mb-1 text-xl leading-tight font-bold break-words text-gray-800"
                  >
                    {{
                      $this->selectedDetails[
                        "name"
                      ]
                    }}
                  </h2>
                  <p class="mb-4 text-xs font-semibold text-gray-400">{{
                    $this->selectedDetails[
                      "path"
                    ]
                  }}</p>

                  <!-- Status Badge -->
                  <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1"
                  >
                    <div
                      class="h-2 w-2 rounded-full {{ $this->selectedDetails['is_used'] ? 'bg-orange-500' : 'bg-foresty' }}"
                    ></div>
                    <span
                      class="text-[11px] font-bold {{ $this->selectedDetails['is_used'] ? 'text-orange-700' : 'text-foresty' }}"
                    >
                      {{
                        $this->selectedDetails["is_used"]
                          ? "Terpakai"
                          : "Belum dipakai"
                      }}
                    </span>
                  </div>

                  <!-- Input Alt Text -->
                  @if ($this->selectedDetails["type"] === "media" &&
                    $this->selectedDetails["is_image"])
                    <div class="mb-6">
                      <label
                        class="mb-2 flex items-center gap-2 text-[10px] font-bold tracking-widest text-gray-500"
                      >
                        <x-dynamic-component
                          component="lucide-image"
                          class="h-3 w-3"
                        />
                        TEKS ALTERNATIF (ALT TEXT)
                      </label>
                      <input
                        type="text"
                        wire:model.blur="activeAltText"
                        placeholder="Deskripsikan gambar ini..."
                        class="focus:border-foresty focus:ring-foresty w-full rounded-lg border-orange-200 bg-orange-50/30 p-3 text-sm shadow-sm"
                      />
                      @if (empty($this->activeAltText))
                        <p class="mt-1.5 text-[10px] font-bold text-orange-600">Belum diisi — pembaca layar & mesin pencari tidak akan memahami gambar ini.</p>
                      @endif
                    </div>
                  @endif

                  <!-- Grid Metadata Tabel -->
                  <div class="mb-6 space-y-3">
                    <div
                      class="flex justify-between border-b border-gray-100 py-2"
                    >
                      <span class="text-sm font-semibold text-gray-500"
                        >Ukuran</span
                      >
                      <span class="text-sm font-bold text-gray-800">{{
                        $this->selectedDetails[
                          "size"
                        ]
                      }}</span>
                    </div>
                    @if ($this->selectedDetails["type"] === "media")
                      <div
                        class="flex justify-between border-b border-gray-100 py-2"
                      >
                        <span class="text-sm font-semibold text-gray-500"
                          >Dimensi</span
                        >
                        <span class="text-sm font-bold text-gray-800">{{
                          $this->selectedDetails[
                            "dimensions"
                          ]
                        }}</span>
                      </div>
                    @endif
                    <div
                      class="flex justify-between border-b border-gray-100 py-2"
                    >
                      <span class="text-sm font-semibold text-gray-500"
                        >Diunggah</span
                      >
                      <span class="text-sm font-bold text-gray-800">{{
                        $this->selectedDetails[
                          "created_at"
                        ]
                      }}</span>
                    </div>
                    <div
                      class="flex justify-between border-b border-gray-100 py-2"
                    >
                      <span class="text-sm font-semibold text-gray-500"
                        >Oleh</span
                      >
                      <span class="text-sm font-bold text-gray-800">{{
                        $this->selectedDetails[
                          "uploader"
                        ]
                      }}</span>
                    </div>
                    @if ($this->selectedDetails["type"] === "folder")
                      <div
                        class="flex justify-between border-b border-gray-100 py-2"
                      >
                        <span class="text-sm font-semibold text-gray-500"
                          >Isi Folder</span
                        >
                        <span class="text-sm font-bold text-gray-800">{{
                          $this->selectedDetails[
                            "count"
                          ]
                        }}</span>
                      </div>
                    @endif
                  </div>

                  <!-- Usage Tracker -->
                  <div class="mb-6">
                    <h3
                      class="mb-2 text-[10px] font-bold tracking-widest text-gray-500"
                    >
                      DIGUNAKAN DI
                    </h3>
                    <p class="text-xs text-gray-400 italic">
                      {{
                        $this->selectedDetails["is_used"]
                          ? "Digunakan pada artikel/halaman."
                          : "Belum dipakai di halaman mana pun."
                      }}
                    </p>
                  </div>

                  <!-- Tombol Aksi -->
                  <div class="mt-auto space-y-2">
                    @if ($this->selectedDetails["type"] === "media")

                      <!-- 🌟 TOMBOL GUNAKAN (Khusus Mode Modal) -->
                      @if ($isModal)
                        <button
                          type="button"
                          wire:click="selectMedia({{ $this->selectedDetails['id'] }}, '{{ $this->selectedDetails['url'] }}')"
                          class="bg-foresty flex w-full cursor-pointer items-center justify-center gap-3 rounded-xl px-4 py-3 text-sm font-bold text-white shadow-md transition-colors outline-none hover:bg-emerald-800"
                        >
                          <x-dynamic-component component="lucide-check-circle" class="h-4 w-4" />
                          Gunakan Berkas Ini
                        </button>
                        <div class="my-3 border-t border-gray-100"></div>
                      @endif

                      <a
                        href="{{ $this->selectedDetails['url'] }}"
                        download
                        class="bg-sage-soft/40 text-foresty hover:bg-sage-soft flex w-full cursor-pointer items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition-colors"
                      >
                        <x-dynamic-component
                          component="lucide-download"
                          class="h-4 w-4"
                        />
                        Unduh
                      </a>
                      <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $this->selectedDetails['url'] }}'); $wire.notify('Tautan disalin!', 'success')"
                        class="bg-sage-soft/40 text-foresty hover:bg-sage-soft flex w-full cursor-pointer items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition-colors outline-none"
                      >
                        <x-dynamic-component
                          component="lucide-link"
                          class="h-4 w-4"
                        />
                        Salin Tautan
                      </button>
                      <button
                        type="button"
                        @click="let n = prompt('Ganti nama file:', '{{ addslashes($this->selectedDetails['name']) }}'); if(n) { $wire.renameMedia({{ $this->selectedDetails['id'] }}, n); }"
                        class="bg-sage-soft/40 text-foresty hover:bg-sage-soft flex w-full cursor-pointer items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition-colors outline-none"
                      >
                        <x-dynamic-component
                          component="lucide-pencil"
                          class="h-4 w-4"
                        />
                        Ganti Nama
                      </button>
                    @endif
                    <button
                      type="button"
                      wire:click="deleteMedia({{ $this->selectedDetails['id'] }})"
                      wire:confirm="Hapus item ini secara permanen?"
                      class="mt-4 flex w-full cursor-pointer items-center gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-600 transition-colors outline-none hover:bg-red-100"
                    >
                      <x-dynamic-component
                        component="lucide-trash-2"
                        class="h-4 w-4"
                      />
                      Pindah ke Sampah
                    </button>
                  </div>
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- ======================================================== -->
  <!-- 🌟 SLIDE-OVER PANEL: PINDAH FOLDER (Versi Adaptif) 🌟 -->
  <!-- ======================================================== -->
  <div
    wire:key="slide-over-move-panel"
    x-data="{
            isMoveOpen: @entangle('isMovePanelOpen'),
            searchQuery: '',
         }"
    x-init="
      $watch('isMoveOpen', (value) => {
        if (value) searchQuery = '';
      })
    "
    x-show="isMoveOpen"
    class="fixed inset-0 z-[110] overflow-hidden"
    x-cloak
  >
    <div
      x-show="isMoveOpen"
      x-transition.opacity.duration.300ms
      wire:click="closeMovePanel"
      class="absolute inset-0 cursor-pointer bg-black/60 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- 🌟 TRANSISI ADAPTIF: Bawah (Mobile) & Kanan (Desktop) -->
    <div
      class="pointer-events-none fixed inset-x-0 bottom-0 flex max-h-[85vh] max-w-full md:inset-x-auto md:inset-y-0 md:right-0 md:max-h-full md:pl-10"
    >
      <div
        x-show="isMoveOpen"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-full"
        x-transition:enter-end="translate-y-0 md:translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 md:translate-x-0"
        x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-full"
        class="pointer-events-auto flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl md:w-screen md:rounded-none"
      >
        <div class="bg-foresty shrink-0 px-4 py-6 sm:px-6">
          <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
              <x-dynamic-component
                component="lucide-folder-symlink"
                class="h-5 w-5"
              />
              Pindah Folder
            </h2>
            <button
              type="button"
              wire:click="closeMovePanel"
              class="cursor-pointer rounded-md text-emerald-200 outline-none hover:text-white"
            >
              <x-dynamic-component component="lucide-x" class="h-6 w-6" />
            </button>
          </div>
          <p class="mt-1 text-xs text-emerald-100">Cari dan pilih direktori tujuan.</p>
        </div>

        <div class="flex flex-1 flex-col overflow-hidden bg-gray-50">
          <div class="shrink-0 border-b border-gray-200 bg-white p-4">
            <div class="relative">
              <x-dynamic-component
                component="lucide-search"
                class="absolute top-2.5 left-3 h-4 w-4 text-gray-400"
              />
              <input
                type="text"
                x-model="searchQuery"
                placeholder="Cari nama folder..."
                class="focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm"
              />
            </div>
          </div>

          <div class="flex-1 scrollbar-thin overflow-y-auto p-4">
            <div
              class="space-y-1 rounded-xl border border-gray-200 bg-white p-2 shadow-sm"
            >
              @foreach ($this->flatFolders as $ff)
                <button
                  type="button"
                  wire:key="move-btn-{{ $ff['id'] }}"
                  wire:click="$set('movingToFolderId', '{{ $ff['id'] }}')"
                  x-data="{ name: '{{ strtolower(addslashes($ff['name'])) }}' }"
                  x-show="
                    searchQuery === '' ||
                    name.includes(searchQuery.toLowerCase())
                  "
                  class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors outline-none"
                  x-bind:class="$wire.movingToFolderId == '{{ $ff['id'] }}' ? 'bg-sage-soft text-foresty shadow-inner' : 'text-gray-700 hover:bg-gray-100'"
                >
                  <div
                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition-all"
                    x-bind:class="$wire.movingToFolderId == '{{ $ff['id'] }}' ? 'border-[5px] border-foresty bg-white' : 'border-gray-300 bg-white'"
                  ></div>
                  <span
                    class="truncate text-sm font-semibold whitespace-pre"
                    >{{ $ff["name"] }}</span
                  >
                </button>
              @endforeach
            </div>
          </div>
        </div>

        <div
          class="flex shrink-0 justify-end gap-3 border-t border-gray-200 bg-white px-4 py-4"
        >
          <button
            type="button"
            wire:click="closeMovePanel"
            class="cursor-pointer rounded-lg bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm ring-1 ring-gray-300 transition-colors outline-none ring-inset hover:bg-gray-50"
          >
            Batal
          </button>
          <button
            type="button"
            wire:click="executeMove"
            class="bg-foresty inline-flex cursor-pointer justify-center rounded-lg px-4 py-2 text-sm font-bold text-white shadow-md transition-colors outline-none hover:bg-emerald-800"
          >
            Pindahkan File
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
