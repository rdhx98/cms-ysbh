{{--
  <x-content.page-settings> — panel "Halaman" di kolom kanan builder: judul, slug, status, SEO (halaman/artikel),
  atau nama, kunci, penutup, urutan (snippet). Memakai path tetap ke properti komponen builder ($titles, $slug, ...).
  Slug terisi otomatis dari judul hanya saat membuat baru.
--}}
@props ([
  "type" => "page",
  "locales" => ["id", "en"],
  "isNew" => true,
  "statuses" => null, // label status yang BOLEH dipilih pengguna ini (value => label); null = semua
  "categories" => [], // artikel: id => nama
  "tags" => [], // artikel: [['id' => 1, 'name' => '…'], …]
  "workflow" => [], // artikel: panel alur persetujuan (builder->workflow); kosong untuk halaman/snippet
  "featured" => [] // artikel: thumbnail terpilih (builder->featuredPreview): kind none|media|legacy|missing, id, url, name
])

@php
  $ct = \App\Content\ContentType::from($type);
  $titleLabel = match ($ct) {
    \App\Content\ContentType::Page => "Judul Halaman",
    \App\Content\ContentType::Article => "Judul Artikel",
    \App\Content\ContentType::Snippet => "Nama Snippet",
  };
  $statuses = $statuses ?? $ct->statusLabels();
  $section = "text-[10px] font-extrabold tracking-widest text-gray-400 uppercase";
@endphp

<div
  x-data="pageSettings(@js($isNew && $ct->usesSlug()), @js($locales))"
  class="flex flex-col gap-5"
