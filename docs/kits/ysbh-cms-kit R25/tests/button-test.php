<?php
/**
 * Pemeriksaan blok Tombol (logika murni, tanpa Laravel):  php tests/button-test.php [akar-kit]
 * Tautan aman, pembersih data, gaya tombol, dan kecocokan registri <-> nilai bawaan <-> pembersih.
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Names', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) {
        $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v;
    }
}

use App\Content\Blocks\BlockSanitizer as S;
use App\Content\Blocks\ButtonStyle as B;
use App\Content\Links\LinkResolver as L;
use App\Editor\BlockRegistry;
use App\Editor\Defaults;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }

$calls = [];
$r = new L(
    page: function (int $id, string $loc) use (&$calls) { $calls[] = "page:$id:$loc"; return $id === 5 ? "/p/tentang-$loc" : null; },   // 5 online; lainnya offline
    article: function (int $id, string $loc) use (&$calls) { $calls[] = "article:$id"; return $id === 9 ? '/a/berita' : null; },
    file: function (int $id) use (&$calls) { $calls[] = "file:$id"; return $id === 7 ? 'https://x.test/storage/laporan.pdf' : null; },
);
$u = fn (array $link) => $r->url($link, 'id');

section('Tautan URL: hanya yang AMAN');
foreach (['https://ysbh.org/donasi' => 'https://ysbh.org/donasi', 'http://x.test' => 'http://x.test', '/donasi' => '/donasi', '/' => '/', ' https://x.test/a?b=1#c ' => 'https://x.test/a?b=1#c'] as $in => $want) {
    check("diterima: " . json_encode($in), $u(['kind' => 'url', 'ref' => $in]) === $want, (string) $u(['kind' => 'url', 'ref' => $in]));
}
foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)", 'data:text/html,<script>1</script>', 'vbscript:msgbox(1)', '//evil.com/x', 'ftp://x.test/f', 'https://', 'https:///x', 'x.test/tanpa-skema', 'https://a.test/ spasi', "https://a.test/\nlain", 'https://evil.com\\@x.test', 'file:///etc/passwd', 'mailto:a@b.co', ''] as $bad) {
    check("DITOLAK: " . json_encode($bad), $u(['kind' => 'url', 'ref' => $bad]) === null);
}
check('URL terlalu panjang (>2048) ditolak', $u(['kind' => 'url', 'ref' => 'https://x.test/' . str_repeat('a', 2100)]) === null);

section('Telepon, surel, anchor');
check("telepon dinormalkan: '+62 812-3456-7890' -> tel:+6281234567890", $u(['kind' => 'tel', 'ref' => '+62 812-3456-7890']) === 'tel:+6281234567890');
check("telepon: '(0967) 123-456' -> tel:0967123456", $u(['kind' => 'tel', 'ref' => '(0967) 123-456']) === 'tel:0967123456');
check('telepon: tanda + hanya di depan; 2 angka ditolak, nomor darurat 3 angka (119/112) SAH; huruf dibuang', $u(['kind' => 'tel', 'ref' => '08+12345']) === 'tel:0812345' && $u(['kind' => 'tel', 'ref' => '12']) === null && $u(['kind' => 'tel', 'ref' => '119']) === 'tel:119' && $u(['kind' => 'tel', 'ref' => '112']) === 'tel:112' && $u(['kind' => 'tel', 'ref' => 'abc']) === null);
check('surel sah -> mailto:', $u(['kind' => 'mailto', 'ref' => 'info@ysbh.org']) === 'mailto:info@ysbh.org');
check('surel tak sah / berisi tambahan header ditolak', $u(['kind' => 'mailto', 'ref' => 'bukan-surel']) === null && $u(['kind' => 'mailto', 'ref' => "a@b.co?subject=x&bcc=y@z.co"]) === null && $u(['kind' => 'mailto', 'ref' => "a@b.co\nBcc: x@y.co"]) === null);
check("anchor: 'kontak' dan '#kontak' -> #kontak", $u(['kind' => 'anchor', 'ref' => 'kontak']) === '#kontak' && $u(['kind' => 'anchor', 'ref' => '#kontak']) === '#kontak');
check('anchor tak sah (spasi, diawali angka, kosong, tanda kutip)', $u(['kind' => 'anchor', 'ref' => 'a b']) === null && $u(['kind' => 'anchor', 'ref' => '1abc']) === null && $u(['kind' => 'anchor', 'ref' => '']) === null && $u(['kind' => 'anchor', 'ref' => 'a"onclick']) === null);

section('Halaman, artikel, berkas (lewat ID)');
check('halaman online -> URL dari fungsi, dengan ID bilangan bulat dan bahasa', $u(['kind' => 'page', 'ref' => '5']) === '/p/tentang-id' && $r->url(['kind' => 'page', 'ref' => 5], 'en') === '/p/tentang-en' && in_array('page:5:en', $calls, true));
check('halaman offline/tidak ada (fungsi mengembalikan null) -> tombol tidak ditautkan', $u(['kind' => 'page', 'ref' => 6]) === null);
check('artikel terbit -> URL; belum terbit -> null', $u(['kind' => 'article', 'ref' => 9]) === '/a/berita' && $u(['kind' => 'article', 'ref' => 10]) === null);
$calls = [];
foreach (['abc', '0', '-3', '5.5', '', null, ['5'], "5; DROP"] as $badId) check('ID tak sah tidak memanggil pencarian: ' . json_encode($badId), $u(['kind' => 'page', 'ref' => $badId]) === null);
check('...dan memang tidak ada pemanggilan sama sekali', $calls === [], json_encode($calls));
check('berkas: HANYA media_id dipercaya; url/ref dari browser diabaikan', $u(['kind' => 'file', 'media_id' => 7, 'ref' => 'https://evil.test', 'url' => 'javascript:1']) === 'https://x.test/storage/laporan.pdf');
check('berkas tanpa media_id sah -> null (url saja tidak cukup)', $u(['kind' => 'file', 'url' => 'https://x.test/a.pdf']) === null && $u(['kind' => 'file', 'media_id' => 0]) === null && $u(['kind' => 'file', 'media_id' => 99]) === null);
check('jenis tak dikenal -> null', $u(['kind' => 'eval', 'ref' => 'x']) === null && $u(['kind' => ['x'], 'ref' => 'https://x.test']) === null);

section('Gaya tombol: kelas hanya dari daftar');
$all = []; foreach (B::VARIANTS as $v) foreach (B::COLORS as $c) foreach (B::SIZES as $s) $all[] = B::button($v, $c, $s);
check('36 kombinasi (3 gaya x 4 warna x 3 ukuran), semuanya berbeda', count($all) === 36 && count(array_unique($all)) === 36);
check('tidak ada sisa pola template ("{", "$")', !array_filter($all, fn ($x) => str_contains($x, '{') || str_contains($x, '$')));
check('nilai tak sah jatuh ke bawaan; tidak ada teks sisipan di kelas', B::button('x" onclick="1', ['a'], '<b>') === B::button('solid', 'foresty', 'md') && !str_contains(B::button('x" onclick="1', 'y', 'z'), 'onclick'));
check('ditumpuk: tombol memenuhi lebar di layar kecil, normal dari sm', str_contains(B::button('solid', 'coral', 'md', true), 'w-full sm:w-auto') && !str_contains(B::button('solid', 'coral', 'md', false), 'w-full'));
check('baris: 3 perataan x tumpuk; nilai tak sah -> kiri', str_contains(B::row('center', false), 'justify-center') && str_contains(B::row('right', true), 'flex-col') && str_contains(B::row('right', true), 'sm:justify-end') && B::row('zzz', false) === B::row('left', false));

section('Pembersih data blok');
$good = ['align' => 'center', 'stack_mobile' => false, 'buttons' => [[
    'id' => 'itm_abc12345', 'label' => ['id' => 'Donasi', 'en' => 'Donate'],
    'link' => ['kind' => 'url', 'ref' => 'https://ysbh.org/donasi', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => true],
    'variant' => 'outline', 'color' => 'coral', 'size' => 'lg', 'icon' => 'heart', 'icon_position' => 'right']]];
check('data sah TIDAK berubah sama sekali', S::buttonBuilder($good) === $good, json_encode(S::buttonBuilder($good)));
check('idempoten: dibersihkan dua kali = sekali', S::buttonBuilder(S::buttonBuilder($good)) === S::buttonBuilder($good));
$evil = ['align' => 'x" onload="1', 'stack_mobile' => 'ya', 'buttons' => [
    ['id' => 'a b"c', 'label' => ['id' => '<script>alert(1)</script>Halo   dunia', 'en' => str_repeat('x', 100), 'fr' => 'bocor'],
     'link' => ['kind' => 'url', 'ref' => 'javascript:alert(document.cookie)', 'new_tab' => 'yes'], 'variant' => 'neon', 'color' => 'red" class="x', 'size' => 'xl', 'icon' => 'a b<c', 'icon_position' => 'atas'],
    'bukan-array', 5, null,
]];
$c = S::buttonBuilder($evil); $b = $c['buttons'][0];
check('perataan tak sah -> kiri; stack_mobile menjadi boolean', $c['align'] === 'left' && $c['stack_mobile'] === true);
check('entri bukan larik dibuang', count($c['buttons']) === 1);
check('ID tak sah diganti ID baru yang sah', preg_match('/^itm_[a-f0-9]{8}$/', $b['id']) === 1);
check('teks: tag dibuang, spasi dirapikan, dipotong 60, bahasa di luar daftar dibuang', $b['label'] === ['id' => 'alert(1)Halo dunia', 'en' => str_repeat('x', 60)], json_encode($b['label']));
check('URL javascript: dikosongkan (tombol tidak akan ditautkan)', $b['link']['ref'] === '' && $b['link']['kind'] === 'url');
check('gaya/warna/ukuran/ikon/posisi tak sah -> bawaan', $b['variant'] === 'solid' && $b['color'] === 'foresty' && $b['size'] === 'md' && $b['icon'] === '' && $b['icon_position'] === 'left');
check('lebih dari 12 tombol dipotong menjadi 12', count(S::buttonBuilder(['buttons' => array_fill(0, 30, ['label' => ['id' => 'x']])])['buttons']) === 12);
$k = fn (array $link) => S::buttonBuilder(['buttons' => [['link' => $link]]])['buttons'][0]['link'];
check('jenis tautan tak dikenal -> url', $k(['kind' => 'eval', 'ref' => 'x'])['kind'] === 'url');
check('halaman: ref dipaksa ID bilangan bulat ("5" -> 5, "abc" -> kosong)', $k(['kind' => 'page', 'ref' => '5'])['ref'] === 5 && $k(['kind' => 'page', 'ref' => 'abc'])['ref'] === '');
check('berkas: media_id dipertahankan; ref liar dibuang', ($f = $k(['kind' => 'file', 'media_id' => '7', 'ref' => 'https://evil.test', 'url' => 'laporan.pdf']))['media_id'] === 7 && $f['ref'] === '' && $f['url'] === 'laporan.pdf');
check('berpindah jenis (berkas -> URL) tidak membawa media_id lama', ($g = $k(['kind' => 'url', 'ref' => 'https://x.test', 'media_id' => 7, 'url' => 'a.pdf']))['media_id'] === null && $g['url'] === '' && $g['ref'] === 'https://x.test');
check('telepon/surel/anchor: tidak bisa membuka tab baru', !$k(['kind' => 'tel', 'ref' => '081234567', 'new_tab' => true])['new_tab'] && !$k(['kind' => 'mailto', 'ref' => 'a@b.co', 'new_tab' => true])['new_tab'] && !$k(['kind' => 'anchor', 'ref' => 'x', 'new_tab' => true])['new_tab']);
$blocks = ['b1' => ['id' => 'b1', 'type' => 'button_builder', 'data' => $evil], 'h' => ['id' => 'h', 'type' => 'heading', 'data' => ['text' => ['id' => '<b>x</b>']]]];
$cb = S::clean($blocks);
check("clean(): mengenali 'button_builder' (garis bawah); blok lain TIDAK disentuh", $cb['b1']['data']['buttons'][0]['link']['ref'] === '' && $cb['h'] === $blocks['h']);

section('Registri <-> nilai bawaan <-> pembersih (tidak boleh saling bertentangan)');
$def = BlockRegistry::block('button-builder');
check('Tombol terdaftar, dengan nilai bawaan', $def !== null && $def->defaults !== []);
$mat = Defaults::materialize($def->defaults, ['id', 'en']);
check('bawaan: satu tombol, ID baru sah, teks kosong per bahasa, bukan isi palsu', count($mat['buttons']) === 1 && preg_match('/^itm_[a-f0-9]{8}$/', $mat['buttons'][0]['id']) && $mat['buttons'][0]['label'] === ['id' => '', 'en' => ''], json_encode($mat['buttons'][0]));
check('dua pembuatan menghasilkan ID berbeda', Defaults::materialize($def->defaults)['buttons'][0]['id'] !== $mat['buttons'][0]['id']);
check('nilai bawaan melewati pembersih TANPA berubah (registri dan pembersih sepakat)', S::buttonBuilder($mat) === $mat, json_encode(S::buttonBuilder($mat)));
check('bahasa aktif ikut ke bawaan', array_keys(Defaults::materialize($def->defaults, ['id', 'en', 'fr'])['buttons'][0]['label']) === ['id', 'en', 'fr']);
$rep = array_values(array_filter($def->fields, fn ($f) => $f->type === 'repeater'))[0];
$itemKeysTop = array_unique(array_map(fn ($f) => explode('.', $f->key)[0], $rep->extra['fields']));
sort($itemKeysTop); $sanKeys = array_keys($mat['buttons'][0]); $sanKeys = array_values(array_diff($sanKeys, ['id'])); sort($sanKeys);
check('setiap bidang item yang dikenal pembersih punya kontrol di inspektur, dan sebaliknya', $itemKeysTop === $sanKeys, json_encode([$itemKeysTop, $sanKeys]));
$bad = [];
foreach ($rep->extra['fields'] as $f) {
    $allowed = $f->allowedValues();
    if ($allowed === null) continue;
    $val = $mat['buttons'][0]; foreach (explode('.', $f->key) as $seg) { $val = $val[$seg] ?? null; }
    if (!in_array($val, $allowed, true)) $bad[] = $f->key . '=' . json_encode($val);
    if ($f->default !== null && !in_array($f->default, $allowed, true)) $bad[] = $f->key . ' default ' . json_encode($f->default);
}
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', $bad === [], implode(', ', $bad));
check('pilihan gaya/warna/ukuran di inspektur = daftar yang diizinkan pembersih dan ButtonStyle', (function () use ($rep) {
    $by = fn ($key) => array_values(array_filter($rep->extra['fields'], fn ($f) => $f->key === $key))[0]->allowedValues();
    return $by('variant') === B::VARIANTS && $by('color') === B::COLORS && $by('size') === B::SIZES;
})());
check('perataan di inspektur = daftar ButtonStyle', array_values(array_filter($def->fields, fn ($f) => $f->key === 'data.align'))[0]->allowedValues() === B::ALIGNS);
check('daftar jenis tautan = jenis yang bisa dipilih di kontrol tautan', L::KINDS === ['page', 'article', 'file', 'url', 'tel', 'mailto', 'anchor']);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
