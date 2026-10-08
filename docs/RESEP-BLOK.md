# Resep menambah blok baru (sejak rilis 4: modul)

Blok baru = **dua berkas baru**. Registri (inspektur), palet (menu tambah blok), dan pembersih data menemukannya otomatis; tidak ada berkas lama yang diubah.

1. **Modul**: `app/Editor/Blocks/<Nama>Block.php`, kelas yang mengimplementasikan `BlockModule` (contoh: `AccordionBlock.php`).
```php
final class VideoBlock implements BlockModule
{
    public static function definition(): BlockType      // tipe, label, ikon, kontrol di inspektur, nilai bawaan
    {
        return new BlockType('video-builder', 'Video', 'play', 'block', [
            Field::text('data.url', 'Alamat video'), /* … */
        ], defaults: ['url' => '', /* … */]);
    }
    public static function placement(): array           // menu: grup, tingkat atas?, boleh di dalam kolom?
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }
    public static function sanitize(array $data, array $locales): array   // idempoten; nilai tak sah -> bawaan
    {
        return ['url' => /* validasi */];
    }
}
```
2. **Tampilan publik**: `resources/views/components/blocks/render/<tipe>.blade.php` (props `block`, `data`, `lang`, `allContent`). Bersihkan lagi dengan `BlockSanitizer::forType('<tipe>', $data, $locales)`; cetak dengan `{{ }}`; kelas CSS dari daftar tetap. `$canvasMode` bernilai `true` bila sedang dirender di kanvas editor.
3. **Pemeriksaan**: salin pola `tests/accordion-test.php` (modul ↔ pembersih ↔ palet) dan uji tampilan dengan data berbahaya.

Kelas bantu (peta gaya, pengubah teks) taruh di `app/Content/Blocks/`.

**Kapan masih perlu mengubah berkas lama?** Hanya bila blok butuh **jenis kontrol baru** di inspektur (mis. daftar gambar): tambahkan `Field::...` di `app/Editor/Field.php` dan satu `@case` di `field.blade.php`, sekali saja. Kontrol yang sudah ada: teks, teks per bahasa (satu/banyak baris), teks berformat, pilihan segmented/select/swatches, ikon, sakelar, media (`Field::media($key, $label, '')` = semua berkas, dengan nama berkas ditampilkan; bawaan `'image'`), tautan, dan daftar berulang.

Catatan: blok **Tombol** masih bawaan (bukan modul); perilakunya sama.

