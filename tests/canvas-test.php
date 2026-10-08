<?php
/**
 * Pemeriksaan kanvas (logika murni, tanpa Laravel):  php tests/canvas-test.php [akar-kit]
 *  - SectionBuilder dibandingkan dengan SALINAN PERSIS logika asli page-preview (oracle), pada kasus tangan dan dokumen acak
 *  - PreviewStore: token, pemilik, pembersihan, kedaluwarsa, batas ukuran
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Names', 'Content/Slug', 'Content/ContentDocument', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/SectionBuilder', 'Content/PreviewStore'] as $f) {
    require_once "$root/app/$f.php";
}
use App\Content\{PreviewStore, SectionBuilder};

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }

/** SALINAN PERSIS blok pengelompokan di page-preview (hanya $this->lang -> $lang). Jangan "memperbaikinya": ini patokan. */
function oracle(array $allContent, array $rootOrder, array $settings, string $lang): array
{
    $groupedSections = [];
    $tocItems = [];
    $tocPosition = $settings["toc_position"] ?? "right";
    $currentSection = ["bgClass" => "bg-paper", "textClass" => "text-charcoal", "padding" => "py-16 sm:py-24", "anchor" => "", "blocks" => []];
    foreach ($rootOrder as $blockId) {
        if (!isset($allContent[$blockId])) { continue; }
        $block = $allContent[$blockId];
        $normalizedType = str_replace("-", "_", $block["type"]);
        if (!empty($block["anchor"])) {
            $tocTitle = "";
            $extractText = function ($dataField) use ($lang) {
                if (is_array($dataField)) { return $dataField[$lang] ?? ($dataField["id"] ?? (reset($dataField) ?? "")); }
                return $dataField ?? "";
            };
            if ($normalizedType === "heading") { $tocTitle = strip_tags($extractText($block["data"]["text"] ?? "")); }
            elseif ($normalizedType === "section_divider") { $tocTitle = strip_tags($extractText($block["data"]["title"] ?? "")); }
            if (empty($tocTitle) && !empty($block["data"]["title"])) { $tocTitle = strip_tags($extractText($block["data"]["title"])); }
            if (!empty(trim($tocTitle))) { $tocItems[] = ["title" => trim($tocTitle), "anchor" => $block["anchor"]]; }
        }
        if ($normalizedType === "section_divider") {
            if (count($currentSection["blocks"]) > 0) { $groupedSections[] = $currentSection; }
            $currentSection = [
                "bgClass" => $block["data"]["background"] ?? "bg-paper",
                "textClass" => $block["data"]["text_color"] ?? "text-gray-900",
                "padding" => $block["data"]["padding"] ?? "py-16 sm:py-24",
                "anchor" => $block["anchor"] ?? "",
                "blocks" => [],
            ];
            continue;
        }
        $currentSection["blocks"][] = $blockId;
    }
    if (count($currentSection["blocks"]) > 0) { $groupedSections[] = $currentSection; }
    return [$groupedSections, $tocItems, $tocPosition];
}
/** Hasil SectionBuilder dalam bentuk yang sama dengan oracle (tanpa kunci tambahan dividerId). */
function mine(array $blocks, array $order, array $settings, string $lang): array
{
    $r = SectionBuilder::group($blocks, $order, $settings, $lang);
    return [array_map(function ($s) { unset($s['dividerId']); return $s; }, $r['sections']), $r['toc'], $r['tocPosition']];
}
$blk = fn (string $id, string $type, array $data = [], array $extra = []) => [$id => ['id' => $id, 'type' => $type, 'data' => $data] + $extra];

section('SectionBuilder: kasus tangan (hasil harus sama dengan oracle, lalu dicek maknanya)');
$B = $blk('h1', 'heading', ['text' => ['id' => 'Pendahuluan', 'en' => 'Intro']], ['anchor' => 'pendahuluan'])
   + $blk('p1', 'paragraph') + $blk('d1', 'section-divider', ['background' => 'bg-forest', 'text_color' => 'text-white', 'padding' => 'py-8', 'title' => ['id' => 'Bagian Dua']], ['anchor' => 'dua'])
   + $blk('p2', 'paragraph') + $blk('img', 'image') + $blk('d2', 'section_divider') + $blk('p3', 'paragraph');
