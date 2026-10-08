<?php
/**
 * Uji ContentType & ContentDocument (tanpa Laravel).   php tests/content-test.php [path/ke/app/Content]
 */
$dir = $argv[1] ?? __DIR__ . '/../app/Content';
require_once "$dir/LocaleMap.php";
require_once "$dir/Names.php";
require_once "$dir/ContentType.php";
require_once "$dir/ContentDocument.php";

use App\Content\ContentDocument as Doc;
use App\Content\ContentType as T;
use App\Content\LocaleMap as L;
use App\Content\Names as N;

$pass = $fail = 0;
function check(string $name, bool $ok, string $extra = ''): void {
    global $pass, $fail; $ok ? $pass++ : $fail++;
    echo ($ok ? '  PASS  ' : '  FAIL  ') . $name . ($ok || $extra === '' ? '' : "   [$extra]") . "\n";
}
function section(string $t): void { echo "\n$t\n"; }

// ------------------------------------------------------------------
section('ContentType: dari nama rute');
foreach ([
    'page.create' => T::Page, 'page.edit' => T::Page, 'page.preview' => T::Page,
    'article.write' => T::Article, 'article.edit' => T::Article,
    'snippet.create' => T::Snippet, 'v2.page.create' => T::Page, 'admin.article.edit' => T::Article,
] as $name => $want) {
    check("$name -> {$want->value}", T::fromRouteName($name) === $want);
}
foreach ([null, '', 'dashboard', 'livewire.update'] as $bad) {
    try { T::fromRouteName($bad); check('menolak rute bukan builder: ' . var_export($bad, true), false); }
    catch (InvalidArgumentException) { check('menolak rute bukan builder: ' . var_export($bad, true), true); }
}

section('ContentType: metadata');
check('parameter rute: page/post/snippet', [T::Page->routeParam(), T::Article->routeParam(), T::Snippet->routeParam()] === ['page', 'post', 'snippet']);
check('rute edit: page.edit, article.edit, snippet.edit', [T::Page->editRoute(), T::Article->editRoute(), T::Snippet->editRoute()] === ['page.edit', 'article.edit', 'snippet.edit']);
check('status halaman hanya offline/online; artikel enam status', T::Page->statuses() === ['offline', 'online'] && count(T::Article->statuses()) === 6);
check("status awal halaman = 'offline' (bukan 'draft' seperti editor lama), artikel = 'draft'", T::Page->defaultStatus() === 'offline' && T::Article->defaultStatus() === 'draft');
check('status awal selalu lolos aturannya sendiri', array_reduce(T::cases(), fn ($ok, $t) => $ok && in_array($t->defaultStatus(), $t->statuses(), true), true));
check('aturan status', T::Page->statusRule() === 'required|in:offline,online');
check('kunci judul: ui.title.article.create / ui.title.page.edit', T::Article->titleKey(false) === 'ui.title.article.create' && T::Page->titleKey(true) === 'ui.title.page.edit');
check('kunci header menggantikan ui.header.write_page yang tampil mentah', T::Article->headerKey(false) === 'ui.header.article.create');
check('nama rute edit & kunci judul unik per jenis dan mode', count(array_unique(array_merge(...array_map(fn ($t) => [$t->titleKey(true), $t->titleKey(false)], T::cases())))) === 6);

section('ContentType: kemampuan per jenis');
check('snippet tanpa slug & metadata SEO, memakai key', !T::Snippet->usesSlug() && !T::Snippet->usesMeta() && T::Snippet->usesKey());
check('halaman & artikel punya slug + metadata, tanpa key', T::Page->usesSlug() && T::Article->usesMeta() && !T::Page->usesKey() && !T::Article->usesKey());

// ------------------------------------------------------------------
section('ContentDocument: format sekarang');
$new = ['blocks' => ['a' => ['id' => 'a', 'type' => 'heading'], 'b' => ['id' => 'b', 'type' => 'paragraph']], 'order' => ['a', 'b'], 'settings' => ['toc_position' => 'left']];
$d = Doc::fromRaw($new);
check('array dibaca apa adanya', $d->blocks === $new['blocks'] && $d->order === ['a', 'b'] && $d->settings['toc_position'] === 'left');
check('string JSON dibaca sama', Doc::fromRaw(json_encode($new))->toArray() === $d->toArray());
check('JSON di dalam JSON (cacat lama) dibaca sama', Doc::fromRaw(json_encode(json_encode($new)))->toArray() === $d->toArray());
check('toArray -> fromRaw bolak-balik stabil', Doc::fromRaw($d->toArray())->toArray() === $d->toArray());

