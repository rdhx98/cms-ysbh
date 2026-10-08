<?php
/**
 * Pemeriksaan kartu "Artikel terbaru" (logika murni):  php tests/article-cards-test.php [akar-kit]
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
foreach (['Content/LocaleMap', 'Content/Names', 'Content/ContentDocument', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/ButtonStyle', 'Content/Links/LinkResolver', 'Content/Blocks/ArticleCards'] as $f) {
    require_once "$root/app/$f.php";
}
use App\Content\Blocks\ArticleCards as A;
use App\Content\Links\LinkResolver as L;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }

$artUrl = fn (string $slug) => L::publicUrl($slug, '/artikel/{slug}', 'https://ysbh.org');
$cover = fn (string $f) => L::publicUrl($f, '/storage/posts/{file}', 'https://ysbh.org', '{file}');
$media = fn (int $id) => $id === 7 ? 'https://ysbh.org/storage/m/7.jpg' : null;
$para = fn (string $t, string $id = 'p') => [$id => ['id' => $id, 'type' => 'paragraph', 'data' => ['text' => ['id' => $t, 'en' => '']]]];
$content = fn (array $blocks, array $order) => ['blocks' => $blocks, 'order' => $order, 'settings' => []];
$row = fn (array $o = []) => $o + ['id' => 1, 'title' => ['id' => 'Imunisasi dasar', 'en' => 'Basic immunization'], 'slug' => ['id' => 'imunisasi-dasar', 'en' => 'basic-immunization'], 'content' => $content($para('<p>Imunisasi melindungi <b>anak</b> dari penyakit.</p>'), ['p']), 'meta_description' => ['id' => '', 'en' => ''], 'featured_image' => 'cover-a.webp', 'category_id' => 3, 'published_at' => '2026-10-08 09:30:00'];

echo "\nKartu\n";
$c = A::prepare([$row()], [3 => ['id' => 'Kesehatan Anak', 'en' => 'Child Health']], 'id', $artUrl, $cover, $media)[0];
check('judul, tautan absolut ke landing, kategori, ringkasan dari paragraf pertama (tanpa HTML), sampul dari berkas, tanggal', $c['title'] === 'Imunisasi dasar' && $c['url'] === 'https://ysbh.org/artikel/imunisasi-dasar' && $c['category'] === 'Kesehatan Anak' && $c['excerpt'] === 'Imunisasi melindungi anak dari penyakit.' && $c['cover'] === 'https://ysbh.org/storage/posts/cover-a.webp' && $c['dateIso'] === '2026-10-08' && $c['dateLabel'] === '8 Okt 2026', json_encode($c));
$e = A::prepare([$row()], [3 => ['id' => 'Kesehatan Anak', 'en' => 'Child Health']], 'en', $artUrl, $cover, $media)[0];
check('bahasa en: judul, slug, kategori en; tanggal "Oct 8, 2026"', $e['title'] === 'Basic immunization' && $e['url'] === 'https://ysbh.org/artikel/basic-immunization' && $e['category'] === 'Child Health' && $e['dateLabel'] === 'Oct 8, 2026');
check('judul en kosong jatuh ke id', A::prepare([$row(['title' => ['id' => 'Hanya ID', 'en' => '']])], [], 'en', $artUrl, $cover, $media)[0]['title'] === 'Hanya ID');
check('artikel tanpa judul DILEWATI; baris bukan larik dilewati; urutan terjaga', array_column(A::prepare([$row(['id' => 1]), $row(['id' => 2, 'title' => ['id' => '', 'en' => '']]), 'x', $row(['id' => 3])], [], 'id', $artUrl, $cover, $media), 'id') === [1, 3]);
check('tanpa slug / pembentuk alamat menolak: kartu tetap ada tanpa tautan', A::prepare([$row(['slug' => ['id' => '', 'en' => '']])], [], 'id', $artUrl, $cover, $media)[0]['url'] === null && A::prepare([$row()], [], 'id', fn () => null, $cover, $media)[0]['url'] === null);
check('kategori tidak ada -> kosong', A::prepare([$row(['category_id' => 99])], [3 => ['id' => 'X']], 'id', $artUrl, $cover, $media)[0]['category'] === '');

echo "\nRingkasan\n";
check('meta_description diutamakan atas paragraf', A::excerpt(['id' => 'Ringkasan dari meta.', 'en' => ''], $row()['content'], 'id') === 'Ringkasan dari meta.');
check('paragraf kosong dilewati; blok non-paragraf (judul) dilewati; paragraf berikutnya dipakai', A::excerpt(null, $content($para('', 'a') + $para('Teks kedua', 'b') + ['h' => ['id' => 'h', 'type' => 'heading', 'data' => ['text' => ['id' => 'Judul']]]], ['h', 'a', 'b']), 'id') === 'Teks kedua');
check('HTML artikel LAMA (teks mentah) diimpor sebagai paragraf dan dibersihkan', A::excerpt(null, '<h2>Imunisasi</h2><p>Dasar <strong>lengkap</strong> &amp; aman</p>', 'id') === 'Imunisasi Dasar lengkap & aman');
check('KELUARAN EXCERPT ADALAH TEKS, BUKAN HTML: entitas didekode ("&lt;script&gt;" menjadi "<script>"), sehingga pemanggil WAJIB meng-escape saat mencetak', A::excerpt(['id' => '&lt;script&gt;alert(1)&lt;/script&gt;'], null, 'id') === '<script>alert(1)</script>');
check('"usia <5 tahun" di meta tetap utuh sebagai teks entitas', A::excerpt(['id' => 'usia &lt;5 tahun'], null, 'id') === 'usia <5 tahun');
$long = str_repeat('abcdefgh ', 40);   // 9 karakter per kata: 160 = 17x9 + 7, jadi tanpa penjagaan pemotongan jatuh di TENGAH kata
$ex = A::excerpt(null, $content($para($long), ['p']), 'id');
check('dipotong di batas kata: hanya kata UTUH, diakhiri elipsis, panjang maksimum 161', mb_strlen($ex) <= 161 && preg_match('/^(?:abcdefgh )*abcdefgh…$/u', $ex) === 1, $ex);
check('kata sangat panjang tanpa spasi tetap dipotong aman', mb_strlen(A::excerpt(['id' => str_repeat('a', 400)], null, 'id')) === 161);
check('isi kosong / null / larik kosong -> ""', A::excerpt(null, null, 'id') === '' && A::excerpt(null, '', 'id') === '' && A::excerpt(null, [], 'id') === '' && A::excerpt(null, ['blocks' => [], 'order' => []], 'id') === '');

echo "\nSampul\n";
check("'default.webp' = belum ada sampul -> null (bukan gambar rusak); huruf besar juga", A::cover('default.webp', $cover, $media) === null && A::cover('DEFAULT.WEBP', $cover, $media) === null && A::cover('', $cover, $media) === null && A::cover(null, $cover, $media) === null);
check('angka murni = ID media (lewat pemecah media); ID tak dikenal -> null; 0 -> null', A::cover('7', $cover, $media) === 'https://ysbh.org/storage/m/7.jpg' && A::cover(7, $cover, $media) === 'https://ysbh.org/storage/m/7.jpg' && A::cover('99', $cover, $media) === null && A::cover('0', $cover, $media) === null);
check('nama berkas dengan subfolder: dipertahankan dan di-encode per segmen', A::cover('2026/10/foto kita.webp', $cover, $media) === 'https://ysbh.org/storage/posts/2026/10/foto%20kita.webp');
foreach (['../../etc/passwd', 'a/../b.jpg', '/etc/x', 'a//b.jpg', "a\0b.jpg", 'a\\b.jpg', './x.jpg'] as $bad) {
    check("nama berkas tak aman '" . str_replace("\0", '\\0', $bad) . "' ditolak", A::cover($bad, $cover, $media) === null);
}
check('tanpa templat sampul di konfigurasi: tidak ada gambar (null), bukan alamat tebakan', A::cover('a.webp', fn (string $f) => L::publicUrl($f, null, '', '{file}'), $media) === null);
check('nilai sampul bukan teks (larik/objek) -> null', A::cover(['x'], $cover, $media) === null && A::cover(new stdClass, $cover, $media) === null);

echo "\nTanggal\n";
check('format ISO dan tanggal dengan zona/ detik; tak terbaca -> kosong', A::date('2026-01-05T08:00:00+07:00', 'id') === ['2026-01-05', '5 Jan 2026'] && A::date('bukan tanggal', 'id') === ['', ''] && A::date(null, 'id') === ['', ''] && A::date('', 'en') === ['', ''] && A::date(['x'], 'en') === ['', '']);
check('semua 12 bulan berlabel (id: Mei, Agu, Des; en: May, Aug, Dec)', A::date('2026-05-02', 'id')[1] === '2 Mei 2026' && A::date('2026-08-02', 'id')[1] === '2 Agu 2026' && A::date('2026-12-02', 'id')[1] === '2 Des 2026' && A::date('2026-05-02', 'en')[1] === 'May 2, 2026' && A::date('2026-12-02', 'fr')[1] === 'Dec 2, 2026');

echo "\nGrid dan pilihan\n";
check('jumlah kolom 2/3/4 (teks atau angka); lainnya -> 3; kelas dari daftar tetap', A::columns(4) === '4' && A::columns('2') === '2' && A::columns(7) === '3' && A::columns('x" onload="1') === '3' && A::grid(4) === 'grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4' && A::grid('zz') === A::grid(3));
check('batas jumlah 3/6/9/12; lainnya -> 3', A::limit('9') === '9' && A::limit(12) === '12' && A::limit(100) === '3' && A::limit(['x']) === '3');
$cw = file_get_contents("$root/app/Content/ContentWriter.php");
check("DEFAULT_COVER sama dengan nilai bawaan yang ditulis ContentWriter ('default.webp')", preg_match("/featured_image = '([^']+)'/", $cw, $m) === 1 && $m[1] === A::DEFAULT_COVER, $m[1] ?? '');

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
