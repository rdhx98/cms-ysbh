# Outline: sisip di posisi, pindah lintas level, masuk/keluar kontainer (rilis 43)

Semua pengaturan struktur halaman dilakukan dari **outline** (panel kiri). Kanvas sengaja **tanpa** tombol, "+" atau kotak kolom yang bisa diklik: kanvas hanya untuk melihat hasil dan memilih blok.

## Yang bisa dilakukan
Arahkan kursor ke baris blok → tombol **⋯** (di samping Naik / Turun / Duplikat / Hapus). Menunya:

| Menu | Fungsi |
|---|---|
| **Sisipkan di atas… / di bawah…** | pilih jenis blok; blok baru muncul **tepat di atas/bawah baris itu, di daftar yang sama** (tingkat atas, atau kolom/langkah yang sama), lalu langsung terfokus. Jenis yang ditawarkan = jenis yang sah di daftar itu (di kolom: tidak ada Pemisah Seksi / Kolom / Step) |
| **Pindahkan ke…** | daftar tujuan yang sah untuk blok ini: *Tingkat atas* dan setiap *Kolom n* / *Langkah* dari kontainer yang menerima jenisnya (mis. "Kolom #1 › Kolom 2"). Pilih satu: blok pindah ke **akhir** daftar itu, **dengan isinya utuh**. Tidak perlu lagi membuat blok di dalam kolom dulu |
| **Keluarkan dari kontainer** | blok jadi blok tingkat atas **tepat sesudah** kontainernya. Hanya muncul untuk blok di dalam kolom/langkah |
| **Masukkan ke Kolom di atas** | blok masuk ke kontainer yang berada **tepat di atasnya** (kolom terakhir yang tampil, paling bawah). Hanya muncul bila saudara di atasnya kontainer yang menerima jenis blok ini |

Pintasan (baris outline terfokus, mis. setelah diklik atau dicapai dengan Tab): **Alt+↑ / Alt+↓** = naik/turun, **Alt+Shift+← / →** = keluar/masuk kontainer. Tab tidak dipakai ulang. Setelah pindah, fokus keyboard tetap di baris yang sama.

## Aturan (diperiksa di SERVER, bukan hanya disembunyikan di tampilan)
- Tujuan harus kontainer yang ada, zona yang sah dan **tampil** (zona tersembunyi karena `col_count` dikurangi tidak menerima blok baru/pindahan; blok di dalamnya tetap bisa **dikeluarkan**).
- Jenis harus diizinkan di tujuan (`BlockPalette::allowsChild` / `allowedAtRoot`): Pemisah Seksi dan kontainer hanya di tingkat atas. **Kontainer di dalam kontainer tetap tidak diizinkan.**
- Blok tidak bisa dipindah ke dirinya sendiri atau turunannya (pohon tak boleh terputus).
- Indeks di luar jangkauan = akhir daftar; id/zona palsu = tidak terjadi apa-apa. Pohon selalu utuh: tiap blok tepat satu tempat.
- Hanya referensi yang berpindah; **isi, bahasa, dan ID blok tidak berubah** (jadi pratinjau, jangkar, dan blok terfokus tetap sama).

## Kode
| Bagian | Berkas |
|---|---|
| `addBlockAt($parent,$zone,$type,$index=null)`, `moveBlockTo($id,$parent,$zone,$index=null): bool`, `indentBlock($id)`, `outdentBlock($id)`, `moveTargets($id)` (`#[Renderless]`, hanya baca) | `app/Livewire/Traits/ManagesBlockStructure.php` |
| `BlockPalette::CONTAINERS` (daftar jenis kontainer; outline membuat satu daftar jenis untuk tiap kontainer) | `app/Editor/BlockPalette.php` |
| Menu tunggal + daftar jenis (dirender SEKALI, bukan per baris) | `resources/views/components/editor/outline.blade.php` |
| Konteks baris di `data-ctx` (induk, zona, indeks, jenis daftar, bisa keluar/masuk) | `resources/views/components/editor/outline-node.blade.php` |
| Alpine: `menu`, `openMenu`, `loadTargets` (penjaga `_seq` seperti `thumbnailPicker`), `insert`, `moveTo`, `rowKey`, `refocus` | `resources/js/editor.js` → `Alpine.data('outline')` |

`moveTargets()` dipanggil hanya saat "Pindahkan ke…" dibuka (satu request ringan, tanpa render ulang builder). Menu ditaruh `fixed` dekat tombol supaya tidak terpotong sidebar yang bisa digulir.

## Belum ada
- **Seret-dan-lepas (drag & drop) di outline**: rencana rilis 44, terpisah karena paling berisiko dan butuh uji Livewire sungguhan.
- Kontainer di dalam kontainer (tetap ditolak; bila kelak diperlukan, ubah `BlockPalette` dan renderer kolom, bukan hanya menu).
