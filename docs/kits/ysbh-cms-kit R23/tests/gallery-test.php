<?php
/**
 * Pemeriksaan blok Galeri / Logo (logika murni):  php tests/gallery-test.php [akar-kit]
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/GalleryStyle', 'Content/Blocks/GalleryList',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}
App\Editor\Modules::all();

use App\Content\Blocks\{BlockSanitizer as S, GalleryList as G, GalleryStyle as St};
use App\Content\Links\LinkResolver;
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\GalleryBlock as B;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }
$loc = ['id', 'en'];

section('GalleryStyle');
check('kolom: angka/teks 2–6 sah; selain itu -> 3; disimpan sebagai TEKS', St::columns(4) === '4' && St::columns('6') === '6' && St::columns(7) === '3' && St::columns('x" onload="1') === '3' && St::columns(['4']) === '3' && St::columns(null) === '3');
$grids = array_map(fn ($c) => St::grid($c), St::COLUMNS); $bases = array_map(fn ($c) => St::basis($c), St::COLUMNS);
check('lima pola grid berbeda dan lima lebar carousel berbeda, tanpa sisa pola template', count(array_unique($grids)) === 5 && count(array_unique($bases)) === 5 && !array_filter(array_merge($grids, $bases), fn ($x) => str_contains($x, '{') || str_contains($x, '$')));
check('grid: ponsel 2 kolom; kolom desktop sesuai pilihan (lg:grid-cols-N)', str_contains(St::grid(5), 'grid-cols-2') && str_contains(St::grid(5), 'lg:grid-cols-5') && !str_contains(St::grid(2), 'md:') && str_contains(St::grid(3), 'md:grid-cols-3') && !str_contains(St::grid(3), 'lg:'));
check('carousel: ponsel 2 per layar, tablet paling banyak 3, desktop sesuai pilihan', str_contains(St::basis(6), 'basis-[calc(50%-0.5rem)]') && str_contains(St::basis(6), 'md:basis-[calc(33.333%-0.667rem)]') && str_contains(St::basis(6), 'lg:basis-[calc(16.666%-0.833rem)]') && !str_contains(St::basis(2), 'md:'));
check('nilai tak sah jatuh ke bawaan (foto, grid, 4:3)', St::mode('x') === 'photos' && St::layout(['a']) === 'grid' && St::ratio('9:16') === '4:3' && St::tile('<b>', 'x') === St::tile('photos', '4:3'));
check('petak: logo = kotak putih 3:2 dengan gambar utuh; foto = rasio pilihan', str_contains(St::tile('logos', '1:1'), 'aspect-[3/2]') && str_contains(St::tile('logos', '1:1'), 'bg-white') && str_contains(St::tile('photos', '16:9'), 'aspect-video') && str_contains(St::tile('photos', '1:1'), 'aspect-square') && !str_contains(St::tile('photos', '1:1'), 'aspect-[3/2]'));
check('gambar: logo utuh (object-contain), hitam-putih hanya bila diminta; foto memotong (object-cover)', str_contains(St::image('logos', false), 'object-contain') && !str_contains(St::image('logos', false), 'grayscale') && str_contains(St::image('logos', true), 'grayscale') && str_contains(St::image('logos', true), 'group-hover:grayscale-0') && str_contains(St::image('photos', true), 'object-cover') && !str_contains(St::image('photos', true), 'grayscale'));

section('GalleryList: butir, alt, tautan');
$files = [
    1 => ['url' => 'https://x.test/storage/logo-mitra_utama.png', 'name' => 'logo-mitra_utama.png', 'mime' => 'image/png', 'size' => 100],
    2 => ['url' => 'https://x.test/storage/foto.JPG', 'name' => 'foto.JPG', 'mime' => 'IMAGE/JPEG', 'size' => 100],
    3 => ['url' => 'https://x.test/storage/laporan.pdf', 'name' => 'laporan.pdf', 'mime' => 'application/pdf', 'size' => 100],
];
$res = new LinkResolver(page: fn (int $id, string $loc) => $id === 5 ? "/p/tentang-$loc" : null, article: fn (int $id, string $loc) => null, file: fn (int $id) => null);
$link = fn (array $o = []) => $o + ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];
$it = fn (?int $media, string $title = '', string $cap = '', ?array $lk = null, string $id = 'itm_x') => ['id' => $id, 'title' => ['id' => $title, 'en' => ''], 'caption' => ['id' => $cap, 'en' => ''], 'image' => ['media_id' => $media, 'url' => ''], 'link' => $lk ?? $link()];
$data = ['items' => [
    $it(1, '', '', $link(['kind' => 'url', 'ref' => 'https://mitra.test', 'new_tab' => true]), 'a'), $it(2, 'Penyuluhan', 'Posyandu 2026', null, 'b'), $it(null, 'Tanpa gambar', '', null, 'c'), $it(99, 'Hilang', '', null, 'd'), $it(3, 'Bukan gambar', '', null, 'e'),
    $it(2, 'Halaman offline', '', $link(['kind' => 'page', 'ref' => 9]), 'f'), $it(2, 'Halaman online', '', $link(['kind' => 'page', 'ref' => 5]), 'g'), $it(2, 'Telepon', '', $link(['kind' => 'tel', 'ref' => '119', 'new_tab' => true]), 'h'),
]];
$pub = G::prepare($data, $files, $res, 'id'); $by = array_column($pub, null, 'id');
check('publik: butir tanpa gambar / gambar hilang / berkas bukan gambar DILEWATI; urutan terjaga', array_column($pub, 'id') === ['a', 'b', 'f', 'g', 'h'], json_encode(array_column($pub, 'id')));
check('mime huruf besar (IMAGE/JPEG) tetap dikenali sebagai gambar', $by['b']['src'] === 'https://x.test/storage/foto.JPG' && !$by['b']['incomplete']);
check('alt kosong -> nama berkas yang dirapikan ("logo-mitra_utama.png" -> "logo mitra utama"); alt/keterangan diisi -> dipakai', $by['a']['alt'] === 'logo mitra utama' && $by['b']['alt'] === 'Penyuluhan' && $by['b']['caption'] === 'Posyandu 2026');
check('tautan URL + tab baru; tanpa tautan -> href null', $by['a']['href'] === 'https://mitra.test' && $by['a']['newTab'] === true && $by['b']['href'] === null && $by['b']['newTab'] === false);
check('halaman online: tautan sesuai bahasa; telepon: href tel: dan TANPA tab baru', $by['g']['href'] === '/p/tentang-id' && $by['h']['href'] === 'tel:119' && $by['h']['newTab'] === false);
check('tautan ke halaman OFFLINE: gambar TETAP tampil (hanya tanpa tautan) dan ditandai linkIssue', $by['f']['src'] !== null && $by['f']['href'] === null && $by['f']['linkIssue'] === true && $by['g']['linkIssue'] === false);
$can = G::prepare($data, $files, $res, 'id', true); $cb = array_column($can, null, 'id');
check('kanvas: SEMUA 8 butir tampil; yang tak lengkap bertanda dengan penjelasan', count($can) === 8 && count(array_filter($can, fn ($i) => $i['incomplete'])) === 3 && $cb['c']['note'] === 'Gambar belum dipilih.' && str_contains($cb['d']['note'], 'tidak ditemukan') && $cb['e']['note'] === 'Berkas yang dipilih bukan gambar.' && $cb['c']['src'] === null);
check('butir tak lengkap tidak pernah mendapat href (walau tautannya terisi)', G::prepare(['items' => [$it(null, 'x', '', $link(['ref' => 'https://a.test']), 'z')]], $files, $res, 'id', true)[0]['href'] === null);
check('bahasa en: judul en kosong jatuh ke id', G::prepare(['items' => [$it(2, 'Penyuluhan', '', null, 'z')]], $files, $res, 'en')[0]['alt'] === 'Penyuluhan');
check('tanpa butir sama sekali -> daftar kosong', G::prepare(['items' => []], [], $res, 'id') === [] && G::prepare([], [], $res, 'id') === []);
check('fromFilename: aman untuk nama aneh (kosong, hanya simbol, sangat panjang, berisi jalur folder)', G::fromFilename('') === '' && G::fromFilename('___---.png') === '' && mb_strlen(G::fromFilename(str_repeat('a', 300) . '.png')) === 80 && !str_contains(G::fromFilename('a/b/c.png'), '/') && G::fromFilename('a/b/c.png') === 'c');

section('GalleryBlock::sanitize');
$good = ['mode' => 'logos', 'layout' => 'carousel', 'columns' => '5', 'ratio' => '16:9', 'grayscale' => true, 'lightbox' => false, 'autoplay' => true,
         'items' => [['id' => 'itm_a1b2c3d4', 'title' => ['id' => 'Mitra A', 'en' => 'Partner A'], 'caption' => ['id' => 'Kemitraan', 'en' => ''], 'image' => ['media_id' => 1, 'url' => 'https://x.test/storage/a.png'],
                      'link' => ['kind' => 'url', 'ref' => 'https://mitra.test', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => true]]]];
check('data sah TIDAK berubah; idempoten', B::sanitize($good, $loc) === $good && B::sanitize(B::sanitize($good, $loc), $loc) === $good, json_encode(B::sanitize($good, $loc)));
$evil = B::sanitize(['mode' => 'x" onload="1', 'layout' => ['x'], 'columns' => 99, 'ratio' => '9:16', 'grayscale' => '', 'lightbox' => '0', 'autoplay' => 'ya', 'z' => 1, 'items' => [
    ['id' => 'a b', 'title' => ['id' => "  Usia <5\ttahun\n", 'en' => str_repeat('x', 300), 'fr' => 'bocor'], 'caption' => ['id' => str_repeat('k', 400)], 'image' => ['media_id' => '7', 'url' => "javascript:alert(1)\x00"], 'link' => ['kind' => 'url', 'ref' => 'javascript:alert(1)']],
    ['image' => ['media_id' => 'x', 'url' => 'https://evil.test/a.png']], ['image' => ['media_id' => -3]], 'bukan-larik', null]], $loc);
check('mode/tampilan/kolom/rasio tak sah -> bawaan; bendera menjadi boolean; kunci asing dibuang', $evil['mode'] === 'photos' && $evil['layout'] === 'grid' && $evil['columns'] === '3' && $evil['ratio'] === '4:3' && $evil['grayscale'] === false && $evil['lightbox'] === false && $evil['autoplay'] === true && array_keys($evil) === ['mode', 'layout', 'columns', 'ratio', 'grayscale', 'lightbox', 'autoplay', 'items']);
check('entri bukan larik dibuang; ID tak sah diganti; judul satu baris "<5" utuh dan dipotong; keterangan dipotong', count($evil['items']) === 3 && preg_match('/^itm_[a-f0-9]{8}$/', $evil['items'][0]['id']) === 1 && $evil['items'][0]['title'] === ['id' => 'Usia <5 tahun', 'en' => str_repeat('x', 150)] && mb_strlen($evil['items'][0]['caption']['id']) === 300);
check('media_id: "7" -> 7; "x" / -3 -> null; url tanpa media_id DIKOSONGKAN; karakter kontrol dibuang', $evil['items'][0]['image']['media_id'] === 7 && $evil['items'][1]['image'] === ['media_id' => null, 'url' => ''] && $evil['items'][2]['image']['media_id'] === null && !preg_match('/[\x00-\x1f]/', $evil['items'][0]['image']['url']));
check('tautan javascript: DIKOSONGKAN (aturan sama dengan tombol); telepon 119 sah', $evil['items'][0]['link']['ref'] === '' && B::sanitize(['items' => [['link' => ['kind' => 'tel', 'ref' => '119', 'new_tab' => true]]]], $loc)['items'][0]['link'] === ['kind' => 'tel', 'ref' => '119', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false]);
check('maksimal 60 butir', count(B::sanitize(['items' => array_fill(0, 90, ['title' => ['id' => 'x']])], $loc)['items']) === 60);
check('tanpa data -> bawaan aman (foto, grid, 3 kolom, lightbox aktif, tanpa autoplay)', B::sanitize([], $loc) === ['mode' => 'photos', 'layout' => 'grid', 'columns' => '3', 'ratio' => '4:3', 'grayscale' => false, 'lightbox' => true, 'autoplay' => false, 'items' => []]);

section('Penyambungan modul');
check('Modules menemukan GalleryBlock; modul lain tetap ada', Modules::for('gallery-builder') === B::class && Modules::for('video-builder') !== null && Modules::for('callout-builder') !== null && Modules::for('downloads-builder') !== null && Modules::for('accordion-builder') !== null);
$def = BlockRegistry::block('gallery-builder');
check('registri: panel block:gallery-builder; palet: "Galeri / Logo", tingkat atas + kolom, bukan step-group', $def !== null && $def->panelKey() === 'block:gallery-builder' && BlockPalette::label('gallery-builder') === 'Galeri / Logo' && BlockPalette::allowedAtRoot('gallery-builder') && BlockPalette::allowsChild('multi-columns', 'gallery-builder') && !BlockPalette::allowsChild('step-group', 'gallery-builder'));
check('BlockSanitizer::forType dan clean() memanggil pembersih modul', S::forType('gallery_builder', ['mode' => 'x'], $loc)['mode'] === 'photos' && S::clean(['a' => ['id' => 'a', 'type' => 'gallery-builder', 'data' => ['layout' => 'x']]], $loc)['a']['data']['layout'] === 'grid');
$mat = Defaults::materialize($def->defaults, $loc);
check('bawaan: satu butir kosong (ID baru, tanpa gambar), tanpa penanda "@"; lolos pembersih TANPA berubah', count($mat['items']) === 1 && $mat['items'][0]['title'] === ['id' => '', 'en' => ''] && $mat['items'][0]['image'] === ['media_id' => null, 'url' => ''] && !str_contains(json_encode($mat), '@') && B::sanitize($mat, $loc) === $mat, json_encode(B::sanitize($mat, $loc)));
$rep = array_values(array_filter($def->fields, fn ($f) => $f->type === 'repeater'))[0];
$itemKeys = array_values(array_unique(array_map(fn ($f) => explode('.', $f->key)[0], $rep->extra['fields']))); sort($itemKeys);
$sanKeys = array_values(array_diff(array_keys($mat['items'][0]), ['id'])); sort($sanKeys);
check('bidang item di inspektur = bidang item yang dikenal pembersih', $itemKeys === $sanKeys, json_encode([$itemKeys, $sanKeys]));
$tops = array_values(array_map(fn ($f) => substr($f->key, 5), array_filter($def->fields, fn ($f) => str_starts_with($f->key, 'data.')))); sort($tops); $san = array_keys($mat); sort($san);
check('bidang blok di inspektur = bidang yang dikenal pembersih', $tops === $san, json_encode([$tops, $san]));
$byk = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan jenis/tampilan/kolom/rasio di inspektur = daftar GalleryStyle', $byk('data.mode')->allowedValues() === St::MODES && $byk('data.layout')->allowedValues() === St::LAYOUTS && $byk('data.columns')->allowedValues() === St::COLUMNS && $byk('data.ratio')->allowedValues() === St::RATIOS);
$img = array_values(array_filter($rep->extra['fields'], fn ($f) => $f->type === 'media'))[0];
check("gambar: pemilih dengan filter 'image'; judul butir bernama 'title' (jadi label baris repeater)", ($img->extra['accept'] ?? null) === 'image' && in_array('title', array_map(fn ($f) => $f->key, $rep->extra['fields']), true));
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', !array_filter($def->fields, fn ($f) => $f->allowedValues() !== null && $f->default !== null && !in_array((string) $f->default, array_map('strval', $f->allowedValues()), true)));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
