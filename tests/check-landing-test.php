<?php
/**
 * Pengujian tools/check-landing.php:  php tests/check-landing-test.php [akar-kit]
 * Membangun proyek landing tiruan yang lengkap, lalu merusaknya satu per satu dan memastikan alat melaporkannya (galat vs peringatan).
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }
function rrm(string $d): void { if (!is_dir($d)) return; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($d); }
function put(string $p, string $c): void { @mkdir(dirname($p), 0775, true); file_put_contents($p, $c); }
function copyDir(string $from, string $to): void { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $f) { $t = $to . '/' . substr($f->getPathname(), strlen($from) + 1); $f->isDir() ? @mkdir($t, 0775, true) : (@mkdir(dirname($t), 0775, true) || true) && copy($f->getPathname(), $t); } }
function run(string $dir, string $root): array { exec('php ' . escapeshellarg("$root/tools/check-landing.php") . ' ' . escapeshellarg($dir) . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }

$tmp = sys_get_temp_dir() . '/check-landing-' . getmypid();
function fresh(string $tmp, string $root, string $name = 'p'): string
{
    $d = "$tmp/$name"; rrm($d); mkdir($d, 0775, true);
    put("$d/artisan", '<?php'); put("$d/composer.json", '{}');
    exec('php ' . escapeshellarg("$root/tools/sync-landing.php") . ' ' . escapeshellarg($d) . ' --apply 2>&1', $o, $c);
    copyDir("$root/landing-app/app", "$d/app"); copyDir("$root/landing-app/config", "$d/config"); copyDir("$root/landing-app/resources", "$d/resources"); copyDir("$root/landing-app/routes", "$d/routes"); copyDir("$root/landing-app/public", "$d/public");
    foreach (['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns'] as $t) {
        put("$d/resources/views/components/blocks/render/$t.blade.php", "@props(['block' => [], 'data' => [], 'lang' => 'id', 'allContent' => []])\n<div data-r=\"$t\">{{ \$lang }}</div>\n");
    }
    // milik aplikasi landing yang dipakai berkas landing-app (layout dan model Setting)
    put("$d/resources/views/components/layouts/app.blade.php", "<html><body>{{ \$slot }}</body></html>\n");
    put("$d/app/Models/Setting.php", "<?php\nnamespace App\\Models;\nclass Setting extends \\Illuminate\\Database\\Eloquent\\Model {}\n");
    return $d;
}
$P = fresh($tmp, $root);

echo "\nProyek lengkap\n";
[$c, $o] = run($P, $root);
check('proyek landing lengkap: 0 galat, 0 peringatan, kode keluar 0', $c === 0 && str_contains($o, '==> 0 galat, 0 peringatan'), $o);
check('memeriksa 14 tipe blok (8 bawaan yang dirender + 6 modul; section-divider bukan komponen)', str_contains($o, 'Renderer blok (14 tipe)'));
check('menyebut lucide 75 ikon dan templat alamat', str_contains($o, 'lucide: 75 ikon') && str_contains($o, "public.page '/{slug}'"));

echo "\nGalat\n";
$scenario = function (string $label, callable $mutate, array $mustContain, int $code = 1, bool $notContainError = false) use ($tmp, $root) {
    $d = fresh($tmp, $root, 's'); $mutate($d);
    [$c, $o] = run($d, $root);
    $ok = $c === $code; foreach ($mustContain as $needle) { $ok = $ok && str_contains($o, $needle); }
    check($label, $ok, "kode $c :: $o");
    return [$c, $o];
};
$scenario('section-divider TIDAK diminta: proyek tanpa renderer section-divider lolos tanpa galat (ia pemisah seksi, bukan komponen)', fn ($d) => @unlink("$d/resources/views/components/blocks/render/section-divider.blade.php"), ['==> 0 galat, 0 peringatan'], 0);
$scenario('renderer section-divider yang ikut tersalin tidak mengganggu (diabaikan)', fn ($d) => put("$d/resources/views/components/blocks/render/section-divider.blade.php", "<div></div>\n"), ['==> 0 galat'], 0);
$scenario('renderer "heading" belum disalin dari CMS: galat menyebut tipe dan petunjuk salin', fn ($d) => unlink("$d/resources/views/components/blocks/render/heading.blade.php"), ['GALAT', 'render/heading.blade.php', '(salin dari CMS)', '==> 1 galat']);
$scenario('renderer modul (video) hilang: petunjuk sinkron kit, bukan salin dari CMS', fn ($d) => unlink("$d/resources/views/components/blocks/render/video-builder.blade.php"), ['video-builder.blade.php', 'sync-landing']);
$scenario('komponen EDITOR tersalin sebagai renderer (x-blocks.editor.wrapper + @props blockId): dua ciri terdeteksi', fn ($d) => put("$d/resources/views/components/blocks/render/paragraph.blade.php", "@props([\"blockId\", \"code\", \"block\", \"allContent\" => []])\n<x-blocks.editor.wrapper :block-id=\"\$blockId\" :block=\"\$block\">x</x-blocks.editor.wrapper>\n"), ['paragraph.blade.php', 'komponen EDITOR', 'blockId/activeLocales']);
$scenario('renderer berisi wire:model (UI editor Livewire)', fn ($d) => put("$d/resources/views/components/blocks/render/eyebrow.blade.php", "<input wire:model=\"x\">\n"), ['eyebrow.blade.php', 'wire:model']);
$scenario('renderer memakai <x-editor.rich>: komponen admin', fn ($d) => put("$d/resources/views/components/blocks/render/image.blade.php", "<x-editor.rich />\n"), ['image.blade.php', 'x-editor.']);
$scenario('renderer membaca config("cms.design.tidak_ada"): galat menyebut kunci dan berkas', fn ($d) => put("$d/resources/views/components/blocks/render/step-group.blade.php", "@php \$x = config('cms.design.tidak_ada', []); @endphp\n<div></div>\n"), ["config('cms.design.tidak_ada')", 'step-group.blade.php', 'tidak ada di config/cms.php landing']);
$scenario('renderer membaca kunci yang ADA (cms.design.margin_bottom): tidak ada galat', fn ($d) => put("$d/resources/views/components/blocks/render/step-group.blade.php", "@php \$x = config('cms.design.margin_bottom', []); \$f = config('cms.fonts'); @endphp\n<div></div>\n"), ['==> 0 galat'], 0);
$scenario('renderer memakai komponen yang tidak ada (<x-foo.bar>): galat', fn ($d) => put("$d/resources/views/components/blocks/render/card-builder.blade.php", "<x-foo.bar />\n"), ['<x-foo.bar>', 'card-builder.blade.php']);
$scenario('komponen yang ADA, ikon lucide, dan dynamic-component tidak dianggap hilang', fn ($d) => put("$d/resources/views/components/blocks/render/card-builder.blade.php", "<x-table-of-contents :items=\"[]\" /><x-lucide-phone /><x-dynamic-component :component=\"'lucide-mail'\" />\n"), ['==> 0 galat'], 0);
$scenario('renderer memakai model yang tidak ada (App\\Models\\Hantu): galat', fn ($d) => put("$d/resources/views/components/blocks/render/multi-columns.blade.php", "@php \$h = \\App\\Models\\Hantu::first(); @endphp\n"), ['App\\Models\\Hantu', 'multi-columns.blade.php']);
$scenario('daftar isi belum ada: galat yang menunjuk versi yang sudah diperbaiki di landing-app', fn ($d) => unlink("$d/resources/views/components/table-of-contents.blade.php"), ['table-of-contents.blade.php', 'landing-app/resources/views/components/table-of-contents.blade.php']);
$scenario('config tanpa kunci public: galat', fn ($d) => put("$d/config/cms.php", "<?php\nreturn ['lucide' => ['phone'], 'design' => []];\n"), ["kunci 'public'"]);
$scenario('config dengan lucide kosong: galat', fn ($d) => put("$d/config/cms.php", "<?php\nreturn ['lucide' => [], 'design' => [], 'public' => ['page' => '/{slug}', 'article' => '/artikel/{slug}']];\n"), ["kunci 'lucide' kosong"]);
$scenario('config rusak (sintaks): galat, alat tidak crash', fn ($d) => put("$d/config/cms.php", "<?php\nreturn [ 'lucide' => \n"), ['config/cms.php']);
$scenario('config tidak ada: galat', fn ($d) => unlink("$d/config/cms.php"), ['config/cms.php tidak ada']);
$scenario('Media tanpa SoftDeletes: galat (berkas terhapus tetap tampil)', fn ($d) => put("$d/app/Models/Media.php", "<?php\nnamespace App\\Models;\nclass Media extends ReadOnlyModel {}\n"), ['Media.php tanpa SoftDeletes']);
$scenario('tabrakan direktif Blade (\'@context\' di dalam {!! !!}) pada renderer salinan: galat dari pemindai', fn ($d) => put("$d/resources/views/components/blocks/render/heading.blade.php", "<script>{!! json_encode(['@context' => 'https://schema.org']) !!}</script>\n"), ['@context']);
$scenario('berkas kit belum tersinkron (PublicLookup hilang): galat + peringatan sinkron', fn ($d) => unlink("$d/app/Content/PublicLookup.php"), ['app/Content/PublicLookup.php', 'sync-landing']);

echo "\nPeringatan (kode keluar tetap 0)\n";
$scenario('folder blocks/editor ikut tersalin: peringatan', fn ($d) => put("$d/resources/views/components/blocks/editor/wrapper.blade.php", "<div></div>\n"), ['PERINGATAN', 'blocks/editor', '==> 0 galat'], 0);
$scenario('app.css tanpa @source config, charcoal, dan x-cloak: tiga peringatan', fn ($d) => put("$d/resources/css/app.css", "@import 'tailwindcss';\n@source '../views';\n"), ["@source '../../app'", "@source '../../config'", '--color-charcoal', '[x-cloak]', '==> 0 galat'], 0);
$scenario('berkas kit di landing berbeda dari kit: peringatan sinkron', fn ($d) => put("$d/app/Content/Names.php", "<?php // disunting"), ['PERINGATAN', 'berbeda', '==> 0 galat'], 0);

echo "\nKeamanan ekspresi Alpine dan teks sisa\n";
$probe = fn (string $html) => fn ($d) => put("$d/resources/views/components/probe.blade.php", $html . "\n");
$scenario('versi ASLI daftar isi dari CMS (anchor di-{{ }} ke dalam @click dan :class): GALAT, dan petunjuknya menyebut @js', $probe('<button @click="scrollTo(\'{{ $item[\'anchor\'] }}\')" :class="activeAnchor === \'{{ $item[\'anchor\'] }}\' ? \'a\' : \'b\'">{{ $item[\'title\'] }}</button>'), ['GALAT', 'probe.blade.php:1', '@js($nilai)']);
$scenario('x-data dengan literal string berisi {{ }}: GALAT', $probe('<div x-data="{ id: \'{{ $id }}\' }">x</div>'), ['probe.blade.php:1', '@js']);
$scenario('x-text / @click dengan {!! !!}: GALAT', $probe('<span x-text="{!! $x !!}"></span>'), ['probe.blade.php:1']);
$scenario('atribut Alpine dengan tanda kutip tunggal luar dan literal berkutip ganda berisi {{ }}: GALAT', $probe('<div x-data=\'{ id: "{{ $id }}" }\'>x</div>'), ['probe.blade.php:1']);
$scenario('pola AMAN tidak dituduh: @js(), x-data statis, :class kondisional, class="{{ }}" biasa, wire:click', $probe('<button @click="go(@js($x))" x-data="{ open: false }" :class="open ? \'a\' : \'b\'" class="{{ $cls }}" wire:click="pilih">{{ $t }}</button>'), ['==> 0 galat, 0 peringatan'], 0);
$scenario('variabel yang hanya diisi KONSTANTA lewat match (seperti $activeText di card-builder Anda): TIDAK dituduh', $probe('@php $activeText = match ($theme) { "coral" => "text-coral", "dark" => \'text-gray-900\', default => "text-foresty" }; @endphp<div x-bind:class="open ? \'{{ $activeText }}\' : \'text-gray-800\'">x</div>'), ['==> 0 galat, 0 peringatan'], 0);
$scenario('variabel yang hanya diisi konstanta string biasa: tidak dituduh', $probe('@php $warna = "text-red-700"; @endphp<div :class="open ? \'{{ $warna }}\' : \'x\'">x</div>'), ['==> 0 galat, 0 peringatan'], 0);
$scenario('match yang SALAH SATU cabangnya berisi variabel/data: GALAT', $probe('@php $c = match ($t) { "a" => "text-a", default => $data["warna"] }; @endphp<div :class="open ? \'{{ $c }}\' : \'x\'">x</div>'), ['probe.blade.php:1', '@js']);
$scenario('variabel dari data ($data[...]) disisipkan ke literal Alpine: GALAT', $probe('@php $c = $data["warna"] ?? "text-a"; @endphp<div :class="open ? \'{{ $c }}\' : \'x\'">x</div>'), ['probe.blade.php:1']);
$scenario('variabel diisi konstanta di satu tempat dan data di tempat lain: GALAT', $probe('@php $c = "text-a"; if ($x) { $c = $data["w"]; } @endphp<div :class="open ? \'{{ $c }}\' : \'x\'">x</div>'), ['probe.blade.php:1']);
$scenario('variabel yang tidak diisi di berkas ini (prop dari luar): GALAT', $probe('<div :class="open ? \'{{ $dariLuar }}\' : \'x\'">x</div>'), ['probe.blade.php:1']);
$scenario('ekspresi bukan variabel sederhana (indeks larik) tidak pernah dianggap konstan: GALAT', $probe('@php $k = "a"; @endphp<div @click="go(\'{{ $x[$k] }}\')">x</div>'), ['probe.blade.php:1']);
$scenario('baris yang memuat {!! !!} (data mentah) DAN {{ konstan }} sekaligus: GALAT (konstanta tidak boleh menutupi {!! !!})', $probe('@php $k = "text-a"; @endphp<div :class="open ? \'{{ $k }}\' : \'{!! $x !!}\'">x</div>'), ['probe.blade.php:1']);
$scenario('nomor baris laporan = baris SEBENARNYA walau ada komentar Blade multibaris di atasnya', fn ($d) => put("$d/resources/views/components/probe.blade.php", "{{-- komentar\nsatu\ndua --}}\n<div @click=\"go('{{ \$x }}')\">x</div>\n"), ['probe.blade.php:4']);
$scenario('teks "[cite: 1]" yang TAMPIL (sisa salinan dari alat AI): PERINGATAN menyebut berkas dan teksnya', $probe('<p>{{ $title }}[cite: 1]</p>'), ['PERINGATAN', 'probe.blade.php', '[cite: 1]', 'TAMPIL'], 0);
$scenario('"[cite: 1]" di dalam komentar Blade tidak mengganggu (tidak tampil)', $probe('{{-- Header Daftar Isi[cite: 1] --}} <p>{{ $title }}</p>'), ['==> 0 galat, 0 peringatan'], 0);
echo "\nSlug terlarang vs rute statis\n";
$scenario('rute statis baru (/tentang) belum dicatat di reserved_slugs: PERINGATAN menyebut slug dan akibatnya, kode keluar tetap 0', function ($d) { file_put_contents("$d/routes/web.php", "Route::view('/tentang', 'pages.about')->name('tentang');\n", FILE_APPEND); }, ["PERINGATAN", "rute statis '/tentang'", 'TIDAK PERNAH terbuka', '==> 0 galat'], 0);
$scenario('rute yang sudah dicatat (about, contact, ...) dan rute teknis bawaan (/articles), /artikel, serta /{slug} dan /artikel/{slug} TIDAK ditandai', fn ($d) => null, ['rute statis satu-segmen: semuanya tercatat', '==> 0 galat, 0 peringatan'], 0);
$scenario('rute statis di dalam komentar tidak dihitung', function ($d) { file_put_contents("$d/routes/web.php", "// Route::view('/lama', 'x');\n# Route::view('/lama2', 'x');\n", FILE_APPEND); }, ['==> 0 galat, 0 peringatan'], 0);
$scenario('rute statis dengan petik ganda dan Route::match juga dibaca', function ($d) { file_put_contents("$d/routes/web.php", "Route::get(\"/kontak-kami\", fn () => 1);\nRoute::match(['get', 'post'], '/formulir', fn () => 1);\n", FILE_APPEND); }, ["rute statis '/kontak-kami'", "rute statis '/formulir'"], 0);
$scenario('slug yang dicatat dengan huruf besar di config tetap dikenali', function ($d) { file_put_contents("$d/routes/web.php", "Route::view('/dampak', 'x');\n", FILE_APPEND); $c = file_get_contents("$d/config/cms.php"); $c = str_replace('"reserved_slugs" => [', '"reserved_slugs" => ["DAMPAK", ', $c); file_put_contents("$d/config/cms.php", str_replace('"sitemap_static" => ["/",', '"sitemap_static" => ["/dampak", "/",', $c)); }, ['==> 0 galat, 0 peringatan'], 0);
$scenario('articles_index_slug berbeda dari jalur rute articles-index: PERINGATAN', fn ($d) => file_put_contents("$d/config/cms.php", str_replace('"articles_index_slug" => "artikel"', '"articles_index_slug" => "berita"', file_get_contents("$d/config/cms.php"))), ["articles_index_slug ('berita')", "'/artikel'", 'PERINGATAN'], 0);
$scenario('routes/web.php tidak ada: peringatan, pemeriksaan dilewati (bukan galat)', fn ($d) => unlink("$d/routes/web.php"), ['pemeriksaan slug terlarang dilewati', '==> 0 galat'], 0);

echo "\nPeta situs dan robots.txt\n";
$scenario('proyek lengkap: peta situs tercatat, rute ada, robots.txt menunjuk /sitemap.xml (dan "sitemap.xml" TIDAK dituduh sebagai slug tak terjaga)', fn ($d) => null, ['jalur statis tercatat di sitemap_static', '==> 0 galat, 0 peringatan'], 0);
$scenario('rute /sitemap.xml tidak ada: PERINGATAN', fn ($d) => file_put_contents("$d/routes/web.php", str_replace("Route::get('/sitemap.xml', \\App\\Http\\Controllers\\SitemapController::class)->name('sitemap');", '', file_get_contents("$d/routes/web.php"))), ["rute GET '/sitemap.xml' tidak ada", '==> 0 galat'], 0);
$scenario('rute merujuk SitemapController tetapi berkasnya tidak ada: GALAT', fn ($d) => unlink("$d/app/Http/Controllers/SitemapController.php"), ['GALAT', 'SitemapController.php tidak ada']);
$scenario("rute statis baru (/dampak) belum ada di sitemap_static: PERINGATAN (selain peringatan slug terlarang)", fn ($d) => file_put_contents("$d/routes/web.php", "Route::view('/dampak', 'x');\n", FILE_APPEND), ["rute statis '/dampak' belum ada di config('cms.sitemap_static')", 'tidak akan masuk peta situs'], 0);
$scenario('sitemap_static memuat jalur yang tidak punya rute (halaman sudah pindah ke CMS, atau salah ketik): PERINGATAN', fn ($d) => file_put_contents("$d/config/cms.php", str_replace('"sitemap_static" => ["/",', '"sitemap_static" => ["/lama", "/",', file_get_contents("$d/config/cms.php"))), ["memuat '/lama'", 'alamat mati'], 0);
$scenario('robots.txt tidak ada: PERINGATAN', fn ($d) => unlink("$d/public/robots.txt"), ['public/robots.txt tidak ada'], 0);
$scenario('robots.txt tanpa baris Sitemap: PERINGATAN', fn ($d) => file_put_contents("$d/public/robots.txt", "User-agent: *\nDisallow:\n"), ['tidak memuat baris "Sitemap:'], 0);

echo "\nPemakaian\n";
[$c, $o] = run("$tmp/tidak-ada", $root);
check('folder tidak ada: kode 2 dengan petunjuk pemakaian', $c === 2 && str_contains($o, 'Pemakaian'), "$c $o");
mkdir("$tmp/kosong", 0775, true);
[$c, $o] = run("$tmp/kosong", $root);
check('folder bukan proyek Laravel: kode 2', $c === 2, "$c $o");
exec('php ' . escapeshellarg("$root/tools/check-landing.php") . ' 2>&1', $o2, $c2);
check('tanpa argumen: kode 2', $c2 === 2);
check('alat hanya MEMBACA: berkas proyek tiruan tidak berubah setelah pemeriksaan', (function () use ($tmp, $root) { $d = fresh($tmp, $root, 'ro'); $before = []; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) { $before[$f->getPathname()] = md5_file($f->getPathname()); } run($d, $root); $after = []; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) { $after[$f->getPathname()] = md5_file($f->getPathname()); } return $before === $after; })());
rrm($tmp);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
