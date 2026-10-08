<?php
/**
 * Peta situs murni (App\Content\Sitemap):  php tests/sitemap-test.php [akar-kit]
 * Tidak butuh basis data. Memeriksa alamat, penggabungan, tanggal, batas, dan bahwa keluarannya SELALU XML yang sah.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/app/Content/Links/LinkResolver.php";
require_once "$root/app/Content/Sitemap.php";

use App\Content\Sitemap;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 300) . "]" : '') . "\n"; }
/**
 * Pemeriksa kebenaran-bentuk XML untuk tata bahasa keluaran kita (tidak bergantung pada ekstensi DOM/SimpleXML, yang tidak selalu ada).
 * Ketat: deklarasi, ruang nama, dan SETIAP <url> harus persis bentuknya; teks tidak boleh memuat < > atau & mentah atau karakter kendali XML 1.0;
 * UTF-8 tidak sah menggagalkan (/u). Bila ekstensi DOM ada, XML juga diurai olehnya.
 */
function wellFormed(string $xml): bool
{
    $text = '(?:[^<>&\x00-\x08\x0b\x0c\x0e-\x1f]|&(?:amp|lt|gt|quot|apos);|&#\d+;)*';
    $re = '~\A<\?xml version="1.0" encoding="UTF-8"\?>\n<urlset xmlns="http://www\.sitemaps\.org/schemas/sitemap/0\.9">\n(?:  <url><loc>' . $text . '</loc>(?:<lastmod>' . $text . '</lastmod>)?</url>\n)*</urlset>\n\z~u';
    if (preg_match($re, $xml) !== 1) { return false; }
    if (class_exists('DOMDocument')) { $d = new DOMDocument(); libxml_use_internal_errors(true); $ok = $d->loadXML($xml); libxml_clear_errors(); return (bool) $ok; }

    return true;
}
function parse(string $xml): ?string { return wellFormed($xml) ? $xml : null; }
function locs(string $xml): array { preg_match_all('#<loc>(.*?)</loc>#su', $xml, $m); return array_map(fn ($l) => htmlspecialchars_decode($l, ENT_XML1 | ENT_QUOTES), $m[1]); }

$B = 'https://ysbh.org'; $PT = '/{slug}'; $AT = '/artikel/{slug}';

echo "\nentries(): alamat\n";
$rows = [['kind' => 'page', 'slug' => 'tentang-kami', 'lastmod' => '2026-10-01'], ['kind' => 'article', 'slug' => 'imunisasi-dasar', 'lastmod' => '2026-09-30']];
$e = Sitemap::entries($rows, ['/', '/about'], $B, $PT, $AT);
check('halaman -> /{slug}, artikel -> /artikel/{slug}, jalur statis ditambahkan; semua absolut', array_column($e, 'loc') === ['https://ysbh.org/tentang-kami', 'https://ysbh.org/artikel/imunisasi-dasar', 'https://ysbh.org/', 'https://ysbh.org/about'], json_encode(array_column($e, 'loc')));
check('lastmod dibawa; jalur statis tanpa lastmod', $e[0]['lastmod'] === '2026-10-01' && $e[1]['lastmod'] === '2026-09-30' && $e[2]['lastmod'] === null);
check('alamat dasar dengan garis miring penutup atau spasi tetap bekerja', Sitemap::entries($rows, [], ' https://ysbh.org/ ', $PT, $AT)[0]['loc'] === 'https://ysbh.org/tentang-kami');
check('alamat dasar tidak sah (kosong, tanpa skema, berjalur, javascript:, protokol-relatif): hasil KOSONG, bukan alamat relatif', array_filter(['', 'ysbh.org', 'https://ysbh.org/path', 'javascript:alert(1)', '//ysbh.org', 'ftp://ysbh.org', 'https://ysbh.org?x=1', 'https://'], fn ($b) => Sitemap::entries($rows, ['/'], $b, $PT, $AT) !== []) === []);
check('templat rusak hanya membuang baris jenis itu: templat artikel rusak -> halaman tetap ada, artikel hilang', array_column(Sitemap::entries($rows, [], $B, $PT, 'artikel/{slug}'), 'loc') === ['https://ysbh.org/tentang-kami']);
check('slug tidak aman di-ENCODE oleh LinkResolver (spasi, garis miring, kutip) dan tidak pernah lolos mentah', (function () use ($B, $PT, $AT) { $r = Sitemap::entries([['kind' => 'page', 'slug' => 'a b/c"d<e>', 'lastmod' => null], ['kind' => 'page', 'slug' => '', 'lastmod' => null]], [], $B, $PT, $AT); foreach ($r as $x) { if (preg_match('/[\s"<>]/', $x['loc'])) return false; } return true; })());
$static = ['/ok', '//evil.test/x', 'javascript:alert(1)', '/a b', '/x?y=1', '/x#frag', "/x\n", '/a"b', '/a<b', '/a&b', 'https://evil.test/', '', 5, null, ['/x'], '/programs/malaria'];
check('jalur statis tidak sah dibuang (protokol-relatif, skema, spasi, query, fragmen, baris baru, kutip, &, bukan teks); yang sah tetap', array_column(Sitemap::entries([], $static, $B, $PT, $AT), 'loc') === ['https://ysbh.org/ok', 'https://ysbh.org/programs/malaria'], json_encode(array_column(Sitemap::entries([], $static, $B, $PT, $AT), 'loc')));

