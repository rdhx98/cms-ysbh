<?php

use Livewire\Component;
use App\Models\User;
use App\Livewire\Traits\WithNotifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

new class extends Component {
  use WithNotifications;

  public ?User $user = null;
  public bool $isCreateMode = false;

  // Deklarasi wire:model
  public $name;
  public $handle;
  public $email;
  public $password;
  public $password_confirmation; // <-- Tambahan kolom konfirmasi
  public $job_title;
  public array $roles = [];
  public bool $active = true;

  public function mount(?User $user = null)
  {
    $this->user = $user ?? new User();
    $this->isCreateMode = !$this->user->exists;

    if (!$this->isCreateMode) {
      $this->name = $this->user->name;
      $this->handle = $this->user->handle;
      $this->email = $this->user->email;
      $this->job_title = $this->user->job_title;
      $this->active = (bool) $this->user->active;

      $this->roles = $this->user->roles->pluck("name")->toArray();
      if (empty($this->roles)) {
        $this->roles = ["writer"];
      }
    } else {
      $this->roles = ["writer"];
      $this->active = true;
    }

    // Kosongkan form password setiap kali dimuat
    $this->password = null;
    $this->password_confirmation = null;
  }

  /**
   * Fungsi ajaib Livewire: Akan otomatis berjalan setiap kali $name berubah
   */
  public function updatedName($value)
  {
    // Hanya jalankan auto-generate saat mode Create (buat pengguna baru)
    // dan memastikan inputan tidak kosong
    if ($this->isCreateMode && !empty(trim($value))) {
      $this->handle = $this->generateHandle($value);
    }
  }

  /**
   * Logika Generate Handle
   */
  private function generateHandle($fullName)
  {
    $cleanName = strtolower(trim(preg_replace("/\s+/", " ", $fullName)));

    // Mencegah error jika admin hanya mengetik spasi
    if (empty($cleanName)) {
      return "";
    }

    $words = explode(" ", $cleanName);
    $wordCount = count($words);

    $firstWord = $words[0];
    $lastWord = $words[$wordCount - 1];

    // --- SKENARIO UTAMA ---
    if ($wordCount === 1) {
      $baseHandle = substr($firstWord, 0, 1) . $firstWord;
    } else {
      $baseHandle = substr($firstWord, 0, 1) . $lastWord;
    }

    $handle = preg_replace("/[^a-z0-9]/", "", $baseHandle);

    // PENTING: Ubah pencarian dari 'username' menjadi 'handle'
    if (!User::where("handle", $handle)->exists()) {
      return $handle;
    }

    // --- SKENARIO ALTERNATIF ---
    if ($wordCount > 2) {
      $fallbackBase =
        substr($firstWord, 0, 1) . substr($words[1], 0, 1) . $lastWord;
    } else {
      $fallbackBase =
        substr($firstWord, 0, 2) . ($wordCount > 1 ? $lastWord : $firstWord);
    }

    $handle = preg_replace("/[^a-z0-9]/", "", $fallbackBase);

    if (!User::where("handle", $handle)->exists()) {
      return $handle;
    }

    // --- PERTAHANAN TERAKHIR ---
    $counter = 1;
    while (User::where("handle", $handle)->exists()) {
      $handle = preg_replace("/[^a-z0-9]/", "", $fallbackBase) . $counter;
      $counter++;
    }

    return $handle;
  }

  public function save()
  {
    $userId = $this->user->id ?? "";

    // 1. Cek apakah yang menekan tombol adalah Admin
    $isAdmin = auth()->user()->hasRole("admin");

    // ==========================================
    // BAGIAN 1: ATURAN VALIDASI
    // ==========================================

    // Aturan dasar yang berlaku untuk SEMUA orang
    $rules = [
      "name" => "required|string|max:255",
      "handle" => "required|string|max:255|unique:users,handle," . $userId,
    ];

    if ($this->isCreateMode) {
      $rules["password"] = "required|string|min:8|confirmed";
    } else {
      $rules["password"] = "nullable|string|min:8|confirmed";
    }

    // Aturan tambahan HANYA jika yang mengeksekusi adalah Admin
    if ($isAdmin) {
      $rules["job_title"] = "required|string|max:255";
      $rules["active"] = "boolean";
      $rules["email"] = "required|email|max:255|unique:users,email," . $userId;
      $rules["roles"] = "required|array|min:1";
      $rules["roles.*"] = "in:writer,editor,admin";
    }

    $this->validate($rules, [
      "roles.required" => "Pengguna harus memiliki minimal 1 peran.",
      "password.required" => "Kata sandi wajib diisi untuk pengguna baru.",
      "password.min" => "Kata sandi minimal 8 karakter.",
      "password.confirmed" => "Konfirmasi kata sandi tidak cocok.",
    ]);

    // ==========================================
    // BAGIAN 2: EKSEKUSI PENYIMPANAN
    // ==========================================

    if ($this->isCreateMode) {
      // Keamanan Ekstra: Cegah user biasa membuat akun baru lewat celah apa pun
      abort_if(!$isAdmin, 403, "Hanya Admin yang dapat membuat pengguna baru.");

      $newUser = User::create([
        "name" => $this->name,
        "handle" => $this->handle,
        "email" => $this->email,
        "job_title" => $this->job_title,
        "active" => $this->active,
        "password" => Hash::make($this->password),
      ]);

      $newUser->syncRoles($this->roles);

      $this->notifyFlash("Pengguna baru berhasil ditambahkan", "success");
      return $this->redirect(route("user.index"), navigate: true);
    } else {
      // Keamanan Ekstra: Cegah user biasa mengedit akun milik orang lain
      abort_if(
        !$isAdmin && auth()->id() !== $this->user->id,
        403,
        "Anda hanya diizinkan mengubah profil Anda sendiri.",
      );

      // Data yang boleh diubah oleh SEMUA orang
      $updateData = [
        "name" => $this->name,
        "handle" => $this->handle,
      ];

      // Memasukkan data sensitif HANYA jika pelakunya Admin
      if ($isAdmin) {
        $updateData["email"] = $this->email;
        $updateData["job_title"] = $this->job_title;
        $updateData["active"] = $this->active;
      }

      if (!empty($this->password)) {
        $updateData["password"] = Hash::make($this->password);
      }

      $this->user->update($updateData);

      // Hanya Admin yang berhak menyinkronisasi/mengubah peran (role)
      if ($isAdmin) {
        $this->user->syncRoles($this->roles);
      }

      // Pencatatan Log Aktivitas jika kata sandi diubah
      if (!empty($this->password)) {
        activity("security")
          ->performedOn($this->user)
          ->causedBy(auth()->user())
          ->withProperties([
            "ip_address" => request()->ip(),
            "browser" => request()->userAgent(),
            "type" => "password_update",
          ])
          ->log("Kata sandi berhasil diperbarui");
      }

      $handleBerubah = $this->user->wasChanged("handle");

      $this->password = null;
      $this->password_confirmation = null;

      if ($handleBerubah) {
        $this->notifyFlash("Perubahan Disimpan", "success");
        return $this->redirect(
          route("user.detail", $this->handle),
          navigate: true,
        );
      } else {
        // Jika handle tidak berubah, cukup tutup mode edit seperti biasa
        $this->dispatch("close-edit-mode");
        $this->notifyFlash("Perubahan Disimpan", "success");
      }
    }
  }
  // public function save()
  // {
  //     $userId = $this->user->id ?? '';

  //     $rules = [
  //         'name'      => 'required|string|max:255',
  //         'job_title' => 'required|string|max:255',
  //         'active'    => 'boolean',
  //         'handle'    => 'required|string|max:255|unique:users,handle,' . $userId,
  //         'email'     => 'required|email|max:255|unique:users,email,' . $userId,
  //         'roles'     => 'required|array|min:1',
  //         'roles.*'   => 'in:writer,editor,admin',
  //     ];

  //     // Validasi Dinamis: Tambahkan aturan 'confirmed'
  //     // Ini akan otomatis mencocokkan dengan $password_confirmation
  //     if ($this->isCreateMode) {
  //         $rules['password'] = 'required|string|min:8|confirmed';
  //     } else {
  //         $rules['password'] = 'nullable|string|min:8|confirmed';
  //     }

  //     $this->validate($rules, [
  //         'roles.required'     => 'Pengguna harus memiliki minimal 1 peran.',
  //         'password.required'  => 'Kata sandi wajib diisi untuk pengguna baru.',
  //         'password.min'       => 'Kata sandi minimal 8 karakter.',
  //         'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.', // Pesan error khusus
  //     ]);

  //     if ($this->isCreateMode) {
  //         $newUser = User::create([
  //             'name'      => $this->name,
  //             'handle'    => $this->handle,
  //             'email'     => $this->email,
  //             'job_title' => $this->job_title,
  //             'active'    => $this->active,
  //             'password'  => Hash::make($this->password),
  //         ]);

  //         $newUser->syncRoles($this->roles);

  //         $this->notifyFlash('Pengguna baru berhasil ditambahkan', 'success');
  //         return $this->redirect(route('user.index'), navigate: true);

  //     } else {
  //         $updateData = [
  //             'name'      => $this->name,
  //             'handle'    => $this->handle,
  //             'email'     => $this->email,
  //             'job_title' => $this->job_title,
  //             'active'    => $this->active,
  //         ];

  //         if (!empty($this->password)) {
  //             $updateData['password'] = Hash::make($this->password);
  //         }

  //         $this->user->update($updateData);
  //         $this->user->syncRoles($this->roles);

  //         if (!empty($this->password)) {
  //             activity('security')
  //                 ->performedOn($this->user)         // Target: Akun siapa yang sedang diubah
  //                 ->causedBy(auth()->user())         // Pelaku: Siapa yang sedang login & mengeklik tombol
  //                 ->withProperties([
  //                     'ip_address' => request()->ip(),
  //                     'browser' => request()->userAgent(),
  //                     'type' => 'password_update'
  //                 ])->log('Kata sandi berhasil diperbarui secara manual');
  //         }

  //         // Bersihkan field password setelah simpan berhasil
  //         $this->password = null;
  //         $this->password_confirmation = null;

  //         $this->dispatch('close-edit-mode');
  //         $this->notify('Perubahan Disimpan', 'success');
  //     }
  // }

  public function deleteUser()
  {
    if ($this->isCreateMode) {
      return;
    }

    if (!auth()->user()->hasRole("admin")) {
      $this->notify("Anda tidak memiliki otoritas admin!", "error");
      return;
    }

    if ($this->user->hasRole("admin")) {
      $this->notify(
        "Tidak bisa menghapus akun yang memiliki peran admin!",
        "error",
      );
      return;
    }

    $this->user->delete();
    $this->notifyFlash("Akun berhasil dihapus", "success");
    return $this->redirect(route("user.index"), navigate: true);
  }

  public function cancel()
  {
    $this->mount($this->user);
    $this->resetValidation();
  }
};
?>

