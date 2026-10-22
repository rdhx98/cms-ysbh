<?php

use App\Livewire\Traits\WithNotifications;
use App\Models\Setting;
use Livewire\Component;

new class extends Component {
  use WithNotifications;

  /** Warna latar halaman publik (salah satu nilai di config('cms.design.site_background')). */
  public string $landingBgColor = "#FBF7EA";

  public function mount(): void
  {
    // Rute sudah dibatasi middleware role:admin; ini sabuk kedua kalau komponen dipasang di tempat lain.
    abort_unless(auth()->user()?->hasRole("admin"), 403);

    $saved = Setting::where("key", "landing_bg_color")->value("value");
    $this->landingBgColor =
      $this->canonicalColor(is_string($saved) ? $saved : "") ??
      $this->defaultColor();
  }

  /** Daftar warna latar yang boleh dipilih: [['value' => '#FBF7EA', 'label' => '...'], ...] */
  private function choices(): array
  {
    return array_values(
      array_filter(
        (array) config("cms.design.site_background", []),
        fn($i) => is_array($i) && isset($i["value"], $i["label"]),
      ),
    );
  }

  private function defaultColor(): string
  {
    return $this->choices()[0]["value"] ?? "#FBF7EA";
  }

  /** Nilai persis seperti di config bila $value termasuk pilihan (tanpa membedakan huruf besar/kecil), selain itu null. */
  private function canonicalColor(string $value): ?string
  {
    foreach ($this->choices() as $item) {
      if (strcasecmp(trim($value), $item["value"]) === 0) {
        return $item["value"];
      }
    }

    return null;
  }

  public function saveAppearance(): void
  {
    abort_unless(auth()->user()?->hasRole("admin"), 403);

    // Nilai radio datang dari browser: hanya pilihan di config yang diterima
    $color = $this->canonicalColor($this->landingBgColor);
    if ($color === null) {
      $this->notify(
        "Warna latar tidak dikenal. Pilih salah satu dari daftar.",
        "error",
      );

      return;
    }

    Setting::updateOrCreate(["key" => "landing_bg_color"], ["value" => $color]);
    $this->landingBgColor = $color;
    $this->notify("Tampilan disimpan.", "success");
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
  @php
    $siteBg = config("cms.design.site_background", []);
    $tabs = [
      "appearance" => ["Tampilan", "paint-brush"],
      "palette" => ["Palet", "swatch"],
      "profile" => ["Profil & SEO", "globe-alt"],
    ];
  @endphp

  <div class="flex min-h-0 w-full flex-col">
    <div
      x-data="{
        tabs: @js(array_keys($tabs)),
        activeTab: 'appearance',
        init() {
          // Tab terakhir bertahan setelah halaman dimuat ulang (rilis 88): alamat #tab-… dulu, lalu ingatan peramban;
          // tab yang sudah tidak ada (mis. Footer, Pengumuman) jatuh ke tab pertama.
          let t = ''
          try {
            const m = location.hash.match(/^#tab-([a-z-]+)$/)
            t = m ? m[1] : localStorage.getItem('ysbh.settings.tab') || ''
          } catch (e) {}
          this.activeTab = this.tabs.includes(t) ? t : this.tabs[0]
          this.$watch('activeTab', (v) => {
            try { localStorage.setItem('ysbh.settings.tab', v) } catch (e) {}
            try { history.replaceState(history.state, '', '#tab-' + v) } catch (e) {}
          })
        },
      }"
      class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
      {{-- Tab --}}
      <div
        class="flex flex-none gap-6 overflow-x-auto border-b border-zinc-200 bg-zinc-50 px-6 pt-4 dark:border-zinc-800 dark:bg-zinc-800/50"
        role="tablist"
      >
        @foreach ($tabs as $key => [$label, $icon])
          <button
            type="button"
            role="tab"
            x-on:click="activeTab = '{{ $key }}'"
            x-bind:aria-selected="(activeTab === '{{ $key }}').toString()"
            :class="activeTab === '{{ $key }}'
              ? 'border-forest text-forest border-b-2 font-bold'
              : 'text-zinc-500 hover:text-zinc-700'"
            class="flex items-center gap-2 px-2 pb-3 text-sm whitespace-nowrap transition-all focus:outline-none"
          >
            <flux:icon variant="outline" :icon="$icon" class="h-4 w-4" />
            {{ $label }}
          </button>
        @endforeach
      </div>

      {{-- Area isi: satu-satunya yang menggulir --}}
      <div class="min-h-0 flex-1 overflow-y-auto">
        {{-- Tampilan --}}
        <form
          x-show="activeTab === 'appearance'"
          wire:submit.prevent="saveAppearance"
          class="p-6"
        >
          <h3 class="mb-4 text-lg font-bold text-zinc-800">
            Pengaturan Visual
          </h3>

          <div class="mb-6">
            <label class="mb-3 block text-sm font-medium text-zinc-700">
              Warna latar belakang halaman publik. Warna ini juga terlihat di
              dalam editor CMS.
            </label>
            <div class="flex flex-wrap gap-4">
              @foreach ($siteBg as $item)
                <label
                  class="group relative cursor-pointer"
                  wire:key="bg-{{ $loop->index }}"
                >
                  <input
                    type="radio"
                    wire:model="landingBgColor"
                    value="{{ $item['value'] }}"
                    class="peer sr-only"
                    name="bg_color"
                  />
                  <div
                    class="peer-checked:border-forest peer-checked:ring-forest/20 h-16 w-24 rounded-xl border-2 border-zinc-200 peer-checked:ring-2"
                    style="background-color: {{ Str::lower($item['value']) }}"
                  ></div>
                  <div
                    class="mt-2 text-center text-xs font-semibold text-zinc-600"
                  >
                    {{ $item["label"] }}
                  </div>
                </label>
              @endforeach
            </div>
          </div>

          <div class="mt-8 flex justify-end border-t border-zinc-200 pt-6">
            <button
              type="submit"
              wire:loading.attr="disabled"
              class="bg-forest hover:bg-forest/90 flex cursor-pointer items-center gap-2 rounded-xl px-6 py-2.5 font-bold text-white shadow-sm transition-all select-none disabled:opacity-60"
            >
              <flux:icon variant="solid" icon="check-circle" class="h-5 w-5" />
              Simpan Tampilan
            </button>
          </div>
        </form>

        {{-- Palet --}}
        <div x-show="activeTab === 'palette'" x-cloak style="display: none">
          <livewire:content.palette-editor lazy wire:key="palette-editor" />
        </div>

        {{-- Profil & SEO (rilis 83) --}}
        <div
          x-show="activeTab === 'profile'"
          x-cloak
          style="display: none"
          class="p-6"
        >
          <p class="mb-4 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" data-settings-pointer>
            <flux:icon
              variant="outline"
              icon="information-circle"
              class="mt-0.5 h-4 w-4 shrink-0"
            />
            <span>
              Kontak, media sosial, dan tautan footer diatur di
              <b>Navigasi &gt; Footer</b>. Banner dan popup diatur di halaman
              <b>Pengumuman</b>.
            </span>
          </p>
          <livewire:content.site-profile-editor
            lazy
            wire:key="site-profile-editor"
          />
        </div>
      </div>
    </div>
  </div>
</x-main-wrapper>