echo "\nnormalize(): penggabungan\n";
$n = Sitemap::normalize([['loc' => 'https://ysbh.org/artikel', 'lastmod' => null], ['loc' => 'https://ysbh.org/artikel', 'lastmod' => '2026-10-02'], ['loc' => 'https://ysbh.org/artikel', 'lastmod' => '2026-09-01'], ['loc' => 'https://ysbh.org/x', 'lastmod' => '2026-01-01']]);
check('alamat kembar digabung dan lastmod TERBARU dipertahankan, apa pun urutannya; urutan kemunculan pertama tetap', count($n) === 2 && $n[0] === ['loc' => 'https://ysbh.org/artikel', 'lastmod' => '2026-10-02'] && $n[1]['loc'] === 'https://ysbh.org/x');
check('alamat yang HANYA beda huruf besar-kecil dianggap berbeda (jalur peka huruf besar-kecil)', count(Sitemap::normalize([['loc' => 'https://ysbh.org/A'], ['loc' => 'https://ysbh.org/a']])) === 2);
$bad = ['http://', 'https://ysbh.org/a b', "https://ysbh.org/a\n", 'https://ysbh.org/a"b', 'https://ysbh.org/a<b', 'https://ysbh.org/a&b', 'https://ysbh.org/a?x=1', 'https://ysbh.org/a#f', '/relatif', 'ysbh.org/x', 'javascript:alert(1)', 'https://ysbh.org/' . str_repeat('a', 2100), 'https://[::1]/x', 'https://user@ysbh.org/x', 'https://ysbh.org:99999999/x', ['x'], null, 12];
check('entri tidak sah dibuang SEMUA (tanpa diperbaiki diam-diam)', Sitemap::normalize(array_map(fn ($l) => ['loc' => $l], $bad)) === [] && Sitemap::normalize(['bukan-larik', null, 5, ['tanpa-loc' => 1]]) === []);
check('alamat sah yang unik: port, subdomain, %-encoding, titik, garis bawah, tilde diterima', count(Sitemap::normalize(array_map(fn ($l) => ['loc' => $l], ['https://ysbh.org:8443/x', 'http://a.b-c.ysbh.org/x', 'https://ysbh.org/a%20b', 'https://ysbh.org/a.b_c~d', 'https://ysbh.org']))) === 5);

echo "\ndate()\n";
$ok = ['2026-10-08' => '2026-10-08', '2026-10-08 13:00:00' => '2026-10-08', '2026-10-08T13:00:00Z' => '2026-10-08', '2026-10-08T13:00:00+07:00' => '2026-10-08', '2024-02-29' => '2024-02-29', ' 2026-10-08 ' => '2026-10-08', '2026-10-08 13:00:00.123456' => '2026-10-08'];
$allOk = true; foreach ($ok as $in => $want) { $allOk = $allOk && Sitemap::date($in) === $want; }
check('tanggal sah dari berbagai bentuk basis data menjadi YYYY-MM-DD (termasuk 29 Februari kabisat)', $allOk);
$allBad = true; foreach (['2026-13-01', '2026-02-30', '2025-02-29', '2026-00-10', '10-08-2026', '2026/10/08', 'kemarin', '', '0000-00-00 00:00:00', '2026-10-08; DROP', "2026-10-08\n<x>"] as $in) { $allBad = $allBad && Sitemap::date($in) === null; }
check('tanggal mustahil atau berbentuk lain -> null (0000-00-00 dari MySQL lama, 30 Februari, 29 Februari bukan kabisat)', $allBad && Sitemap::date(null) === null && Sitemap::date(20261008) === null && Sitemap::date(['x']) === null);

