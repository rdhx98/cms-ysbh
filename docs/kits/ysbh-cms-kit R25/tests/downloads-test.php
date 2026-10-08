<?php
/**
 * Pemeriksaan blok Daftar Unduhan (logika murni):  php tests/downloads-test.php [akar-kit]
 * FileInfo (ukuran/jenis), DownloadList (urut, kelompok, butir tak lengkap), pembersih, dan penyambungan modul.
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/FileInfo', 'Content/Blocks/DownloadList', 'Content/Blocks/DownloadsStyle',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}
App\Editor\Modules::all();

use App\Content\Blocks\{BlockSanitizer as S, DownloadList as D, DownloadsStyle, FileInfo as F};
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\DownloadsBlock as B;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }

section('FileInfo::size');
foreach ([[0, '0 B'], [1023, '1023 B'], [1024, '1 KB'], [870400, '850 KB'], [1048575, '1 MB'], [1048576, '1 MB'], [1258291, '1,2 MB'], [10485760, '10 MB'], [1073741824, '1 GB'], [1610612736, '1,5 GB'], [-5, '0 B']] as [$b, $want]) {
    check("$b byte (id) = '$want'", F::size($b, 'id') === $want, F::size($b, 'id'));
}
check("bahasa Inggris memakai titik desimal: 1.2 MB / 1.5 GB", F::size(1258291, 'en') === '1.2 MB' && F::size(1610612736, 'en') === '1.5 GB');
check('tidak pernah menampilkan "1024 KB" di perbatasan', F::size(1048000, 'id') !== '1024 KB' && F::size(1048575, 'id') === '1 MB');

section('FileInfo::type');
foreach ([['laporan.pdf', 'PDF', 'pdf'], ['a.DOCX', 'Word', 'doc'], ['a.xlsx', 'Excel', 'sheet'], ['a.csv', 'CSV', 'sheet'], ['a.pptx', 'PowerPoint', 'slides'], ['a.zip', 'ZIP', 'archive'], ['foto.JPG', 'Gambar', 'image'], ['catatan.txt', 'Teks', 'text'], ['laporan.final.PDF', 'PDF', 'pdf'], ['data.xyz', 'XYZ', 'file']] as [$n, $label, $kind]) {
    $t = F::type($n); check("$n -> $label/$kind", $t === ['label' => $label, 'kind' => $kind], json_encode($t));
}
check('tanpa ekstensi: dari mime (PDF, gambar); selain itu "Berkas"', F::type('tanpa-ekstensi', 'application/pdf')['label'] === 'PDF' && F::type('x', 'image/png')['label'] === 'Gambar' && F::type('x', 'application/octet-stream')['label'] === 'Berkas');
check('ekstensi aneh (terlalu panjang / berisi simbol) tidak dicetak sembarangan', F::type('a.toolongext')['label'] === 'Berkas' && F::type('a.<b>')['label'] === 'Berkas');

section('DownloadList: butir, bahasa, butir tak lengkap');
$files = [
    1 => ['url' => 'https://x.test/storage/lap-2025.pdf', 'name' => 'lap-2025.pdf', 'mime' => 'application/pdf', 'size' => 1258291],
    2 => ['url' => 'https://x.test/storage/lap-2024.docx', 'name' => 'lap-2024.docx', 'mime' => '', 'size' => 870400],
    3 => ['url' => 'https://x.test/storage/audit.xlsx', 'name' => 'audit.xlsx', 'mime' => '', 'size' => 2048],
];
$it = fn (string $id, string $title, string $year, ?int $media, string $cat = '', string $en = '') => ['id' => $id, 'title' => ['id' => $title, 'en' => $en], 'year' => $year, 'category' => ['id' => $cat, 'en' => ''], 'file' => ['media_id' => $media, 'url' => '']];
$base = ['sort' => 'manual', 'group_by' => 'none'];
$data = $base + ['items' => [$it('a', 'Laporan 2025', '2025', 1, 'Keuangan', 'Report 2025'), $it('b', 'Laporan 2024', '2024', 2, 'Keuangan'), $it('c', 'Audit', '', 3, 'Audit'), $it('d', 'Tanpa berkas', '2023', null), $it('e', 'Berkas dihapus', '2022', 99), $it('f', '', '2021', 1)]];
$pub = D::prepare($data, $files, 'id');
$titles = fn (array $r) => array_merge(...array_map(fn ($g) => array_column($g['items'], 'title'), $r['groups']));
check('publik: butir tanpa berkas, berkas dihapus, dan tanpa judul DILEWATI', $titles($pub) === ['Laporan 2025', 'Laporan 2024', 'Audit'] && $pub['count'] === 3, json_encode($titles($pub)));
$a = $pub['groups'][0]['items'][0];
check('butir lengkap: URL dari berkas, jenis, ukuran, tahun', $a['url'] === 'https://x.test/storage/lap-2025.pdf' && $a['type'] === 'PDF' && $a['size'] === '1,2 MB' && $a['year'] === 2025 && $a['incomplete'] === false);
check('butir tanpa tahun: year = null', $pub['groups'][0]['items'][2]['year'] === null);
$can = D::prepare($data, $files, 'id', true);
check('kanvas: SEMUA butir tampil, yang tak lengkap bertanda dengan penjelasan', $can['count'] === 6 && count(array_filter(array_merge(...array_column($can['groups'], 'items')), fn ($i) => $i['incomplete'])) === 3);
$byId = array_column(array_merge(...array_column($can['groups'], 'items')), null, 'id');
check('catatan: berkas belum dipilih / tidak ditemukan / judul kosong', $byId['d']['note'] === 'Berkas belum dipilih.' && str_contains($byId['e']['note'], 'tidak ditemukan') && $byId['f']['note'] === 'Judul kosong.' && $byId['f']['title'] === '(tanpa judul)' && $byId['d']['url'] === null);
check('bahasa en memakai judul en; en kosong jatuh ke id', D::prepare($data, $files, 'en')['groups'][0]['items'][0]['title'] === 'Report 2025' && D::prepare($data, $files, 'en')['groups'][0]['items'][1]['title'] === 'Laporan 2024');
check('tidak ada butir sama sekali -> tanpa grup', D::prepare($base + ['items' => []], [], 'id') === ['groups' => [], 'count' => 0]);

section('DownloadList: urutan');
$srt = fn (string $mode) => $titles(D::prepare(['sort' => $mode, 'group_by' => 'none', 'items' => [$it('a', 'Laporan 10', '2023', 1), $it('b', 'laporan 2', '2025', 2), $it('c', 'Audit', '', 3), $it('d', 'Zakat', '2025', 1), $it('e', 'Beta', '2024', 2)]], $files, 'id'));
check('manual: urutan asli', $srt('manual') === ['Laporan 10', 'laporan 2', 'Audit', 'Zakat', 'Beta']);
check('tahun terbaru: menurun, TANPA tahun paling bawah, urutan asli untuk tahun sama (stabil)', $srt('year_desc') === ['laporan 2', 'Zakat', 'Beta', 'Laporan 10', 'Audit'], json_encode($srt('year_desc')));
check('judul A–Z: tanpa peduli huruf besar, angka wajar ("2" sebelum "10")', $srt('title_asc') === ['Audit', 'Beta', 'laporan 2', 'Laporan 10', 'Zakat'], json_encode($srt('title_asc')));
check('mode urutan tak dikenal jatuh ke manual', $srt('acak') === $srt('manual'));

section('DownloadList: pengelompokan');
$grp = fn (string $by, string $lang = 'id') => array_map(fn ($g) => [$g['heading'], array_column($g['items'], 'title')], D::prepare(['sort' => 'manual', 'group_by' => $by, 'items' => [$it('a', 'A', '2023', 1, 'Keuangan'), $it('b', 'B', '2025', 1, 'Program'), $it('c', 'C', '', 1, ''), $it('d', 'D', '2025', 1, 'Keuangan')]], $files, $lang)['groups']);
check('tanpa kelompok: satu grup tanpa judul', $grp('none') === [[null, ['A', 'B', 'C', 'D']]]);
check('per tahun: terbaru dulu, "Tanpa tahun" paling bawah', $grp('year') === [['2025', ['B', 'D']], ['2023', ['A']], ['Tanpa tahun', ['C']]], json_encode($grp('year')));
check('per kategori: urutan kemunculan, "Lainnya" paling bawah', $grp('category') === [['Keuangan', ['A', 'D']], ['Program', ['B']], ['Lainnya', ['C']]], json_encode($grp('category')));
check('judul kelompok bahasa Inggris: No year / Other', $grp('year', 'en')[2][0] === 'No year' && $grp('category', 'en')[2][0] === 'Other');

section('DownloadsBlock::sanitize');
$loc = ['id', 'en'];
$good = ['layout' => 'cards', 'group_by' => 'year', 'sort' => 'year_desc', 'color' => 'coral', 'show_meta' => false, 'new_tab' => false,
         'items' => [['id' => 'itm_a1b2c3d4', 'title' => ['id' => 'Laporan Keuangan 2025', 'en' => 'Financial Report'], 'year' => '2025', 'category' => ['id' => 'Keuangan', 'en' => ''], 'file' => ['media_id' => 7, 'url' => 'https://x.test/storage/lap.pdf']]]];
check('data sah TIDAK berubah; idempoten', B::sanitize($good, $loc) === $good && B::sanitize(B::sanitize($good, $loc), $loc) === $good, json_encode(B::sanitize($good, $loc)));
$evil = B::sanitize(['layout' => 'x" onload="1', 'group_by' => ['x'], 'sort' => 'acak', 'color' => 'red', 'show_meta' => '', 'items' => [
    ['id' => 'a b', 'title' => ['id' => "  Usia <5\ttahun \n", 'en' => str_repeat('x', 300), 'fr' => 'bocor'], 'year' => '20a5', 'category' => ['id' => str_repeat('k', 200)], 'file' => ['media_id' => '7', 'url' => "javascript:alert(1)\x00\n"]],
    ['year' => '1800', 'file' => ['media_id' => 'x', 'url' => 'https://evil.test/a.pdf']], ['year' => '2101', 'file' => ['media_id' => -3]], ['year' => ' 2025 ', 'file' => ['media_id' => 0]], 'bukan-larik', null], 'x' => 1], $loc);
check('tampilan/kelompok/urutan/warna tak sah -> bawaan; bendera menjadi boolean; kunci asing dibuang', $evil['layout'] === 'list' && $evil['group_by'] === 'none' && $evil['sort'] === 'manual' && $evil['color'] === 'foresty' && $evil['show_meta'] === false && $evil['new_tab'] === true && array_keys($evil) === ['layout', 'group_by', 'sort', 'color', 'show_meta', 'new_tab', 'items']);
check('entri bukan larik dibuang; ID tak sah diganti', count($evil['items']) === 4 && preg_match('/^itm_[a-f0-9]{8}$/', $evil['items'][0]['id']) === 1);
check('judul: satu baris, "<5" utuh, dipotong 200, bahasa di luar daftar dibuang; kategori dipotong 80', $evil['items'][0]['title'] === ['id' => 'Usia <5 tahun', 'en' => str_repeat('x', 200)] && mb_strlen($evil['items'][0]['category']['id']) === 80);
check('tahun: hanya 4 angka 1900–2100 (dipangkas spasinya), selain itu kosong', [$evil['items'][0]['year'], $evil['items'][1]['year'], $evil['items'][2]['year'], $evil['items'][3]['year']] === ['', '', '', '2025']);
check('media_id: "7" -> 7; "x", -3, 0 -> null', $evil['items'][0]['file']['media_id'] === 7 && $evil['items'][1]['file']['media_id'] === null && $evil['items'][2]['file']['media_id'] === null && $evil['items'][3]['file']['media_id'] === null);
check('url tanpa media_id DIKOSONGKAN (url dari browser tidak berarti apa pun); karakter kontrol dibuang', $evil['items'][1]['file']['url'] === '' && !preg_match('/[\x00-\x1f]/', $evil['items'][0]['file']['url']));
check('maksimal 60 butir', count(B::sanitize(['items' => array_fill(0, 90, ['title' => ['id' => 'x']])], $loc)['items']) === 60);
check('tanpa data -> bawaan aman', B::sanitize([], $loc) === ['layout' => 'list', 'group_by' => 'none', 'sort' => 'manual', 'color' => 'foresty', 'show_meta' => true, 'new_tab' => true, 'items' => []]);

section('Penyambungan modul');
check('Modules menemukan DownloadsBlock; Akordion tetap ada', Modules::for('downloads-builder') === B::class && Modules::for('accordion-builder') !== null);
$def = BlockRegistry::block('downloads-builder');
check('registri: panel block:downloads-builder', $def !== null && $def->panelKey() === 'block:downloads-builder');
check('palet: berlabel "Daftar Unduhan", ikon download, di tingkat atas dan di dalam kolom, bukan di step-group', BlockPalette::label('downloads-builder') === 'Daftar Unduhan' && BlockPalette::icon('downloads-builder') === 'download' && BlockPalette::allowedAtRoot('downloads-builder') && BlockPalette::allowsChild('multi-columns', 'downloads-builder') && !BlockPalette::allowsChild('step-group', 'downloads-builder'));
check('BlockSanitizer::forType dan clean() memanggil pembersih modul', S::forType('downloads_builder', ['layout' => 'x'], $loc)['layout'] === 'list' && S::clean(['a' => ['id' => 'a', 'type' => 'downloads-builder', 'data' => ['color' => 'x']]], $loc)['a']['data']['color'] === 'foresty');
$mat = Defaults::materialize($def->defaults, $loc);
check('bawaan: satu butir kosong (ID baru, judul/kategori kosong per bahasa, tanpa berkas), tanpa penanda "@"', count($mat['items']) === 1 && preg_match('/^itm_[a-f0-9]{8}$/', $mat['items'][0]['id']) && $mat['items'][0]['title'] === ['id' => '', 'en' => ''] && $mat['items'][0]['file'] === ['media_id' => null, 'url' => ''] && !str_contains(json_encode($mat), '@'));
check('nilai bawaan lolos pembersih TANPA berubah', B::sanitize($mat, $loc) === $mat, json_encode(B::sanitize($mat, $loc)));
$rep = array_values(array_filter($def->fields, fn ($f) => $f->type === 'repeater'))[0];
$itemKeys = array_values(array_unique(array_map(fn ($f) => explode('.', $f->key)[0], $rep->extra['fields']))); sort($itemKeys);
$sanKeys = array_values(array_diff(array_keys($mat['items'][0]), ['id'])); sort($sanKeys);
check('bidang item di inspektur = bidang item yang dikenal pembersih', $itemKeys === $sanKeys, json_encode([$itemKeys, $sanKeys]));
$byKey = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan tampilan/warna di inspektur = daftar DownloadsStyle', $byKey('data.layout')->allowedValues() === DownloadsStyle::LAYOUTS && $byKey('data.color')->allowedValues() === DownloadsStyle::COLORS);
check('pilihan kelompok dan urutan di inspektur = yang diterima pembersih', $byKey('data.group_by')->allowedValues() === ['none', 'year', 'category'] && $byKey('data.sort')->allowedValues() === ['manual', 'year_desc', 'title_asc']);
$fileField = array_values(array_filter($rep->extra['fields'], fn ($f) => $f->type === 'media'))[0];
check("pemilih berkas TANPA filter jenis (accept = '': nilai allowedFileType untuk dokumen tidak ditebak)", ($fileField->extra['accept'] ?? null) === '');
check('kolom tahun dibatasi 4 karakter; kolom kategori satu baris', ($rep->extra['fields'][1]->extra['maxlength'] ?? null) === 4 && ($rep->extra['fields'][2]->extra['multi'] ?? false) === false);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
