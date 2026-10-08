<?php
/**
 * Paritas mesin seksi dengan mesin pratinjau ASLI:  php tests/section-parity-test.php [akar-kit]
 * Murni (tanpa lab). Membandingkan App\Content\SectionBuilder::group dengan salinan APA ADANYA dari logika pengelompokan seksi dan
 * perakitan daftar isi di page-preview.blade.php milik aplikasi CMS (blok @php), pada ribuan dokumen acak.
 *
 * Satu-satunya selisih yang disengaja: kunci `dividerId` pada setiap seksi (perluasan kit agar kanvas tahu blok pemisah mana yang diklik).
 * Bila uji ini gagal, SectionBuilder telah menyimpang dari perilaku mesin asli: periksa dulu mana yang benar sebelum mengubah salah satunya.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
foreach (['Content/LocaleMap', 'Content/Names', 'Content/Slug', 'Content/SectionBuilder'] as $f) { require_once "$root/app/$f.php"; }

use App\Content\SectionBuilder;

/** SALINAN APA ADANYA (kecuali penulisan ulang spasi tidak dilakukan) dari blok @php mesin render di page-preview.blade.php. */
const REFERENCE = <<<'PHPREF'
$groupedSections = [];
      $tocItems = [];

      // Pengaturan Posisi TOC
      $tocPosition = $settings["toc_position"] ?? "right";

      $currentSection = [
        "bgClass" => "bg-paper",
        "textClass" => "text-charcoal",
        "padding" => "py-16 sm:py-24",
        "anchor" => "",
        "blocks" => [],
      ];

      // PENGELOMPOKAN SEKSI DAN PERAKITAN DAFTAR ISI
      foreach ($rootOrder as $blockId) {
        if (!isset($allContent[$blockId])) {
          continue;
        }
        $block = $allContent[$blockId];
        $normalizedType = str_replace("-", "_", $block["type"]);

        // Deteksi Blok Anchor
        if (!empty($block["anchor"])) {
          $tocTitle = "";

          $extractText = function ($dataField) {
            if (is_array($dataField)) {
              return $dataField[$this->lang] ??
                ($dataField["id"] ?? (reset($dataField) ?? ""));
            }
            return $dataField ?? "";
          };

          if ($normalizedType === "heading") {
            $tocTitle = strip_tags($extractText($block["data"]["text"] ?? ""));
          } elseif ($normalizedType === "section_divider") {
            $tocTitle = strip_tags($extractText($block["data"]["title"] ?? ""));
          }

          if (empty($tocTitle) && !empty($block["data"]["title"])) {
            $tocTitle = strip_tags($extractText($block["data"]["title"]));
          }

          if (!empty(trim($tocTitle))) {
            $tocItems[] = [
              "title" => trim($tocTitle),
              "anchor" => $block["anchor"],
            ];
          }
        }

        if ($normalizedType === "section_divider") {
          if (count($currentSection["blocks"]) > 0) {
            $groupedSections[] = $currentSection;
          }

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

      if (count($currentSection["blocks"]) > 0) {
        $groupedSections[] = $currentSection;
      }
PHPREF;

$engine = new class {
    public string $lang = 'id';

    /** @return array{sections:array,toc:array,tocPosition:string} */
    public function run(array $allContent, array $rootOrder, array $settings): array
    {
        $groupedSections = [];
        $tocItems = [];
        eval(REFERENCE);

        return ['sections' => $groupedSections, 'toc' => $tocItems, 'tocPosition' => $tocPosition];
    }
};

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 500) . "]" : '') . "\n"; }
$strip = function (array $r): array { foreach ($r['sections'] ?? [] as $k => $sec) { unset($r['sections'][$k]['dividerId']); } return $r; };