section('ContentDocument: pembersihan otomatis');
$d = Doc::fromRaw(['blocks' => ['a' => ['type' => 'heading'], 'settings' => ['toc_position' => 'hidden']], 'order' => ['a']]);
check("'settings' yang terselip di blocks dipindahkan ke tempatnya", !isset($d->blocks['settings']) && $d->settings['toc_position'] === 'hidden');
$d = Doc::fromRaw(['blocks' => ['a' => ['type' => 'heading']], 'order' => ['a', 'hantu', 7, null]]);
check('ID hantu & non-string di urutan dibuang', $d->order === ['a']);
$d = Doc::fromRaw(['blocks' => ['a' => ['type' => 'heading']], 'order' => ['a']]);
check("pengaturan bawaan toc_position = 'right' bila kosong", $d->settings === ['toc_position' => 'right']);
$d = Doc::fromRaw(['blocks' => ['a' => ['type' => 'heading']], 'order' => ['a'], 'settings' => ['toc_position' => 'left', 'x' => 1]]);
check('nilai pengguna tidak ditimpa bawaan, kunci lain dipertahankan', $d->settings['toc_position'] === 'left' && $d->settings['x'] === 1);

section('ContentDocument: format lama (seeder lawas)');
$d = Doc::fromRaw(['id' => [['type' => 'heading', 'id' => 'h1'], ['type' => 'paragraph']], 'en' => []]);
check("{id:[blok...]}: blok dibaca, ID diberikan bila hilang, urutan sama", count($d->blocks) === 2 && $d->order[0] === 'h1' && str_starts_with($d->order[1], 'blk_') && isset($d->blocks[$d->order[1]]));
$d = Doc::fromRaw(['en' => [['type' => 'heading']]]);
check('{en:[blok...]} dibaca bila tidak ada id', count($d->blocks) === 1);
$d = Doc::fromRaw([['type' => 'heading', 'id' => 'x'], ['bukan' => 'blok'], 'sampah']);
check('daftar blok langsung; entri bukan blok diabaikan', $d->order === ['x'] && count($d->blocks) === 1);

section('ContentDocument: data kosong / bukan teks');
foreach ([null, '', '   ', 123, [], '[]', '{}', 'null', ['id' => '', 'en' => null]] as $bad) {
    $d = Doc::fromRaw($bad);
    check('menghasilkan dokumen kosong tanpa galat: ' . json_encode($bad), $d->blocks === [] && $d->order === [] && $d->settings === Doc::DEFAULT_SETTINGS && $d->imported === false);
}

section('ContentDocument: IMPOR isi lama (artikel dari editor lama) — jangan sampai kosong');
$html = '<p>Imunisasi dasar <strong>lengkap</strong></p><img src="https://x.test/storage/articles/a.webp">';
$d = Doc::fromRaw($html);
$blk = $d->blocks[$d->order[0] ?? ''] ?? [];
check('HTML satu bahasa -> satu blok Paragraf, ditandai imported', count($d->order) === 1 && ($blk['type'] ?? '') === 'paragraph' && $d->imported === true);
check('HTML utuh (tag dan gambar) tersimpan di bahasa utama; bahasa lain kosong', ($blk['data']['text']['id'] ?? null) === $html && ($blk['data']['text']['en'] ?? 'x') === '');
$d = Doc::fromRaw('Halo', ['en', 'id']); $b = $d->blocks[$d->order[0]];
check('bahasa utama = bahasa pertama yang diberikan (en)', $b['data']['text'] === ['en' => 'Halo', 'id' => '']);
$d = Doc::fromRaw(['id' => '<p>Satu</p>', 'en' => '<p>One</p>']);
$blk = $d->blocks[$d->order[0]];
check('HTML per bahasa {id,en} -> satu Paragraf dengan kedua bahasa', $d->imported && $blk['data']['text'] === ['id' => '<p>Satu</p>', 'en' => '<p>One</p>']);
$d = Doc::fromRaw(json_encode(['id' => '<p>Satu</p>', 'en' => '']));
check('versi string JSON dibaca sama; bahasa kosong dibuang dari impor tetapi kunci tetap ada', $d->imported && $d->blocks[$d->order[0]]['data']['text'] === ['id' => '<p>Satu</p>', 'en' => '']);
$d = Doc::fromRaw(json_encode('<p>x</p>')); $b = $d->blocks[$d->order[0] ?? ''] ?? [];
check('string JSON yang membungkus teks (\\"<p>x</p>\\") dibuka lalu diimpor', $d->imported && ($b['data']['text']['id'] ?? null) === '<p>x</p>');
$d = Doc::fromRaw('Teks polos tanpa tag');
check('teks polos (bukan JSON) juga diimpor, bukan dibuang', $d->imported && str_contains(json_encode($d->blocks), 'Teks polos tanpa tag'));
$new = ['blocks' => ['a' => ['id' => 'a', 'type' => 'heading', 'data' => []]], 'order' => ['a']];
check('dokumen blok yang valid TIDAK ditandai imported', Doc::fromRaw($new)->imported === false && Doc::fromRaw(json_encode($new))->imported === false);
check('toArray tidak membawa bendera imported (tidak ikut tersimpan ke database)', !array_key_exists('imported', Doc::fromRaw($html)->toArray()));
check("impor tidak menelan format blok lama: {id:[blok]} tetap dibaca sebagai blok", Doc::fromRaw(['id' => [['type' => 'heading', 'id' => 'h1']]])->imported === false);