$O = ['h1', 'p1', 'd1', 'p2', 'img', 'hantu', 'd2', 'p3'];
foreach (['id', 'en', 'fr'] as $lg) check("sama dengan oracle (bahasa $lg)", mine($B, $O, ['toc_position' => 'left'], $lg) === oracle($B, $O, ['toc_position' => 'left'], $lg));
$r = SectionBuilder::group($B, $O, [], 'id');
check('tiga seksi: awal (bawaan), forest, dan setelah d2', count($r['sections']) === 3 && $r['sections'][0]['bgClass'] === 'bg-paper' && $r['sections'][1]['bgClass'] === 'bg-forest' && $r['sections'][2]['bgClass'] === 'bg-paper');
check('divider tidak ikut menjadi blok; blok terbagi benar', $r['sections'][0]['blocks'] === ['h1', 'p1'] && $r['sections'][1]['blocks'] === ['p2', 'img'] && $r['sections'][2]['blocks'] === ['p3']);
check('seksi mengingat ID divider-nya (untuk memilihnya di kanvas); seksi awal tidak punya', $r['sections'][1]['dividerId'] === 'd1' && $r['sections'][2]['dividerId'] === 'd2' && $r['sections'][0]['dividerId'] === null);
check('ID hantu dilewati', !in_array('hantu', array_merge(...array_column($r['sections'], 'blocks')), true));
check('TOC: heading ber-anchor dan divider ber-anchor, judul sesuai bahasa', $r['toc'] === [['title' => 'Pendahuluan', 'anchor' => 'pendahuluan'], ['title' => 'Bagian Dua', 'anchor' => 'dua']] && SectionBuilder::group($B, $O, [], 'en')['toc'][0]['title'] === 'Intro');
check("posisi TOC bawaan 'right'; mengikuti pengaturan", $r['tocPosition'] === 'right' && SectionBuilder::group($B, $O, ['toc_position' => 'hidden'], 'id')['tocPosition'] === 'hidden');
check('dokumen kosong -> tanpa seksi', SectionBuilder::group([], [], [], 'id')['sections'] === [] && SectionBuilder::group($blk('d', 'section-divider'), ['d'], [], 'id')['sections'] === []);
check('seksi tanpa blok dibuang (divider berurutan)', count(SectionBuilder::group($blk('a', 'section-divider') + $blk('b', 'section-divider') + $blk('p', 'paragraph'), ['a', 'b', 'p'], [], 'id')['sections']) === 1);

section('SectionBuilder: 3000 dokumen acak == oracle');
mt_srand(20261007);
$texts = [null, '', ' ', '0', 'Judul', '<b>Tebal</b> Biasa', ['id' => 'A', 'en' => 'B'], ['en' => 'B'], ['fr' => 'Z'], ['id' => '<i>I</i>', 'en' => ''], ['id' => '', 'en' => 'Hanya En']];
$types = ['heading', 'paragraph', 'image', 'section-divider', 'section_divider', 'card-builder', 'multi-columns', 'button-builder', 'step-group'];
$anchors = [null, '', '0', 'a1', 'bagian-2', 'x y'];
$bad = 0; $firstBad = null;
for ($n = 0; $n < 3000; $n++) {
    $blocks = []; $order = []; $count = mt_rand(0, 14);
    for ($i = 0; $i < $count; $i++) {
        $id = 'b' . $i; $type = $types[array_rand($types)];
        $data = [];
        if (mt_rand(0, 1)) $data['text'] = $texts[array_rand($texts)];
        if (mt_rand(0, 1)) $data['title'] = $texts[array_rand($texts)];
        if (mt_rand(0, 2) === 0) $data['background'] = 'bg-x' . $i;
        if (mt_rand(0, 2) === 0) $data['text_color'] = 'text-y';
        if (mt_rand(0, 2) === 0) $data['padding'] = 'py-' . $i;
        $block = ['id' => $id, 'type' => $type, 'data' => $data];
        $a = $anchors[array_rand($anchors)]; if ($a !== null) $block['anchor'] = $a;
        if (mt_rand(0, 6) !== 0) $blocks[$id] = $block;      // kadang blok hilang (ID hantu di urutan)
        $order[] = $id; if (mt_rand(0, 9) === 0) $order[] = $id; // kadang ganda
    }
    $settings = mt_rand(0, 3) === 0 ? ['toc_position' => ['left', 'hidden', 'right'][mt_rand(0, 2)]] : [];
    $lg = ['id', 'en', 'fr'][mt_rand(0, 2)];
    if (mine($blocks, $order, $settings, $lg) !== oracle($blocks, $order, $settings, $lg)) { $bad++; $firstBad ??= json_encode([$blocks, $order, $settings, $lg]); }
}
check('3000 dokumen acak: SEMUA sama dengan oracle', $bad === 0, "$bad berbeda; contoh: " . substr((string) $firstBad, 0, 300));

