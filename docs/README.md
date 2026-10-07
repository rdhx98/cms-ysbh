# YSBH CMS Kit — rilis 2 (2026-10-07)

**Zip ini satu-satunya sumber resmi.** Tidak ada lagi berkas lepas yang saya lampirkan di pesan dengan nama yang sama; kalau Anda melihat
berkas bernama sama di luar zip, abaikan (itu sisa lama). Setiap rilis baru = zip baru berisi **seluruh** kit + daftar apa yang berubah.

## Isi (berbentuk folder proyek Laravel)
| Folder | Isi | Disalin ke proyek? |
|---|---|---|
| `app/`, `database/`, `resources/` | semua kode aplikasi (Editor, Content, Snippet, Media, komponen Blade, `editor.js`) | **ya**, ke akar proyek (Replace) |
| `docs/` | panduan: `DEBUG`, `BLOK-TOMBOL`, `RESEP-BLOK`, `PASANG`, `ARTIKEL`, `PETA-JALAN`, `USULAN-BLOK`, `SNIPPET`, `MIGRASI`, `REBUILD`, `MEDIA-MANAGER` | tidak (bacaan) |
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

## Aturan kerja (supaya tidak ganda lagi)
- Pesan saya hanya melampirkan **zip ini**. Rincian tiap berkas ada di zip, bukan di pesan.
- Bila saya menyebut "berkas X berubah", artinya X di zip rilis terbaru, dan tercantum di `PERUBAHAN.md`.
- Setiap rilis: nomor naik, `VERSI.txt` berubah.
