<?php
/**
 * Memeriksa kelengkapan dan keamanan proyek LANDING setelah berkas disalin (analisis statis; tidak menjalankan aplikasi).
 *
 *   php tools/check-landing.php <folder-landing>
 *
 * Kode keluar 0 = tidak ada GALAT (peringatan boleh ada); 1 = ada galat; 2 = folder bukan proyek Laravel.
 * Yang diperiksa: renderer untuk SETIAP tipe blok, komponen yang dipakai renderer tersedia, renderer BUKAN komponen editor/admin,
 * kunci config('cms...') yang dibaca tersedia, model yang dipakai ada, tabrakan direktif Blade, dan pengaturan CSS Tailwind.
 */
$kit = realpath(dirname(__DIR__));
$target = isset($argv[1]) ? realpath($argv[1]) : false;
if ($target === false || !is_file("$target/artisan") || !is_file("$target/composer.json")) {
    fwrite(STDERR, "Pemakaian: php tools/check-landing.php <folder-landing>  (folder harus proyek Laravel: artisan dan composer.json)\n");
    exit(2);
}
$errors = $warnings = 0;
$err = function (string $m) use (&$errors) { $errors++; echo "  GALAT      $m\n"; };
$warn = function (string $m) use (&$warnings) { $warnings++; echo "  PERINGATAN $m\n"; };
$ok = fn (string $m) => print("  ok         $m\n");
$views = "$target/resources/views";
$rel = fn (string $p) => ltrim(str_replace('\\', '/', substr($p, strlen($target))), '/');

