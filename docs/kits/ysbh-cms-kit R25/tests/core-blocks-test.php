<?php
/**
 * Tujuh blok inti: penyaring struktural dan jalur publik:  php tests/core-blocks-test.php [akar-kit]
 * Murni (tanpa lab). Memeriksa bahwa HANYA kolom berisiko yang berubah (non-destruktif), dan perilaku BlockSanitizer::forType/forPublic.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/app/Content/Languages.php";
require_once "$root/app/Content/Links/LinkResolver.php";
require_once "$root/app/Content/Blocks/RichText.php";
require_once "$root/app/Content/Blocks/CoreBlocks.php";
require_once "$root/app/Content/Blocks/ButtonStyle.php";
require_once "$root/app/Content/Blocks/BlockSanitizer.php";

use App\Content\Blocks\{BlockSanitizer as BS, CoreBlocks as C};

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }
$int = fn (string $kind, string $slug): ?string => $kind === 'article' ? "/artikel/$slug" : "/$slug";

echo "\nEyebrow: ikon dan warna (masuk ke nama komponen dan atribut style)\n";
$ok = ['text' => ['en' => 'Hello'], 'icon' => 'heart-pulse', 'color' => '#064f3b', 'margin_bottom' => 'mb-8 md:mb-10'];
check('data sah TIDAK berubah sama sekali (urutan dan kunci lain utuh)', C::clean('eyebrow', $ok) === $ok && array_keys(C::clean('eyebrow', $ok)) === array_keys($ok));
foreach (['#064f3b', '#E42326', '#fff', '#ffff', '#ffffff80'] as $c) { check("warna sah $c dipertahankan", C::clean('eyebrow', ['color' => $c])['color'] === $c); }
foreach (['red', 'red; position: fixed', '#12', '#12345', '#ggg', 'url(http://x)', 'expression(alert(1))', "#fff\n", '#fff;}body{display:none', '', null, ['#fff'], 5] as $bad) {
    check('warna tidak sah menjadi bawaan: ' . json_encode($bad), C::clean('eyebrow', ['color' => $bad])['color'] === '#e05a47');
}
foreach (['X::Y', '../x', 'a b', 'A', '', 'x"y', 'icon.name', str_repeat('a', 41), null, ['a'], 7] as $bad) {
    check('ikon tidak sah menjadi "newspaper": ' . json_encode($bad), C::clean('eyebrow', ['icon' => $bad])['icon'] === 'newspaper');
}
check('kunci yang tidak ada TIDAK ditambahkan (tidak merusak bawaan renderer)', C::clean('eyebrow', ['text' => ['en' => 'x']]) === ['text' => ['en' => 'x']]);

echo "\nGambar: alamat\n";
foreach (['https://ysbh.org/a.webp', 'http://x.test/a.jpg', '/storage/articles/a.jpg', 'storage/a.jpg', 'https://ysbh.org/a b.jpg'] as $u) { check("alamat sah dipertahankan: $u", C::clean('image', ['url' => $u])['url'] === str_replace(' ', '%20', $u)); }
foreach (['javascript:alert(1)', 'JaVaScRiPt:x', ' java script:x', 'data:text/html,x', 'data:image/png;base64,AAAA', '//evil.test/x.jpg', 'mailto:a@b.c', '#anchor', 'file:///etc/passwd', "/a\\b", 'vbscript:x', '', null, ['x'], 5, 'internal://page/x'] as $bad) {
    check('alamat gambar tidak sah -> "": ' . json_encode($bad), C::clean('image', ['url' => $bad])['url'] === '');
}
check('gambar tanpa url tidak ditambahi kunci; kolom lain (caption, kelas) tidak disentuh', C::clean('image', ['caption' => ['en' => 'c'], 'radius' => 'rounded-xl']) === ['caption' => ['en' => 'c'], 'radius' => 'rounded-xl']);

echo "\nKartu (card-builder)\n";
$card = fn (array $o = []) => ['grid' => ['cols' => 3, 'margin_bottom' => 'mb-8'], 'cards' => [[
    'container' => ['bg' => 'bg-white', 'url' => $o['url'] ?? ''],
    'layout' => ['children' => [['width' => $o['w'] ?? 1, 'align' => 'items-start', 'children' => [
        ['elementType' => 'text', 'data' => ['style' => ['size' => 'text-sm'], 'content' => ['id' => 'Halo', 'en' => 'Hi']]],
        ['elementType' => 'icon', 'data' => ['style' => ['bg' => 'bg-mist'], 'content' => ['icon' => $o['icon'] ?? 'heart']]],
        ['elementType' => 'button', 'data' => ['style' => ['variant' => 'solid'], 'content' => ['label' => ['en' => 'Go'], 'url' => $o['btn'] ?? '#']]],
    ]]]],
]]];
$urlOf = fn (array $d) => $d['cards'][0]['container']['url'];
$wOf = fn (array $d) => $d['cards'][0]['layout']['children'][0]['width'];
$iconOf = fn (array $d) => $d['cards'][0]['layout']['children'][0]['children'][1]['data']['content']['icon'];
$btnOf = fn (array $d) => $d['cards'][0]['layout']['children'][0]['children'][2]['data']['content']['url'];
check('kartu sah TIDAK berubah sama sekali', C::clean('card-builder', $card()) === $card());
check('url kartu sah dipertahankan (https, jalur, anchor, mailto, tel)', array_reduce(['https://ysbh.org/x', '/about', '#kontak', 'mailto:a@ysbh.org', 'tel:+62812'], fn ($ok, $u) => $ok && $urlOf(C::clean('card-builder', $card(['url' => $u]))) === $u, true));
foreach (['javascript:alert(1)', ' JaVaScRiPt:alert(1)', 'java&#x09;script:1', "java\tscript:1", 'data:text/html,x', '//evil.test', 'vbscript:x', '<script>', "https://ok.test\"onmouseover=\"x"] as $bad) {
    $r = C::clean('card-builder', $card(['url' => $bad, 'btn' => $bad]));
    check('url kartu dan tombol tidak sah: ' . json_encode($bad) . ' -> "" dan "#"', $urlOf($r) === '' || !preg_match('/^(javascript|vbscript|data|\/\/)/i', preg_replace('/[\x00-\x20]+/', '', $urlOf($r))), $urlOf($r));
    check('  ...dan tidak ada skema berbahaya tersisa di url kartu/tombol', !preg_match('/javascript|vbscript|^data:|^\/\//i', preg_replace('/[\x00-\x20]+/', '', $urlOf($r) . '|' . $btnOf($r))), $urlOf($r) . '|' . $btnOf($r));
    check('  ...tombol jatuh ke "#" (bawaan renderer)', $btnOf($r) === '#' || $btnOf($r) === $bad && false);
}
foreach ([1, 2, '3', 'auto', 1.5, '2.5', 12] as $w) { check('lebar kolom sah dipertahankan: ' . json_encode($w), $wOf(C::clean('card-builder', $card(['w' => $w]))) === $w); }
foreach (['1fr; position:fixed; inset:0', '99', -1, 0, 'abc', null, ['1'], '1fr', '1; x', 13, '1e3'] as $bad) {
    $got = $wOf(C::clean('card-builder', $card(['w' => $bad])));
    check('lebar kolom tidak sah menjadi 1: ' . json_encode($bad), $got === 1 || $got === '1', json_encode($got));
}
check('ikon elemen: sah dipertahankan; tidak sah -> "" (renderer melewati elemen ikon kosong)', $iconOf(C::clean('card-builder', $card(['icon' => 'heart-pulse']))) === 'heart-pulse' && $iconOf(C::clean('card-builder', $card(['icon' => 'x::y']))) === '' && $iconOf(C::clean('card-builder', $card(['icon' => '../etc']))) === '');
check('elemen TEKS tidak disentuh walau isinya menyerupai kolom url/icon (hanya elemen icon dan button yang disaring)', (function () use ($card) { $c = $card(); $c['cards'][0]['layout']['children'][0]['children'][0]['data']['content'] = ['id' => 'a', 'url' => 'javascript:x', 'icon' => 'X::Y']; return C::clean('card-builder', $c) === $c; })());
check('grid.cols: sah dipertahankan; di luar 1-6 atau bukan angka dijepit/dibuat 3', C::clean('card-builder', ['grid' => ['cols' => '4']])['grid']['cols'] === '4' && C::clean('card-builder', ['grid' => ['cols' => 99]])['grid']['cols'] === 6 && C::clean('card-builder', ['grid' => ['cols' => 'x']])['grid']['cols'] === 3 && C::clean('card-builder', ['grid' => ['cols' => ['a']]])['grid']['cols'] === 3);
check('bentuk data rusak (cards bukan larik, layout hilang, elemen null) tidak membuat galat', (function () { foreach ([['cards' => 'x'], ['cards' => [null, 5, 'x']], ['cards' => [['layout' => 'x']]], ['cards' => [['layout' => ['children' => [null, 'x', ['children' => 'x']]]]]], ['cards' => [['layout' => ['children' => [['children' => [null, ['elementType' => 'icon']]]]]]]], []] as $d) { C::clean('card-builder', $d); } return true; })());
$internalCard = $card(['url' => 'internal://page/tentang-kami', 'btn' => 'internal://article/imunisasi']);
check('internal:// pada kartu: jalur SIMPAN mempertahankan; jalur PUBLIK menyelesaikan; tak terselesaikan -> "" dan "#"', $urlOf(C::clean('card-builder', $internalCard)) === 'internal://page/tentang-kami' && $urlOf(C::clean('card-builder', $internalCard, $int)) === '/tentang-kami' && $btnOf(C::clean('card-builder', $internalCard, $int)) === '/artikel/imunisasi' && $urlOf(C::clean('card-builder', $internalCard, fn () => null)) === '' && $btnOf(C::clean('card-builder', $internalCard, fn () => null)) === '#');

echo "\nKolom (multi-columns) dan langkah (step-group)\n";
$mc = ['col_count' => '3', 'col_1_zone_width' => '1', 'col_2_zone_width' => '2', 'col_3_zone_width' => 'auto', 'col_1_zone' => ['a1', 'b_2'], 'col_2_zone' => [], 'gap' => 'gap-2', 'mobile_reverse' => true];
check('data sah TIDAK berubah (col_count "3" tetap string, lebar tetap)', C::clean('multi-columns', $mc) === $mc);
check('lebar kolom disaring (masuk ke --md-grid-cols): "1fr; position:fixed" -> "1"', C::clean('multi-columns', ['col_1_zone_width' => '1fr; position:fixed'])['col_1_zone_width'] === '1' && C::clean('multi-columns', ['col_2_zone_width' => 99])['col_2_zone_width'] === 1 && C::clean('multi-columns', ['col_3_zone_width' => ['x']])['col_3_zone_width'] === 1);
check('col_count: di luar 1-6 dijepit, bukan angka -> 2, larik tidak membuat galat', C::clean('multi-columns', ['col_count' => 50])['col_count'] === 6 && C::clean('multi-columns', ['col_count' => 0])['col_count'] === 1 && C::clean('multi-columns', ['col_count' => 'x'])['col_count'] === 2 && C::clean('multi-columns', ['col_count' => ['a']])['col_count'] === 2);
check('daftar id anak: hanya teks id yang sah; yang lain dibuang; bukan larik dibiarkan', C::clean('multi-columns', ['col_1_zone' => ['ok1', 'b a', '"x"', 5, null, ['a'], 'ok-2', '../x', str_repeat('a', 65)]])['col_1_zone'] === ['ok1', 'ok-2'] && C::clean('multi-columns', ['col_1_zone' => 'x'])['col_1_zone'] === 'x');
check('step-group: children disaring dengan aturan yang sama; kelas (gap, warna) tidak disentuh', C::clean('step-group', ['children' => ['s1', 'a b', 7, 's_2'], 'gap' => 'gap-8', 'node_color' => 'bg-foresty text-white']) === ['children' => ['s1', 's_2'], 'gap' => 'gap-8', 'node_color' => 'bg-foresty text-white']);

echo "\nIntegrasi BlockSanitizer\n";
$html = '<p>Halo <a href="javascript:alert(1)">x</a><script>alert(1)</script><a href="internal://page/tentang-kami" onclick="a()">y</a></p>';
$blocks = ['h' => ['id' => 'h', 'type' => 'heading', 'data' => ['text' => ['id' => $html, 'en' => $html], 'level' => 'h2']], 'p' => ['id' => 'p', 'type' => 'paragraph', 'data' => ['text' => ['en' => '<img src=x onerror=alert(1)>ok']]], 'e' => ['id' => 'e', 'type' => 'eyebrow', 'data' => ['icon' => 'x::y', 'color' => 'red;position:fixed', 'text' => ['en' => 'E']]]];
check('SIMPAN (forType/clean): HTML teks kaya TIDAK diubah (data editor utuh); kolom berisiko eyebrow diperbaiki', BS::forType('heading', $blocks['h']['data'])['text']['en'] === $html && BS::clean($blocks)['h']['data']['text']['en'] === $html && BS::clean($blocks)['e']['data']['icon'] === 'newspaper' && BS::clean($blocks)['e']['data']['color'] === '#e05a47');
$pub = BS::forPublic($blocks, ['id', 'en'], $int);
check('PUBLIK (forPublic): HTML disaring untuk SETIAP bahasa; skrip dan penangan hilang; tautan internal diselesaikan', $pub['h']['data']['text']['en'] === '<p>Halo x<a href="/tentang-kami">y</a></p>' && $pub['h']['data']['text']['id'] === $pub['h']['data']['text']['en'] && $pub['p']['data']['text']['en'] === '<img src="x" loading="lazy">ok', $pub['h']['data']['text']['en']);
check('PUBLIK: kolom lain di heading (level) tidak disentuh; blok tak dikenal dan non-larik dibiarkan', $pub['h']['data']['level'] === 'h2' && BS::forPublic(['z' => ['type' => 'unknown', 'data' => ['text' => '<script>x</script>']], 'n' => 'bukan-larik'], ['en'])['z']['data']['text'] === '<script>x</script>' && BS::forPublic(['n' => 'bukan-larik'], ['en'])['n'] === 'bukan-larik');
check('PUBLIK tanpa pemecah tautan internal: tautan internal dilepas (teks tetap), tidak ada href internal:// tersisa', !str_contains(BS::forPublic($blocks, ['en'], fn () => null)['h']['data']['text']['en'], 'internal://'));
check('text berupa string tunggal (bukan per bahasa) juga disaring; bukan teks -> ""', BS::forPublic(['h' => ['type' => 'heading', 'data' => ['text' => '<script>x</script>aman']]], ['en'])['h']['data']['text'] === 'aman' && BS::forPublic(['h' => ['type' => 'paragraph', 'data' => ['text' => ['en' => ['x'], 'id' => null]]]], ['en'])['h']['data']['text'] === ['en' => '', 'id' => '']);
check('jalur publik tetap menyaring TOMBOL (tautan javascript: dikosongkan) seperti sebelumnya', (function () use ($int) { $b = ['b' => ['type' => 'button-builder', 'data' => ['buttons' => [['label' => ['en' => 'X'], 'link' => ['kind' => 'url', 'ref' => 'javascript:alert(1)']]]]]]; $r = BS::forPublic($b, ['en'], $int); return !str_contains(json_encode($r), 'javascript:'); })());
check('forPublic idempoten: dijalankan dua kali hasilnya sama', BS::forPublic($pub, ['id', 'en'], $int) === $pub);
check('clean() mengembalikan data TANPA perubahan bila semua kolom sah (tidak memaksa simpan ulang)', (function () use ($ok, $mc) { $b = ['e' => ['type' => 'eyebrow', 'data' => $ok], 'm' => ['type' => 'multi-columns', 'data' => $mc]]; return BS::clean($b) === $b; })());

echo "\nAnchor blok (id=\"...\" dan ekspresi JS daftar isi)\n";
foreach (['beban-kasus', 'Bagian_2', '2024', 'a', 'Bagian-Satu', str_repeat('a', 64)] as $ok) { check("anchor sah TIDAK diubah: $ok", BS::anchor($ok) === $ok); }
foreach ([['Beban Kasus', 'beban-kasus'], ["x');alert(1);('", 'x-alert-1'], ['  spasi  ', 'spasi'], ['a/b\\c', 'a-b-c'], ['</script><script>', 'script-script'], ["a\nb", 'a-b'], ['-awal', 'awal'], ['__x__', 'x'], ['a"b', 'a-b'], ['${x}', 'x']] as [$in, $want]) {
    check('anchor tidak sah dinormalkan: ' . json_encode($in) . ' -> ' . json_encode($want), BS::anchor($in) === $want, BS::anchor($in));
}
foreach ([null, ['a'], true, new stdClass(), '', '   ', "'';", '---', "\u{4e2d}\u{6587}"] as $bad) { check('bukan anchor yang bisa dibentuk -> "": ' . json_encode($bad), BS::anchor($bad) === ''); }
check('angka menjadi teks anchor; panjang dipotong 64; hasilnya SELALU sah dan idempoten', BS::anchor(2024) === '2024' && strlen(BS::anchor(str_repeat('ab ', 100))) <= 64 && (function () { foreach (["x');a", 'A B', str_repeat('z-', 90), 'a--b', '_a'] as $v) { $a = BS::anchor($v); if ($a !== '' && !preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/D', $a)) return false; if (BS::anchor($a) !== $a) return false; } return true; })());
$ab = ['h' => ['id' => 'h', 'type' => 'heading', 'anchor' => "x');alert(1);('", 'data' => ['text' => ['en' => 'T']]], 'p' => ['id' => 'p', 'type' => 'paragraph', 'anchor' => 'sah', 'data' => []], 'q' => ['id' => 'q', 'type' => 'paragraph', 'data' => []]];
check('clean() (simpan, kanvas, publik) menormalkan anchor jahat; yang sah utuh; blok tanpa anchor TIDAK ditambahi kunci anchor', BS::clean($ab)['h']['anchor'] === 'x-alert-1' && BS::clean($ab)['p']['anchor'] === 'sah' && !array_key_exists('anchor', BS::clean($ab)['q']) && BS::forPublic($ab, ['en'])['h']['anchor'] === 'x-alert-1');
check('clean() tanpa perubahan bila anchor sah (tidak memaksa simpan ulang)', BS::clean(['p' => $ab['p']]) === ['p' => $ab['p']]);

echo "\nUji acak: idempoten dan tanpa galat\n";
mt_srand(31337);
$junk = ['javascript:alert(1)', '#fff', 'red;x:y', 'a::b', '', null, 5, 1.5, 'auto', '99', ['x'], ['a' => ['b']], "x\ny", '<script>', 'https://ok.test', '/path', 'internal://page/x', str_repeat('a', 300)];
$allOk = true; $msg = '';
for ($i = 0; $i < 4000 && $allOk; $i++) {
    $type = C::TYPES[mt_rand(0, count(C::TYPES) - 1)];
    $d = [];
    foreach (['icon', 'color', 'url', 'col_count', 'col_1_zone_width', 'col_2_zone', 'children', 'cards', 'grid', 'text'] as $k) {
        if (mt_rand(0, 2)) { $d[$k] = $junk[mt_rand(0, count($junk) - 1)]; }
    }
    if (mt_rand(0, 1)) { $d['cards'] = [['container' => ['url' => $junk[mt_rand(0, 17)]], 'layout' => ['children' => [['width' => $junk[mt_rand(0, 17)], 'children' => [['elementType' => mt_rand(0, 1) ? 'icon' : 'button', 'data' => ['content' => ['icon' => $junk[mt_rand(0, 17)], 'url' => $junk[mt_rand(0, 17)]]]]]]]]]]; }
    try {
        $a = BS::forPublic(['b' => ['type' => $type, 'data' => $d]], ['en'], $int);
        $b = BS::forPublic($a, ['en'], $int);
        if ($a !== $b) { $allOk = false; $msg = "tidak idempoten $type " . json_encode($d); }
    } catch (Throwable $e) { $allOk = false; $msg = $e->getMessage() . ' :: ' . $type . ' ' . json_encode($d); }
}
check('4000 data acak (nilai ganjil di setiap kolom): tanpa galat/peringatan dan idempoten', $allOk, $msg);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
