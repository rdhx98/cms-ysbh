<?php
/**
 * Pemeriksaan blok Callout (logika murni):  php tests/callout-test.php [akar-kit]
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/CalloutStyle',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}
App\Editor\Modules::all();

use App\Content\Blocks\{BlockSanitizer as S, CalloutStyle as C};
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\CalloutBlock as B;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }
$loc = ['id', 'en'];

section('CalloutStyle');
$boxes = []; foreach (C::STYLES as $st) foreach (C::TONES as $tn) $boxes[] = C::box($st, $tn) . '|' . C::icon($st, $tn);
check('3 gaya x 6 jenis = 18 kombinasi berbeda, tanpa sisa pola template', count(array_unique($boxes)) === 18 && !array_filter($boxes, fn ($x) => str_contains($x, '{') || str_contains($x, '$')));
check('nilai tak sah jatuh ke bawaan (info, lembut); tidak ada teks sisipan', C::box('x" onload="1', ['a']) === C::box('soft', 'info') && !str_contains(C::box('<b>', 'y"><i>'), '<'));
check('gaya penuh: ikon putih; kuning (peringatan) memakai teks dan ikon gelap demi kontras', str_contains(C::icon('solid', 'danger'), 'text-white') && str_contains(C::box('solid', 'warning'), 'text-gray-900') && str_contains(C::icon('solid', 'warning'), 'text-gray-900'));
check('gaya lembut/garis: ikon berwarna sesuai jenis', str_contains(C::icon('soft', 'danger'), 'text-red-600') && str_contains(C::icon('outline', 'success'), 'text-emerald-600'));
check('gaya lembut memakai garis aksen kiri; garis/penuh memakai bingkai penuh', str_contains(C::box('soft', 'info'), 'border-l-4') && str_contains(C::box('outline', 'info'), 'border-2') && str_contains(C::box('solid', 'info'), 'border-2'));
check('setiap jenis punya ikon bawaan SVG sendiri (tidak bergantung daftar ikon aplikasi), berbeda-beda', count(array_unique(array_map([C::class, 'iconPath'], C::TONES))) === 6 && !array_filter(C::TONES, fn ($t) => !str_contains(C::iconPath($t), '<')));
check('nama jenis untuk pembaca layar per bahasa; bahasa lain jatuh ke Inggris', C::label('warning', 'id') === 'Peringatan' && C::label('warning', 'en') === 'Warning' && C::label('danger', 'fr') === 'Danger' && C::label('x', 'id') === 'Informasi');

section('CalloutBlock::sanitize');
$good = ['tone' => 'danger', 'style' => 'solid', 'show_icon' => false, 'icon' => 'heart',
         'title' => ['id' => 'Waspada demam', 'en' => 'Beware fever'], 'body' => ['id' => "Segera ke puskesmas:\n- demam tinggi\n- kejang", 'en' => 'See a clinic'],
         'action' => ['label' => ['id' => 'Hubungi 119', 'en' => 'Call 119'], 'link' => ['kind' => 'tel', 'ref' => '119', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false]]];
check('data sah TIDAK berubah; idempoten', B::sanitize($good, $loc) === $good && B::sanitize(B::sanitize($good, $loc), $loc) === $good, json_encode(B::sanitize($good, $loc)));
$evil = B::sanitize(['tone' => 'neon" onload="1', 'style' => ['x'], 'show_icon' => '', 'icon' => 'a b<c',
    'title' => ['id' => "  Usia <5\ttahun\n", 'en' => str_repeat('x', 300), 'fr' => 'bocor'], 'body' => ['id' => "Satu\r\n\r\n\r\n\r\nDua <b>x</b>\x07", 'en' => str_repeat('y', 4000)],
    'action' => ['label' => ['id' => str_repeat('z', 100)], 'link' => ['kind' => 'url', 'ref' => 'javascript:alert(1)', 'new_tab' => 'ya']], 'x' => 1], $loc);
check('jenis/gaya/ikon tak sah -> bawaan; show_icon menjadi boolean; kunci asing dibuang', $evil['tone'] === 'info' && $evil['style'] === 'soft' && $evil['icon'] === '' && $evil['show_icon'] === false && array_keys($evil) === ['tone', 'style', 'show_icon', 'icon', 'title', 'body', 'action']);
check('judul: satu baris, "<5" utuh, dipotong 150, bahasa asing dibuang; isi: baris baru terjaga (maks. dua), kontrol dibuang, dipotong 3000', $evil['title'] === ['id' => 'Usia <5 tahun', 'en' => str_repeat('x', 150)] && $evil['body']['id'] === "Satu\n\nDua <b>x</b>" && mb_strlen($evil['body']['en']) === 3000, json_encode($evil['title']));
check('teks tautan dipotong 60', mb_strlen($evil['action']['label']['id']) === 60);
check('tautan aksi: javascript: DIKOSONGKAN (aturan sama dengan tombol)', $evil['action']['link']['ref'] === '' && $evil['action']['link']['kind'] === 'url');
$lk = fn (array $l) => B::sanitize(['action' => ['link' => $l]], $loc)['action']['link'];
check('jenis tautan: halaman -> ID bilangan bulat; telepon dinormalkan dan tanpa tab baru; surel/anchor sah; jenis asing -> url', $lk(['kind' => 'page', 'ref' => '5'])['ref'] === 5 && $lk(['kind' => 'tel', 'ref' => '(0967) 123-456', 'new_tab' => true]) === ['kind' => 'tel', 'ref' => '0967123456', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false] && $lk(['kind' => 'mailto', 'ref' => 'a@b.co'])['ref'] === 'a@b.co' && $lk(['kind' => 'anchor', 'ref' => '#kontak'])['ref'] === 'kontak' && $lk(['kind' => 'eval', 'ref' => 'x'])['kind'] === 'url');
check('tanpa data -> bawaan aman (info, lembut, ikon tampil, kosong)', B::sanitize([], $loc) === ['tone' => 'info', 'style' => 'soft', 'show_icon' => true, 'icon' => '', 'title' => ['id' => '', 'en' => ''], 'body' => ['id' => '', 'en' => ''], 'action' => ['label' => ['id' => '', 'en' => ''], 'link' => ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false]]]);

section('Penyambungan modul');
check('Modules menemukan CalloutBlock; modul lain tetap ada', Modules::for('callout-builder') === B::class && Modules::for('accordion-builder') !== null && Modules::for('downloads-builder') !== null);
$def = BlockRegistry::block('callout-builder');
check('registri: panel block:callout-builder; palet: "Callout / Catatan", ikon ada di daftar ikon aplikasi (megaphone), tingkat atas + kolom, bukan step-group', $def !== null && $def->panelKey() === 'block:callout-builder' && BlockPalette::label('callout-builder') === 'Callout / Catatan' && BlockPalette::icon('callout-builder') === 'megaphone' && BlockPalette::allowedAtRoot('callout-builder') && BlockPalette::allowsChild('multi-columns', 'callout-builder') && !BlockPalette::allowsChild('step-group', 'callout-builder'));
check('BlockSanitizer::forType dan clean() memanggil pembersih modul', S::forType('callout_builder', ['tone' => 'x'], $loc)['tone'] === 'info' && S::clean(['a' => ['id' => 'a', 'type' => 'callout-builder', 'data' => ['style' => 'x']]], $loc)['a']['data']['style'] === 'soft');
$mat = Defaults::materialize($def->defaults, $loc);
check('bawaan: kosong per bahasa (tanpa isi palsu), tanpa penanda "@"; lolos pembersih TANPA berubah', $mat['title'] === ['id' => '', 'en' => ''] && $mat['action']['label'] === ['id' => '', 'en' => ''] && !str_contains(json_encode($mat), '@') && B::sanitize($mat, $loc) === $mat, json_encode(B::sanitize($mat, $loc)));
$tops = array_values(array_unique(array_map(fn ($f) => explode('.', substr($f->key, 5))[0], array_filter($def->fields, fn ($f) => str_starts_with($f->key, 'data.'))))); sort($tops);
$san = array_keys($mat); sort($san);
check('bidang di inspektur = bidang yang dikenal pembersih', $tops === $san, json_encode([$tops, $san]));
$by = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan jenis dan gaya di inspektur = daftar CalloutStyle', $by('data.tone')->allowedValues() === C::TONES && $by('data.style')->allowedValues() === C::STYLES);
check('isi banyak baris; judul satu baris; ikon boleh dikosongkan; tautan aksi memakai kontrol tautan', ($by('data.body')->extra['multi'] ?? false) === true && ($by('data.title')->extra['multi'] ?? true) === false && ($by('data.icon')->extra['clearable'] ?? false) === true && $by('data.action.link')->type === 'link');
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', !array_filter($def->fields, fn ($f) => $f->allowedValues() !== null && $f->default !== null && !in_array($f->default, $f->allowedValues(), true)));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
