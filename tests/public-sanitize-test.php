<?php
/**
 * Jalur PUBLIK dengan isi berbahaya:  php tests/public-sanitize-test.php <bootstrap-lab.php> <support.php>
 * Halaman, snippet, dan artikel lama (HTML mentah) lewat PublicLookup::document() dengan model landing yang sebenarnya.
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
require $support;
foreach (['ReadOnlyModel', 'Page', 'Post', 'Category', 'Snippet', 'Media'] as $m) { require "$root/landing-app/app/Models/$m.php"; }

use App\Content\PublicLookup as PL;
use App\Models\{Page, Post};
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

// config() ada di aplikasi Laravel, tidak di lab: templat alamat publik dari config('cms.public')
$GLOBALS['cfg'] = ['cms.public.page' => '/{slug}', 'cms.public.article' => '/artikel/{slug}', 'cms.public.base' => ''];
if (!function_exists('config')) { function config($key = null, $default = null) { return array_key_exists($key, $GLOBALS['cfg']) ? $GLOBALS['cfg'][$key] : $default; } }

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

boot_database();
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
(require "$root/database/migrations/2026_10_07_000001_create_snippets_table.php")->up();
foreach (['pages', 'posts'] as $tb) { Schema::create($tb, function ($t) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); $t->string('status')->default('online'); $t->text('updated_at')->nullable(); $t->text('created_at')->nullable(); }); }
$now = date('Y-m-d H:i:s');

$evil = '<p>Halo <strong>dunia</strong><script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(2)" onclick="x()">klik</a> <a href="internal://page/tentang-kami">internal</a> <a href="internal://article/imunisasi-dasar" target="_blank">artikel</a><iframe src="https://evil.test"></iframe></p>';
$blocks = [
    's1' => ['id' => 's1', 'type' => 'section_divider', 'data' => ['background' => 'bg-paper']],
    'h1' => ['id' => 'h1', 'type' => 'heading', 'data' => ['level' => 'h2', 'text' => ['id' => $evil, 'en' => $evil]]],
    'p1' => ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => ['en' => '<p style="text-indent: 2rem; position: fixed;">Indentasi <span class="pill-wrapper" style="background-color: #E9F1EB; border: 1.5px solid #FCA5A5;">pill</span></p>']]],
    'e1' => ['id' => 'e1', 'type' => 'eyebrow', 'data' => ['text' => ['en' => 'Tag'], 'icon' => 'x::y', 'color' => 'red; position: fixed; inset: 0']],
    'i1' => ['id' => 'i1', 'type' => 'image', 'data' => ['url' => 'javascript:alert(3)', 'caption' => ['en' => 'c']]],
    'm1' => ['id' => 'm1', 'type' => 'multi-columns', 'data' => ['col_count' => 2, 'col_1_zone_width' => '1fr; position:fixed', 'col_2_zone_width' => '2', 'col_1_zone' => ['c1'], 'col_2_zone' => []]],
    'c1' => ['id' => 'c1', 'type' => 'card-builder', 'data' => ['grid' => ['cols' => 3], 'cards' => [['container' => ['url' => 'javascript:alert(4)'], 'layout' => ['children' => [['width' => '1; x', 'children' => [['elementType' => 'button', 'data' => ['content' => ['label' => ['en' => 'Go'], 'url' => 'internal://page/kontak']]]]]]]]]]],
];
$content = json_encode(['blocks' => $blocks, 'order' => ['s1', 'h1', 'p1', 'e1', 'i1', 'm1'], 'settings' => []]);
DB::table('pages')->insert(['title' => '{"en":"T"}', 'slug' => '{"en":"uji","id":"uji"}', 'status' => 'online', 'content' => $content, 'created_at' => $now, 'updated_at' => $now]);

echo "\nHalaman: isi berbahaya lewat PublicLookup::document()\n";
$page = Page::query()->first();
$raw = $page->getRawOriginal('content');
$doc = PL::document($raw, ['id', 'en']);
$h = $doc['blocks']['h1']['data']['text']['en'];
check('judul: skrip, iframe, penangan, dan tautan javascript: hilang; teks aman dan format dipertahankan', !preg_match('/<script|<iframe|onerror|onclick|javascript:/i', $h) && str_contains($h, '<strong>dunia</strong>') && str_contains($h, 'klik'), $h);
check('tautan internal diselesaikan lewat config("cms.public"): halaman -> /tentang-kami, artikel -> /artikel/imunisasi-dasar (+ noopener karena _blank)', str_contains($h, '<a href="/tentang-kami">internal</a>') && str_contains($h, '<a href="/artikel/imunisasi-dasar" target="_blank" rel="noopener noreferrer">artikel</a>'), $h);
check('SETIAP bahasa disaring (id dan en sama)', $doc['blocks']['h1']['data']['text']['id'] === $h);
$p = $doc['blocks']['p1']['data']['text']['en'];
check('paragraf: indentasi dan pill (format editor) utuh; properti berbahaya ("position: fixed") dibuang', str_contains($p, 'text-indent: 2rem;') && !str_contains($p, 'position') && str_contains($p, 'class="pill-wrapper"') && str_contains($p, 'border: 1.5px solid #FCA5A5'), $p);
check('eyebrow: ikon tak sah -> newspaper; warna dengan sisipan CSS -> bawaan', $doc['blocks']['e1']['data']['icon'] === 'newspaper' && $doc['blocks']['e1']['data']['color'] === '#e05a47');
check('gambar: url javascript: dikosongkan (renderer tidak mencetak gambar tanpa url)', $doc['blocks']['i1']['data']['url'] === '');
check('kolom: lebar dengan sisipan CSS -> "1"; lebar sah "2" utuh', $doc['blocks']['m1']['data']['col_1_zone_width'] === '1' && $doc['blocks']['m1']['data']['col_2_zone_width'] === '2');
check('kartu: url javascript: -> ""; lebar kolom tak sah -> "1"; tombol internal:// diselesaikan -> /kontak', $doc['blocks']['c1']['data']['cards'][0]['container']['url'] === '' && $doc['blocks']['c1']['data']['cards'][0]['layout']['children'][0]['width'] === '1' && $doc['blocks']['c1']['data']['cards'][0]['layout']['children'][0]['children'][0]['data']['content']['url'] === '/kontak');
check('ID blok, urutan, dan pengaturan dokumen tidak berubah', array_keys($doc['blocks']) === array_keys($blocks) && $doc['order'] === ['s1', 'h1', 'p1', 'e1', 'i1', 'm1']);
check('NILAI DI BASIS DATA tidak berubah (penyaringan hanya pada tampilan; landing pun baca-saja)', DB::table('pages')->value('content') === $content && Page::query()->first()->getRawOriginal('content') === $content);
check('dipanggil dua kali: hasil sama (idempoten)', PL::document($raw, ['id', 'en']) === $doc);

echo "\nArtikel lama (HTML mentah diimpor sebagai satu paragraf)\n";
$legacy = '<h2>Judul</h2><p>Isi <a href="internal://article/lain">tautan</a></p><table><tr><th colspan="2">T</th></tr><tr><td>1</td><td onclick="x()">2</td></tr></table><iframe src="https://www.youtube.com/embed/abc"></iframe><script>steal()</script><img src="/storage/articles/a.webp" onerror="x()" alt="Foto">';
DB::table('posts')->insert(['title' => '{"id":"Lama"}', 'slug' => '{"id":"artikel-lama"}', 'status' => 'published', 'content' => json_encode($legacy), 'created_at' => $now, 'updated_at' => $now]);
$post = Post::query()->first();
$ld = PL::document($post->getRawOriginal('content'), ['id', 'en']);
$imported = array_values($ld['blocks']);
$text = $imported[0]['data']['text']['id'] ?? ($imported[0]['data']['text']['en'] ?? json_encode($imported));
check('HTML lama diimpor sebagai SATU blok paragraf dan ditandai imported', count($imported) === 1 && ($imported[0]['type'] ?? '') === 'paragraph' && $ld['imported'] === true, json_encode($ld));
check('artikel lama: skrip, iframe, dan penangan dibuang; tabel (colspan), gambar, judul, dan tautan internal utuh', !preg_match('/<script|<iframe|onerror|onclick|steal/i', $text) && str_contains($text, '<table>') && str_contains($text, 'colspan="2"') && str_contains($text, '<h2>Judul</h2>') && str_contains($text, 'src="/storage/articles/a.webp"') && str_contains($text, 'href="/artikel/lain"'), $text);
check('catatan: sematan YouTube (iframe) pada artikel lama memang HILANG di tampilan publik (gunakan blok Video); isi basis data tetap utuh', !str_contains($text, 'youtube') && str_contains((string) DB::table('posts')->value('content'), 'youtube.com'));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
