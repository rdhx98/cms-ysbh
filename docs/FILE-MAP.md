Bentuk datanya

Konten halaman berupa peta datar content, kuncinya id blok. Setiap blok adalah {id, type, data}.

Grup Langkah (step-group) hanya menyimpan pengaturan tampilan dan children, yaitu daftar id blok. Anak yang diizinkan hanya card-builder, satu blok per langkah (BlockPalette::STEP_CHILDREN).
Kartu Builder (card-builder) menyimpan bentuk di bawah ini. Elemen dialamatkan sebagai content.<id>.data.cards.<ci>.layout.children.<co>.children.<ei>.
data.grid {cols, margin_bottom}
data.cards[] {id, container{bg, padding, radius, border_*, shadow, hover, align_y, url, url_label},
layout{children[] = kolom}}
kolom {id, type:"column", width, align, children[] = elemen}
elemen {id, type:"element", elementType, data{content, style}}

elementType bisa text, icon, initials, profile_photo, image (baru), atau accordion. button hanya dikenal renderer.

Berkas, per lapisan

1. Definisi panel dan aturan (kit, app/)

Berkas Isi
app/Editor/BlockRegistry.php Daftar kontrol panel untuk blok step-group, card-builder, dan semua elemen kartu, beserta nilai bawaannya. Titik awal hampir semua perubahan.
app/Editor/BlockPalette.php Aturan siapa boleh berada di dalam siapa (STEP_CHILDREN, CONTAINERS) dan menu “+ Tambah blok”.
app/Editor/CardPanel.php Bahan panel kartu: templat kartu (Tumpuk, Ikon+teks, Dokumen, Kosong), kontrol tampilan kartu dan kolom, daftar jenis elemen, batas jumlah (24 kartu, 6 kolom, 20 elemen).
app/Editor/Field.php Jenis kontrol: segmented, swatches, toggle, glyphs (baru), media, urlLink, dan lainnya.
app/Content/Blocks/StepStyle.php Murni: bentuk angka, warna, dan garis Grup Langkah. Daftar putih, kelas Tailwind ditulis utuh.
app/Content/Blocks/ElementStyle.php Murni: ukuran, ketebalan, efek, huruf, Jarak Bawah, dan rasio gambar elemen kartu.
app/Content/Blocks/CoreBlocks.php Penyaring simpan untuk 7 blok inti. Memeriksa alamat gambar, tautan, dan nama ikon di dalam kartu.
app/Content/Blocks/MediaUrls.php Membentuk ulang url dari media_id. Menjangkau seluruh larik bersarang, termasuk elemen kartu.
app/Livewire/Traits/ManagesBlockStructure.php Aksi server kartu: addCardWithPreset, addCardElementAt, moveCard, plus pindah/sisip blok.
patch/HasContentBlocks.php Salinan trait milik proyek Anda yang sudah ditambal: addCardItem, cardLayoutPresets, nilai awal blok.

2. Editor (kit, resources/)

Berkas Isi
components/editor/cards.blade.php Panel daftar kartu → kolom → elemen.
components/editor/add-card-menu.blade.php Popover “Tambah kartu”.
components/editor/container-kids.blade.php Daftar “Isi langkah” di panel Grup Langkah.
components/editor/inspector.blade.php dan field.blade.php Menggambar panel dari daftar Field. field.blade.php memetakan jenis kontrol ke komponen.
components/editor/segmented, swatches, glyphs, media, url-link Komponen kontrol itu sendiri.
components/editor/outline*.blade.php Pohon outline di kiri.
resources/js/editor.js Alpine: wireField, cardPanel, addCardMenu, crumbs, containerKids.

3. Tampilan publik dan kanvas (milik proyek Anda, bukan otomatis dari kit)

Berkas kit Salin ke
opsional/render-card-builder/card-builder.blade.php resources/views/components/blocks/render/card-builder.blade.php (CMS dan landing)
opsional/render-step-group/step-group.blade.php …/blocks/render/step-group.blade.php (CMS dan landing)

components/content/sections.blade.php memilih renderer lewat nama blocks.render.<tipe>.

4. Paket landing dan tes

landing-files.txt adalah daftar berkas yang ikut ke landing. Daftar ini dibuat otomatis oleh php tools/landing-files.php --write, jadi jangan diedit tangan.
Tes: step-style-test.php, element-style-test.php, add-card-test.php, card-panel-test.php, registry-audit.php. Semuanya dijalankan lewat php tests/run-all.php.
Alur
Edit: BlockRegistry membuat daftar Field, inspector menggambarnya, lalu wireField menulis ke $wire.content.
Simpan: ContentWriter memanggil BlockSanitizer, yang memanggil CoreBlocks.
Tampil publik: PublicLookup memanggil BlockSanitizer::forPublic. Hasilnya dirender oleh sections.blade.php, lalu card-builder.blade.php memakai ElementStyle.
Mau mengubah apa, sentuh apa
Opsi atau kontrol baru di elemen kartu: BlockRegistry (field dan defaults), ElementStyle (daftar putih dan kelas), renderer card-builder.blade.php, lalu tes.
Jenis elemen baru: tambah BlockType(..., 'element', ...) dengan defaults di registri. CardPanel mengambilnya otomatis ke menu “+ Tambah elemen”. Tambah cabang di renderer, dan pembersihan di CoreBlocks::cardBuilder bila ada URL atau tautan.
Tampilan Grup Langkah: StepStyle ditambah field di registri step-group dan step-group.blade.php.
Templat kartu baru: CardPanel::PRESETS, PRESET_SHAPES, PRESET_HINTS, dan cardLayoutPresets() di trait.
Perubahan data: tambah kunci baru bersifat opsional dan data lama tetap aman. Mengganti nama kunci butuh migrasi.

Setelah berkas baru masuk, jalankan php tools/landing-files.php --write dan npm run build. Kelas Tailwind baru harus tertulis utuh di berkas PHP agar terbaca pemindai.

Perubahan apa yang Anda rencanakan? Dari situ saya bisa menyebut berkas dan urutan kerjanya.
