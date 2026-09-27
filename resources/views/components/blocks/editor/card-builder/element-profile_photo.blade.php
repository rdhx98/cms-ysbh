@php
  $imgUrl = $el['data']['content']['url'] ?? '';
  $imgAlt = $el['data']['content']['alt'] ?? '';
  $imgSize = $style['size'] ?? 'w-16 h-16 md:w-20 md:h-20'; // Sedang sebagai default
  $imgRadius = $style['radius'] ?? 'rounded-full';
  $imgBorder = $style['border'] ?? 'border-0';
  $imgBorderColor = $style['border_color'] ?? 'border-transparent';
@endphp
<div class="mb-3 flex items-center justify-between">
  <span class="bg-indigo-100 text-indigo-700 rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest uppercase">Foto Profil</span>
</div>

{{-- Area Pratinjau & Input Data --}}
<div class="mb-3 flex items-start gap-3">
  {{-- Kotak Pratinjau (Live Preview) --}}
  {{-- <div class="shrink-0 flex items-center justify-center bg-gray-100 object-cover shadow-sm transition-all {{ $imgSize }} {{ $imgRadius }} {{ $imgBorder }} {{ $imgBorderColor }}">
    @if($imgUrl)
      <img src="{{ $imgUrl }}" class="h-full w-full object-cover {{ $imgRadius }}" alt="Preview">
    @else
      <x-dynamic-component component="lucide-image" class="h-6 w-6 text-gray-400" />
    @endif
  </div> --}}

  {{-- Input URL & Alt Text --}}
  <div class="flex-1 space-y-2">
    {{-- Opsi 1: Tombol Upload (Via Endpoint API Controller Anda) --}}
    {{-- <div x-data="{ isUploading: false }" class="w-full">
      <label
        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs font-bold shadow-sm transition-colors"
        x-bind:class="isUploading ? 'cursor-wait bg-gray-200 text-gray-400' : 'hover:border-foresty hover:bg-sage-soft hover:text-foresty text-gray-600'"
      >
        <!-- Ikon: Berubah jadi spinner saat loading -->
        <x-dynamic-component component="lucide-upload-cloud" class="h-4 w-4 shrink-0" x-show="!isUploading" />
        <x-dynamic-component component="lucide-loader-2" class="h-4 w-4 shrink-0 animate-spin" x-show="isUploading" x-cloak />

        <!-- Teks: Berubah saat proses upload -->
        <span class="truncate" x-text="isUploading ? 'Mengunggah...' : 'Unggah Foto dari Komputer'"></span>

        <input
          type="file"
          accept="image/png, image/jpeg, image/webp, image/gif"
          class="hidden"
          :disabled="isUploading"
          x-on:change="
            const file = $event.target.files[0];
            if(!file) return;

            isUploading = true;

            // Siapkan data untuk dikirim ke Controller
            let formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}'); // Wajib untuk Laravel POST

            // Kirim ke route yang Anda buat
            fetch('{{ route('editor.upload-image') }}', {
              method: 'POST',
              body: formData
            })
            .then(res => {
              if(!res.ok) throw new Error('Gagal mengunggah gambar.');
              return res.json();
            })
            .then(data => {
              // Update Livewire dengan URL final dari server
              $wire.set('{{ $elPath }}.data.content.url', data.url);

              $wire.set('{{$elPath }}.data.content.media_id', data.id);
            })
              
            .catch(err => {
              alert(err.message);
            })
            .finally(() => {
              isUploading = false;
              $event.target.value = ''; // Reset input agar bisa upload file yang sama lagi jika perlu
            });
          "
        />
      </label>
    </div> --}}
    {{-- Tombol Buka File Manager --}}
    <button
      type="button"
      x-on:click="$dispatch('openFileManager', { 
          {{-- targetEvent: 'mediaSelectedForCard',  --}}
          targetEvent: 'mediaSelected',
          {{-- targetComponentId: '{{ $blockId }}-{{ $elPath }}'  --}}
          targetComponentId: '{{ $elPath }}.data.content' 
      })"
      class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold shadow-sm transition-colors hover:border-foresty hover:bg-sage-soft hover:text-foresty text-gray-600"
    >
      <x-dynamic-component component="lucide-folder-search" class="h-4 w-4 shrink-0" />
      <span>Jelajahi File Manager</span>
    </button>
    <input
      type="text"
      wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.alt"
      placeholder="Teks Alternatif (Untuk SEO & Tunanetra)"
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm"
    />
  </div>
</div>

{{-- Pengaturan Gaya Visual --}}
<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  {{-- Ukuran --}}
  {{-- <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-12 h-12')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-12 h-12' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Kecil</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-20 h-20')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-20 h-20' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sedang</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-32 h-32' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar</button>
    </div>
  </div> --}}
  {{-- Ukuran Foto Profil Responsif --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Standar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10 md:w-12 md:h-12')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-10 h-10 md:w-12 md:h-12' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-2.5 w-2.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Sedang" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16 md:w-20 md:h-20')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-16 h-16 md:w-20 md:h-20' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-3.5 w-3.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Besar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-24 h-24 md:w-32 md:h-32')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-24 h-24 md:w-32 md:h-32' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Paling Besar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32 md:w-48 md:h-48')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-32 h-32 md:w-48 md:h-48' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-5 w-5 rounded-sm bg-current transition-all"></div>
      </button>
    </div>
  </div>

  {{-- Bentuk --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Bentuk</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Kotak" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-md')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-md' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-md border-2 border-current"></div>
      </button>
      <button type="button" title="Agak Bulat" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[20px]')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-[20px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-[8px] border-2 border-current"></div>
      </button>
      <button type="button" title="Lingkaran" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full border-2 border-current"></div>
      </button>
    </div>
  </div>

  {{-- Ketebalan Garis --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Garis Tepi</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-0')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tanpa Garis</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-2')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tipis (2px)</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-4')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tebal (4px)</button>
    </div>
  </div>

  {{-- Warna Garis --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Garis</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Transparan" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-transparent')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
          <div class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></div>
        </div>
      </button>
      <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-foresty')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
      </button>
      <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-coral')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
      <button type="button" title="Abu-abu" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-gray-200')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-gray-200' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
      </button>
    </div>
  </div>
</div>