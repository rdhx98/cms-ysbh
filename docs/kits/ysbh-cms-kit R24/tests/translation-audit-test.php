<?php
/**
 * Audit terjemahan (rilis 24):  php tests/translation-audit-test.php [akar-kit]
 * TranslationAudit::check/gaps, murni. Perintah cms:audit-translations hanya penyambung (kontraknya diperiksa di bawah).
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/app/Content/LocaleMap.php";
require_once "$root/app/Content/Names.php";
require_once "$root/app/Content/TranslationAudit.php";

use App\Content\TranslationAudit as A;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

$L = ['en', 'id'];
$content = fn (array $blocks) => json_encode(['blocks' => $blocks, 'order' => array_keys($blocks), 'settings' => []]);
$para = fn (string $id, array $text) => [$id => ['id' => $id, 'type' => 'paragraph', 'data' => ['text' => $text]]];
$row = fn (array $o) => $o + ['id' => 1, 'title' => '{"en":"About","id":"Tentang"}', 'slug' => '{"en":"about-us","id":"tentang-kami"}', 'content' => $content($para('p1', ['en' => '<p>Hello</p>', 'id' => '<p>Halo</p>']))];
$kinds = fn (array $r) => array_map(fn ($x) => $x['locale'] . ':' . $x['problem'], $r);

echo "\nLengkap dan tidak lengkap\n";
check('halaman lengkap di kedua bahasa: tidak ada masalah', A::check([$row([])], $L) === []);
check('tanpa baris: kosong', A::check([], $L) === []);
$r = A::check([$row(['title' => '{"en":"","id":"Hanya ID"}', 'slug' => '{"en":"","id":"hanya-id"}'])], $L);
check('slug DAN judul en kosong: "belum" (informasi), bukan masalah isi; isi blok en yang kosong TIDAK dihitung lagi', $kinds($r) === ['en:belum'] && str_contains($r[0]['detail'], 'tidak ada di situs berbahasa en') && $r[0]['title'] === 'Hanya ID', json_encode($r));
$r = A::check([$row(['title' => '{"en":"","id":"Judul"}'])], $L);
check('slug en terisi tetapi judul kosong: "separuh" (tidak masuk peta situs dan hreflang)', $kinds($r) === ['en:separuh'] && str_contains($r[0]['detail'], 'slug terisi tetapi judul kosong'), json_encode($r));
$r = A::check([$row(['slug' => '{"en":"","id":"tentang-kami"}'])], $L);
check('judul en terisi tetapi slug kosong: "separuh"', $kinds($r) === ['en:separuh'] && str_contains($r[0]['detail'], 'judul terisi tetapi slug kosong'));
$r = A::check([$row(['content' => $content($para('p1', ['en' => '', 'id' => 'Halo']))])], $L);
check('blok yang hanya terisi id: "isi" untuk en dengan tipe dan id blok', $kinds($r) === ['en:isi'] && str_contains($r[0]['detail'], '1 teks kosong di blok: paragraph p1'), json_encode($r));
$r = A::check([$row(['content' => $content($para('p1', ['en' => 'Hello', 'id' => '']))])], $L);
check('kebalikannya: terisi en, kosong id -> "isi" untuk id', $kinds($r) === ['id:isi']);
check('kosong di KEDUA bahasa: bukan masalah (tidak ada yang diterjemahkan)', A::check([$row(['content' => $content($para('p1', ['en' => '', 'id' => '']))])], $L) === []);
foreach (['<p></p>', '<p> </p>', "<p>&nbsp;</p>", '<br>', "\n  "] as $empty) {
    check('HTML kosong ' . json_encode($empty) . ' dihitung kosong', A::gaps($content($para('p1', ['en' => $empty, 'id' => 'Halo'])), 'en', $L) === ['paragraph p1']);
}
check('gambar saja ("<img src=x>") dihitung berisi', A::gaps($content($para('p1', ['en' => '<p><img src="x.jpg"></p>', 'id' => 'Halo'])), 'en', $L) === []);

echo "\nPeta bahasa di dalam blok\n";
$card = ['c1' => ['id' => 'c1', 'type' => 'card-builder', 'data' => ['cards' => [['id' => 'a', 'title' => ['en' => 'One', 'id' => 'Satu'], 'body' => ['en' => '', 'id' => 'Isi']], ['id' => 'b', 'title' => ['en' => '', 'id' => 'Dua'], 'body' => ['en' => 'Body', 'id' => 'Isi']]]]]];
$g = A::gaps($content($card), 'en', $L);
check('peta bersarang (daftar kartu) dibaca: dua teks kosong dalam satu blok -> dua entri berlabel blok itu', $g === ['card-builder c1', 'card-builder c1'], json_encode($g));
check('larik bukan peta bahasa TIDAK dituduh: satu kunci, kunci selain bahasa, nilai bukan teks, id item', A::gaps($content(['x' => ['id' => 'x', 'type' => 'heading', 'data' => ['a' => ['id' => 'itm_1'], 'b' => ['id' => 'k', 'kind' => 'url'], 'c' => ['id' => 5, 'en' => 6], 'd' => ['fr' => 'a', 'en' => 'b'], 'e' => ['en' => ['x'], 'id' => ['y']]]]]), 'en', $L) === []);
check('tiga bahasa: kosong di en, terisi di salah satu lainnya cukup; id dan fr dinilai sendiri', A::gaps($content($para('p1', ['en' => '', 'id' => 'a', 'fr' => ''])), 'en', ['en', 'id', 'fr']) === ['paragraph p1'] && A::gaps($content($para('p1', ['en' => '', 'id' => 'a', 'fr' => ''])), 'fr', ['en', 'id', 'fr']) === ['paragraph p1'] && A::gaps($content($para('p1', ['en' => 'x', 'id' => 'a', 'fr' => ''])), 'id', ['en', 'id', 'fr']) === []);
check('kunci bahasa di luar daftar bahasa situs bukan peta bahasa ("fr" tidak ada di [en, id])', A::gaps($content($para('p1', ['en' => '', 'id' => 'a', 'fr' => 'b'])), 'en', $L) === []);
$many = []; foreach (range(1, 5) as $i) { $many += $para("p$i", ['en' => '', 'id' => 'x']); }
$r = A::check([$row(['content' => $content($many)])], $L);
check('banyak blok: jumlah penuh, contoh dipotong tiga dengan "…"', str_contains($r[0]['detail'], '5 teks kosong di blok: paragraph p1, paragraph p2, paragraph p3, …') && !str_contains($r[0]['detail'], 'p4'), $r[0]['detail'] ?? '');

echo "\nBentuk data aneh tidak menjatuhkan audit\n";
foreach ([null, '', 'null', '<p>HTML lama</p>', '{rusak', [], ['blocks' => 'bukan-larik'], ['blocks' => [1, 'a', null]], json_encode(['blocks' => ['a' => 'teks']])] as $odd) {
    $threw = null; try { $x = A::check([$row(['content' => $odd])], $L); } catch (\Throwable $e) { $threw = $e; }
    check('content ' . substr(json_encode($odd), 0, 30) . ': tanpa galat, tanpa masalah', $threw === null && $x === [], $threw?->getMessage() ?? json_encode($x));
}
check('judul/slug null atau teks polos lama: tidak melempar galat; teks polos tidak dihitung milik bahasa mana pun', A::check([['id' => 1, 'title' => null, 'slug' => null, 'content' => null], ['id' => 2, 'title' => 'Judul lama', 'slug' => 'slug-lama', 'content' => null]], $L) === []);
$deep = ['a' => ['en' => '', 'id' => 'x']]; for ($i = 0; $i < 30; $i++) { $deep = ['n' => $deep]; }
check('bersarang sangat dalam (30 lapis): berhenti di batas, tanpa galat', A::gaps(json_encode(['blocks' => ['d' => ['id' => 'd', 'type' => 't', 'data' => $deep]]]), 'en', $L) === []);
check('urutan hasil mengikuti baris lalu urutan bahasa; id baris dibawa', (function () use ($row) { $a = A::check([$row(['id' => 7, 'title' => '{"en":"","id":"A"}', 'slug' => '{"en":"","id":"a"}']), $row(['id' => 9, 'content' => json_encode(['blocks' => ['p' => ['id' => 'p', 'type' => 'paragraph', 'data' => ['text' => ['en' => 'x', 'id' => '']]]]])])], ['en', 'id']); return array_map(fn ($x) => $x['id'] . ':' . $x['locale'] . ':' . $x['problem'], $a) === ['7:en:belum', '9:id:isi']; })());

echo "\nPerintah cms:audit-translations (kontrak)\n";
$cmd = (string) file_get_contents("$root/app/Console/Commands/AuditTranslations.php");
check('hanya MEMBACA (tidak ada save/update/delete/insert), memakai TranslationAudit dan Languages, kode keluar 1 bila ada masalah', !preg_match('/->(save|update|delete|insert|create|forceDelete)\(/', $cmd) && str_contains($cmd, 'TranslationAudit::check(') && str_contains($cmd, 'Languages::fromConfig()') && str_contains($cmd, 'return self::FAILURE'));
check('bawaan: halaman online dan artikel terbit saja; --all, --locale, --strict tersedia; bahasa tak dikenal ditolak (INVALID)', str_contains($cmd, 'PublicLookup::PAGE_STATUS') && str_contains($cmd, 'PublicLookup::ARTICLE_STATUS') && str_contains($cmd, '{--all') && str_contains($cmd, '{--locale=') && str_contains($cmd, '{--strict') && str_contains($cmd, 'self::INVALID'));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
