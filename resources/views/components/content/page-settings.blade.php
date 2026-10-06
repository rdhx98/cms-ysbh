{{--
  <x-content.page-settings> — panel "Halaman" di kolom kanan builder: judul, slug, status, SEO (halaman/artikel),
  atau nama, kunci, penutup, urutan (snippet). Memakai path tetap ke properti komponen builder ($titles, $slug, ...).
  Slug terisi otomatis dari judul hanya saat membuat baru.
--}}
@props ([ "type" => "page", "locales" => ["id", "en"], "isNew" => true ])

@php
  $ct = \App\Content\ContentType::from($type);
  $titleLabel = match ($ct) {
    \App\Content\ContentType::Page => "Judul Halaman",
    \App\Content\ContentType::Article => "Judul Artikel",
    \App\Content\ContentType::Snippet => "Nama Snippet",
  };
  $statuses = $ct->statusLabels();
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

  <div class="flex flex-col gap-2">
    <span class="{{ $section }}">Status</span>
    @if (count($statuses) <= 3)
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
