<?php

use Livewire\Component;
use App\Models\Setting;
use App\Livewire\Traits\WithNotifications;

new class extends Component {
  use WithNotifications;
  //
  // --- PENGATURAN TAMPILAN ---
  public string $landingBgColor = "#FBF7EA";

  // --- PENGATURAN UMUM ---
  public string $siteName = "Sinar Bhakti Husada";
  public string $contactEmail = "admin@ysbh.org";

  public function mount()
  {
    // Logika ambil data dari database (seperti contoh sebelumnya)
    $setting = Setting::where("key", "landing_bg_color")->first();
    if ($setting) {
      $this->landingBgColor = $setting->value;
    }
  }

  public function saveSettings()
  {
    // Logika simpan semua data ke database
    // Simpan atau update ke database
    Setting::updateOrCreate(
      ["key" => "landing_bg_color"],
      ["value" => $this->landingBgColor],
    );
    $this->notify("Semua pengaturan berhasil disimpan.", "success");
    // session()->flash('message', 'Semua pengaturan berhasil disimpan.');
  }
};
?>
<x-slot:title>
  {{
    __(
      "ui.header.settings",
    )
  }}
</x-slot:title>

<x-main-wrapper>
  {{-- The best way to take care of the future is to take care of the present moment. - Thich Nhat Hanh --}}
  <div class="mx-auto w-full">
    <!-- 🌟 BUNGKUSAN UTAMA DENGAN ALPINE.JS STATE 🌟 -->
    <div
      x-data="{ activeTab: 'appearance' }"
      class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
      <!-- HEADER & NAVIGASI TAB -->
      <div
        class="flex gap-6 border-b border-zinc-200 bg-zinc-50 px-6 pt-4 dark:border-zinc-800 dark:bg-zinc-800/50"
      >
        <!-- Tombol Tab: Tampilan -->
        <button
          @click="activeTab = 'appearance'"
          :class="activeTab === 'appearance'
            ? 'border-forest text-forest border-b-2 font-bold'
            : 'text-zinc-500 hover:text-zinc-700'"
          class="flex items-center gap-2 px-2 pb-3 text-sm transition-all focus:outline-none"
        >
          <flux:icon variant="outline" icon="paint-brush" class="h-4 w-4" />
          Tampilan
        </button>

        <!-- Tombol Tab: Umum -->
        <button
          @click="activeTab = 'general'"
          :class="activeTab === 'general'
            ? 'border-forest text-forest border-b-2 font-bold'
            : 'text-zinc-500 hover:text-zinc-700'"
          class="flex items-center gap-2 px-2 pb-3 text-sm transition-all focus:outline-none"
        >
          <flux:icon variant="outline" icon="cog-8-tooth" class="h-4 w-4" />
          Umum
        </button>
        <!-- Tombol Tab: Palet -->
        <button
          @click="activeTab = 'palette'"
          :class="activeTab === 'palette'
            ? 'border-forest text-forest border-b-2 font-bold'
            : 'text-zinc-500 hover:text-zinc-700'"
          class="flex items-center gap-2 px-2 pb-3 text-sm transition-all focus:outline-none"
        >
          <flux:icon variant="outline" icon="cog-8-tooth" class="h-4 w-4" />
          Palet
        </button>
      </div>

      <!-- AREA FORM PENGATURAN -->
      <form wire:submit.prevent="saveSettings" class="p-6">
        <!-- 🎨 ISI TAB: TAMPILAN -->
        <div
          x-show="activeTab === 'appearance'"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 translate-y-2"
        >
          <h3 class="mb-4 text-lg font-bold text-zinc-800">
            Pengaturan Visual
          </h3>

          <!-- Opsi Warna (Kode dari jawaban sebelumnya) -->
          <div class="mb-6">
            <label class="mb-3 block text-sm font-medium text-zinc-700"
              >Warna Latar Belakang</label
            >
            <div class="flex gap-4">
              <label class="group relative cursor-pointer">
                <input
                  type="radio"
                  wire:model="landingBgColor"
                  value="#FBF7EA"
                  class="peer sr-only"
                  name="bg_color"
                />
                <div
                  class="peer-checked:border-forest peer-checked:ring-forest/20 h-16 w-24 rounded-xl border-2 border-zinc-200 peer-checked:ring-2"
                  style="background-color: #fbf7ea"
                ></div>
                <div
                  class="mt-2 text-center text-xs font-semibold text-zinc-600"
                >
                  Kertas (Paper)
                </div>
              </label>
              <label class="group relative cursor-pointer">
                <input
                  type="radio"
                  wire:model="landingBgColor"
                  value="#FFFFFF"
                  class="peer sr-only"
                  name="bg_color"
                />
                <div
                  class="peer-checked:border-forest peer-checked:ring-forest/20 h-16 w-24 rounded-xl border-2 border-zinc-200 peer-checked:ring-2"
                  style="background-color: #ffffff"
                ></div>
                <div
                  class="mt-2 text-center text-xs font-semibold text-zinc-600"
                >
                  Putih Bersih
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- ⚙️ ISI TAB: UMUM -->
        <div
          x-show="activeTab === 'general'"
          x-cloak
          style="display: none"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 translate-y-2"
        >
          <h3 class="mb-4 text-lg font-bold text-zinc-800">
            Informasi Dasar Situs
          </h3>

          <div class="max-w-lg space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-zinc-700"
                >Nama Organisasi / Situs</label
              >
              <input
                type="text"
                wire:model="siteName"
                class="focus:ring-forest focus:border-forest w-full rounded-lg border-zinc-300"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-zinc-700"
                >Email Kontak Resmi</label
              >
              <input
                type="email"
                wire:model="contactEmail"
                class="focus:ring-forest focus:border-forest w-full rounded-lg border-zinc-300"
              />
            </div>
          </div>
        </div>
        <div
          x-show="activeTab === 'palette'"
          x-cloak
          style="display: none"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 translate-y-2"
        >
          <livewire:content.palette-editor lazy wire:key="palette-editor" />
        </div>

        <!-- TOMBOL SIMPAN GLOBAL -->
        <div class="mt-8 flex justify-end border-t border-zinc-200 pt-6">
          <button
            type="submit"
            class="bg-forest hover:bg-forest/90 flex cursor-pointer items-center gap-2 rounded-xl px-6 py-2.5 font-bold text-white shadow-sm transition-all select-none"
          >
            <flux:icon variant="solid" icon="check-circle" class="h-5 w-5" />
            Simpan Semua Pengaturan
          </button>
        </div>
      </form>
    </div>
  </div>
</x-main-wrapper>