section('PreviewStore');
class ArrayCache { public array $d = []; public array $ttl = []; public function get($k) { return $this->d[$k] ?? null; } public function put($k, $v, $t) { $this->d[$k] = $v; $this->ttl[$k] = $t; } public function forget($k) { unset($this->d[$k]); } }
$c = new ArrayCache; $s = new PreviewStore($c); $loc = ['id', 'en'];
$evil = ['b1' => ['id' => 'b1', 'type' => 'button-builder', 'data' => ['buttons' => [['label' => ['id' => 'X'], 'link' => ['kind' => 'url', 'ref' => 'javascript:alert(1)']]]]], 'h' => ['id' => 'h', 'type' => 'heading', 'data' => []]];
$p = $s->publish('page', 7, null, $evil, ['b1', 'hantu', 'h'], [], $loc, ['id' => 'Judul']);
check('token 40 heksadesimal; revisi 1', PreviewStore::validToken($p['token']) && $p['rev'] === 1);
$got = $s->get($p['token'], 7);
check('pemilik bisa membaca; id pengguna cocok (juga bila bertipe string)', $got !== null && $s->get($p['token'], '7') !== null);
check('data DIBERSIHKAN sebelum dititipkan (tautan javascript: dikosongkan)', $got['blocks']['b1']['data']['buttons'][0]['link']['ref'] === '', json_encode($got['blocks']['b1']['data']['buttons'][0]['link'] ?? null));
check('ID hantu di urutan dibuang; pengaturan bawaan terisi', $got['order'] === ['b1', 'h'] && ($got['settings']['toc_position'] ?? null) === 'right');
check('judul dan bahasa ikut tersimpan', $got['titles'] === ['id' => 'Judul'] && $got['locales'] === $loc && $got['type'] === 'page');
check('kedaluwarsa 30 menit', array_values($c->ttl)[0] === 1800);
check('pengguna LAIN tidak bisa membaca; tanpa pengguna (belum masuk) juga tidak', $s->get($p['token'], 8) === null && $s->get($p['token'], null) === null);
foreach (['', '../etc/passwd', strtoupper($p['token']), substr($p['token'], 1), $p['token'] . 'a', "{$p['token']}\n", str_repeat('g', 40)] as $badToken) {
    check('token tak sah ditolak tanpa menyentuh cache: ' . json_encode(substr($badToken, 0, 20)), $s->get($badToken, 7) === null && !PreviewStore::validToken($badToken));
}
$p2 = $s->publish('page', 7, $p['token'], $evil, ['b1'], [], $loc);
check('publish ulang oleh pemilik memakai token yang sama dan menaikkan revisi', $p2['token'] === $p['token'] && $p2['rev'] === 2 && $s->get($p['token'], 7)['order'] === ['b1']);
$p3 = $s->publish('page', 8, $p['token'], [], [], [], $loc);
check('token milik orang lain TIDAK ditimpa: pengguna lain mendapat token baru', $p3['token'] !== $p['token'] && $s->get($p['token'], 7)['rev'] === 2 && $s->get($p3['token'], 8) !== null);
$big = ['x' => ['id' => 'x', 'type' => 'paragraph', 'data' => ['text' => ['id' => str_repeat('a', 2_100_000), 'en' => '']]]];
$thrown = null; try { $s->publish('page', 7, null, $big, ['x'], [], $loc); } catch (\LengthException $e) { $thrown = $e; }
check("baris baru di ujung TIDAK lolos validasi (pola '$' tanpa modifier D mengizinkannya): token, slug, kunci, id tombol, ikon (anchor: ujungnya dipangkas, tengahnya ditolak)", !PreviewStore::validToken($p['token'] . "\n") && !\App\Content\Slug::isValid("tentang-kami\n") && !preg_match(\App\Content\ContentDocument::KEY_PATTERN, "donasi\n") && \App\Content\Links\LinkResolver::anchor("kontak\n") === 'kontak' && \App\Content\Links\LinkResolver::anchor("kon\ntak") === null && \App\Content\Blocks\BlockSanitizer::buttonBuilder(['buttons' => [['id' => "itm_1\n", 'icon' => "heart\n"]]])['buttons'][0]['icon'] === '');
check('isi lebih dari 2 MB ditolak (tidak memenuhi cache)', $thrown !== null);
$s->forget($p['token']);
check('forget menghapus; forget token tak sah tidak berbuat apa-apa', $s->get($p['token'], 7) === null && ($s->forget('../x') ?? true));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