<x-slot:title>
  {{
    $isCreateMode
      ? __("Tambah Pengguna")
      : __("Users Detail") . " | " . auth()->user()->name
  }}
</x-slot:title>
<x-main-wrapper>
  <div
    x-data="{ showDeleteModal: false }"
    class="mb-8 max-h-full w-full max-w-screen overflow-x-auto overflow-y-auto border-zinc-200 dark:border-zinc-700"
  >
    <div
      class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center"
    >
      <div>
        <h2 class="text-forest text-2xl font-bold dark:text-zinc-100">
          {{
            $isCreateMode
              ? "Tambah Pengguna Baru"
              : "Detail Pengguna"
          }}
        </h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
          {{
            $isCreateMode
              ? "Buat akun staf baru untuk sistem."
              : "Lihat detail dan kelola informasi akun staf."
          }}
        </p>
      </div>
      @if (!$isCreateMode)
        {{-- <a href="{{ route('user.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                    <x-dynamic-component :component="'lucide-arrow-left'" class="w-4 h-4" />
                    Kembali ke Daftar
                </a> --}}
        <a
          href="{{ route('user.index') }}"
          wire:navigate
          x-data="{
            isAnimating: false,
            playAnim() {
              this.isAnimating = false;
              this.$nextTick(() => {
                this.isAnimating = true;
                setTimeout(() => (this.isAnimating = false), 500);
              });
            },
          }"
          x-on:mouseenter="playAnim()"
          {{-- x-on:click=" playAnim(); deleteType = 'category'; deleteId = {{ $i->id }}; showDeleteModal = true " --}}
          class="text-foresty hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm transition-colors"
        >
          <x-dynamic-component
            :component="'lucide-arrow-left'"
            class="h-5 w-5 origin-center"
            stroke-width="2"
            x-bind:class="isAnimating ? 'animate-back' : ''"
          />
          {{ __("Back") }}
        </a>
      @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <!-- 👈 KOLOM KIRI -->
      <div class="space-y-6 lg:col-span-4">
        <div
          class="relative flex flex-col items-center overflow-hidden rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div
            class="bg-sage-soft absolute top-0 right-0 left-0 h-24 dark:bg-zinc-800/50"
          ></div>
          <div class="relative mt-8 mb-4">
            <div
              class="bg-misty text-forest relative z-10 flex h-24 w-24 items-center justify-center rounded-full border-4 border-white text-3xl font-bold shadow-md dark:border-zinc-900"
            >
              {{
                $isCreateMode
                  ? "NEW"
                  : $user->initials() ?? "US"
              }}
            </div>
          </div>
          <h3 class="text-lg font-bold text-zinc-900 dark:text-white">
            {{
              $isCreateMode
                ? "Pengguna Baru"
                : $user->name ?? "Nama Staf"
            }}
          </h3>
          <p class="text-goldy mb-4 text-sm font-medium dark:text-amber-400">
            {{
              "@" .
                ($isCreateMode
                  ? "username"
                  : $user->handle ?? "handle")
            }}
          </p>
          <div
            class="flex w-full flex-wrap justify-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800"
          >
            @if ($active)
              <span
                class="bg-misty text-foresty border-foresty/20 inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium"
              >
                <span class="bg-forest h-1.5 w-1.5 rounded-full"></span> Aktif
              </span>
            @else
              <span
                class="bg-coral-muted inline-flex items-center gap-1.5 rounded-full border border-red-200 px-3 py-1 text-xs font-medium text-red-700"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                Nonaktif
              </span>
            @endif
            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600"
            >
              <x-dynamic-component
                :component="'lucide-briefcase'"
                class="h-3 w-3"
              />
              {{
                $isCreateMode
                  ? "Staf"
                  : $user->job_title ?? "Staf"
              }}
            </span>
          </div>
        </div>
      </div>

      <!-- 👉 KOLOM KANAN -->
      <div
        class="rounded-3xl transition-all duration-300 ease-in-out lg:col-span-8"
        x-bind:class="
          isEditing
            ? 'border-foresty border'
            : 'border border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
        "
        x-data="{ isEditing: {{ $isCreateMode ? 'true' : 'false' }} }"
        @close-edit-mode.window="isEditing = false"
      >
        <form
          wire:submit="save"
          class="overflow-hidden rounded-3xl bg-white shadow-sm dark:bg-zinc-900"
        >
          {{-- <div class="lg:col-span-8 rounded-3xl transition-shadow duration-500 ease-out" x-bind:class="isEditing ? 'ring-4 ring-forest/20 shadow-xl shadow-forest/10' : 'shadow-sm hover:shadow-md'" x-data="{ isEditing: {{ $isCreateMode ? 'true' : 'false' }} }" @close-edit-mode.window="isEditing = false">

                <form wire:submit="save" class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200 dark:border-zinc-800 overflow-hidden"> --}}
          <!-- Isi Form -->

          {{-- <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex justify-between items-center">
                        <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                            <x-dynamic-component :component="'lucide-user-cog'" class="w-5 h-5 text-forest" />
                            Informasi Akun
                        </h3>
                    </div> --}}
          <!-- Header Form -->
          <div
            class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 transition-colors duration-500 dark:border-zinc-800"
            x-bind:class="
              isEditing
                ? 'bg-sage-soft/50 dark:bg-forest/10'
                : 'bg-zinc-50/50 dark:bg-zinc-900/50'
            "
          >
            <h3
              class="flex items-center gap-2 text-base font-bold text-zinc-800 dark:text-zinc-100"
            >
              <x-dynamic-component
                :component="'lucide-user-cog'"
                class="h-5 w-5 transition-colors duration-300"
                x-bind:class="isEditing ? 'text-forest' : 'text-zinc-400'"
              />
              Informasi Akun
            </h3>

            <!-- BADGE MODE EDIT BERDENYUT -->
            <div
              x-show="isEditing"
              x-cloak
              x-transition.opacity
              class="border-forest/20 flex items-center gap-2 rounded-full border bg-white px-3 py-1 shadow-sm dark:bg-zinc-800"
            >
              <span class="relative flex h-2.5 w-2.5">
                <span
                  class="bg-forest absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                ></span>
                <span
                  class="bg-forest relative inline-flex h-2.5 w-2.5 rounded-full"
                ></span>
              </span>
              <span class="text-forest text-xs font-bold">Mode Edit</span>
            </div>
          </div>

          <div class="space-y-6 p-6">
            <div>
              <label
                class="mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                >Nama Lengkap</label
              >

              <!-- UBAH MENJADI SEPERTI INI: -->
              <input
                x-bind:disabled="!isEditing"
                type="text"
                wire:model.live.debounce.500ms="name"
                class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                placeholder="Masukkan nama lengkap..."
              />
              {{-- <input x-bind:disabled="!isEditing" type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-forest/20 focus:border-forest transition-colors shadow-sm text-sm" placeholder="Masukkan nama lengkap..."> --}}
              @error ("name")
                <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
              @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label
                  class="mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                  >Username (Handle)</label
                >
                <div class="relative">
                  <span
                    class="absolute inset-y-0 left-0 flex items-center pl-4 font-medium text-zinc-400"
                    >@</span
                  >
                  <input
                    x-bind:disabled="!isEditing"
                    type="text"
                    wire:model="handle"
                    class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-zinc-50 py-2.5 pr-4 pl-9 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="username"
                  />
                </div>
                @error ("handle")
                  <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
                @enderror
              </div>

              <div>
                <label
                  class="mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                  >Alamat Email</label
                >
                <input
                  x-bind:disabled="!isEditing"
                  type="email"
                  wire:model="email"
                  class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                  placeholder="email@organisasi.org"
                />
                @error ("email")
                  <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
                @enderror
              </div>
            </div>

            <!-- PASSWORD & KONFIRMASI PASSWORD -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label
                  class="mb-1.5 flex items-center justify-between text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                >
                  <span>Kata Sandi</span>
                  @if (!$isCreateMode)
                    <span
                      x-show="isEditing"
                      x-cloak
                      class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-normal text-zinc-500 dark:bg-zinc-800"
                    >
                      Kosongkan jika tidak diubah
                    </span>
                  @endif
                </label>

                <input
                  x-bind:disabled="!isEditing"
                  type="password"
                  wire:model="password"
                  class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                  placeholder="{{ $isCreateMode ? 'Minimal 8 karakter...' : '••••••••' }}"
                  autocomplete="new-password"
                />

                @error ("password")
                  <span class="mt-1 text-xs text-red-500">{{ $message }}</span>
                @enderror
              </div>

              <!-- KOLOM BARU: Konfirmasi Kata Sandi -->
              <div>
                <label
                  class="mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                >
                  <span>Konfirmasi Kata Sandi</span>
                </label>

                <input
                  x-bind:disabled="!isEditing"
                  type="password"
                  wire:model="password_confirmation"
                  class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                  placeholder="{{ $isCreateMode ? 'Ulangi kata sandi...' : '••••••••' }}"
                  autocomplete="new-password"
                />
              </div>
            </div>

            <div class="my-4 h-px w-full bg-zinc-100 dark:bg-zinc-800"></div>

            @hasrole ("admin")
              <!-- ROLES -->
              <div x-data="{ selectedRoles: @entangle('roles') }">
                <label
                  class="mb-3 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                >
                  Akses Wewenang
                </label>

                <div
                  class="grid grid-cols-1 gap-4 transition-opacity duration-300 sm:grid-cols-3"
                  x-bind:class="
                    !isEditing ? 'pointer-events-none opacity-80' : ''
                  "
                >
                  <label
                    class="group relative block"
                    x-bind:class="isEditing ? 'cursor-pointer' : ''"
                  >
                    <input
                      x-bind:disabled="!isEditing"
                      type="checkbox"
                      wire:model="roles"
                      value="writer"
                      class="peer sr-only"
                    />
                    <div
                      class="h-full rounded-2xl border-2 bg-white p-4 transition-all duration-200 dark:bg-zinc-900"
                      x-bind:class="
                        selectedRoles.includes('writer')
                          ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10'
                          : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
                      "
                    >
                      <div class="mb-1.5 flex items-center gap-2">
                        <x-dynamic-component
                          :component="'lucide-feather'"
                          class="h-5 w-5 transition-colors"
                          x-bind:class="
                            selectedRoles.includes('writer')
                              ? 'text-foresty'
                              : 'text-zinc-400 group-hover:text-foresty/70'
                          "
                        />
                        <span
                          class="font-bold transition-colors"
                          x-bind:class="
                            selectedRoles.includes('writer')
                              ? 'text-foresty'
                              : 'text-zinc-800 dark:text-zinc-200'
                          "
                        >
                          Writer
                        </span>
                      </div>
                      <p class="text-xs leading-relaxed text-zinc-500 select-none dark:text-zinc-400">Dapat membuat dan mengelola draf artikel miliknya sendiri.</p>
                    </div>
                    <div
                      class="absolute top-4 right-4 transition-opacity"
                      x-bind:class="
                        selectedRoles.includes('writer')
                          ? 'opacity-100 text-foresty'
                          : 'opacity-0 text-zinc-400'
                      "
                    >
                      <x-dynamic-component
                        :component="'lucide-circle-check'"
                        class="fill-foresty/10 h-5 w-5"
                      />
                    </div>
                  </label>

                  <label
                    class="group relative block"
                    x-bind:class="isEditing ? 'cursor-pointer' : ''"
                  >
                    <input
                      x-bind:disabled="!isEditing"
                      type="checkbox"
                      wire:model="roles"
                      value="editor"
                      class="peer sr-only"
                    />
                    <div
                      class="h-full rounded-2xl border-2 bg-white p-4 transition-all duration-200 dark:bg-zinc-900"
                      x-bind:class="
                        selectedRoles.includes('editor')
                          ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10'
                          : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
                      "
                    >
                      <div class="mb-1.5 flex items-center gap-2">
                        <x-dynamic-component
                          :component="'lucide-clipboard-check'"
                          class="h-5 w-5 transition-colors"
                          x-bind:class="
                            selectedRoles.includes('editor')
                              ? 'text-foresty'
                              : 'text-zinc-400 group-hover:text-foresty/70'
                          "
                        />
                        <span
                          class="font-bold transition-colors"
                          x-bind:class="
                            selectedRoles.includes('editor')
                              ? 'text-foresty'
                              : 'text-zinc-800 dark:text-zinc-200'
                          "
                        >
                          Editor
                        </span>
                      </div>
                      <p class="text-xs leading-relaxed text-zinc-500 select-none dark:text-zinc-400">Dapat menyunting semua artikel dan menyetujui publikasi.</p>
                    </div>
                    <div
                      class="absolute top-4 right-4 transition-opacity"
                      x-bind:class="
                        selectedRoles.includes('editor')
                          ? 'opacity-100 text-foresty'
                          : 'opacity-0 text-zinc-400'
                      "
                    >
                      <x-dynamic-component
                        :component="'lucide-circle-check'"
                        class="fill-foresty/10 h-5 w-5"
                      />
                    </div>
                  </label>

                  <label
                    class="group relative block"
                    x-bind:class="isEditing ? 'cursor-pointer' : ''"
                  >
                    <input
                      x-bind:disabled="!isEditing"
                      type="checkbox"
                      wire:model="roles"
                      value="admin"
                      class="peer sr-only"
                    />
                    <div
                      class="h-full rounded-2xl border-2 bg-white p-4 transition-all duration-200 dark:bg-zinc-900"
                      x-bind:class="
                        selectedRoles.includes('admin')
                          ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10'
                          : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
                      "
                    >
                      <div class="mb-1.5 flex items-center gap-2">
                        <x-dynamic-component
                          :component="'lucide-shield-check'"
                          class="h-5 w-5 transition-colors"
                          x-bind:class="
                            selectedRoles.includes('admin')
                              ? 'text-foresty'
                              : 'text-zinc-400 group-hover:text-foresty/70'
                          "
                        />
                        <span
                          class="font-bold transition-colors"
                          x-bind:class="
                            selectedRoles.includes('admin')
                              ? 'text-foresty'
                              : 'text-zinc-800 dark:text-zinc-200'
                          "
                        >
                          Admin
                        </span>
                      </div>
                      <p class="text-xs leading-relaxed text-zinc-500 select-none dark:text-zinc-400">Akses penuh ke semua pengaturan sistem dan pengguna.</p>
                    </div>
                    <div
                      class="absolute top-4 right-4 transition-opacity"
                      x-bind:class="
                        selectedRoles.includes('admin')
                          ? 'opacity-100 text-foresty'
                          : 'opacity-0 text-zinc-400'
                      "
                    >
                      <x-dynamic-component
                        :component="'lucide-circle-check'"
                        class="fill-foresty/10 h-5 w-5"
                      />
                    </div>
                  </label>
                </div>
                @error ("roles")
                  <span
                    class="mt-2 block text-xs font-semibold text-red-500"
                    >{{ $message }}</span
                  >
                @enderror
              </div>

              <div class="grid grid-cols-1 gap-6 md:grid-cols-1">
                <div>
                  <label
                    class="mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                    >Jabatan (Organisasi)</label
                  >
                  <input
                    x-bind:disabled="!isEditing"
                    type="text"
                    wire:model="job_title"
                    class="focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="Misal: Program Officer KIA"
                  />
                  @error ("job_title")
                    <span
                      class="mt-1 text-xs text-red-500"
                      >{{ $message }}</span
                    >
                  @enderror
                </div>
              </div>

              <div
                class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 p-4 transition-opacity dark:border-zinc-800 dark:bg-zinc-900/50"
                :class="!isEditing ? 'opacity-70' : ''"
              >
                <div>
                  <h4
                    class="text-sm font-bold text-zinc-800 dark:text-zinc-200"
                  >
                    Status Akun Aktif
                  </h4>
                  <p class="mt-0.5 text-xs text-zinc-500">Jika dimatikan, pengguna ini tidak akan bisa login ke dalam CMS.</p>
                </div>
                <label
                  x-data="{ checked: @entangle('active') }"
                  class="relative inline-flex items-center"
                  :class="isEditing
                    ? 'cursor-pointer'
                    : 'cursor-default pointer-events-none'"
                >
                  <input
                    x-bind:disabled="!isEditing"
                    type="checkbox"
                    x-model="checked"
                    class="peer sr-only"
                  />
                  <div
                    class="peer peer-checked:bg-forest h-6 w-11 rounded-full bg-zinc-200 peer-focus:outline-none after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:border-gray-600 dark:bg-zinc-700"
                  ></div>
                </label>
              </div>
            @endhasrole
          </div>

          <!-- Footer Area (Action Buttons) -->
          <div
            class="flex w-full items-center justify-between border-t border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900/50"
          >
            <!-- KIRI: Tombol Hapus -->
            @if (!$isCreateMode && auth()->user()->hasRole("admin"))
              <button
                x-show="!isEditing"
                type="button"
                x-cloak
                x-data="{
                  isAnimating: false,
                  playAnim() {
                    this.isAnimating = false;
                    this.$nextTick(() => {
                      this.isAnimating = true;
                      setTimeout(() => (this.isAnimating = false), 500);
                    });
                  },
                }"
                x-on:click="
                  playAnim();
                  showDeleteModal = true;
                "
                x-on:mouseenter="playAnim()"
                class="text-foresty bg-coral-muted hover:text-goldy flex cursor-pointer items-center gap-2 rounded-xl p-2 text-sm font-bold shadow-md transition-colors select-none hover:bg-red-700"
              >
                <x-dynamic-component
                  :component="'lucide-trash-2'"
                  class="group-hover:animate-trash h-5 w-5 origin-center"
                  stroke-width="2"
                  x-bind:class="isAnimating ? 'animate-trash' : ''"
                />
                <span>Hapus Pengguna</span>
              </button>
            @endif

            <!-- KANAN: Group Tombol Aksi (Otomatis terdorong ke kanan karena ml-auto) -->
            <div class="ml-auto flex items-center gap-3">
              {{-- SAVE or CREATE --}}
              @if (auth()->user()->hasRole("admin") || auth()->id() === optional($user)->id)
                <button
                  x-data="{
                    isAnimating: false,
                    playAnim() {
                      this.isAnimating = false;
                      this.$nextTick(() => {
                        this.isAnimating = true;
                        setTimeout(() => (this.isAnimating = false), 500);
                      });
                    },
                  }"
                  x-on:mouseenter="playAnim()"
                  x-on:click="playAnim()"
                  x-show="isEditing"
                  x-cloak
                  type="submit"
                  class="bg-misty text-foresty hover:text-goldy hover:bg-forest flex cursor-pointer items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold shadow-md transition-colors select-none"
                >
                  <x-dynamic-component
                    :component="'lucide-save'"
                    class="h-4 w-4"
                    x-bind:class="isAnimating ? 'animate-save' : ''"
                  />
                  <span wire:loading.remove wire:target="save">{{
                    $isCreateMode
                      ? "Tambahkan Pengguna"
                      : "Simpan Perubahan"
                  }}</span>
                  <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
              @endif

              @if ($isCreateMode && auth()->user()->hasRole("admin"))
                <a
                  href="{{ route('user.index') }}"
                  wire:navigate
                  class="flex cursor-pointer items-center rounded-xl bg-zinc-100 px-5 py-2.5 text-sm font-semibold text-zinc-600 transition-colors select-none hover:bg-zinc-200"
                  >Batal</a
                >
              @elseif (auth()->user()->hasRole("admin") ||
                auth()->id() === optional($user)->id)
                <button
                  x-show="isEditing"
                  x-cloak
                  x-on:click="isEditing = false"
                  type="button"
                  wire:click="cancel"
                  class="cursor-pointer rounded-xl bg-zinc-100 px-5 py-2.5 text-sm font-semibold text-zinc-600 transition-colors select-none hover:bg-zinc-200"
                >
                  Batal
                </button>

                <button
                  type="button"
                  x-data="{
                    isAnimating: false,
                    playAnim() {
                      this.isAnimating = false;
                      this.$nextTick(() => {
                        this.isAnimating = true;
                        setTimeout(() => (this.isAnimating = false), 500);
                      });
                    },
                  }"
                  x-on:mouseenter="playAnim()"
                  x-show="!isEditing"
                  x-on:click="
                    playAnim();
                    isEditing = true;
                  "
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 scale-95"
                  x-transition:enter-end="opacity-100 scale-100"
                  class="text-forest bg-sage-soft/30 hover:bg-sage-soft border-forest/10 inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-semibold transition-colors select-none"
                >
                  <x-dynamic-component
                    :component="'lucide-pencil'"
                    class="h-4 w-4"
                    x-bind:class="isAnimating ? 'animate-stroke' : ''"
                  />
                  Edit Profil
                </button>
              @endif
            </div>
          </div>
        </form>
      </div>

      {{-- DELETE MODAL --}}
      @if (!$isCreateMode && auth()->user()->hasRole("admin"))
        <div
          x-show="showDeleteModal"
          class="relative z-99"
          aria-labelledby="modal-title"
          role="dialog"
          aria-modal="true"
          x-cloak
        >
          <div
            x-show="showDeleteModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
          ></div>
          <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div
              class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
            >
              <div
                x-show="showDeleteModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.away="showDeleteModal = false"
                class="pt relative w-full max-w-sm transform overflow-hidden rounded-2xl bg-white px-4 pb-4 text-left shadow-xl transition-all sm:my-8 sm:p-6 dark:bg-zinc-900"
              >
                <div>
                  <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30"
                  >
                    <flux:icon
                      variant="outline"
                      icon="exclamation-triangle"
                      class="text-terracotta h-6 w-6 dark:text-red-400"
                    />
                  </div>
                  <div class="mt-3 text-center sm:mt-5">
                    <h3
                      class="text-base leading-6 font-bold text-zinc-900 dark:text-white"
                      id="modal-title"
                    >
                      Hapus Pengguna
                    </h3>
                    <div class="mt-2">
                      <p class="text-sm text-zinc-500 dark:text-zinc-400">Apakah Anda yakin ingin menghapus pengguna ini? Data yang sudah dihapus tidak dapat dikembalikan.</p>
                    </div>
                  </div>
                </div>
                <div
                  class="mt-5 flex flex-col gap-3 sm:mt-6 sm:flex-row-reverse"
                >
                  <button
                    type="button"
                    class="bg-sage-soft text-forest inline-flex w-full cursor-pointer justify-center rounded-xl px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-red-600 hover:text-white sm:w-auto"
                    @click="
                      $wire.deleteUser();
                      showDeleteModal = false;
                    "
                  >
                    Ya, Hapus
                  </button>
                  <button
                    type="button"
                    @click="showDeleteModal = false"
                    class="inline-flex w-full cursor-pointer justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-zinc-300 transition-colors ring-inset hover:bg-zinc-50 sm:w-auto dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-700"
                  >
                    Batal
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif
      <!-- end of modal wrapper-->
    </div>
  </div>
</x-main-wrapper>
