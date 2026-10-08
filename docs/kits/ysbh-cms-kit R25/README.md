# YSBH CMS Kit — rilis 25 (2026-10-09)

**Zip ini adalah ARSIP LENGKAP kit.** Setiap rilis, pesan chat memuat hanya **berkas baru dan berkas yang berubah** (agar Anda tidak menimpa berkas yang sama berulang kali); isinya **sama persis, bayt demi bayt** dengan isi zip rilis itu (cocok dengan `MANIFEST.sha256`). Bila ada selisih, yang benar adalah zip terbaru; daftar yang berubah selalu ada di `PERUBAHAN.md`.

## Isi (berbentuk folder proyek Laravel)
| Folder | Isi | Disalin ke proyek? |
|---|---|---|
| `app/`, `database/`, `resources/` | semua kode aplikasi (Editor, Content, Snippet, Media, komponen Blade, `editor.js`) | **ya**, ke akar proyek (Replace) |
| `docs/` | panduan: `DEBUG`, `DEBUG-RINGKAS`, `KANVAS`, `BLOK-TOMBOL`, `BLOK-AKORDION`, `BLOK-UNDUHAN`, `BLOK-CALLOUT`, `BLOK-VIDEO`, `BLOK-GALERI`, `DUA-APLIKASI`, `LANDING-TAHAP-1`, `LANDING-RAMPING`, `PENGUJIAN`, `BAHASA`, `HALAMAN-SITUS`, `PASANG-LANDING`, `PASANG-CMS`, `BLOK-ARTIKEL-TERBARU`, `JENIS-BERKAS`, `RESEP-BLOK`, `PASANG`, `ARTIKEL`, `PETA-JALAN`, `USULAN-BLOK`, `SNIPPET`, `MIGRASI`, `REBUILD`, `MEDIA-MANAGER` | tidak (bacaan) |
| `contoh-kode/` | rute, kunci bahasa, `app.js`, morph map, layout: tempel manual | tidak (contoh) |
| `patch/` | patch untuk `HasContentBlocks` | **jangan salin langsung**; pasang dengan `git apply` |
| `tests/` | pemeriksaan logika (opsional, boleh diabaikan); `button-test.php` memeriksa keamanan tautan Tombol | tidak |
| `opsional/` | blade elemen editor LAMA (v1); hanya bila Anda masih memakai editor lama | tidak perlu |
| `MANIFEST.sha256`, `HAPUS.txt`, `PERUBAHAN.md`, `VERSI.txt` | daftar berkas, berkas yang harus dihapus, dan apa yang berubah | tidak |

## Memasang rilis baru
1. Ekstrak zip. Baca `PERUBAHAN.md` (berkas apa saja yang berubah di rilis ini).
2. Salin isi `app/`, `database/`, `resources/` ke akar proyek, pilih **Replace**. Hapus berkas di `HAPUS.txt`.
3. `php artisan view:clear` dan `npm run build` (atau `npm run dev` dijalankan ulang).
4. Cek cepat galat Blade (PowerShell): `php artisan view:cache`, lalu
   `Get-ChildItem storage\framework\views\*.php | ForEach-Object { $r = php -l $_.FullName 2>&1; if ($LASTEXITCODE -ne 0) { $r } }` — tidak ada keluaran = bersih.
5. Cek **tabrakan direktif Blade** (galat diam-diam yang tidak tertangkap langkah 4), dari folder zip yang diekstrak, ke akar proyek Anda: `php tests\blade-scan.php C:\jalur\ke\proyek`. Ia memindai SEMUA berkas Blade proyek (termasuk milik Anda sendiri, mis. JSON-LD di layout) untuk teks seperti `'@context'` yang dikompilasi Blade sebagai direktif. `==> … tidak ada tabrakan direktif` = bersih.

## Mengecek apa yang berbeda di proyek Anda (PowerShell, di akar proyek)
```powershell
$kit = '.\ysbh-cms-kit'   # folder hasil ekstrak
function H($p){ $t=[IO.File]::ReadAllText((Resolve-Path $p)) -replace "`r`n","`n"; $s=[Security.Cryptography.SHA256]::Create(); -join ($s.ComputeHash([Text.Encoding]::UTF8.GetBytes($t)) | ForEach-Object { $_.ToString('x2') }) }
Get-Content "$kit\MANIFEST.sha256" | ForEach-Object {
  $hash, $path = $_ -split '  ', 2
  if (-not (Test-Path $path)) { "HILANG  $path" } elseif ((H $path) -ne $hash) { "BEDA    $path" }
}
Get-Content "$kit\HAPUS.txt" | ForEach-Object { if (Test-Path $_) { "HAPUS   $_" } }
```
- `HILANG` = belum ada di proyek. `HAPUS` = berkas usang yang masih ada. `BEDA` = isinya berbeda dari rilis ini.
- `BEDA` tidak selalu berarti usang: akhir baris Windows (CRLF) sudah dinormalkan oleh skrip, tetapi **Prettier** yang memformat ulang berkas Blade/PHP Anda
  mengubah hash walau isinya sama. Untuk memastikan satu berkas: VS Code → klik kanan berkas di zip → *Select for Compare*, lalu pilih berkas proyek → *Compare with Selected*.
- Berkas yang Anda ubah sendiri (mis. `⚡builder.blade.php`) pasti `BEDA`. Beri tahu saya perubahan Anda; saya gabungkan ke rilis berikutnya supaya tidak tertimpa.

## Aturan kerja
- Pesan memuat berkas **baru/berubah** rilis itu; zip memuat **semuanya**. Berkas di pesan dan di zip identik (hash sama).
- `PERUBAHAN.md` menyebut apa yang berubah dan potongan kecil yang ditambahkan sendiri (rute, `app.js`).
- Setiap rilis: nomor naik, `VERSI.txt` berubah, `MANIFEST.sha256` dibuat ulang.

## Yang dipakai kit dari aplikasi Anda (bukan bagian kit)
Layout `layouts.landing.dynamic-preview`, komponen `<x-table-of-contents>`, komponen render `blocks.render.<tipe>` untuk tiap blok, model `Page`, `Post`, `Category`, `Tag`, `Media`, dan konfigurasi `cms.lucide` serta `app.supported_locales`.
