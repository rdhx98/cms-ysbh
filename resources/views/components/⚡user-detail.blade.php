<?php

use App\Content\ChatAvatar;
use App\Livewire\Traits\WithNotifications;
use App\Models\MediaUsage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
  use WithNotifications;
  use WithFileUploads;

  // Kolom di tabel users yang menyimpan jalur foto profil (relatif di disk "public").
  // "avatar" ikut dikenali ChatAvatar, jadi foto yang sama tampil di percakapan Review.
  private const AVATAR_COLUMN = "avatar";

  // #[Locked]: properti publik Livewire bisa diubah dari browser. Model target dan mode (buat/ubah) tidak boleh.
  #[Locked]
  public ?User $user = null;
  #[Locked]
  public bool $isCreateMode = false;

  // Foto baru yang BELUM disimpan (berkas unggahan sementara) dan penanda "hapus foto". Baru berlaku saat tombol Simpan.
  public $avatarUpload = null;
  #[Locked]
  public bool $avatarRemove = false;
  #[Locked]
  public bool $avatarEnabled = false;

  // Deklarasi wire:model
  public $name;
  public $handle;
  public $email;
  public $password;
  public $password_confirmation;
  public $job_title;
  public array $roles = [];
  public bool $active = true;

  public function mount(?User $user = null)
  {
    $this->user = $user ?? new User();
    $this->isCreateMode = !$this->user->exists;

    // Akses per profil (UserPolicy): admin = semua; editor/writer = hanya profil sendiri. Rute hanya memeriksa PERAN, bukan siapa pemiliknya.
    if ($this->isCreateMode) {
      $this->authorize("create", User::class);
    } else {
      $this->authorize("view", $this->user);
    }

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

    // Foto: dicek sekali di sini (bukan tiap render). Bila kolom belum ada, pemilih foto tidak ditampilkan.
    $this->avatarEnabled = Schema::hasColumn("users", self::AVATAR_COLUMN);
    $this->avatarUpload = null;
    $this->avatarRemove = false;
    unset($this->avatarUrl);
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
   * Alamat foto yang ditampilkan: foto baru (pratinjau unggahan sementara) bila ada, kalau tidak foto tersimpan.
   * Null = tampilkan inisial.
   */
  #[Computed]
  public function avatarUrl(): ?string
  {
    if ($this->avatarRemove) {
      return null;
    }

    if ($this->avatarUpload) {
      try {
        return $this->avatarUpload->temporaryUrl();
      } catch (\Throwable) {
        return null;
      }
    }

    if ($this->isCreateMode) {
      return null;
    }

    return $this->resolveAvatar(
      $this->user->getAttribute(self::AVATAR_COLUMN),
    ) ?? ChatAvatar::url($this->user);
  }

  /** Diizinkan mengubah foto? Sama dengan hak menyimpan profil ini (admin, atau pemilik profil). */
  private function authorizeAvatar(): void
  {
    $this->isCreateMode
      ? $this->authorize("create", User::class)
      : $this->authorize("update", $this->user);
  }

  /**
   * Jalur tersimpan -> alamat tampil, lewat konfigurasi disk "public" di lingkungan ini
   * (disk yang sama dengan File Manager).
   * Nilai lama berbentuk "/storage/..." dikembalikan ke jalurnya dulu, jadi tidak perlu mengunggah ulang.
   */
  private function resolveAvatar(mixed $value): ?string
  {
    $v = trim((string) $value);
    if ($v === "") {
      return null;
    }
    if (preg_match("#^https?://#i", $v)) {
      return $v;
    }
    if (str_starts_with($v, "/storage/")) {
      $v = rawurldecode(substr($v, 9));
    } elseif (str_starts_with($v, "/")) {
      return $v;
    }
    if (str_contains($v, "..")) {
      return null;
    }

    return preg_replace_callback(
      "/[\s\"'<>]/",
      fn($m) => rawurlencode($m[0]),
      Storage::disk("public")->url($v),
    );
  }

  /** Berjalan setelah berkas selesai diunggah ke folder sementara: periksa jenis dan ukuran sebelum dipratinjau. */
  public function updatedAvatarUpload(): void
  {
    $this->authorizeAvatar();

    if (!$this->avatarEnabled) {
      $this->avatarUpload = null;
      $this->notify(
        "Kolom foto belum ada di tabel users. Jalankan migrasinya dulu.",
        "error",
      );
      return;
    }

    try {
      $this->validateOnly(
        "avatarUpload",
        ["avatarUpload" => ["image", "mimes:jpg,jpeg,png,webp", "max:3072"]],
        [
          "avatarUpload.image" => "Berkas harus berupa gambar.",
          "avatarUpload.mimes" => "Gunakan foto berformat JPG, PNG, atau WebP.",
          "avatarUpload.max" => "Ukuran foto maksimal 3 MB.",
          "avatarUpload.uploaded" =>
            "Unggahan gagal. Ukuran berkas mungkin melebihi batas server.",
        ],
      );
    } catch (\Illuminate\Validation\ValidationException $e) {
      $this->avatarUpload = null;
      throw $e;
    }

    $this->avatarRemove = false;
    unset($this->avatarUrl);
  }

  /** Tandai foto tersimpan untuk dihapus (berlaku saat Simpan). Foto baru yang belum disimpan ikut dibuang. */
  public function removeAvatar(): void
  {
    if (!$this->avatarEnabled) {
      return;
    }
    $this->authorizeAvatar();

    $this->avatarUpload = null;
    $this->avatarRemove = !$this->isCreateMode;
    unset($this->avatarUrl);
  }

  /** Batalkan foto baru yang belum disimpan; foto tersimpan tetap dipakai. */
  public function discardAvatarUpload(): void
  {
    $this->avatarUpload = null;
    $this->avatarRemove = false;
    unset($this->avatarUrl);
  }

  /**
   * Foto diproses ulang di server: dipotong persegi, diperkecil (maks 512 px), diputar sesuai EXIF, lalu disimpan
   * sebagai WebP. Menyimpan ulang membuang metadata (lokasi GPS di foto ponsel) dan memastikan isinya benar-benar gambar.
   * Mengembalikan jalur di disk "public" (avatars/<id>-<acak>.webp), atau null bila gagal.
   */
  private function storeAvatar($file, User $target): ?string
  {
    $real = $file->getRealPath();
    $bytes = $real ? @file_get_contents($real) : false;
    if (!is_string($bytes) || $bytes === "") {
      return null;
    }

    $name = $target->id . "-" . Str::lower(Str::random(16));
    $disk = Storage::disk("public");

    // Tanpa ekstensi GD: simpan apa adanya (jenis sudah divalidasi saat diunggah).
    if (!function_exists("imagecreatefromstring")) {
      $path = "avatars/" . $name . "." . $file->extension();

      return $disk->put($path, $bytes) ? $path : null;
    }

    $info = @getimagesizefromstring($bytes);
    if ($info === false || $info[0] * $info[1] > 40000000) {
      return null; // bukan gambar, atau terlalu besar untuk diproses dengan aman
    }

    $src = @imagecreatefromstring($bytes);
    if ($src === false) {
      return null;
    }

    if (
      $file->getMimeType() === "image/jpeg" &&
      function_exists("exif_read_data")
    ) {
      $exif = @exif_read_data($real);
      $angle =
        [3 => 180, 6 => -90, 8 => 90][(int) ($exif["Orientation"] ?? 1)] ?? 0;
      if ($angle !== 0) {
        $rotated = imagerotate($src, $angle, 0);
        if ($rotated !== false) {
          $src = $rotated;
        }
      }
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $side = min($w, $h);
    $size = min(512, $side);

    $dst = imagecreatetruecolor($size, $size);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled(
      $dst,
      $src,
      0,
      0,
      intdiv($w - $side, 2),
      intdiv($h - $side, 2),
      $size,
      $size,
      $side,
      $side,
    );

    $webp = function_exists("imagewebp");
    ob_start();
    $ok = $webp ? imagewebp($dst, null, 85) : imagejpeg($dst, null, 85);
    $out = (string) ob_get_clean();
    if (!$ok || $out === "") {
      return null;
    }

    $path = "avatars/" . $name . ($webp ? ".webp" : ".jpg");

    return $disk->put($path, $out) ? $path : null;
  }

  /** Hanya berkas di folder avatars/ yang dihapus; nilai lama dari File Manager (media/...) tidak pernah disentuh. */
  private function deleteOwnAvatarFile(mixed $path): void
  {
    $p = is_string($path) ? $path : "";
    if (
      $p === "" ||
      !str_starts_with($p, "avatars/") ||
      str_contains($p, "..")
    ) {
      return;
    }

    try {
      Storage::disk("public")->delete($p);
    } catch (\Throwable) {
      // berkas yatim tidak boleh menggagalkan penyimpanan profil
    }
  }

  /** Menyimpan foto baru / menghapus foto, bila diminta. Dipanggil sesudah data profil tersimpan. */
  private function applyAvatar(User $target): void
  {
    if (!$this->avatarEnabled) {
      return;
    }

    $newPath = null;
    if ($this->avatarUpload) {
      $newPath = $this->storeAvatar($this->avatarUpload, $target);
      if ($newPath === null) {
        $this->notify(
          "Foto tidak bisa diproses. Gunakan berkas JPG, PNG, atau WebP yang utuh.",
          "error",
        );
        return;
      }
    } elseif (!$this->avatarRemove) {
      return;
    }

    $old = $target->getAttribute(self::AVATAR_COLUMN);

    try {
      DB::transaction(function () use ($target, $newPath) {
        $target->forceFill([self::AVATAR_COLUMN => $newPath])->save();

        // Catatan pemakaian media dari cara lama (foto dipilih lewat File Manager) tidak berlaku lagi.
        MediaUsage::query()
          ->where("usable_type", $target->getMorphClass())
          ->where("usable_id", $target->id)
          ->delete();
      });
    } catch (\Throwable $e) {
      $this->deleteOwnAvatarFile($newPath); // jangan tinggalkan berkas baru tanpa pemilik
      throw $e;
    }

    $this->deleteOwnAvatarFile($old);
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
    // Setiap aksi Livewire bisa dipanggil langsung dari browser: periksa lagi di SINI, sebelum validasi dan sebelum menulis.
    $this->isCreateMode
      ? $this->authorize("create", User::class)
      : $this->authorize("update", $this->user);

    // 1. Cek apakah yang menekan tombol adalah Admin
    $isAdmin = auth()->user()->hasRole("admin");

    // Rapikan masukan dulu: handle dipakai di URL, jadi selalu huruf kecil tanpa spasi tepi.
    $this->name = trim((string) $this->name);
    $this->handle = Str::lower(trim((string) $this->handle));
    $this->job_title = trim((string) $this->job_title);

    // ==========================================
    // BAGIAN 1: ATURAN VALIDASI
    // ==========================================

    // Aturan dasar yang berlaku untuk SEMUA orang
    $rules = [
      "name" => "required|string|max:255",
      "handle" => [
        "required",
        "string",
        "max:255",
        "alpha_dash:ascii",
        Rule::unique("users", "handle")->ignore($this->user->id),
      ],
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
      $rules["email"] = [
        "required",
        "email",
        "max:255",
        Rule::unique("users", "email")->ignore($this->user->id),
      ];
      $rules["roles"] = "required|array|min:1";
      $rules["roles.*"] = "in:writer,editor,admin";
    }

    $this->validate($rules, [
      "handle.alpha_dash" =>
        "Username hanya boleh berisi huruf, angka, strip, dan garis bawah (tanpa spasi).",
      "handle.unique" => "Username ini sudah dipakai pengguna lain.",
      "email.unique" => "Alamat email ini sudah dipakai pengguna lain.",
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
      $this->applyAvatar($newUser);

      $this->notifyFlash("Pengguna baru berhasil ditambahkan", "success");
      return $this->redirect(route("user.index"), navigate: true);
    } else {
      $isSelf = (int) auth()->id() === (int) $this->user->id;

      // Keamanan Ekstra: Cegah user biasa mengedit akun milik orang lain
      abort_if(
        !$isAdmin && !$isSelf,
        403,
        "Anda hanya diizinkan mengubah profil Anda sendiri.",
      );

      // Admin tidak boleh mengunci dirinya sendiri dari sistem.
      if ($isAdmin && $isSelf) {
        if (!in_array("admin", $this->roles, true)) {
          $this->notify(
            "Anda tidak bisa melepas peran admin dari akun Anda sendiri.",
            "error",
          );
          return;
        }
        if (!$this->active) {
          $this->notify(
            "Anda tidak bisa menonaktifkan akun Anda sendiri.",
            "error",
          );
          return;
        }
      }

      $oldHandle = $this->user->handle;
      $oldRoles = $this->user->roles->pluck("name")->sort()->values()->all();
      $oldActive = (bool) $this->user->active;

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

        $newRoles = collect($this->roles)->sort()->values()->all();
        if ($newRoles !== $oldRoles || $oldActive !== (bool) $this->active) {
          activity("user_updates")
            ->performedOn($this->user)
            ->causedBy(auth()->user())
            ->withProperties([
              "peran_lama" => $oldRoles,
              "peran_baru" => $newRoles,
              "aktif_lama" => $oldActive,
              "aktif_baru" => (bool) $this->active,
            ])
            ->log("Peran atau status pengguna diubah");
        }
      }

      $avatarWasChanged = $this->avatarUpload !== null || $this->avatarRemove;
      $this->applyAvatar($this->user);

      // Foto di header navigasi ikut berganti tanpa memuat ulang halaman (hanya bila yang diubah profil sendiri).
      if ($isSelf && $avatarWasChanged) {
        $this->dispatch(
          "avatar-updated",
          url: $this->resolveAvatar(
            $this->user->getAttribute(self::AVATAR_COLUMN),
          ),
        );
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

      $handleBerubah = $this->user->handle !== $oldHandle;

      $this->password = null;
      $this->password_confirmation = null;
      $this->avatarUpload = null;
      $this->avatarRemove = false;
      unset($this->avatarUrl);

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

    $oldAvatar = $this->avatarEnabled
      ? $this->user->getAttribute(self::AVATAR_COLUMN)
      : null;

    try {
      // Catat dan hapus dalam satu transaksi: bila salah satu gagal, tidak ada yang berubah.
      DB::transaction(function () {
        activity("user_updates")
          ->performedOn($this->user)
          ->causedBy(auth()->user())
          ->withProperties([
            "name" => $this->user->name,
            "handle" => $this->user->handle,
            "email" => $this->user->email,
            "peran" => $this->user->roles->pluck("name")->all(),
          ])
          ->log("Pengguna dihapus permanen");

        MediaUsage::query()
          ->where("usable_type", $this->user->getMorphClass())
          ->where("usable_id", $this->user->id)
          ->delete();

        $this->user->delete();
      });
    } catch (QueryException $e) {
      // Biasanya: masih ada data (mis. artikel) yang menunjuk ke pengguna ini.
      report($e);
      $this->notify(
        "Pengguna tidak bisa dihapus karena masih punya data terkait (mis. artikel). Nonaktifkan akunnya sebagai gantinya.",
        "error",
      );
      return;
    }

    $this->deleteOwnAvatarFile($oldAvatar);

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

@php
  $me = auth()->user();
  $isAdmin = (bool) $me?->hasRole("admin");
  $isSelf = !$isCreateMode && (int) $me?->id === (int) $user->id;
  $canEdit = $isAdmin || $isSelf;
  $avatarUrl = $this->avatarUrl;
  $avatarPending = $avatarUpload !== null || $avatarRemove;
  $displayName = $isCreateMode ? "Pengguna Baru" : ($user->name ?: "Nama Staf");
  $initials = $isCreateMode
    ? "NEW"
    : \App\Content\ChatAvatar::initials((string) $user->name);

  $roleCards = [
    [
      "writer",
      "feather",
      "Writer",
      "Dapat membuat dan mengelola draf artikel miliknya sendiri.",
    ],
    [
      "editor",
      "clipboard-check",
      "Editor",
      "Dapat menyunting semua artikel dan menyetujui publikasi.",
    ],
    [
      "admin",
      "shield-check",
      "Admin",
      "Akses penuh ke semua pengaturan sistem dan pengguna.",
    ],
  ];

  $input =
    "focus:ring-forest/20 focus:border-forest w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition-colors focus:ring-2 disabled:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100";
  $label = "mb-1.5 block text-sm font-semibold text-zinc-700 dark:text-zinc-300";
@endphp

<x-slot:title>
  {{
    $isCreateMode
      ? __("Tambah Pengguna")
      : __("Users Detail") . " | " . $user->name
  }}
</x-slot:title>
<x-main-wrapper>
  {{--
    Satu kartu setinggi sisa layar di bawah bilah navigasi (maks). Hanya bagian isi yang menggulir:
    judul + tombol kembali tetap di atas, tombol hapus/edit tetap di bawah.
    fit() mengukur jarak kartu dari atas layar, jadi tinggi bilah navigasi tidak perlu ditulis di sini.
    Tinggi disimpan sebagai variabel CSS di <html> (bukan style inline di kartu) supaya tidak hilang saat Livewire memperbarui DOM.
  --}}
  <div
    x-data="{
      isEditing: {{ $isCreateMode ? 'true' : 'false' }},
      showDeleteModal: false,
      fit() {
        const top = this.$el.getBoundingClientRect().top + window.scrollY;
        const h = Math.max(360, Math.floor(window.innerHeight - top - 24));
        document.documentElement.style.setProperty('--user-card-max-h', h + 'px');
      },
    }"
    x-init="$nextTick(() => fit())"
    x-on:resize.window.debounce.100ms="fit()"
    x-on:close-edit-mode.window="isEditing = false"
    class="mx-auto flex max-h-[var(--user-card-max-h,calc(100dvh_-_7rem))] w-full max-w-7xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
  >
    {{-- ATAS (tetap): judul + kembali --}}
    <div
      class="flex shrink-0 flex-col justify-between gap-4 border-b border-zinc-100 px-4 py-4 md:flex-row md:items-center dark:border-zinc-800"
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
      @if (!$isCreateMode && $isAdmin)
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
          class="text-foresty hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 self-start rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm transition-colors"
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

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
      {{-- TENGAH (menggulir) --}}
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
          <!-- KOLOM KIRI -->
          <div class="space-y-6 lg:sticky lg:top-0 lg:col-span-4 lg:self-start">
            <div
              class="relative flex flex-col items-center overflow-hidden rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
              <div
                class="bg-sage-soft absolute top-0 right-0 left-0 h-24 dark:bg-zinc-800/50"
              ></div>

              {{-- Foto profil: inisial di bawah, foto di atasnya. Bila foto gagal dimuat, <img> dibuang dan inisial terlihat. --}}
              <div class="relative z-10 mt-8 mb-4">
                <div
                  class="bg-mist text-forest relative flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-white text-3xl font-bold shadow-md dark:border-zinc-900"
                >
                  <span>{{ $initials }}</span>
                  @if ($avatarUrl)
                    <img
                      src="{{ $avatarUrl }}"
                      alt="{{ __('Foto profil') }} {{ $displayName }}"
                      class="absolute inset-0 h-full w-full object-cover"
                      onerror="this.remove()"
                    />
                  @endif
                </div>

                @if ($avatarEnabled && ($canEdit || $isCreateMode))
                  <button
                    type="button"
                    x-show="isEditing"
                    x-cloak
                    aria-label="Ganti foto profil"
                    title="Ganti foto profil"
                    x-on:click="$refs.avatarFile.click()"
                    class="bg-forest hover:bg-foresty absolute right-0 bottom-0 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border-2 border-white text-white shadow-md transition-colors dark:border-zinc-900"
                  >
                    <x-dynamic-component
                      :component="'lucide-camera'"
                      class="h-4 w-4"
                    />
                  </button>
                @endif
              </div>

              @if ($avatarEnabled && ($canEdit || $isCreateMode))
                <div
                  x-show="isEditing"
                  x-cloak
                  class="relative z-10 -mt-1 mb-3 flex flex-col items-center gap-1 text-xs"
                >
                  <input
                    type="file"
                    x-ref="avatarFile"
                    wire:model="avatarUpload"
                    accept="image/jpeg,image/png,image/webp"
                    class="sr-only"
                    tabindex="-1"
                    aria-label="Pilih berkas foto profil"
                  />
                  <div class="flex items-center gap-3">
                    <button
                      type="button"
                      x-on:click="$refs.avatarFile.click()"
                      class="text-forest cursor-pointer font-semibold hover:underline"
                    >
                      {{
                        $avatarUrl
                          ? "Ganti foto"
                          : "Pilih foto"
                      }}
                    </button>
                    @if ($avatarUpload)
                      <button
                        type="button"
                        wire:click="discardAvatarUpload"
                        class="cursor-pointer font-semibold text-zinc-500 hover:underline"
                      >
                        Batalkan foto baru
                      </button>
                    @elseif ($avatarUrl)
                      <button
                        type="button"
                        wire:click="removeAvatar"
                        class="cursor-pointer font-semibold text-red-500 hover:underline"
                      >
                        Hapus foto
                      </button>
                    @endif
                  </div>
                  <span
                    wire:loading
                    wire:target="avatarUpload"
                    class="text-zinc-500"
                    >Mengunggah…</span
                  >
                  @error ("avatarUpload")
                    <span
                      class="max-w-[16rem] text-red-500"
                      >{{ $message }}</span
                    >
                  @enderror
                  @if ($avatarPending)
                    <span class="text-zinc-500 dark:text-zinc-400"
                      >Perubahan foto belum disimpan. Tekan "{{
                        $isCreateMode
                          ? "Tambahkan Pengguna"
                          : "Simpan Perubahan"
                      }}".</span
                    >
                  @else
                    <span class="text-zinc-400"
                      >JPG, PNG, atau WebP. Maks. 3 MB.</span
                    >
                  @endif
                </div>
              @elseif (!$avatarEnabled && $isAdmin)
                <p
                  x-show="isEditing"
                  x-cloak
                  class="relative z-10 mb-3 max-w-[16rem] text-xs text-amber-600"
                >Foto profil belum aktif: kolom "avatar" belum ada di tabel users (jalankan php artisan migrate).</p>
              @endif

              <h3
                class="relative z-10 text-lg font-bold text-zinc-900 dark:text-white"
              >
                {{ $displayName }}
              </h3>
              <p class="text-goldy relative z-10 mb-4 text-sm font-medium dark:text-amber-400">
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
                    class="bg-mist text-foresty border-foresty/20 inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium"
                  >
                    <span class="bg-forest h-1.5 w-1.5 rounded-full"></span>
                    Aktif
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

          <!-- KOLOM KANAN -->
          <div
            class="rounded-3xl transition-all duration-300 ease-in-out lg:col-span-8"
            x-bind:class="
              isEditing
                ? 'border-foresty border'
                : 'border border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
            "
          >
            <div
              class="overflow-hidden rounded-3xl bg-white shadow-sm dark:bg-zinc-900"
            >
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
                  <label for="ud-name" class="{{ $label }}">Nama Lengkap</label>
                  {{-- Pembuatan handle otomatis hanya di mode buat; di mode ubah tidak perlu kirim ke server tiap mengetik --}}
                  <input
                    id="ud-name"
                    x-bind:disabled="!isEditing"
                    type="text"
                    @if ($isCreateMode) wire:model.live.debounce.500ms="name" @else wire:model="name" @endif
                    class="{{ $input }}"
                    placeholder="Masukkan nama lengkap..."
                  />
                  @error ("name")
                    <span
                      class="mt-1 block text-xs text-red-500"
                      >{{ $message }}</span
                    >
                  @enderror
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                  <div>
                    <label for="ud-handle" class="{{ $label }}"
                      >Username (Handle)</label
                    >
                    <div class="relative">
                      <span
                        class="absolute inset-y-0 left-0 flex items-center pl-4 font-medium text-zinc-400"
                        >@</span
                      >
                      <input
                        id="ud-handle"
                        x-bind:disabled="!isEditing"
                        type="text"
                        wire:model="handle"
                        class="{{ str_replace("px-4", "pr-4 pl-9", $input) }}"
                        placeholder="username"
                        autocapitalize="none"
                      />
                    </div>
                    @error ("handle")
                      <span
                        class="mt-1 block text-xs text-red-500"
                        >{{ $message }}</span
                      >
                    @enderror
                  </div>

                  <div>
                    <label for="ud-email" class="{{ $label }}"
                      >Alamat Email</label
                    >
                    <input
                      id="ud-email"
                      x-bind:disabled="!isEditing || @js(! $isAdmin)"
                      type="email"
                      wire:model="email"
                      class="{{ $input }}"
                      placeholder="email@organisasi.org"
                    />
                    @unless ($isAdmin)
                      <span class="mt-1 block text-xs text-zinc-500"
                        >Perubahan e-mail hanya oleh admin.</span
                      >
                    @endunless
                    @error ("email")
                      <span
                        class="mt-1 block text-xs text-red-500"
                        >{{ $message }}</span
                      >
                    @enderror
                  </div>
                </div>

                <!-- PASSWORD & KONFIRMASI PASSWORD -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                  <div>
                    <label
                      for="ud-password"
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
                      id="ud-password"
                      x-bind:disabled="!isEditing"
                      type="password"
                      wire:model="password"
                      class="{{ $input }}"
                      placeholder="{{ $isCreateMode ? 'Minimal 8 karakter...' : '••••••••' }}"
                      autocomplete="new-password"
                    />

                    @error ("password")
                      <span
                        class="mt-1 block text-xs text-red-500"
                        >{{ $message }}</span
                      >
                    @enderror
                  </div>

                  <div>
                    <label for="ud-password-confirm" class="{{ $label }}"
                      >Konfirmasi Kata Sandi</label
                    >
                    <input
                      id="ud-password-confirm"
                      x-bind:disabled="!isEditing"
                      type="password"
                      wire:model="password_confirmation"
                      class="{{ $input }}"
                      placeholder="{{ $isCreateMode ? 'Ulangi kata sandi...' : '••••••••' }}"
                      autocomplete="new-password"
                    />
                  </div>
                </div>

                @if ($isAdmin)
                  <div
                    class="my-4 h-px w-full bg-zinc-100 dark:bg-zinc-800"
                  ></div>

                  <!-- ROLES -->
                  <div x-data="{ selectedRoles: @entangle('roles') }">
                    <span
                      class="mb-3 block text-sm font-semibold text-zinc-700 dark:text-zinc-300"
                    >
                      Akses Wewenang
                    </span>

                    <div
                      class="grid grid-cols-1 gap-4 transition-opacity duration-300 sm:grid-cols-3"
                      x-bind:class="
                        !isEditing ? 'pointer-events-none opacity-80' : ''
                      "
                    >
                      @foreach ($roleCards as [$value, $icon, $title, $desc])
                        <label
                          class="group relative block"
                          x-bind:class="isEditing ? 'cursor-pointer' : ''"
                        >
                          <input
                            x-bind:disabled="!isEditing"
                            type="checkbox"
                            wire:model="roles"
                            value="{{ $value }}"
                            class="peer sr-only"
                          />
                          <div
                            class="peer-focus-visible:ring-forest/40 h-full rounded-2xl border-2 bg-white p-4 transition-all duration-200 peer-focus-visible:ring-2 dark:bg-zinc-900"
                            x-bind:class="
                              selectedRoles.includes('{{ $value }}')
                                ? 'border-foresty bg-sage-soft/30 dark:bg-foresty/10'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-foresty/50'
                            "
                          >
                            <div class="mb-1.5 flex items-center gap-2">
                              <x-dynamic-component
                                :component="'lucide-' . $icon"
                                class="h-5 w-5 transition-colors"
                                x-bind:class="
                                  selectedRoles.includes('{{ $value }}')
                                    ? 'text-foresty'
                                    : 'text-zinc-400 group-hover:text-foresty/70'
                                "
                              />
                              <span
                                class="font-bold transition-colors"
                                x-bind:class="
                                  selectedRoles.includes('{{ $value }}')
                                    ? 'text-foresty'
                                    : 'text-zinc-800 dark:text-zinc-200'
                                "
                              >
                                {{ $title }}
                              </span>
                            </div>
                            <p class="text-xs leading-relaxed text-zinc-500 select-none dark:text-zinc-400">{{ $desc }}</p>
                          </div>
                          <div
                            class="absolute top-4 right-4 transition-opacity"
                            x-bind:class="
                              selectedRoles.includes('{{ $value }}')
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
                      @endforeach
                    </div>
                    @error ("roles")
                      <span
                        class="mt-2 block text-xs font-semibold text-red-500"
                        >{{ $message }}</span
                      >
                    @enderror
                  </div>

                  <div>
                    <label for="ud-job" class="{{ $label }}"
                      >Jabatan (Organisasi)</label
                    >
                    <input
                      id="ud-job"
                      x-bind:disabled="!isEditing"
                      type="text"
                      wire:model="job_title"
                      class="{{ $input }}"
                      placeholder="Misal: Program Officer KIA"
                    />
                    @error ("job_title")
                      <span
                        class="mt-1 block text-xs text-red-500"
                        >{{ $message }}</span
                      >
                    @enderror
                  </div>

                  <div
                    class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 p-4 transition-opacity dark:border-zinc-800 dark:bg-zinc-900/50"
                    x-bind:class="!isEditing ? 'opacity-70' : ''"
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
                      x-bind:class="
                        isEditing
                          ? 'cursor-pointer'
                          : 'cursor-default pointer-events-none'
                      "
                    >
                      <input
                        x-bind:disabled="!isEditing"
                        type="checkbox"
                        x-model="checked"
                        class="peer sr-only"
                        aria-label="Status akun aktif"
                      />
                      <div
                        class="peer peer-checked:bg-forest h-6 w-11 rounded-full bg-zinc-200 peer-focus:outline-none after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:border-gray-600 dark:bg-zinc-700"
                      ></div>
                    </label>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- BAWAH (tetap): hapus di kiri, simpan/batal/edit di kanan --}}
      <div
        class="flex w-full shrink-0 items-center justify-between gap-3 border-t border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900/50"
      >
        <!-- KIRI: Tombol Hapus -->
        @if (!$isCreateMode && $isAdmin)
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

        <!-- KANAN: Group Tombol Aksi -->
        <div class="ml-auto flex items-center gap-3">
          @if ($canEdit || $isCreateMode)
            {{-- SIMPAN / TAMBAHKAN --}}
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
              wire:loading.attr="disabled"
              wire:target="save"
              class="bg-mist text-foresty hover:text-goldy hover:bg-forest flex cursor-pointer items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold shadow-md transition-colors select-none disabled:opacity-60"
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

          @if ($isCreateMode)
            <a
              href="{{ route('user.index') }}"
              wire:navigate
              class="flex cursor-pointer items-center rounded-xl bg-zinc-100 px-5 py-2.5 text-sm font-semibold text-zinc-600 transition-colors select-none hover:bg-zinc-200"
              >Batal</a
            >
          @elseif ($canEdit)
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

    {{-- DELETE MODAL (di luar form dan di luar area gulir) --}}
    @if (!$isCreateMode && $isAdmin)
      <div
        x-show="showDeleteModal"
        class="relative z-99"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
        x-cloak
        x-on:keydown.escape.window="showDeleteModal = false"
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
              x-on:click.outside="showDeleteModal = false"
              class="relative w-full max-w-sm transform overflow-hidden rounded-2xl bg-white px-4 pb-4 text-left shadow-xl transition-all sm:my-8 sm:p-6 dark:bg-zinc-900"
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
              <div class="mt-5 flex flex-col gap-3 sm:mt-6 sm:flex-row-reverse">
                <button
                  type="button"
                  class="bg-sage-soft text-forest inline-flex w-full cursor-pointer justify-center rounded-xl px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-red-600 hover:text-white sm:w-auto"
                  x-on:click="
                    $wire.deleteUser();
                    showDeleteModal = false;
                  "
                >
                  Ya, Hapus
                </button>
                <button
                  type="button"
                  x-on:click="showDeleteModal = false"
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
  </div>
</x-main-wrapper>