## Jebakan yang sudah pernah terjadi (jangan ulangi)
| Jebakan | Akibat | Pencegah |
|---|---|---|
| komentar `//` di baris `@props([...])` yang berisi prop lain | sisa prop **tertelan**, variabel tak terdefinisi | jangan beri komentar di baris props |
| ternary bersarang tanpa kurung (`a ? b : c ?: d`) | **galat fatal PHP 8**, halaman publik mati | kompilasi semua Blade lalu lint hasilnya (`php -l` pada `.blade.php` **tidak** menangkapnya); lihat README langkah 4 |
| `$a + compact('x')` untuk menimpa kunci | kunci kiri menang; penyaringan tidak berlaku | `array_replace` |
| path kontrol berbasis indeks + penulisan tertunda | nilai lama menimpa item lain / item hantu | `typeAt` kini membaca nilai **saat menembak** dan membatalkan bila path hilang |
| `{...objek}` untuk memperluas data Alpine | getter berubah menjadi nilai beku | `Object.defineProperties(..., getOwnPropertyDescriptors(...))` (`extend`) |
| memercayai `url` dari browser untuk berkas | tautan sisipan | hanya `media_id`, URL dari model `Media` |
| regex validasi berjangkar `$` tanpa modifier `D` | baris baru di ujung (`"abc\n"`) **lolos** | selalu `/…$/D` untuk token, slug, kunci, id |
| memanggil komponen render yang belum ada (`x-dynamic-component`) | seluruh halaman publik jatuh karena SATU blok | `x-content.sections` memeriksa `view()->exists(...)` dan melewati/menandai blok itu |
| `strip_tags` pada teks medis | "usia <5 tahun" **terpotong** sebagai tag | simpan teks apa adanya, escape saat dicetak (`singleLines`/`multiLines`) |
| memeriksa pengaman hanya pada kasus bersih | pengaman bisa dihapus tanpa ada tes yang gagal | uji dengan **merusak** pengaman di salinan (mutasi); tes harus gagal |
| direktif Blade menempel pada kata (`p@if (...)`) | `@if` **tidak dikenali** (Blade hanya mengenali direktif yang tidak menempel pada karakter kata) tetapi `@endif` dikompilasi: **galat sintaks** | beri spasi atau susun nilai di `@php`; kompilasi semua Blade (README langkah 4) |
| mengubah komponen yang dipakai bidang lain tanpa menguji bidang itu | bidang gambar lama ikut berubah perilaku | uji **kedua** bentuk (mis. `accept='image'` tetap mengirim `allowedFileType`, `accept=''` tidak) |
| batas "wajar" yang menolak data nyata (telepon ≥ 5 angka) | nomor darurat **119 / 112 / 110 ditolak**, padahal itulah yang paling dipakai di situs kesehatan | uji dengan **contoh dunia nyata** domain Anda, bukan hanya angka yang terbayang; kini minimal 3 angka |
| `<a` sebagai kata kunci pencarian di pengujian HTML | juga cocok dengan `<aside>`, sehingga pemeriksaan "tidak ada tautan" salah | cocokkan tag dengan `<a\s`, atau urai HTML dengan pengurai sungguhan |
| alamat dari penulis dipasang langsung ke `src`/`href` (setelah "divalidasi") | validasi yang lolos lebih longgar dari yang dipakai peramban; sisipan lewat bagian alamat yang tidak diperiksa | **bangun ulang** alamat dari komponen yang lolos pola ketat (ID), jangan pernah memakai teks aslinya (`VideoUrl`) |
| kelas Tailwind hanya ada di berkas PHP (peta gaya) dan CSS tidak memindai `app/` | kelas tidak dihasilkan: tampilan polos / tinggi nol | Tailwind v4 memindai seluruh proyek secara bawaan; bila CSS Anda membatasinya, tambahkan `@source '../../app';` |
| pengujian privasi hanya memeriksa HTML | iframe bisa saja dimuat sejak awal tanpa terdeteksi | uji **permintaan jaringan** di browser sebelum dan sesudah klik (lihat `video_browser`), dan rusak pengamannya untuk membuktikan pengujiannya peka |
| `'@context'` (JSON-LD) atau teks `@nama` lain di dalam `{!! !!}` / `{{ }}` / tanda kutip pada Blade | Blade memproses direktif **sebelum** ekspresi echo: pada Laravel 12.69 `@context` dikompilasi sebagai direktif dan JSON-LD kehilangan `"@context"` (lolos di lab dengan Laravel 12.0.0) | susun JSON di dalam `@php … @endphp` lalu cetak variabelnya; jalankan `php tests/blade-scan.php`; **uji di versi Laravel TERBARU**, bukan hanya versi lama |
| pengujian JSON-LD hanya memeriksa sebagian kunci (`@type`, jumlah butir) | kunci yang hilang (`@context`) tidak terdeteksi | bandingkan **kumpulan kunci persis** dan nilai tiap kunci |
| pilihan segmented bernilai angka ditulis sebagai peta (`['5' => '5']`) | PHP mengubah kunci numerik menjadi bilangan bulat; inspektur menulis `5` (angka), data jadi bercampur teks dan angka | tulis sebagai **daftar** (`['2', '3', '4']`) agar nilai tetap teks, dan pembersih menerima keduanya |
| klik "di luar" dialog diuji pada koordinat di dalam kotaknya | klik di dalam kotak tidak menutup; yang menutup hanya klik pada latar (`::backdrop`) | uji dengan klik di luar kotak; `<dialog>` memberi `target` = dialog hanya untuk klik di latar |
| carousel otomatis tanpa jeda dan tanpa menghormati "kurangi gerakan" | melanggar aksesibilitas (gerakan yang tak bisa dihentikan) | berhenti saat disorot/difokus, tombol Jeda, dan jangan jalankan bila `prefers-reduced-motion` aktif; uji dengan `reduced_motion` di browser |
| kode yang dipakai di KEDUA aplikasi memanggil `route('nama')` atau fungsi/rute yang hanya ada di satu aplikasi | galat "route not found" di aplikasi lain (CMS tidak punya `page.show`/`article.show`) | bentuk alamat dari **templat konfigurasi** (`config('cms.public')`) dan jatuhkan ke null bila tidak ada; jangan melempar galat |
| berkas baru di kit tidak ikut ke landing | halaman publik memanggil kelas yang tidak ada | `php tools/landing-files.php --write` setelah menambah blok, dan `tests/landing-pack-test.php` memeriksa daftar tidak usang |
| templat alamat boleh diawali `//` | alamat protokol-relatif menuju host lain | `LinkResolver::publicUrl` menolaknya; jangan melonggarkan |
| `html_entity_decode` setelah `strip_tags` pada ringkasan, lalu dicetak tanpa escape | masukan `&lt;script&gt;` menjadi `<script>` dan **lolos** sebagai tag | keluaran ringkasan adalah TEKS: cetak selalu dengan `{{ }}`; uji dengan muatan berbasis entitas, bukan hanya tag |
| `strip_tags` pada HTML beberapa blok | kata antar-blok menempel ("Imunisasi" + "Dasar" menjadi "ImunisasiDasar") | ubah penutup blok dan `<br>` menjadi spasi sebelum melepas tag |
| fitur ada di model/aturan tetapi tidak ada kode yang MEMAKAINYA di jalur publik (snippet penutup) | fitur tampak jadi di editor tetapi tidak muncul di situs | untuk setiap fitur editor, tulis dan uji jalur tampilannya di sisi publik (`PublicLookup::document`) |
| definisi blok yang memanggil basis data (daftar kategori di inspektur) | `Modules::for()` memanggil `definition()` di landing: kueri pada tiap pemanggilan | definisi harus statis; data dinamis dibaca di renderer, bukan di definisi |
| model landing tanpa `SoftDeletes` | berkas yang dihapus di CMS tetap tampil di situs | contoh model baca-saja memakainya dan pengujiannya memeriksa |

