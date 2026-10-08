<?php
/**
 * Pemeriksaan blok Video (logika murni):  php tests/video-test.php [akar-kit]
 * Penguraian alamat YouTube/Vimeo (termasuk uji acak), alamat yang dibangun ulang, pembersih, dan penyambungan modul.
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/VideoUrl', 'Content/Blocks/VideoStyle',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}
App\Editor\Modules::all();

use App\Content\Blocks\{BlockSanitizer as S, VideoStyle, VideoUrl as V};
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\VideoBlock as B;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }
$loc = ['id', 'en'];
$YT = 'dQw4w9WgXcQ';

section('YouTube: bentuk alamat yang didukung');
foreach ([
    "https://www.youtube.com/watch?v=$YT", "http://youtube.com/watch?v=$YT", "https://m.youtube.com/watch?v=$YT&feature=share", "youtube.com/watch?v=$YT", "www.youtube.com/watch?v=$YT",
    "https://youtu.be/$YT", "https://youtu.be/$YT?si=abc", "https://www.youtube.com/embed/$YT", "https://www.youtube-nocookie.com/embed/$YT",
    "https://www.youtube.com/shorts/$YT", "https://www.youtube.com/live/$YT", "https://www.youtube.com/v/$YT", "  https://youtu.be/$YT  ", "HTTPS://WWW.YOUTUBE.COM/watch?v=$YT",
] as $u) {
    $r = V::parse($u);
    check("diterima: $u", $r !== null && $r['provider'] === 'youtube' && $r['id'] === $YT && $r['start'] === 0, json_encode($r));
}
section('Vimeo: bentuk alamat yang didukung');
foreach ([['https://vimeo.com/123456789', '123456789', null], ['vimeo.com/123456789', '123456789', null], ['https://vimeo.com/123456789/abcdef1234', '123456789', 'abcdef1234'],
          ['https://player.vimeo.com/video/123456789', '123456789', null], ['https://player.vimeo.com/video/123456789?h=abcdef1234', '123456789', 'abcdef1234'], ['https://vimeo.com/123456789?h=0123abcd', '123456789', '0123abcd']] as [$u, $id, $h]) {
    $r = V::parse($u);
    check("diterima: $u", $r !== null && $r['provider'] === 'vimeo' && $r['id'] === $id && $r['hash'] === $h, json_encode($r));
}

section('Jam mulai');
foreach ([["https://youtu.be/$YT?t=90", 90], ["https://youtu.be/$YT?t=90s", 90], ["https://youtu.be/$YT?t=1m30s", 90], ["https://youtu.be/$YT?t=1h2m3s", 3723], ["https://www.youtube.com/watch?v=$YT&start=45", 45],
          ["https://vimeo.com/123456789#t=30s", 30], ["https://youtu.be/$YT?t=abc", 0], ["https://youtu.be/$YT?t=999999", 0], ["https://youtu.be/$YT?t=-5", 0], ["https://youtu.be/$YT?t=", 0], ["https://youtu.be/$YT?t[]=5", 0]] as [$u, $s]) {
    check("$u -> $s detik", (V::parse($u)['start'] ?? -1) === $s, json_encode(V::parse($u)));
}

section('Alamat yang DITOLAK');
foreach ([
    '', '   ', 'javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', 'vbscript:x', 'file:///etc/passwd', 'ftp://youtube.com/watch?v=' . $YT,
    '//youtube.com/watch?v=' . $YT, '/watch?v=' . $YT, 'https://evil.test/watch?v=' . $YT, 'https://youtube.com.evil.test/watch?v=' . $YT, 'https://evilyoutube.com/watch?v=' . $YT,
    "https://youtube.com@evil.test/watch?v=$YT", "https://user:pass@www.youtube.com/watch?v=$YT", "https://www.youtube.com:8080/watch?v=$YT", "https://www.youtube.com:443/watch?v=$YT",
    'https://www.youtube.com/watch', 'https://www.youtube.com/watch?v=short', 'https://www.youtube.com/watch?v=' . $YT . 'X', 'https://www.youtube.com/watch?v[]=' . $YT, 'https://www.youtube.com/playlist?list=PL1234567890',
    'https://www.youtube.com/channel/UC1234567890123456789012', 'https://www.youtube.com/embed/' . $YT . '/extra', "https://www.youtube.com/embed/{$YT}\"onload=\"x", "https://www.youtube.com/embed/<script>",
    'https://youtu.be/', 'https://youtu.be/short', 'https://vimeo.com/abc', 'https://vimeo.com/123', 'https://vimeo.com/channels/staffpicks', 'https://player.vimeo.com/video/abc', 'https://vimeo.com.evil.test/123456789',
    "https://www.youtube.com/watch?v=$YT\nHost: evil", "https://www.youtube.com/watch?v=$YT javascript:alert(1)", 'https://www.youtube.com/watch?v=' . str_repeat('a', 400),
    "https://youtu.be/$YT?si=a b", "https://youtu.be/$YT lihat ini ya", "https://youtu.be/$YT?x=\"y", "https://youtu.be/$YT?x=<y>", 'https://youtu.be/' . $YT . '?x=' . str_repeat('a', 320),
    'youtube', 'http://', 'https://www.youtube.com\\@evil.test/watch?v=' . $YT,
] as $u) {
    check('ditolak: ' . substr(str_replace(["\n", "\r"], '\\n', $u), 0, 70), V::parse($u) === null, json_encode(V::parse($u)));
}
check('bukan string (null, larik, angka, objek) -> ditolak tanpa galat', V::parse(null) === null && V::parse([$YT]) === null && V::parse(123456789) === null && V::parse(new stdClass) === null);

section('Alamat sematan dan tonton DIBANGUN ULANG');
$y = V::parse("https://www.youtube.com/watch?v=$YT&t=1m30s"); $vm = V::parse('https://vimeo.com/123456789/abcdef1234#t=30s');
check('YouTube: sematan memakai domain mode privasi, autoplay, tanpa video terkait, jam mulai', V::embed($y) === "https://www.youtube-nocookie.com/embed/$YT?autoplay=1&rel=0&modestbranding=1&playsinline=1&start=90", V::embed($y));
check('YouTube: tanpa autoplay bila diminta', !str_contains(V::embed($y, false), 'autoplay'));
check('YouTube: tautan tonton ke youtube.com dengan jam mulai', V::watch($y) === "https://www.youtube.com/watch?v=$YT&t=90s");
check('Vimeo: sematan player.vimeo.com dengan dnt=1, hash, dan jam mulai di fragmen', V::embed($vm) === 'https://player.vimeo.com/video/123456789?autoplay=1&dnt=1&h=abcdef1234#t=30s', V::embed($vm));
check('Vimeo: tautan tonton memuat hash dan jam mulai', V::watch($vm) === 'https://vimeo.com/123456789/abcdef1234#t=30s', V::watch($vm));
check('komponen rusak (ID tak sah, penyedia asing, jam mulai di luar batas, hash tak sah) -> KOSONG, bukan alamat sisipan', V::embed(['provider' => 'youtube', 'id' => 'x" onload="1', 'hash' => null, 'start' => 0]) === '' && V::embed(['provider' => 'evil', 'id' => $YT, 'hash' => null, 'start' => 0]) === '' && V::watch(['provider' => 'youtube', 'id' => $YT, 'hash' => null, 'start' => -1]) === '' && V::embed(['provider' => 'youtube', 'id' => $YT, 'hash' => null, 'start' => 99999999]) === '' && V::embed(['provider' => 'vimeo', 'id' => '123456789', 'hash' => 'zz"', 'start' => 0]) === '' && V::embed([]) === '');
check('nama penyedia untuk tampilan', V::providerLabel('youtube') === 'YouTube' && V::providerLabel('vimeo') === 'Vimeo');

section('Uji acak: apa pun masukannya, hasil sematan/tonton hanya bisa berbentuk alamat yang aman');
mt_srand(20261007);
$hosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'evil.test', 'youtube.com.evil.test', 'x@youtube.com', 'youtube.com:99', '', 'YOUTUBE.COM', "youtube.com\t"];
$paths = ['/watch', '/embed/' . $YT, '/shorts/' . $YT, '/' . $YT, '/123456789', '/123456789/abcdef12', '/video/123456789', '/', '', '/watch/', "/embed/$YT/x", '/..%2f', '/%00', '/a b', '/<script>', "/$YT\n"];
$queries = ['', "?v=$YT", '?v=short', '?v[]=a', '?t=90', '?t=1h2m3s', '?t=<x>', '?h=abcdef12', '?h=zz', "?v=$YT&t=5&start=7", "?v=$YT\"onclick=\"1", '?v=%3Cscript%3E', '?v=' . $YT . '&x=' . str_repeat('a', 50)];
$frags = ['', '#t=30s', '#t=abc', '#"x'];
$schemes = ['https://', 'http://', '', 'javascript:', 'data:', 'ftp://', '//', 'HTTPS://'];
$okEmbed = '#^https://(?:www\.youtube-nocookie\.com/embed/[A-Za-z0-9_-]{11}\?[A-Za-z0-9=&_]*|player\.vimeo\.com/video/\d{5,12}\?[A-Za-z0-9=&_]*(?:\#t=\d+s)?)$#D';
$okWatch = '#^https://(?:www\.youtube\.com/watch\?v=[A-Za-z0-9_-]{11}(?:&t=\d+s)?|vimeo\.com/\d{5,12}(?:/[a-f0-9]{6,20})?(?:\#t=\d+s)?)$#D';
$acc = $bad = 0; $N = 6000;
for ($i = 0; $i < $N; $i++) {
    $u = $schemes[array_rand($schemes)] . $hosts[array_rand($hosts)] . $paths[array_rand($paths)] . $queries[array_rand($queries)] . $frags[array_rand($frags)];
    $r = V::parse($u);
    if ($r === null) continue;
    $acc++;
    if (!preg_match($okEmbed, V::embed($r)) || !preg_match($okWatch, V::watch($r)) || !preg_match($okEmbed, V::embed($r, false))) { $bad++; if ($bad < 3) echo "    ! $u -> " . V::embed($r) . ' | ' . V::watch($r) . "\n"; }
}
check("$N alamat acak: $acc diterima, SEMUA menghasilkan sematan dan tautan berpola aman ($bad menyimpang)", $bad === 0 && $acc > 100, "$acc diterima, $bad menyimpang");
check('uji acak benar-benar menghasilkan keduanya (yang diterima dan yang ditolak)', $acc > 100 && $acc < $N - 100);
$junk = 0;
for ($i = 0; $i < 3000; $i++) { $s = ''; for ($j = random_int(0, 60); $j > 0; $j--) $s .= chr(random_int(0, 255)); try { V::parse($s); V::parse("https://youtu.be/$s"); } catch (\Throwable $e) { $junk++; } }
check('3000 teks biner acak: tidak ada galat/pengecualian dari pengurai', $junk === 0, (string) $junk);

section('VideoStyle');
check('rasio dan lebar: kelas tetap; nilai tak sah -> bawaan', VideoStyle::ratioClass('16:9') === 'aspect-video' && VideoStyle::ratioClass('9:16') === 'aspect-[9/16]' && VideoStyle::ratioClass('x" onload="1') === 'aspect-video' && VideoStyle::widthClass('md') === 'mx-auto w-full max-w-md' && VideoStyle::widthClass(['x']) === 'w-full');
check('lima rasio berbeda, tiga lebar berbeda', count(array_unique(array_map([VideoStyle::class, 'ratioClass'], VideoStyle::RATIOS))) === 5 && count(array_unique(array_map([VideoStyle::class, 'widthClass'], VideoStyle::WIDTHS))) === 3);

section('VideoBlock::sanitize');
$good = ['url' => "https://youtu.be/$YT?t=90", 'title' => ['id' => 'Cuci tangan', 'en' => 'Handwashing'], 'caption' => ['id' => 'Langkah 1–6', 'en' => ''], 'poster' => ['media_id' => 7, 'url' => 'https://x.test/storage/sampul.jpg'], 'ratio' => '4:3', 'max_width' => 'lg'];
check('data sah TIDAK berubah; idempoten', B::sanitize($good, $loc) === $good && B::sanitize(B::sanitize($good, $loc), $loc) === $good, json_encode(B::sanitize($good, $loc)));
$evil = B::sanitize(['url' => "  javascript:alert(1)\x00\n" . str_repeat('a', 400), 'title' => ['id' => "  Usia <5\ttahun\n", 'fr' => 'bocor'], 'caption' => ['id' => str_repeat('c', 400)], 'poster' => ['media_id' => 'x', 'url' => 'https://evil.test/a.jpg'], 'ratio' => '99:1" onload="1', 'max_width' => ['x'], 'z' => 1], $loc);
check('url: karakter kontrol dibuang, dipotong 300 (disimpan APA ADANYA; keamanan ada di VideoUrl saat dirender); kunci asing dibuang', mb_strlen($evil['url']) === 300 && !preg_match('/[\x00-\x1f]/', $evil['url']) && array_keys($evil) === ['url', 'title', 'caption', 'poster', 'ratio', 'max_width']);
check('judul satu baris dengan "<5" utuh; keterangan dipotong 300; rasio/lebar tak sah -> bawaan', $evil['title'] === ['id' => 'Usia <5 tahun', 'en' => ''] && mb_strlen($evil['caption']['id']) === 300 && $evil['ratio'] === '16:9' && $evil['max_width'] === 'full');
check('sampul tanpa media_id sah: url dari browser DIKOSONGKAN', $evil['poster'] === ['media_id' => null, 'url' => '']);
check('media_id: "7" -> 7; -3 / 0 / "x" -> null', B::sanitize(['poster' => ['media_id' => '7']], $loc)['poster']['media_id'] === 7 && B::sanitize(['poster' => ['media_id' => -3]], $loc)['poster']['media_id'] === null && B::sanitize(['poster' => ['media_id' => 0]], $loc)['poster']['media_id'] === null);
check('tanpa data -> bawaan aman', B::sanitize([], $loc) === ['url' => '', 'title' => ['id' => '', 'en' => ''], 'caption' => ['id' => '', 'en' => ''], 'poster' => ['media_id' => null, 'url' => ''], 'ratio' => '16:9', 'max_width' => 'full']);

section('Penyambungan modul');
check('Modules menemukan VideoBlock; modul lain tetap ada', Modules::for('video-builder') === B::class && Modules::for('callout-builder') !== null && Modules::for('downloads-builder') !== null && Modules::for('accordion-builder') !== null);
$def = BlockRegistry::block('video-builder');
check('registri: panel block:video-builder; palet: "Video", ikon video (ada di daftar ikon), tingkat atas + kolom, bukan step-group', $def !== null && $def->panelKey() === 'block:video-builder' && BlockPalette::label('video-builder') === 'Video' && BlockPalette::icon('video-builder') === 'video' && BlockPalette::allowedAtRoot('video-builder') && BlockPalette::allowsChild('multi-columns', 'video-builder') && !BlockPalette::allowsChild('step-group', 'video-builder'));
check('BlockSanitizer::forType dan clean() memanggil pembersih modul', S::forType('video_builder', ['ratio' => 'x'], $loc)['ratio'] === '16:9' && S::clean(['a' => ['id' => 'a', 'type' => 'video-builder', 'data' => ['max_width' => 'x']]], $loc)['a']['data']['max_width'] === 'full');
$mat = Defaults::materialize($def->defaults, $loc);
check('bawaan: kosong, tanpa penanda "@"; lolos pembersih TANPA berubah', $mat['url'] === '' && $mat['title'] === ['id' => '', 'en' => ''] && !str_contains(json_encode($mat), '@') && B::sanitize($mat, $loc) === $mat, json_encode(B::sanitize($mat, $loc)));
$tops = array_values(array_map(fn ($f) => substr($f->key, 5), array_filter($def->fields, fn ($f) => str_starts_with($f->key, 'data.')))); sort($tops); $san = array_keys($mat); sort($san);
check('bidang di inspektur = bidang yang dikenal pembersih', $tops === $san, json_encode([$tops, $san]));
$by = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan rasio dan lebar di inspektur = daftar VideoStyle', $by('data.ratio')->allowedValues() === VideoStyle::RATIOS && $by('data.max_width')->allowedValues() === VideoStyle::WIDTHS);
check("sampul: pemilih gambar (filter 'image'); alamat dibatasi 300 karakter", ($by('data.poster')->extra['accept'] ?? null) === 'image' && ($by('data.url')->extra['maxlength'] ?? null) === 300);
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', !array_filter($def->fields, fn ($f) => $f->allowedValues() !== null && $f->default !== null && !in_array($f->default, $f->allowedValues(), true)));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