echo "\nxml()\n";
$xml = Sitemap::xml($e);
check('XML sah dengan ruang nama protokol, deklarasi UTF-8 di awal, dan elemen akar urlset', wellFormed($xml) && str_starts_with($xml, '<?xml version="1.0" encoding="UTF-8"?>') && str_contains($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">') && str_ends_with($xml, "</urlset>\n"));
check('setiap <url> berisi <loc>; <lastmod> hanya bila ada', substr_count($xml, '<url>') === 4 && substr_count($xml, '<loc>') === 4 && substr_count($xml, '<lastmod>') === 2 && substr_count($xml, '<lastmod>2026-10-01</lastmod>') === 1);
check('pemeriksa bentuk itu sendiri MENOLAK XML rusak (agar uji acak di bawah bermakna)', !wellFormed(str_replace('</loc>', '', $xml)) && !wellFormed(str_replace('<url>', '<url><x/>', $xml)) && !wellFormed(preg_replace('#<loc>https://ysbh.org/about#', '<loc>https://ysbh.org/a&b', $xml)) && !wellFormed("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n  <url><loc>\"\x01\"</loc></url>\n</urlset>\n") && !wellFormed(substr($xml, 0, -9)) && !wellFormed("\xEF\xBB\xBF" . $xml) && !wellFormed(str_replace('<loc>https://ysbh.org/about', "<loc>https://ysbh.org/\xC3\x28", $xml)));
check('tanpa entri: urlset kosong yang sah (pengendali yang memutuskan 503, bukan pustaka ini)', wellFormed(Sitemap::xml([])) && !str_contains(Sitemap::xml([]), '<url>'));
check('batas protokol: 60.000 entri -> TEPAT 50.000 <url>; batas dapat diatur', (function () { $many = []; for ($i = 0; $i < 60000; $i++) { $many[] = ['loc' => "https://ysbh.org/p-$i"]; } return substr_count(Sitemap::xml($many), '<url>') === 50000 && substr_count(Sitemap::xml($many, 10), '<url>') === 10; })());
check('tidak ada karakter XML istimewa mentah di keluaran (kutip, kurung sudut, & hanya dalam bentuk sah)', (function () { $x = Sitemap::xml([['loc' => 'https://ysbh.org/a%26b'], ['loc' => 'https://ysbh.org/ok', 'lastmod' => '2026-10-08']]); return wellFormed($x) && !preg_match('/&(?!amp;|lt;|gt;|quot;|apos;|#)/', $x); })());

echo "\nUji acak: keluaran SELALU XML sah\n";
mt_srand(20261008);
$alphabet = array_merge(str_split("abcXYZ019-_.~%/:?#&<>\"' \t\n\\@[]{}()*+,;=|^`é中"), ['http://', 'https://', 'javascript:', '//', '..', '%00', '\\x00']);
$rnd = fn () => implode('', array_map(fn () => $alphabet[mt_rand(0, count($alphabet) - 1)], range(1, mt_rand(0, 14))));
$allParse = true; $allSafe = true; $worst = '';
for ($i = 0; $i < 3000; $i++) {
    $batch = [];
    for ($j = 0; $j < 6; $j++) { $batch[] = ['loc' => (mt_rand(0, 2) ? 'https://ysbh.org/' : '') . $rnd(), 'lastmod' => mt_rand(0, 1) ? $rnd() : '2026-1' . mt_rand(0, 3) . '-' . mt_rand(0, 3) . mt_rand(0, 9)]; }
    $x = Sitemap::xml($batch);
    if (parse($x) === null) { $allParse = false; $worst = $x; break; }
    foreach (locs($x) as $l) { if (!preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?(?:/[A-Za-z0-9._~%/\-]*)?$#D', $l)) { $allSafe = false; $worst = $l; break 2; } }
}
check('3000 gugus acak (karakter berbahaya, Unicode, skema, \\x00) -> setiap keluaran adalah XML yang sah', $allParse, $worst);
check('...dan setiap <loc> yang lolos tetap absolut http(s) tanpa karakter terlarang', $allSafe, $worst);
$rowsRnd = true;
for ($i = 0; $i < 1500; $i++) {
    $rr = [['kind' => mt_rand(0, 1) ? 'page' : 'article', 'slug' => $rnd(), 'lastmod' => $rnd()], ['kind' => $rnd(), 'slug' => $rnd(), 'lastmod' => null]];
    $x = Sitemap::xml(Sitemap::entries($rr, [$rnd(), '/' . $rnd()], $B, mt_rand(0, 3) ? $PT : $rnd(), mt_rand(0, 3) ? $AT : $rnd()));
    if (parse($x) === null) { $rowsRnd = false; $worst = $x; break; }
}
check('1500 gugus baris + jalur + templat acak lewat entries() -> xml(): selalu XML sah', $rowsRnd, $worst);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