// ---------------------------------------------------------------- 1. tipe blok yang butuh renderer
$types = [];
if (preg_match_all("/^\s*'([a-z0-9-]+)'\s*=>\s*\['/m", (string) file_get_contents("$kit/app/Editor/BlockPalette.php"), $m)) {
    $types = $m[1];
}
foreach (glob("$kit/app/Editor/Blocks/*Block.php") ?: [] as $f) {
    if (preg_match("/new BlockType\(\s*'([a-z0-9-]+)'/", (string) file_get_contents($f), $m)) {
        $types[] = $m[1];
    }
}
// 'section-divider' TIDAK dirender sebagai komponen: mesin seksi (SectionBuilder::group) memakainya hanya untuk memecah halaman menjadi seksi
// (latar, padding, anchor), jadi tidak ada renderer yang diminta. (Permintaan renderer-nya di rilis 14-17 keliru.)
$types = array_values(array_diff(array_unique($types), ['section-divider']));
echo "\n[1] Renderer blok (" . count($types) . " tipe)\n";
$missing = array_filter($types, fn ($t) => !is_file("$views/components/blocks/render/$t.blade.php"));
foreach ($missing as $t) {
    $err("renderer tidak ada: resources/views/components/blocks/render/$t.blade.php" . (in_array($t, ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns'], true) ? '  (salin dari CMS)' : '  (jalankan tools/sync-landing.php --apply)'));
}
if (!$missing) {
    $ok('semua tipe blok punya renderer');
}

// ---------------------------------------------------------------- 2. komponen yang selalu dibutuhkan
echo "\n[2] Berkas wajib\n";
$must = [
    'resources/views/components/content/sections.blade.php' => 'mesin seksi (tools/sync-landing.php)',
    'resources/views/components/content/body.blade.php' => 'isi artikel lama (tools/sync-landing.php)',
    'resources/views/components/table-of-contents.blade.php' => 'daftar isi: pakai landing-app/resources/views/components/table-of-contents.blade.php (versi dari CMS Anda dengan perbaikan keamanan); versi lama dari CMS akan ditolak oleh pemeriksaan di bawah',
    'app/Content/PublicLookup.php' => 'tools/sync-landing.php',
    'app/Models/Page.php' => 'landing-app/app/Models', 'app/Models/Post.php' => 'landing-app/app/Models', 'app/Models/Category.php' => 'landing-app/app/Models',
    'app/Models/Snippet.php' => 'landing-app/app/Models', 'app/Models/Media.php' => 'landing-app/app/Models',
];
$bad = 0;
foreach ($must as $f => $how) {
    if (!is_file("$target/$f")) { $err("tidak ada: $f ($how)"); $bad++; }
}
if (!$bad) { $ok('mesin seksi, isi artikel, daftar isi, PublicLookup, dan lima model ada'); }
if (is_file("$target/app/Models/Media.php") && !preg_match('/use\s+SoftDeletes\s*;/', (string) file_get_contents("$target/app/Models/Media.php"))) {
    $err('app/Models/Media.php tanpa SoftDeletes: berkas yang dihapus di File Manager CMS tetap tampil di situs');
}

// ---------------------------------------------------------------- 3. konfigurasi
echo "\n[3] config/cms.php\n";
$cfg = null;
if (!is_file("$target/config/cms.php")) {
    $err('config/cms.php tidak ada (gabungkan landing-app/config/cms.php)');
} else {
    if (!function_exists('env')) { function env($k, $d = null) { return $d; } }
    try { $cfg = (static fn () => require "$GLOBALS[target]/config/cms.php")(); } catch (\Throwable $e) { $err('config/cms.php tidak bisa dibaca: ' . $e->getMessage()); }
}
if (is_array($cfg)) {
    $pub = $cfg['public'] ?? null;
    if (!is_array($cfg['lucide'] ?? null) || count($cfg['lucide']) < 1) { $err("kunci 'lucide' kosong: ikon blok tidak akan tampil (salin dari config/cms.php CMS)"); }
    if (!is_array($pub) || !is_string($pub['page'] ?? null) || !is_string($pub['article'] ?? null)) { $err("kunci 'public' (page dan article) tidak ada: tautan halaman/artikel tidak akan terbentuk"); }
    if (!is_array($cfg['design'] ?? null)) { $warn("kunci 'design' tidak ada: renderer yang membaca config('cms.design...') akan kehilangan daftar kelasnya"); }
    if (is_array($pub) && ($pub['page'] ?? null) && ($pub['article'] ?? null)) { $ok("lucide: " . count($cfg['lucide'] ?? []) . " ikon; public.page '" . $pub['page'] . "', public.article '" . $pub['article'] . "'"); }
}

// ---------------------------------------------------------------- 3b. rute statis vs slug terlarang
echo "\n[3b] Rute statis dan slug halaman CMS\n";
$webFile = "$target/routes/web.php";
if (!is_file($webFile) || !is_array($cfg)) {
    $warn('routes/web.php atau config/cms.php tidak bisa dibaca: pemeriksaan slug terlarang dilewati');
} else {
    require_once "$kit/app/Content/Slug.php";
    $indexSlug = is_string($cfg['articles_index_slug'] ?? null) && $cfg['articles_index_slug'] !== '' ? $cfg['articles_index_slug'] : 'artikel';
    $reserved = \App\Content\Slug::reserved(is_array($cfg['reserved_slugs'] ?? null) ? $cfg['reserved_slugs'] : [], $indexSlug);
    $web = (string) preg_replace('#^\s*(?://|\#).*$#m', '', (string) file_get_contents($webFile));   // komentar tidak dihitung
    preg_match_all("#Route::(?:view|get|post|any|redirect|livewire|match)\(\s*(?:\[[^\]]*\]\s*,\s*)?['\"]/([^'\"/{}?]+)['\"]#", $web, $m);
    // hanya segmen yang SAH sebagai slug yang bisa bertabrakan ("sitemap.xml" bertitik: mustahil menjadi slug halaman)
    $segments = array_values(array_filter(array_unique($m[1]), fn ($seg) => \App\Content\Slug::isValid($seg)));
    $unguarded = array_values(array_filter($segments, fn ($seg) => $seg !== $indexSlug && !in_array(strtolower($seg), $reserved, true)));
    foreach ($unguarded as $seg) {
        $warn("rute statis '/$seg' belum ada di config('cms.reserved_slugs'): halaman CMS ber-slug \"$seg\" akan tersimpan dan tampak online tetapi TIDAK PERNAH terbuka. Tambahkan \"$seg\" ke reserved_slugs di config/cms.php CMS dan landing");
    }
    if (preg_match("#Route::livewire\(\s*['\"]/([^'\"/{}?]+)['\"]\s*,\s*['\"]articles-index['\"]#", $web, $mi) && $mi[1] !== $indexSlug) {
        $warn("articles_index_slug ('$indexSlug') tidak sama dengan jalur rute articles-index ('/{$mi[1]}'): halaman CMS kepala daftar artikel tidak akan dipakai");
    }
    if (!$unguarded) { $ok(count($segments) . " rute statis satu-segmen: semuanya tercatat sebagai slug terlarang (atau slug kepala '$indexSlug')"); }
}

// ---------------------------------------------------------------- 3c. peta situs dan robots.txt
echo "\n[3c] Peta situs dan robots.txt\n";
if (is_file($webFile) && is_array($cfg)) {
    $hasRoute = (bool) preg_match("#Route::get\(\s*['\"]/sitemap\.xml['\"]#", $web);
    if (!$hasRoute) { $warn("rute GET '/sitemap.xml' tidak ada di routes/web.php (peta situs tidak tersedia)"); }
    if ($hasRoute && !is_file("$target/app/Http/Controllers/SitemapController.php")) { $err('routes/web.php merujuk SitemapController tetapi app/Http/Controllers/SitemapController.php tidak ada'); }
    // jalur statis penuh (tanpa parameter) yang dilayani GET, selain peta situs sendiri; pengalihan tidak dihitung
    preg_match_all("#Route::(?:view|get|livewire)\(\s*['\"](/[^'\"{}?]*)['\"]#", $web, $mp);
    $staticPaths = array_values(array_unique(array_filter($mp[1], fn ($pth) => $pth !== '/sitemap.xml' && !str_contains($pth, '.'))));
    $listed = array_values(array_filter((array) ($cfg['sitemap_static'] ?? []), 'is_string'));
    $notListed = array_values(array_diff($staticPaths, $listed));
    $stale = array_values(array_diff($listed, $staticPaths));
    foreach ($notListed as $pth) { $warn("rute statis '$pth' belum ada di config('cms.sitemap_static'): halaman ini tidak akan masuk peta situs"); }
    foreach ($stale as $pth) { $warn("config('cms.sitemap_static') memuat '$pth' tetapi tidak ada rute statisnya di routes/web.php (alamat mati masuk peta situs, atau halaman sudah pindah ke CMS: hapus dari daftar)"); }
    $robots = is_file("$target/public/robots.txt") ? (string) file_get_contents("$target/public/robots.txt") : null;
    if ($robots === null) { $warn('public/robots.txt tidak ada: tambahkan baris "Sitemap: https://domain-anda/sitemap.xml"'); }
    elseif (!preg_match('#^Sitemap:\s*https?://\S+/sitemap\.xml\s*$#mi', $robots)) { $warn('public/robots.txt tidak memuat baris "Sitemap: https://domain-anda/sitemap.xml"'); }
    if ($hasRoute && !$notListed && !$stale && $robots !== null && preg_match('#^Sitemap:\s*https?://\S+/sitemap\.xml\s*$#mi', $robots)) { $ok(count($listed) . ' jalur statis tercatat di sitemap_static, rute /sitemap.xml ada, dan robots.txt menunjuk peta situs'); }
} else {
    $warn('routes/web.php atau config/cms.php tidak bisa dibaca: pemeriksaan peta situs dilewati');
}

// ---------------------------------------------------------------- 4. isi renderer: bukan komponen editor
echo "\n[4] Renderer dan komponen tampilan\n";
$files = [];
foreach (['components/blocks/render', 'components/content'] as $d) {
    foreach (glob("$views/$d/*.blade.php") ?: [] as $f) { $files[] = $f; }
}
if (is_file("$views/components/table-of-contents.blade.php")) { $files[] = "$views/components/table-of-contents.blade.php"; }
$editorish = ['x-blocks.editor' => 'komponen EDITOR (x-blocks.editor)', 'x-editor.' => 'komponen editor (x-editor.*)', 'wire:model' => 'pengikat Livewire (wire:model): UI editor', '$wire' => '$wire: UI editor', '<livewire:' => 'komponen Livewire', '@livewire' => '@livewire'];
$flagged = 0;
foreach ($files as $f) {
    $src = (string) file_get_contents($f);
    foreach ($editorish as $needle => $why) {
        if (str_contains($src, $needle)) { $err($rel($f) . ": memakai $why. Ini tampaknya komponen EDITOR, bukan renderer publik (renderer publik ada di resources/views/components/blocks/render di CMS)"); $flagged++; break; }
    }
    if (preg_match('/@props\s*\(\s*\[[^\]]*["\'](?:blockId|activeLocales)["\']/s', $src)) { $err($rel($f) . ': @props memuat blockId/activeLocales, ciri komponen editor'); $flagged++; }
    if (preg_match('/\\\\?App\\\\Editor\\\\(?!BlockPalette)/', $src) && !str_ends_with($f, 'sections.blade.php')) { $warn($rel($f) . ': memakai kelas App\\Editor (milik sisi admin)'); }
}
if (!$flagged) { $ok(count($files) . ' berkas tampilan diperiksa: tidak ada ciri komponen editor'); }
if (is_dir("$views/components/blocks/editor")) { $warn('resources/views/components/blocks/editor ada di landing: komponen editor tidak diperlukan dan sebaiknya tidak ikut'); }

// ---------------------------------------------------------------- 5. ketergantungan yang dibaca renderer
echo "\n[5] Ketergantungan yang dibaca tampilan\n";
$allViews = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) { if (str_ends_with($f->getFilename(), '.blade.php')) { $allViews[] = $f->getPathname(); } }
$configKeys = $comps = $models = [];
foreach ($allViews as $f) {
    $src = preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($f));
    if (preg_match_all("/config\(\s*['\"]cms\.([a-zA-Z0-9_.]+)['\"]/", $src, $m)) { foreach ($m[1] as $k) { $configKeys[$k][] = $rel($f); } }
    if (preg_match_all('/<x-([a-z0-9]+(?:[.\-][a-z0-9]+)*)(?=[\s\/>])/', $src, $m)) { foreach ($m[1] as $c) { $comps[$c][] = $rel($f); } }
    if (preg_match_all('/App\\\\Models\\\\([A-Za-z]+)/', $src, $m)) { foreach ($m[1] as $c) { $models[$c][] = $rel($f); } }
}
$miss = 0;
if (is_array($cfg)) {
    foreach ($configKeys as $key => $by) {
        $cur = $cfg; $found = true;
        foreach (explode('.', $key) as $seg) { if (is_array($cur) && array_key_exists($seg, $cur)) { $cur = $cur[$seg]; } else { $found = false; break; } }
        if (!$found) { $err("config('cms.$key') dibaca oleh " . $by[0] . " tetapi tidak ada di config/cms.php landing"); $miss++; }
    }
}
foreach ($comps as $c => $by) {
    if (str_starts_with($c, 'lucide-') || in_array($c, ['dynamic-component', 'slot'], true) || str_starts_with($c, 'livewire')) { continue; }
    $path = str_replace('.', '/', $c);
    $last = basename($path); $dir = dirname($path);
    $candidates = ["$views/components/$path.blade.php", "$views/components/$path/index.blade.php", "$views/components/" . ($dir === '.' ? '' : "$dir/") . "⚡$last.blade.php"];
    if (!array_filter($candidates, 'is_file')) { $err("komponen <x-$c> dipakai oleh " . $by[0] . " tetapi tidak ada di landing"); $miss++; }
}
foreach ($models as $mname => $by) {
    if (!is_file("$target/app/Models/$mname.php")) { $err("model App\\Models\\$mname dipakai oleh " . $by[0] . " tetapi tidak ada di landing"); $miss++; }
}
if (!$miss) { $ok(count($configKeys) . ' kunci config, ' . count($comps) . ' komponen, ' . count($models) . ' model: semuanya tersedia'); }

/**
 * Apakah $name di $src HANYA pernah diisi konstanta string: `$name = "teks";` atau `$name = match (...) { "a" => "teks", default => "teks" };`?
 * (Nilai seperti itu tidak bisa dipengaruhi penyerang, jadi menyisipkannya ke ekspresi Alpine tidak berbahaya.)
 */
function isConstantVar(string $src, string $name): bool
{
    if (!preg_match_all('/\$' . preg_quote($name, '/') . '\s*=(?![=>])\s*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        return false; // tidak diisi di berkas ini (prop, variabel dari luar): anggap data
    }
    $str = '(?:"[^"$\\\\{}]*"|\'[^\'$\\\\{}]*\')';
    foreach ($m[0] as [$hit, $off]) {
        $rest = substr($src, $off + strlen($hit));
        if (preg_match('/^' . $str . '\s*;/', $rest)) {
            continue; // $x = "literal";
        }
        if (preg_match('/^match\s*\([^)]*\)\s*\{/', $rest, $mm)) {
            $body = substr($rest, strlen($mm[0]));
            $depth = 1; $end = null;
            for ($i = 0, $n = strlen($body); $i < $n; $i++) {
                if ($body[$i] === '{') { $depth++; } elseif ($body[$i] === '}' && --$depth === 0) { $end = $i; break; }
            }
            if ($end === null) {
                return false;
            }
            $compact = preg_replace('/\s+/', ' ', preg_replace('/' . $str . '/', 'S', substr($body, 0, $end)) ?? '') ?? '';
            if (!preg_match('/^ ?(?:(?:S|default) => S ?, ?)*(?:(?:S|default) => S ?,? ?)? ?$/', $compact)) {
                return false; // ada cabang berisi variabel/ekspresi
            }
            continue;
        }
        return false;
    }

    return true;
}

// ---------------------------------------------------------------- 5b. injeksi ke ekspresi Alpine dan sisa salinan
echo "\n[5b] Keamanan ekspresi Alpine dan teks sisa\n";
$bad5b = 0;
foreach ($allViews as $f) {
    $raw = (string) file_get_contents($f);
    // komentar Blade tidak dihitung; diganti baris kosong sebanyak barisnya supaya nomor baris pada laporan = nomor baris SEBENARNYA
    $src = preg_replace_callback('/\{\{--.*?--\}\}/s', fn ($mc) => str_repeat("\n", substr_count($mc[0], "\n")), $raw) ?? '';
    foreach (preg_split('/\R/', $src) ?: [] as $i => $line) {
        // {{ }} HANYA meng-escape HTML; peramban mendekodenya kembali sebelum Alpine mengeksekusi atributnya, jadi apostrof di dalam
        // literal string ('...{{ $x }}...') memungkinkan keluar dari string dan menjalankan kode. Gunakan @js($x).
        if (preg_match('/(?:^|\s)(?:@|:|x-)[A-Za-z0-9.:_-]+\s*=\s*"[^"]*[\'`]\s*\{\{/', $line) || preg_match('/(?:^|\s)(?:@|:|x-)[A-Za-z0-9.:_-]+\s*=\s*\'[^\']*["`]\s*\{\{/', $line) || preg_match('/(?:^|\s)(?:@|:|x-)[A-Za-z0-9.:_-]+\s*=\s*"[^"]*\{!!/', $line)) {
            // variabel yang hanya pernah diisi konstanta string tidak berbahaya: dilewati. {!! !!} dan ekspresi lain selalu ditandai.
            if (!str_contains($line, '{!!') && preg_match_all('/\{\{\s*(.*?)\s*\}\}/', $line, $ex) && array_reduce($ex[1], fn ($all, $e) => $all && preg_match('/^\$([A-Za-z_]\w*)$/', $e, $vm) === 1 && isConstantVar($src, $vm[1]), true)) {
                continue;
            }
            $err($rel($f) . ':' . ($i + 1) . ': nilai ({{ }} atau {!! !!}) disisipkan ke dalam ekspresi Alpine (x-*, @*, :*). {{ }} hanya meng-escape HTML dan TIDAK mencegah keluar dari literal string JavaScript: gunakan @js($nilai)');
            $bad5b++;
        }
    }
    if (preg_match_all('/\[cite:\s*\d+\]/', $src, $mc)) {
        $warn($rel($f) . ': teks "' . $mc[0][0] . '" tertinggal di templat dan akan TAMPIL di halaman (sisa salinan dari alat AI); hapus');
        $bad5b++;
    }
}
if (!$bad5b) { $ok(count($allViews) . ' tampilan diperiksa: tidak ada interpolasi ke ekspresi Alpine dan tidak ada teks "[cite: n]" yang tampil'); }

// ---------------------------------------------------------------- 6. tabrakan direktif Blade
echo "\n[6] Tabrakan direktif Blade\n";
exec('php ' . escapeshellarg("$kit/tests/blade-scan.php") . ' ' . escapeshellarg($target) . ' 2>&1', $out, $code);
if ($code === 0) { $ok(trim((string) end($out))); } else { foreach ($out as $line) { if (str_contains($line, 'GALAT')) { $err(trim(str_replace('GALAT', '', $line))); } } }

// ---------------------------------------------------------------- 7. CSS Tailwind
echo "\n[7] Tailwind (resources/css/app.css)\n";
$css = is_file("$target/resources/css/app.css") ? (string) file_get_contents("$target/resources/css/app.css") : '';
if ($css === '') { $warn('resources/css/app.css tidak ditemukan'); } else {
    $c0 = 0;
    if (!preg_match("#@source\s+['\"]\.\./\.\./app['\"]#", $css)) { $warn("tambahkan @source '../../app'; kelas di PHP kit (rasio, lebar carousel) bisa tidak terbentuk"); $c0++; }
    if (!preg_match("#@source\s+['\"]\.\./\.\./config['\"]#", $css)) { $warn("tambahkan @source '../../config'; config/cms.php menyimpan daftar kelas Tailwind (design)"); $c0++; }
    if (!str_contains($css, '--color-charcoal')) { $warn('--color-charcoal tidak didefinisikan: pilihan warna Charcoal pada blok tanpa gaya'); $c0++; }
    if (!preg_match('/\[x-cloak\]\s*\{\s*display:\s*none\s*!important/', $css)) { $warn('[x-cloak] tidak disembunyikan: menu mobile dan nav lengket bisa berkedip'); $c0++; }
    if (!$c0) { $ok('@source app dan config, warna charcoal, dan [x-cloak] sudah ada'); }
}

// ---------------------------------------------------------------- 8. berkas kit sinkron?
echo "\n[8] Sinkron dengan kit\n";
exec('php ' . escapeshellarg("$kit/tools/sync-landing.php") . ' ' . escapeshellarg($target) . ' 2>&1', $out2, $code2);
$summary = '';
foreach ($out2 as $line) { if (str_starts_with(trim($line), 'Periksa:')) { $summary = trim($line); } }   // baris ringkasan, bukan baris terakhir
$summary = $summary !== '' ? $summary : trim((string) end($out2));
if ($code2 === 0) { $ok($summary); } else {
    foreach ($out2 as $line) { if (preg_match('/^\s+(BARU|BERBEDA)\s+(\S+)/', $line, $mm)) { $warn("{$mm[1]}: {$mm[2]}"); } }
    $warn($summary . '  (jalankan: php tools/sync-landing.php "' . $target . '" --apply)');
}

echo "\n==> $errors galat, $warnings peringatan\n";
exit($errors ? 1 : 0);
