# Lab pengujian

Pengujian yang memakai basis data (Eloquent + SQLite), validator, dan model butuh dua pustaka sumber terbuka yang **tidak** ikut kit. Lab ini menyiapkannya tanpa Composer dan tanpa menyentuh proyek Anda.

```
php tests/lab/setup.php        # sekali: mengunduh Laravel 13.30.0 dan Carbon 3.10.0 ke tests/lab/_src
php tests/run-all.php          # semua pengujian
```

Butuh koneksi internet, salah satu dari: ekstensi `zip`, ekstensi `phar` + `zlib`, atau perintah `tar`; untuk mengunduh: `curl` atau (`allow_url_fopen` + `openssl`). `_src` dan `_cache` aman dihapus; `setup.php` membuatnya ulang.

Berkas: `bootstrap.php` (pemuat kelas; kelas `App\*` dimuat dari folder kit ini), `support.php` dan `stubs.php` (pengganti paket Spatie dan penopang SQLite/Schema), `setup.php` (pengunduh).

Pengujian **murni** (tanpa lab) bisa dijalankan sendiri: `php tests/gallery-test.php` dan sejenisnya.
