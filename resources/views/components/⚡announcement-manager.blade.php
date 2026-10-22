<?php

use Livewire\Component;

/**
 * Halaman Pengumuman (rilis 88): banner dan popup di situs publik, satu halaman sendiri di sidebar.
 * Boleh dibuka admin dan editor. Seluruh isinya ada di komponen content.announcement-editor, yang memeriksa hak akses lagi sendiri.
 * Pasang rute dan menu sidebar seperti di contoh-kode/routes.php dan docs/PENGUMUMAN.md.
 */
new class extends Component {
  public function mount(): void
  {
    // Rute sudah dibatasi middleware role:admin|editor; ini sabuk kedua kalau komponen dipasang di tempat lain.
    abort_unless(
      auth()
        ->user()
        ?->hasAnyRole(["admin", "editor"]),
      403,
    );
  }
};
?>
<x-slot:title>
  {{
    __(
      "Pengumuman",
    )
  }}
</x-slot:title>

<x-main-wrapper>
  <div class="flex min-h-0 w-full flex-col" data-announcement-page>
    <livewire:content.announcement-editor lazy wire:key="announcement-editor" />
  </div>
</x-main-wrapper>
