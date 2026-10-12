# Menjalankan pengujian

Semua pengujian kit dijalankan dengan dua perintah, dari folder kit:

```
php tests/lab/setup.php     # sekali saja: mengunduh Laravel 13.30.0 dan Carbon 3.10.0 ke tests/lab/_src (lab pengujian)
php tests/run-all.php       # semua pengujian, dengan ringkasan
```

`run-all.php` mencetak satu baris per rangkaian (`ok` / `GAGAL` / `LEWATI`) dan berakhir dengan kode keluar 0 bila tidak ada yang gagal. Opsi: `--only=kata` (hanya rangkaian yang namanya memuat kata itu, mis. `--only=landing`; tanpa kecocokan = galat, kode 2) dan `--strict` (pengujian yang dilewati dihitung gagal).

## Dua jenis pengujian
**Murni** (31 rangkaian: blok, tautan, kartu artikel, kontrak landing, pemeriksa landing, pemindai direktif, ...): hanya butuh PHP CLI; jalan tanpa lab. Contoh: `php tests/gallery-test.php`.

**Berbasis data** (9 rangkaian: `data`, `slug-terlarang`, `kepala-artikel`, `jalur-publik`, `sitemap (data)`, `landing-nav`, `blade-render`, `landing-models`, `snippet`): memakai Eloquent, SQLite, dan validator sungguhan, jadi butuh lab. Tanpa lab mereka **dilewati dengan keterangan**, tidak diam-diam. Lab memuat kelas `App\*` dari **folder kit tempat Anda menjalankannya**, jadi yang diuji adalah kode di folder itu.

## Kebutuhan lab
Koneksi internet saat `setup.php`; untuk mengekstrak salah satu dari ekstensi `zip`, ekstensi `phar` + `zlib`, atau perintah `tar` (Windows 10+ punya); untuk mengunduh `curl` atau (`allow_url_fopen` + `openssl`). `tests/lab/_src` dan `_cache` aman dihapus. `setup.php --force` mengunduh ulang.

## Yang TIDAK dicakup
- **Render Blade** (sejak rilis 32): `tests/blade-render-test.php` merender inspektur, daftar kartu, dan mesin seksi dengan Illuminate\View dari lab (Laravel 13.30, penyaji `tests/lab/blade.php`; komponen `x-editor.toolbars` dan `x-table-of-contents` milik proyek diganti kosong). Itu memeriksa templat dan HTML-nya, bukan perilaku Alpine di peramban.
- **Blade pada Laravel 12.0.0 dan 12.69.3** (kompilasi seluruh tampilan, render komponen, dan uji peramban): memakai lab terpisah di mesin pengembang kit, belum tersedia di sini. `tests/blade-scan.php` (tabrakan direktif) tetap jalan.
- **Komponen Livewire (`⚡`)** tidak dijalankan di Livewire sungguhan; hanya kontrak statis dan templatnya yang diuji.
- `tests/block-move-test.php` (murni, ikut `run-all.php`) menguji aksi struktur outline rilis 43 dengan trait asli; `tests/outline-test.php` (butuh lab) merender outline. `tests/registry-audit.php` dan `tests/block-tree-test.php` butuh berkas dari aplikasi CMS Anda (lihat baris pemakaian di kepala masing-masing); tidak ikut `run-all.php`.
