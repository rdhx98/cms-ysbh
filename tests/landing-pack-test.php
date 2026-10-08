<?php
/**
 * Pemeriksaan paket landing:  php tests/landing-pack-test.php [akar-kit]
 * Daftar berkas landing (landing-files.txt) sesuai penelusuran, tidak memuat kode admin, dan alat sinkron aman dan benar.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/tools/landing-files.php";

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function run(string $cmd): array { exec($cmd . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
function rrm(string $d): void { if (!is_dir($d)) return; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($d); }

echo "\nDaftar berkas landing\n";
$traced = trace_landing($root);
$listed = array_values(array_filter(array_map('trim', explode("\n", (string) file_get_contents("$root/landing-files.txt"))), fn ($l) => $l !== '' && $l[0] !== '#'));
check('landing-files.txt = hasil penelusuran (bila gagal: jalankan php tools/landing-files.php --write)', $traced === $listed, 'selisih: ' . implode(',', array_merge(array_diff($traced, $listed), array_diff($listed, $traced))));
check('semua berkas di daftar ada, jalurnya relatif di bawah app/ atau resources/, tanpa ".."', !array_filter($listed, fn ($f) => !is_file("$root/$f") || str_contains($f, '..') || !preg_match('#^(app|resources)/#', $f)));
check('titik masuk publik ada di daftar: sections, body, SEMUA renderer blok, dan semua modul blok', in_array('resources/views/components/content/sections.blade.php', $listed, true) && in_array('resources/views/components/content/body.blade.php', $listed, true)
    && !array_filter(glob("$root/resources/views/components/blocks/render/*.blade.php"), fn ($p) => !in_array('resources/views/components/blocks/render/' . basename($p), $listed, true))
    && !array_filter(glob("$root/app/Editor/Blocks/*.php"), fn ($p) => !in_array('app/Editor/Blocks/' . basename($p), $listed, true)));
$adminPattern = '#Livewire|components/content/[^/]*builder\.blade|canvas|record-preview|components/editor/|PreviewStore|ContentWriter|ContentRules|SavedPreview|TagResolver|UniqueLocaleValue|Traits/|ManagesBlockStructure|SearchesLinkTargets|SearchesInternalPages|BlockRegistry\.php\.bak|/Models/|routes|patch/#';
check('TIDAK memuat kode admin: tanpa Livewire/builder/kanvas/pratinjau/penulis record/aturan validasi/komponen editor/model', !array_filter($listed, fn ($f) => preg_match($adminPattern, $f)), implode(',', array_filter($listed, fn ($f) => preg_match($adminPattern, $f))));
check('TIDAK memuat berkas rilis/pengujian/dokumen', !array_filter($listed, fn ($f) => preg_match('#^(tests|docs|tools|patch|contoh-kode|database)/#', $f)));
$req = landing_requirements($root, $traced);
check('kebutuhan landing terdaftar: model Category/Media/Page/Post/Snippet dan komponen table-of-contents milik aplikasi', $req['models'] === ['Category', 'Media', 'Page', 'Post', 'Snippet'] && in_array('table-of-contents', $req['components'], true), json_encode($req));

echo "\nAlat sinkron\n";
$tmp = sys_get_temp_dir() . '/landing-test-' . getmypid();
rrm($tmp); mkdir("$tmp/landing", 0775, true);
$sync = fn (string ...$a) => run('php ' . escapeshellarg("$root/tools/sync-landing.php") . ' ' . implode(' ', array_map('escapeshellarg', $a)));
[$c, $o] = $sync("$tmp/landing");
check('folder BUKAN proyek Laravel (tanpa artisan/composer.json): ditolak (kode 2), tidak ada yang ditulis', $c === 2 && str_contains($o, 'bukan proyek Laravel') && !is_dir("$tmp/landing/app"), "$c $o");
file_put_contents("$tmp/landing/artisan", '<?php'); file_put_contents("$tmp/landing/composer.json", '{}');
[$c, $o] = $sync("$tmp/landing");
check('mode periksa (bawaan): menampilkan berkas BARU, kode keluar 1, TIDAK menulis apa pun', $c === 1 && substr_count($o, 'BARU') === count($listed) && !is_dir("$tmp/landing/app"), "$c");
[$c, $o] = $sync("$tmp/landing", '--apply');
check('--apply menyalin semua berkas; kode 0', $c === 0 && str_contains($o, 'Disalin: ' . count($listed) . ' baru'), "$c $o");
$identical = !array_filter($listed, fn ($f) => !is_file("$tmp/landing/$f") || hash_file('sha256', "$root/$f") !== hash_file('sha256', "$tmp/landing/$f"));
check('hasil salinan IDENTIK bayt demi bayt dengan kit', $identical);
[$c, $o] = $sync("$tmp/landing");
check('periksa lagi: semua sama, kode 0', $c === 0 && str_contains($o, count($listed) . ' sama, 0 baru, 0 berbeda'), $o);
file_put_contents("$tmp/landing/app/Content/Names.php", "<?php // disunting");
[$c, $o] = $sync("$tmp/landing");
check('berkas di landing yang berubah: ditandai BERBEDA (kode 1) dan belum ditimpa', $c === 1 && str_contains($o, 'BERBEDA   app/Content/Names.php') && str_contains((string) file_get_contents("$tmp/landing/app/Content/Names.php"), 'disunting'));
[$c, $o] = $sync("$tmp/landing", '--apply');
check('--apply memulihkan berkas itu saja; yang sama tidak disentuh', $c === 0 && str_contains($o, '0 baru + 1 diperbarui') && hash_file('sha256', "$root/app/Content/Names.php") === hash_file('sha256', "$tmp/landing/app/Content/Names.php"));
file_put_contents("$tmp/landing/berkas-lain.txt", 'milik landing'); mkdir("$tmp/landing/app/Livewire", 0775, true); file_put_contents("$tmp/landing/app/Livewire/Milikku.php", '<?php // milik landing');
$sync("$tmp/landing", '--apply');
check('berkas milik landing yang TIDAK ada di daftar tidak pernah disentuh atau dihapus', is_file("$tmp/landing/berkas-lain.txt") && is_file("$tmp/landing/app/Livewire/Milikku.php"));
[$c, $o] = run('php ' . escapeshellarg("$root/tools/sync-landing.php") . ' ' . escapeshellarg($root));
check('folder landing = folder kit: ditolak', $c === 2, "$c $o");
// daftar berbahaya
$evilKit = "$tmp/evilkit"; mkdir("$evilKit/tools", 0775, true); mkdir("$evilKit/app", 0775, true); file_put_contents("$evilKit/app/a.php", '<?php');
foreach (['../../etc/passwd', '/etc/passwd', 'C:\\Windows\\x', 'app/../../x.php', 'storage/x.php', ".env"] as $bad) {
    file_put_contents("$evilKit/landing-files.txt", "app/a.php\n$bad\n");
    [$c, $o] = run('php ' . escapeshellarg("$root/tools/sync-landing.php") . ' ' . escapeshellarg("$tmp/landing") . ' --apply --kit=' . escapeshellarg($evilKit));
    check("daftar berisi jalur tidak aman '$bad': ditolak sebelum menyalin apa pun", $c === 2 && str_contains($o, 'jalur tidak aman') && !is_file("$tmp/landing/app/a.php"), "$c $o");
}
file_put_contents("$evilKit/landing-files.txt", "app/tidak-ada.php\n");
[$c, $o] = run('php ' . escapeshellarg("$root/tools/sync-landing.php") . ' ' . escapeshellarg("$tmp/landing") . ' --kit=' . escapeshellarg($evilKit));
check('daftar menyebut berkas yang tidak ada di kit: gagal (kode 1) dengan penjelasan', $c === 1 && str_contains($o, 'tidak ada di kit'), "$c $o");
rrm($tmp);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
