<?php
/**
 * Kepala halaman daftar artikel dan titik belah penutup:  php tests/articles-header-test.php <bootstrap-lab.php> <support.php>
 * PublicLookup::articlesHeader() dan document()['closingFrom'], memakai model landing yang SEBENARNYA (landing-app/app/Models).
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
require $support;
foreach (['ReadOnlyModel', 'Page', 'Post', 'Category', 'Snippet', 'Media'] as $m) { require "$root/landing-app/app/Models/$m.php"; }

use App\Content\{PublicLookup as PL, SectionBuilder};
use App\Models\Page;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

boot_database();
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
(require "$root/database/migrations/2026_10_07_000001_create_snippets_table.php")->up();
foreach (['pages', 'posts'] as $tb) {
    Schema::create($tb, function ($t) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); $t->text('meta_title')->nullable(); $t->text('meta_description')->nullable(); $t->string('status')->default('offline'); $t->timestamps(); });
}
$now = date('Y-m-d H:i:s');
$para = fn (string $id, string $text) => '"' . $id . '":{"id":"' . $id . '","type":"paragraph","data":{"text":{"id":"' . $text . '","en":"' . $text . '"}}}';
$sn = fn (string $key, string $title, string $blockId, string $text, int $closing, string $status = 'online') => DB::table('snippets')->insertGetId(['key' => $key, 'title' => '{"id":"' . $title . '"}', 'content' => '{"blocks":{' . $para($blockId, $text) . '},"order":["' . $blockId . '"],"settings":{}}', 'status' => $status, 'is_closing' => $closing, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now]);
$sDonasi = $sn('donasi', 'Donasi', 's1', 'Ajakan donasi', 1);
$sSisip = $sn('sisipan', 'Sisipan', 's2', 'Sisipan tengah', 0);
$page = fn (array $a) => DB::table('pages')->insertGetId($a + ['title' => '{"id":"T"}', 'meta_title' => null, 'meta_description' => null, 'status' => 'online', 'content' => 'null', 'created_at' => $now, 'updated_at' => $now]);
$content = '{"blocks":{"h1":{"id":"h1","type":"heading","data":{"text":{"id":"Kabar"}}},' . $para('p1', 'Pengantar') . ',"sn1":{"id":"sn1","type":"snippet","data":{"snippet_id":' . $sSisip . '}}},"order":["h1","p1","sn1"],"settings":{}}';
$page(['title' => '{"id":"Kabar Terbaru","en":"Latest News"}', 'slug' => '{"id":"artikel","en":"articles-head"}', 'meta_title' => '{"id":"Kabar | YSBH"}', 'meta_description' => '{"id":"Deskripsi id","en":"Description en"}', 'content' => $content]);
$page(['title' => '{"id":"Draf"}', 'slug' => '{"id":"draf-kepala"}', 'status' => 'offline', 'content' => $content]);
$page(['title' => '{"id":"Polos"}', 'slug' => '{"id":"polos"}']);
$page(['title' => '{"id":"Tanpa penutup"}', 'slug' => '{"id":"tanpa-penutup"}', 'content' => '{"blocks":{' . $para('p9', 'Isi') . '},"order":["p9"],"settings":{"closing":"none"}}']);
$page(['title' => '{"id":"Bersih"}', 'slug' => '{"id":"bersih"}', 'content' => '{"blocks":{"b1":{"id":"b1","type":"button-builder","data":{"buttons":[{"label":{"id":"X"},"link":{"kind":"url","ref":"javascript:alert(1)"}}]}}},"order":["b1"],"settings":{}}']);

echo "\nPencarian halaman kepala\n";
$h = PL::articlesHeader('artikel', 'id', ['id', 'en']);
check('halaman online ber-slug "artikel" ditemukan: judul, judul SEO, deskripsi, bahasa', $h && $h['locale'] === 'id' && $h['title'] === 'Kabar Terbaru' && $h['metaTitle'] === 'Kabar | YSBH' && $h['description'] === 'Deskripsi id', json_encode($h));
$he = PL::articlesHeader('articles-head', 'id', ['id', 'en']);
check('rilis 24, KETAT per bahasa: slug kepala bahasa lain TIDAK dipakai sebagai kepala (daftar tampil polos, bukan kepala bahasa lain); slug bahasa itu sendiri dipakai dan teksnya berbahasa itu', PL::articlesHeader('artikel', 'en', ['id', 'en']) === null && PL::articlesHeader('articles-head', 'id', ['id', 'en']) === null && ($he = PL::articlesHeader('articles-head', 'en', ['id', 'en'])) && $he['locale'] === 'en' && $he['title'] === 'Latest News' && $he['description'] === 'Description en', json_encode($he ?? null));
$hp = PL::articlesHeader('polos', 'id', ['id', 'en']);
check('judul SEO kosong: memakai judul halaman; deskripsi kosong: string kosong', $hp && $hp['metaTitle'] === 'Polos' && $hp['description'] === '', json_encode($hp));
check('halaman tidak ada: null', PL::articlesHeader('tidak-ada', 'id') === null);
check('halaman OFFLINE: null (tidak bocor dari draf)', PL::articlesHeader('draf-kepala', 'id') === null);
DB::connection()->enableQueryLog(); DB::connection()->flushQueryLog();
$bad = ['', 'A b', '../x', 'ARTIKEL', "artikel\n", str_repeat('a', 300), 'a--b', '-a'];
$allNull = true; foreach ($bad as $b) { $allNull = $allNull && PL::articlesHeader($b, 'id') === null; }
check('slug tidak sah (kosong, spasi, ../, huruf besar, baris baru, 300 huruf, ganda -): null DAN nol kueri basis data', $allNull && count(DB::connection()->getQueryLog()) === 0, count(DB::connection()->getQueryLog()) . ' kueri');
DB::connection()->disableQueryLog();

echo "\nPengantar dan penutup dipisah\n";
$raw = Page::query()->where('status', 'online')->first()->getRawOriginal('content');
$doc = PL::document($raw, ['id', 'en']);
check('pengantar = isi halaman + blok snippet sisipan DIPERLUAS di tempatnya (h1, p1, s2); wadah sn1 dibuang', $h['intro']['order'] === ['h1', 'p1', 's2'], json_encode($h['intro']['order']));
check('penutup = snippet penutup online (s1), dan HANYA itu; tanpa pengaturan (tanpa daftar isi)', $h['closing']['order'] === ['s1'] && $h['closing']['settings'] === [], json_encode($h['closing']));
check('keduanya memakai kumpulan blok yang sama (s1 dan s2 ada di keduanya); hanya `order` yang berbeda', array_keys($h['intro']['blocks']) === array_keys($h['closing']['blocks']) && isset($h['intro']['blocks']['s1'], $h['intro']['blocks']['s2']));
check('pengantar + penutup = urutan dokumen utuh PublicLookup::document, dan closingFrom = jumlah pengantar', array_merge($h['intro']['order'], $h['closing']['order']) === $doc['order'] && $doc['closingFrom'] === count($h['intro']['order']), json_encode([$doc['order'], $doc['closingFrom']]));
check('snippet sisipan TIDAK ikut sebagai penutup walau online (bukan penutup); snippet penutup tidak muncul di pengantar', !in_array('s2', $h['closing']['order'], true) && !in_array('s1', $h['intro']['order'], true));
$ht = PL::articlesHeader('tanpa-penutup', 'id');
check('halaman memadamkan penutup (settings.closing = "none"): penutup kosong, pengantar tetap', $ht['closing']['order'] === [] && $ht['intro']['order'] === ['p9']);
check('halaman TANPA isi: pengantar kosong, penutup bawaan tetap ada (daftar tetap diapit sebagaimana mestinya)', $hp['intro']['order'] === [] && $hp['closing']['order'] === ['s1']);
check('document(): closingFrom = jumlah order bila tanpa snippet ($withSnippets=false) atau tanpa penutup', PL::document($raw, ['id', 'en'], false)['closingFrom'] === count(PL::document($raw, ['id', 'en'], false)['order']) && ($d = PL::document('null'))['closingFrom'] === count($d['order']) - 1 && PL::document($ht ? Page::query()->where('status', 'online')->get()->firstWhere(fn ($p) => str_contains((string) $p->getRawOriginal('slug'), 'tanpa-penutup'))->getRawOriginal('content') : null)['closingFrom'] === 1);
$gi = SectionBuilder::group($h['intro']['blocks'], $h['intro']['order'], $h['intro']['settings'], 'id');
$gc = SectionBuilder::group($h['closing']['blocks'], $h['closing']['order'], $h['closing']['settings'], 'id');
$idsOf = fn (array $g) => array_merge(...array_map(fn ($sec) => $sec['blocks'], $g['sections'] ?: [['blocks' => []]]));
check('dirakit mesin seksi: seksi pengantar berisi h1, p1, s2 dan TIDAK s1; seksi penutup hanya s1 (tidak tercampur)', $idsOf($gi) === ['h1', 'p1', 's2'] && $idsOf($gc) === ['s1'], json_encode([$idsOf($gi), $idsOf($gc)]));
check('penutup tidak membawa daftar isi dan pengantar tetap membawa pengaturannya', $gc['toc'] === []);

echo "\nData dibersihkan seperti halaman biasa\n";
$hb = PL::articlesHeader('bersih', 'id');
$link = $hb['intro']['blocks']['b1']['data']['buttons'][0]['link'] ?? 'x';
check('tautan javascript: pada tombol di halaman kepala DIKOSONGKAN (BlockSanitizer berlaku juga di sini)', ($link['ref'] ?? '') === '' && !str_contains(json_encode($hb['intro']['blocks']), 'javascript:'), json_encode($link));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
