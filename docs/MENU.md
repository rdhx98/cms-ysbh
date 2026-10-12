# Menu Builder (rilis 28)

`opsional/menu-builder.blade.php` adalah Menu Builder yang dibangun ulang mengikuti konvensi kit. **Milik Anda**: salin isinya **menimpa** berkas menu builder lama (jalur dan nama komponen Anda tetap, jadi rute tidak berubah). Pemasang tidak menyentuhnya. Logikanya ada di `app/Content/MenuTarget.php` (murni, dipasang pemasang CMS).

## Yang berubah dari versi lama
| | Lama | Sekarang |
|---|---|---|
| Bahasa | `label_id` / `label_en` tertulis | dari `app.supported_locales` dan `cms.default_locale`; label bahasa bawaan **wajib**, bahasa lain boleh kosong (memakai label bawaan) |
| Tujuan | `route_name` atau `url` diketik bebas | **satu tujuan untuk semua bahasa**: Beranda, Daftar artikel, **Artikel per kategori (rilis 42, `KATEGORI.md`)**, Halaman CMS (dicari dari judul), URL khusus, Email, Telepon, Anchor |
| `route_name` | diisi manual | **tidak diisi lagi** (rute statis lama dihapus rilis 23). Disimpan kosong |
| Validasi alamat | tidak ada | aturan yang sama dengan tombol: hanya `https://`, `/jalur`, `mailto:`, `tel:`, `#anchor`; maksimal 255 karakter |
| Pratinjau | tidak ada | alamat yang dilihat pembaca di tiap bahasa (memakai aturan landing yang sama, `NavLinks`) |
| Peringatan | tidak ada | baris dengan rute lama yang sudah mati, atau halaman yang belum online/tidak ada (tautan akan 404) |
| Urutan | menulis ulang semua baris, tanpa transaksi | transaksi, hanya baris yang berubah, posisi dijepit |
| Keamanan | tanpa pemeriksaan | login diperiksa di setiap aksi; id bertipe `int`; properti sensitif `#[Locked]` |
| Notifikasi | tidak ada | `WithNotifications` (`notify`) |

## Cara tujuan disimpan
Kolom `url` **satu isian untuk semua bahasa** (`BAHASA.md`). Yang disimpan adalah slug **bahasa bawaan** halaman (`/about-us`), bukan alamat per bahasa; landing menerjemahkannya ke bahasa pembaca. Beranda `/`, daftar artikel `/articles`. Yang lain (`https://…`, `mailto:`, `tel:`, `#anchor`) disimpan apa adanya.

Baris lama:
- `route_name` = `home` atau `articles` (rute yang masih ada): dibaca sebagai Beranda / Daftar artikel; disimpan sebagai `/` dan `/articles` saat Anda menyimpan.
- `route_name` lain (`about`, `programs`, ...): ditandai **rute lama**. Bila `url`-nya terisi, tujuan dibaca dari `url`; bila kosong, pilih tujuan baru. Menyimpan mengosongkan `route_name`.

## Dugaan yang perlu Anda periksa
- Tabel `navigations`: kolom `label` (JSON), `route_name`, `url` (string, diasumsikan 255), `order`, `is_active`. `route_name` disimpan `''` (bukan `NULL`) agar aman bila kolomnya tidak boleh kosong.
- Komponen memakai `x-sort` (Alpine Sort) seperti berkas lama, `<x-main-wrapper>`, dan trait `WithNotifications` / `SearchesLinkTargets`. Model `App\Models\Navigation` dan `App\Models\Page` milik Anda.
- **Belum dijalankan di Livewire sungguhan**; yang diuji hanya logika murni dan kontrak statis (`tests/menu-target-test.php`).