echo "\nKasus terarah\n";
$blocks = [
    'h1' => ['id' => 'h1', 'type' => 'heading', 'anchor' => 'awal', 'data' => ['text' => ['id' => '<b>Awal</b>', 'en' => 'Start']]],
    'd1' => ['id' => 'd1', 'type' => 'section_divider', 'anchor' => 'bag-2', 'data' => ['title' => ['en' => 'Part Two'], 'background' => 'bg-mist', 'padding' => 'py-8 sm:py-12', 'text_color' => 'text-white']],
    'p1' => ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => ['en' => 'x']]],
];
$engine->lang = 'id'; $r1 = $engine->run($blocks, ['h1', 'd1', 'p1'], []);
check('acuan berjalan dan menghasilkan daftar isi dari heading (HTML dibuang) dan pemisah (teks per bahasa, cadangan ke bahasa lain)', ($r1['toc'][0]['title'] ?? '') === 'Awal' && ($r1['toc'][1]['title'] ?? '') === 'Part Two' && count($r1['sections']) === 2, json_encode($r1['toc']));
check('SectionBuilder menghasilkan hasil yang SAMA pada kasus itu (tanpa dividerId)', $strip(SectionBuilder::group($blocks, ['h1', 'd1', 'p1'], [], 'id')) === $r1);
check('pemisah menjadi awal seksi baru dengan latar, padding, warna teks, dan anchor-nya sendiri', $r1['sections'][1]['bgClass'] === 'bg-mist' && $r1['sections'][1]['padding'] === 'py-8 sm:py-12' && $r1['sections'][1]['textClass'] === 'text-white' && $r1['sections'][1]['anchor'] === 'bag-2');
$g = SectionBuilder::group($blocks, ['h1', 'd1', 'p1'], [], 'id')['sections'];
check('kit menambahkan dividerId (perluasan yang disengaja): berisi id pemisah pada seksi yang diawali pemisah, dan null pada seksi pembuka', array_key_exists('dividerId', $g[0]) && $g[0]['dividerId'] === null && $g[1]['dividerId'] === 'd1');

echo "\nUji acak terhadap mesin asli\n";
mt_srand(5150);
$types = ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns', 'section_divider', 'section-divider', 'button-builder', 'accordion-builder', 'unknown'];
$texts = [['id' => 'Judul A', 'en' => 'Title A'], ['id' => '<b>Tebal</b> &amp; <i>miring</i>', 'en' => '<p>Para</p>'], ['en' => 'Only EN'], ['fr' => 'Seulement FR'], [], 'teks polos', null, ['id' => '', 'en' => ''], ['id' => '  spasi  '], ['en' => 'First EN', 'id' => 'Then ID'], ['fr' => 'Premier', 'id' => 'Ensuite ID'], ['en' => 'EN first', 'fr' => 'FR second']];   // 'id' BUKAN kunci pertama: menguji cadangan ke 'id' sebelum 'bahasa pertama'
$bgs = ['bg-paper', 'bg-mist', 'bg-foresty']; $pads = ['py-8 sm:py-12', 'py-16 sm:py-24'];
$bad = 0; $first = null; $total = 6000;
for ($i = 0; $i < $total; $i++) {
    $blocks = []; $order = [];
    for ($j = mt_rand(0, 14); $j > 0; $j--) {
        $id = 'b' . count($blocks); $t = $types[mt_rand(0, count($types) - 1)]; $data = [];
        if (in_array($t, ['heading', 'paragraph', 'eyebrow'], true)) { $data['text'] = $texts[mt_rand(0, count($texts) - 1)]; }
        if (in_array($t, ['section_divider', 'section-divider'], true)) {
            if (mt_rand(0, 1)) { $data['title'] = $texts[mt_rand(0, count($texts) - 1)]; }
            if (mt_rand(0, 1)) { $data['background'] = $bgs[mt_rand(0, 2)]; }
            if (mt_rand(0, 1)) { $data['padding'] = $pads[mt_rand(0, 1)]; }
            if (mt_rand(0, 1)) { $data['text_color'] = 'text-white'; }
        }
        if (mt_rand(0, 6) === 0) { $data['title'] = $texts[mt_rand(0, count($texts) - 1)]; }
        $blk = ['id' => $id, 'type' => $t, 'data' => $data];
        if (mt_rand(0, 2) === 0) { $blk['anchor'] = ['a-' . $id, 'bagian-' . $id, ''][mt_rand(0, 2)]; }
        $blocks[$id] = $blk; $order[] = $id;
    }
    if (mt_rand(0, 8) === 0 && $order) { $order[] = 'hantu'; }
    if (mt_rand(0, 8) === 0) { shuffle($order); }
    $settings = mt_rand(0, 3) === 0 ? ['toc_position' => ['left', 'right', 'hidden'][mt_rand(0, 2)]] : [];
    $lang = ['id', 'en', 'fr'][mt_rand(0, 2)];
    $engine->lang = $lang;
    $theirs = $engine->run($blocks, $order, $settings);
    $kit = $strip(SectionBuilder::group($blocks, $order, $settings, $lang));
    if ($theirs !== $kit) { $bad++; $first ??= json_encode(compact('blocks', 'order', 'settings', 'lang')) . ' => ' . json_encode([$theirs, $kit]); }
}
check("$total dokumen acak (teks per bahasa, bahasa tak tersedia, string polos, null, id hantu, urutan diacak, kedua penulisan tipe pemisah, semua posisi daftar isi): hasil mesin asli dan SectionBuilder IDENTIK", $bad === 0, "$bad berbeda; contoh: " . $first);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