>
  <x-editor.i18n
    path="titles"
    :label="$titleLabel"
    :locales="$locales"
    placeholder="Ketik judul..."
  />

  @if ($ct->usesSlug())
    <div class="flex flex-col gap-1.5">
      <x-editor.i18n
        path="slug"
        label="Slug (alamat)"
        :locales="$locales"
        :slug="true"
        placeholder="contoh: tentang-kami"
      />
      <p class="text-[10px] leading-relaxed text-gray-400">
        @if ($isNew)
          Terisi otomatis dari judul sampai Anda mengubahnya sendiri.
        @else
          <span class="font-semibold text-amber-600"
            >Mengubah slug halaman yang sudah terbit mematahkan tautan
            lama.</span
          >
        @endif
      </p>
    </div>
  @endif

  @if ($ct->usesKey())
    <x-editor.text path="key" label="Kunci" slug placeholder="contoh: donasi" />
    <p class="-mt-3 text-[10px] leading-relaxed text-gray-400">Pengenal tetap untuk kode dan pengaturan halaman. Huruf kecil, angka, tanda hubung.</p>
    <x-editor.text
      path="description"
      label="Catatan admin"
      :rows="3"
      placeholder="Kapan snippet ini dipakai?"
    />
  @endif

  @if ($ct === \App\Content\ContentType::Article)
    <div class="flex flex-col gap-3">
      <div class="flex flex-col gap-2">
        <span class="{{ $section }}">Kategori</span>
        <x-editor.select
          path="category_id"
          :options="['' => '— Pilih kategori —'] + $categories"
          cast="int"
          width="w-full"
        />
      </div>
      <x-editor.tags path="tags" :options="$tags" label="Tag" />
    </div>

    {{-- Rilis 53: nama penulis di kepala halaman artikel (pengaturan dokumen di kolom content; tanpa migrasi) --}}
    <div class="flex flex-col gap-1.5" data-show-author>
      <x-editor.toggle
        path="settings.show_author"
        label="Tampilkan nama penulis"
        :live="true"
      />
      <p class="text-[10px] leading-relaxed text-gray-400">Tampil di samping tanggal sebagai "Ditulis oleh …": penulis utama, lalu penulis tambahan. Mati = nama tidak ditampilkan (bawaan).</p>
    </div>

    {{-- Rilis 54: artikel terkait (berdasarkan tag) tampil bawaan; centang untuk menyembunyikannya di artikel ini (pengaturan dokumen hide_related; tanpa migrasi) --}}
    <div class="flex flex-col gap-1.5" data-hide-related>
      <x-editor.toggle
        path="settings.hide_related"
        label="Sembunyikan artikel terkait"
        :live="true"
      />
      <p class="text-[10px] leading-relaxed text-gray-400">Di bawah artikel tampil hingga 3 artikel lain yang berbagi tag. Centang bila tidak ingin menampilkannya di artikel ini. Artikel tanpa tag tidak menampilkan bagian ini.</p>
    </div>

    {{-- Thumbnail (rilis 40): dari gambar yang dipasang di isi artikel; bila tidak ada, dari File Manager --}}
    <x-content.thumbnail-picker
      :featured="$featured"
      :editable="$workflow['canEdit'] ?? true"
    />

    {{-- Rilis 56: thumbnail dari gambar yang sudah dipasang di isi artikel akan tampil dua kali; centang untuk menyembunyikan gambar lebar di halaman (pengaturan dokumen hide_hero; tanpa migrasi) --}}
    <div class="flex flex-col gap-1.5" data-hide-hero>
      <x-editor.toggle
        path="settings.hide_hero"
        label="Sembunyikan thumbnail di halaman artikel"
        :live="true"
      />
      <p class="text-[10px] leading-relaxed text-gray-400">Thumbnail tetap dipakai di kartu artikel dan pratinjau media sosial. Centang bila gambarnya sudah dipasang di isi artikel, supaya tidak tampil dua kali.</p>
    </div>

    <p class="rounded-md bg-amber-50 px-2.5 py-2 text-[10px] leading-relaxed text-amber-800">Builder menyimpan isi artikel sebagai <b>blok</b>. Pakai artikel percobaan sampai halaman publik artikel mendukung blok.</p>
  @endif

  @if ($ct === \App\Content\ContentType::Article && $workflow !== [])
    {{-- Artikel: status TIDAK dipilih bebas; berubah lewat alur persetujuan (ajukan -> tinjau -> terbit/jadwal/kembali/tolak), yang kini
         ada di tab Review (rilis 49 rev 3). Di sini hanya ringkasan status dengan tautan ke tab itu. --}}
    <div class="flex flex-col gap-2" data-status-line>
      <span class="{{ $section }}">Status</span>
      <div
        class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3"
      >
        <x-content.status-badge
          :tone="$workflow['tone']"
          :label="$workflow['label']"
        />
        @if ($workflow["status"] === "scheduled" && $workflow["publishedAt"])
          <span class="text-[11px] font-semibold text-sky-800"
            >Terbit {{ $workflow["publishedAt"] }}</span
          >
        @elseif ($workflow["status"] === "published" && $workflow["publishedAt"])
          <span class="text-[11px] text-gray-500"
            >sejak {{ $workflow["publishedAt"] }}</span
          >
        @endif
        <button
          type="button"
          x-on:click="$store.editor.tab = 'review'"
          class="ml-auto text-[11px] font-bold text-emerald-800 underline"
          data-open-review-link
        >
          Buka tab Review
        </button>
      </div>
    </div>
  @else
    <div class="flex flex-col gap-2">
      <span class="{{ $section }}">Status</span>
      @if (count($statuses) <= 1 && $ct !== \App\Content\ContentType::Article)
        {{-- Rilis 49 rev 4: online/offline halaman dan snippet hanya diubah admin/editor; yang lain melihat status saja. --}}
        <div
          class="flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3"
          data-status-readonly
        >
          <span class="text-[12px] font-bold text-gray-700">{{
            reset(
              $statuses,
            )
          }}</span>
        </div>
        <p class="text-[10px] leading-relaxed text-gray-400">Hanya admin atau editor yang dapat mengubah status online/offline.</p>
      @elseif (count($statuses) <= 3 && $ct !== \App\Content\ContentType::Article)
        <x-editor.segmented path="status" :options="$statuses" :live="true" />
      @else
        <x-editor.select
          path="status"
          :options="$statuses"
          :live="true"
          width="w-full"
        />
      @endif
    </div>
  @endif

  {{-- Rilis 62: posisi daftar isi (TOC) halaman/artikel: settings.toc_position = left | right | hidden (bawaan right; tanpa migrasi).
       Daftar isi hanya dibuat bila ada Judul bertanda "masuk daftar isi" dan layar cukup lebar (sisi kolom >= ~150 px). --}}
  @if ($ct !== \App\Content\ContentType::Snippet)
    <div class="flex flex-col gap-1.5" data-toc-position>
      <span class="{{ $section }}">Daftar isi</span>
      <x-editor.segmented
        path="settings.toc_position"
        :options="[
          'left' => 'Kiri',
          'right' => 'Kanan',
          'hidden' => 'Sembunyikan',
        ]"
        default="right"
        :live="true"
      />
      <p class="text-[10px] leading-relaxed text-gray-400">Melayang di samping kolom isi, menyatu dengan latar halaman. Tampil di layar lebar saja (sisi kolom cukup lebar) dan menyingkir saat melewati gambar layar penuh.</p>
    </div>
  @endif

  @if ($ct->usesKey())
    <div
      class="flex flex-col gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3"
    >
      <span class="{{ $section }}">Penutup halaman</span>
      <x-editor.toggle
        path="is_closing"
        label="Tampil otomatis di akhir semua halaman"
        :live="true"
      />
      <x-editor.text path="sort_order" label="Urutan" type="number" />
    </div>
  @endif

  @if ($ct->usesMeta())
    <div
      class="flex flex-col gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
    >
      <span class="{{ $section }}">SEO</span>
      <x-editor.i18n
        path="meta_title"
        label="Judul SEO"
        :locales="$locales"
        :counter="70"
        placeholder="Kosong = memakai judul"
      />
      <x-editor.i18n
        path="meta_description"
        label="Deskripsi SEO"
        :locales="$locales"
        :multi="true"
        :rows="3"
        :counter="160"
        placeholder="Ringkasan untuk hasil pencarian"
      />
    </div>
  @endif
</div>
