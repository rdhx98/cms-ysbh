<?php

use Livewire\Component;
use App\Models\Setting;
use App\Livewire\Traits\WithNotifications;

new class extends Component
{
    use WithNotifications;
    //
    // --- PENGATURAN TAMPILAN ---
    public string $landingBgColor = '#FBF7EA';

    // --- PENGATURAN UMUM ---
    public string $siteName = 'Sinar Bhakti Husada';
    public string $contactEmail = 'admin@ysbh.org';

    public function mount()
    {
        // Logika ambil data dari database (seperti contoh sebelumnya)
        $setting = Setting::where('key', 'landing_bg_color')->first();
        if ($setting) {
            $this->landingBgColor = $setting->value;
        }
    }

    public function saveSettings()
    {
        // Logika simpan semua data ke database
        // Simpan atau update ke database
        Setting::updateOrCreate(
            ['key' => 'landing_bg_color'],
            ['value' => $this->landingBgColor]
        );
        $this->notify('Semua pengaturan berhasil disimpan.', 'success');
        // session()->flash('message', 'Semua pengaturan berhasil disimpan.');
    }
};
?>
<x-slot:title>{{ __('ui.header.settings') }}</x-slot:title>

<x-main-wrapper>
        {{-- The best way to take care of the future is to take care of the present moment. - Thich Nhat Hanh --}}
        <div class="w-full mx-auto">

            <!-- 🌟 BUNGKUSAN UTAMA DENGAN ALPINE.JS STATE 🌟 -->
            <div x-data="{ activeTab: 'appearance' }" class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">

                <!-- HEADER & NAVIGASI TAB -->
                <div class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 flex gap-6 px-6 pt-4">

                    <!-- Tombol Tab: Tampilan -->
                    <button
                        @click="activeTab = 'appearance'"
                        :class="activeTab === 'appearance' ? 'border-forest text-forest border-b-2 font-bold' : 'text-zinc-500 hover:text-zinc-700'"
                        class="pb-3 px-2 text-sm transition-all focus:outline-none flex items-center gap-2">
                        <flux:icon variant="outline" icon="paint-brush" class="w-4 h-4" />
                        Tampilan
                    </button>

                    <!-- Tombol Tab: Umum -->
                    <button
                        @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-forest text-forest border-b-2 font-bold' : 'text-zinc-500 hover:text-zinc-700'"
                        class="pb-3 px-2 text-sm transition-all focus:outline-none flex items-center gap-2">
                        <flux:icon variant="outline" icon="cog-8-tooth" class="w-4 h-4" />
                        Umum
                    </button>

                </div>

                <!-- AREA FORM PENGATURAN -->
                <form wire:submit.prevent="saveSettings" class="p-6">

                    <!-- 🎨 ISI TAB: TAMPILAN -->
                    <div x-show="activeTab === 'appearance'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                        <h3 class="text-lg font-bold text-zinc-800 mb-4">Pengaturan Visual</h3>

                        <!-- Opsi Warna (Kode dari jawaban sebelumnya) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-zinc-700 mb-3">Warna Latar Belakang</label>
                            <div class="flex gap-4">
                                <label class="relative cursor-pointer group">
                                    <input type="radio" wire:model="landingBgColor" value="#FBF7EA" class="peer sr-only" name="bg_color">
                                    <div class="w-24 h-16 rounded-xl border-2 peer-checked:border-forest peer-checked:ring-2 peer-checked:ring-forest/20 border-zinc-200" style="background-color: #FBF7EA;"></div>
                                    <div class="mt-2 text-center text-xs font-semibold text-zinc-600">Kertas (Paper)</div>
                                </label>
                                <label class="relative cursor-pointer group">
                                    <input type="radio" wire:model="landingBgColor" value="#FFFFFF" class="peer sr-only" name="bg_color">
                                    <div class="w-24 h-16 rounded-xl border-2 peer-checked:border-forest peer-checked:ring-2 peer-checked:ring-forest/20 border-zinc-200" style="background-color: #FFFFFF;"></div>
                                    <div class="mt-2 text-center text-xs font-semibold text-zinc-600">Putih Bersih</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ⚙️ ISI TAB: UMUM -->
                    <div x-show="activeTab === 'general'" x-cloak style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                        <h3 class="text-lg font-bold text-zinc-800 mb-4">Informasi Dasar Situs</h3>

                        <div class="space-y-4 max-w-lg">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1">Nama Organisasi / Situs</label>
                                <input type="text" wire:model="siteName" class="w-full border-zinc-300 rounded-lg focus:ring-forest focus:border-forest">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1">Email Kontak Resmi</label>
                                <input type="email" wire:model="contactEmail" class="w-full border-zinc-300 rounded-lg focus:ring-forest focus:border-forest">
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN GLOBAL -->
                    <div class="mt-8 pt-6 border-t border-zinc-200 flex justify-end">
                        <button type="submit" class="select-none cursor-pointer px-6 py-2.5 bg-forest text-white font-bold rounded-xl hover:bg-forest/90 transition-all flex items-center gap-2 shadow-sm">
                            <flux:icon variant="solid" icon="check-circle" class="w-5 h-5" />
                            Simpan Semua Pengaturan
                        </button>
                    </div>

                </form>
            </div>
        </div>

</x-main-wrapper>
