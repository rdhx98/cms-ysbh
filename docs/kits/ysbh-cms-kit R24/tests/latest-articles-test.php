<?php
/**
 * Pemeriksaan blok Artikel Terbaru (logika murni):  php tests/latest-articles-test.php [akar-kit]
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Names', 'Content/ContentDocument', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/ArticleCards',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}
App\Editor\Modules::all();

use App\Content\Blocks\{ArticleCards as A, BlockSanitizer as S};
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\LatestArticlesBlock as B;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
$loc = ['id', 'en'];
$LK = ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];

echo "\nLatestArticlesBlock::sanitize\n";
$good = ['title' => ['id' => 'Kabar terbaru', 'en' => 'Latest news'], 'limit' => '6', 'columns' => '4', 'show_image' => false, 'show_category' => true, 'show_date' => false, 'show_excerpt' => true,
         'all_label' => ['id' => 'Lihat semua artikel', 'en' => 'See all'], 'all_link' => ['kind' => 'page', 'ref' => 5, 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false]];
check('data sah TIDAK berubah; idempoten', B::sanitize($good, $loc) === $good && B::sanitize(B::sanitize($good, $loc), $loc) === $good, json_encode(B::sanitize($good, $loc)));
$evil = B::sanitize(['title' => ['id' => "  Usia <5\ttahun\n", 'en' => str_repeat('x', 300), 'fr' => 'bocor'], 'limit' => 99, 'columns' => 'x" onload="1', 'show_image' => '', 'show_date' => '0', 'all_label' => ['id' => str_repeat('z', 100)],
    'all_link' => ['kind' => 'url', 'ref' => 'javascript:alert(1)', 'new_tab' => '1'], 'z' => 1], $loc);
check('jumlah/kolom tak sah -> bawaan (teks); bendera boolean; kunci asing dibuang', $evil['limit'] === '3' && $evil['columns'] === '3' && $evil['show_image'] === false && $evil['show_date'] === false && $evil['show_category'] === true && array_keys($evil) === ['title', 'limit', 'columns', 'show_image', 'show_category', 'show_date', 'show_excerpt', 'all_label', 'all_link']);
check('judul satu baris, "<5" utuh, dipotong 120, bahasa asing dibuang; teks tautan dipotong 60', $evil['title'] === ['id' => 'Usia <5 tahun', 'en' => str_repeat('x', 120)] && mb_strlen($evil['all_label']['id']) === 60);
check('tautan "lihat semua": javascript: DIKOSONGKAN (aturan sama dengan tombol)', $evil['all_link']['ref'] === '' && $evil['all_link']['kind'] === 'url');
check('jumlah dan kolom boleh berupa angka (dari inspektur lama): dinormalkan jadi teks', B::sanitize(['limit' => 9, 'columns' => 2], $loc)['limit'] === '9' && B::sanitize(['limit' => 9, 'columns' => 2], $loc)['columns'] === '2');
check('tanpa data -> bawaan aman (3 artikel, 3 kolom, semua bagian tampil)', B::sanitize([], $loc) === ['title' => ['id' => '', 'en' => ''], 'limit' => '3', 'columns' => '3', 'show_image' => true, 'show_category' => true, 'show_date' => true, 'show_excerpt' => true, 'all_label' => ['id' => '', 'en' => ''], 'all_link' => $LK]);

echo "\nPenyambungan modul\n";
check('Modules menemukan LatestArticlesBlock; modul lain tetap ada', Modules::for('latest-articles-builder') === B::class && Modules::for('gallery-builder') !== null && Modules::for('video-builder') !== null);
$def = BlockRegistry::block('latest-articles-builder');
check('registri: panel block:latest-articles-builder; palet: "Artikel Terbaru", ikon newspaper, tingkat atas + kolom, bukan step-group', $def !== null && $def->panelKey() === 'block:latest-articles-builder' && BlockPalette::label('latest-articles-builder') === 'Artikel Terbaru' && BlockPalette::icon('latest-articles-builder') === 'newspaper' && BlockPalette::allowedAtRoot('latest-articles-builder') && BlockPalette::allowsChild('multi-columns', 'latest-articles-builder') && !BlockPalette::allowsChild('step-group', 'latest-articles-builder'));
check('BlockSanitizer::forType dan clean() memanggil pembersih modul', S::forType('latest_articles_builder', ['limit' => 'x'], $loc)['limit'] === '3' && S::clean(['a' => ['id' => 'a', 'type' => 'latest-articles-builder', 'data' => ['columns' => 'x']]], $loc)['a']['data']['columns'] === '3');
$mat = Defaults::materialize($def->defaults, $loc);
check('bawaan: kosong, tanpa penanda "@"; lolos pembersih TANPA berubah', $mat['title'] === ['id' => '', 'en' => ''] && !str_contains(json_encode($mat), '@') && B::sanitize($mat, $loc) === $mat, json_encode(B::sanitize($mat, $loc)));
$tops = array_values(array_map(fn ($f) => substr($f->key, 5), array_filter($def->fields, fn ($f) => str_starts_with($f->key, 'data.')))); sort($tops); $san = array_keys($mat); sort($san);
check('bidang di inspektur = bidang yang dikenal pembersih', $tops === $san, json_encode([$tops, $san]));
$by = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan jumlah dan kolom = daftar ArticleCards, bernilai TEKS (bentuk daftar)', $by('data.limit')->allowedValues() === A::LIMITS && $by('data.columns')->allowedValues() === A::COLUMNS);
check('tautan memakai kontrol tautan; judul dan teks tautan satu baris', $by('data.all_link')->type === 'link' && ($by('data.title')->extra['multi'] ?? true) === false);
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', !array_filter($def->fields, fn ($f) => $f->allowedValues() !== null && $f->default !== null && !in_array((string) $f->default, array_map('strval', $f->allowedValues()), true)));
check('definition() tidak menyentuh basis data (tidak ada kelas model/DB dalam berkas modul)', !preg_match('/App\\\\Models|DB::|::query\(/', (string) file_get_contents("$root/app/Editor/Blocks/LatestArticlesBlock.php")));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