section('LocaleMap: kolom teks per bahasa dari nilai mentah');
$loc = ['id', 'en'];
check('JSON per bahasa', L::from('{"id":"Judul","en":"Title"}', $loc) === ['id' => 'Judul', 'en' => 'Title']);
check('array langsung', L::from(['id' => 'A', 'en' => 'B'], $loc) === ['id' => 'A', 'en' => 'B']);
check('bahasa yang kurang dilengkapi kosong', L::from('{"id":"Judul"}', $loc) === ['id' => 'Judul', 'en' => '']);
check('teks polos (editor lama) -> bahasa pertama', L::from('Tentang Kami', $loc) === ['id' => 'Tentang Kami', 'en' => '']);
check('string JSON ("\"Judul\"") dari kolom lama -> bahasa pertama', L::from('"Judul"', $loc) === ['id' => 'Judul', 'en' => '']);
check('judul angka ("2026") tidak dianggap angka JSON', L::from('2026', $loc) === ['id' => '2026', 'en' => '']);
check('judul bertanda kutip ("Halo" dunia) bukan JSON valid -> tetap teks', L::from('"Halo" dunia', $loc) === ['id' => '"Halo" dunia', 'en' => '']);
check('JSON berlapis', L::from(json_encode(json_encode(['id' => 'X'])), $loc) === ['id' => 'X', 'en' => '']);
foreach ([null, '', 'null', '[]', 5] as $v) check('kosong/aneh -> semua kosong: ' . json_encode($v), L::from($v, $loc) === ['id' => '', 'en' => '']);

section('Names: nama kategori/tag yang berupa peta bahasa (cast array)');
check('bahasa aktif lebih dulu', N::of(['id' => 'Kesehatan', 'en' => 'Health'], 'en') === 'Health');
check('bahasa aktif kosong -> cadangan id, lalu en', N::of(['id' => 'Kesehatan', 'en' => 'Health'], 'fr') === 'Kesehatan' && N::of(['id' => '', 'en' => 'Health'], 'id') === 'Health');
check('string JSON dan teks polos', N::of('"Berita"') === 'Berita' && N::of('Berita') === 'Berita' && N::of('{"id":"X"}') === 'X');
check('kosong/aneh -> teks kosong', N::of(null) === '' && N::of([]) === '' && N::of(['id' => '']) === '' && N::of(5) === '');

section('ContentDocument: urutan penelusuran mengikuti urutan dokumen');
$d = Doc::fromRaw(['blocks' => [
    'row' => ['type' => 'multi-columns', 'data' => ['col_1_zone' => ['a'], 'col_2_zone' => ['b', 'c']]],
    'a' => ['type' => 'heading', 'data' => []], 'b' => ['type' => 'heading', 'data' => []], 'c' => ['type' => 'heading', 'data' => []],
    'z' => ['type' => 'heading', 'data' => []],
], 'order' => ['row', 'z']]);
check('pre-order: row, a, b, c, z', $d->reachableIds() === ['row', 'a', 'b', 'c', 'z'], json_encode($d->reachableIds()));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
