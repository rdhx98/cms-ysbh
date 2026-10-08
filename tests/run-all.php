<?php
/**
 * Menjalankan SEMUA pengujian kit dan meringkas hasilnya:  php tests/run-all.php [--strict] [--only=kata]
 *
 *   --strict      pengujian yang dilewati (lab belum disiapkan) dihitung GAGAL
 *   --only=kata   hanya pengujian yang namanya memuat kata itu (mis. --only=landing)
 *
 * Pengujian murni langsung jalan. Pengujian berbasis data butuh lab: jalankan sekali `php tests/lab/setup.php`; tanpa itu mereka DILEWATI
 * (dan ditandai jelas, tidak diam-diam). Kode keluar 0 bila tidak ada yang gagal.
 */
$root = dirname(__DIR__);
$strict = in_array('--strict', $argv, true);
$only = null;
foreach ($argv as $a) { if (str_starts_with($a, '--only=')) { $only = substr($a, 7); } }
$labReady = is_dir(__DIR__ . '/lab/_src/framework-13.30.0/src/Illuminate') && is_dir(__DIR__ . '/lab/_src/Carbon-3.10.0/src');
$lab = [__DIR__ . '/lab/bootstrap.php', __DIR__ . '/lab/support.php'];
$mig = "$root/database/migrations";

// nama => [berkas, argumen, butuhLab]
$suites = [
    'accordion' => ['accordion-test.php', [$root], false], 'article-cards' => ['article-cards-test.php', [$root], false],
    'button' => ['button-test.php', [$root], false], 'callout' => ['callout-test.php', [$root], false], 'canvas' => ['canvas-test.php', [$root], false],
    'content' => ['content-test.php', ["$root/app/Content"], false], 'downloads' => ['downloads-test.php', [$root], false],
    'gallery' => ['gallery-test.php', [$root], false], 'latest-articles' => ['latest-articles-test.php', [$root], false],
    'public-url' => ['public-url-test.php', [$root], false], 'video' => ['video-test.php', [$root], false],
    'sitemap (murni)' => ['sitemap-test.php', [$root], false], 'rich-text (penyaring HTML)' => ['rich-text-test.php', [$root], false], 'core-blocks (7 blok inti)' => ['core-blocks-test.php', [$root], false], 'section-parity (mesin asli)' => ['section-parity-test.php', [$root], false], 'pasang-landing (pemasang)' => ['pasang-landing-test.php', [$root], false], 'pasang-cms (pemasang)' => ['pasang-cms-test.php', [$root], false], 'landing-app (kontrak)' => ['landing-app-test.php', [$root], false], 'landing-pack (sinkron)' => ['landing-pack-test.php', [$root], false],
    'check-landing (alat)' => ['check-landing-test.php', [$root], false], 'blade-scan (direktif)' => ['blade-scan.php', [$root], false],
    'data' => ['data-test.php', $lab, true], 'slug-terlarang' => ['slug-reserved-test.php', $lab, true],
    'kepala-artikel' => ['articles-header-test.php', $lab, true], 'jalur-publik (disaring)' => ['public-sanitize-test.php', $lab, true], 'sitemap (data)' => ['sitemap-entries-test.php', $lab, true], 'landing-nav' => ['landing-nav-test.php', $lab, true],
    'landing-models' => ['landing-models-test.php', array_merge($lab, [$mig]), true], 'snippet' => ['snippet-test.php', array_merge($lab, [$mig]), true],
];

$rows = [];
$failed = $skipped = 0;
foreach ($suites as $name => [$file, $args, $needsLab]) {
    if ($only !== null && !str_contains($name, $only)) { continue; }
    if ($needsLab && !$labReady) {
        $rows[] = [$name, 'LEWATI', 'lab belum disiapkan: php tests/lab/setup.php'];
        $skipped++;
        continue;
    }
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . "/$file") . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1';
    $t0 = microtime(true);
    exec($cmd, $out, $code);
    $text = implode("\n", $out);
    $out = [];
    preg_match_all('/==> (\d+) lulus, (\d+) gagal/', $text, $m, PREG_SET_ORDER);
    $last = $m ? end($m) : null;
    $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));
    $detail = $last ? "{$last[1]} lulus, {$last[2]} gagal" : (string) end($lines);
    $ok = $code === 0 && (!$last || (int) $last[2] === 0);
    $rows[] = [$name, $ok ? 'ok' : 'GAGAL', sprintf('%s (%.1f dtk)', $detail, microtime(true) - $t0)];
    if (!$ok) { $failed++; $rows[] = ['', '', '   ' . implode(' | ', array_slice(array_values(array_filter(explode("\n", $text), fn ($l) => str_contains($l, 'FAIL') || str_contains($l, 'Fatal') || str_contains($l, 'GALAT'))), 0, 3))]; }
}

if ($rows === []) {
    fwrite(STDERR, "Tidak ada pengujian yang cocok" . ($only !== null ? " dengan --only=$only" : '') . ". Tidak ada yang dijalankan.\n");
    exit(2);
}
echo "\n";
foreach ($rows as [$n, $s, $d]) { echo str_pad($n, 26) . str_pad($s, 8) . $d . "\n"; }
$total = count(array_filter($rows, fn ($r) => $r[0] !== ''));
echo "\n==> " . ($total - $failed - $skipped) . " ok, $failed gagal, $skipped dilewati (dari $total)\n";
if ($skipped) { echo "Pengujian berbasis data dilewati: jalankan php tests/lab/setup.php lalu ulangi.\n"; }
exit($failed > 0 || ($strict && $skipped > 0) ? 1 : 0);
