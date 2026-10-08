<?php
/**
 * Uji mandiri (tanpa Laravel) untuk operasi pohon blok di HasContentBlocks.
 *
 *   php block-tree-test.php path/ke/HasContentBlocks.php
 *
 * Hanya butuh PHP CLI. Str dan dispatch() di-stub di sini.
 */

namespace Illuminate\Support {
    class Str
    {
        public static function random(int $length = 16): string
        {
            $c = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $s = "";
            for ($i = 0; $i < $length; $i++) {
                $s .= $c[random_int(0, strlen($c) - 1)];
            }
            return $s;
        }
    }
}

namespace {
    if (!isset($argv[1]) || !is_file($argv[1])) {
        fwrite(STDERR, "Pemakaian: php block-tree-test.php path/ke/HasContentBlocks.php\n");
        exit(2);
    }
    require $argv[1];

    class Harness
    {
        use \App\Livewire\Traits\HasContentBlocks;

        public array $content = [];
        public array $blockOrder = [];
        public array $activeLocales = ["id", "en"];

        public function dispatch(string $event, ...$args): void
        {
        }
    }

    // ---------- alat bantu uji (sengaja ditulis terpisah dari trait) ----------
    function isZone(string|int $k): bool
    {
        $k = (string) $k;
        return $k === "children" || str_ends_with($k, "_zone");
    }

    function reachable(Harness $h): array
    {
        $seen = [];
        $stack = $h->blockOrder;
        while ($stack) {
            $id = array_pop($stack);
            if (!is_string($id) || isset($seen[$id]) || !isset($h->content[$id])) {
                continue;
            }
            $seen[$id] = true;
            foreach ($h->content[$id]["data"] ?? [] as $k => $v) {
                if (isZone($k) && is_array($v)) {
                    foreach ($v as $c) {
                        $stack[] = $c;
                    }
                }
            }
        }
        return array_keys($seen);
    }

    function orphans(Harness $h): array
    {
        return array_values(array_diff(array_keys($h->content), reachable($h)));
    }

    function ghosts(Harness $h): array
    {
        $g = [];
        foreach ($h->blockOrder as $id) {
            if (!isset($h->content[$id])) {
                $g[] = "root:$id";
            }
        }
        foreach ($h->content as $pid => $b) {
            foreach ($b["data"] ?? [] as $k => $v) {
                if (isZone($k) && is_array($v)) {
                    foreach ($v as $c) {
                        if (!isset($h->content[$c])) {
                            $g[] = "$pid.$k:$c";
                        }
                    }
                }
            }
        }
        return $g;
    }

    function cardIds(array $block): array
    {
        $ids = [];
        foreach ($block["data"]["cards"] ?? [] as $card) {
            $ids[] = $card["id"];
            foreach ($card["layout"]["children"] ?? [] as $col) {
                $ids[] = $col["id"];
                foreach ($col["children"] ?? [] as $el) {
                    $ids[] = $el["id"];
                }
            }
        }
        return $ids;
    }

    function zoneOf(Harness $h, string $id, string $zone): array
    {
        return $h->content[$id]["data"][$zone] ?? [];
    }

    $pass = 0;
    $fail = 0;
    function check(string $name, bool $ok): void
    {
        global $pass, $fail;
        $ok ? $pass++ : $fail++;
        echo ($ok ? "  PASS  " : "  FAIL  ") . $name . "\n";
    }
    function section(string $t): void
    {
        echo "\n" . $t . "\n";
    }

    /** Step Group berisi 3 paragraf. */
    function stepGroupWithKids(): array
    {
        $h = new Harness();
        $h->addBlock("step-group");
        $s = $h->blockOrder[0];
        for ($i = 0; $i < 3; $i++) {
            $h->addChildBlock($s, "children", "paragraph");
        }
        $kids = zoneOf($h, $s, "children");
        foreach ($kids as $i => $k) {
            $h->content[$k]["data"]["text"]["id"] = "isi-$i";
        }
        return [$h, $s, $kids];
    }

    // =====================================================================
    section("1. Duplikat blok container (Step Group dengan 3 anak)");
    [$h, $s, $kids] = stepGroupWithKids();
    $h->duplicateBlock($s);
    $copy = $h->blockOrder[1] ?? null;
    $copyKids = $copy ? zoneOf($h, $copy, "children") : [];
    check("salinan berada tepat di bawah asli", $copy !== null && count($h->blockOrder) === 2);
    check("ID anak salinan TIDAK sama dengan ID anak asli", $copy !== null && count($copyKids) === 3 && !array_intersect($kids, $copyKids));
    if ($copyKids) {
        $h->content[$copyKids[0]]["data"]["text"]["id"] = "DIUBAH";
    }
    check("mengubah anak salinan tidak mengubah anak asli", ($h->content[$kids[0]]["data"]["text"]["id"] ?? "") === "isi-0");
    check("tidak ada blok yatim", orphans($h) === []);
    check("tidak ada ID hantu", ghosts($h) === []);

    // =====================================================================
    section("2. Duplikat container bersarang dengan Card Builder (ID kartu/kolom/elemen)");
    $h = new Harness();
    $h->addBlock("step-group");
    $s = $h->blockOrder[0];
    $h->addChildBlock($s, "children", "multi-columns");
    $m = zoneOf($h, $s, "children")[0];
    $h->addChildBlock($m, "col_1_zone", "card-builder");
    $c = zoneOf($h, $m, "col_1_zone")[0];
    $h->addCardItem($c, "icon-text");
    $colId = $h->content[$c]["data"]["cards"][0]["layout"]["children"][1]["id"];
    $h->addElementToColumn($c, 0, $colId, "text");
    $before = cardIds($h->content[$c]);
    $h->duplicateBlock($s);
    $copy = $h->blockOrder[1];
    $mCopy = zoneOf($h, $copy, "children")[0];
    $cCopy = zoneOf($h, $mCopy, "col_1_zone")[0] ?? null;
    check("cucu (card-builder) ikut digandakan", $cCopy !== null && $cCopy !== $c);
    check("ID kartu, kolom, dan elemen di salinan semuanya baru", $cCopy !== null && count($before) === 4 && !array_intersect($before, cardIds($h->content[$cCopy])));
    check("tidak ada blok yatim / hantu", orphans($h) === [] && ghosts($h) === []);

    // =====================================================================
    section("3. Hapus container root membersihkan seluruh turunan");
    [$h, $s, $kids] = stepGroupWithKids();
    $h->removeBlock($s);
    check("blok root hilang dari urutan", $h->blockOrder === []);
    check("anak-anaknya ikut terhapus (tidak jadi yatim)", $h->content === []);

    // =====================================================================
    section("4. Hapus anak lewat removeBlock (cara tombol hapus di wrapper)");
    $h = new Harness();
    $h->addBlock("multi-columns");
    $m = $h->blockOrder[0];
    $h->addChildBlock($m, "col_1_zone", "paragraph");
    $h->addChildBlock($m, "col_2_zone", "paragraph");
    $p2 = zoneOf($h, $m, "col_2_zone")[0];
    $h->removeBlock($p2);
    check("ID anak keluar dari col_2_zone (tidak jadi hantu)", zoneOf($h, $m, "col_2_zone") === []);
    check("anak di kolom lain tetap ada", count(zoneOf($h, $m, "col_1_zone")) === 1);
    check("tidak ada ID hantu", ghosts($h) === []);

    section("4b. removeNestedBlock pada anak yang punya anak");
    [$h, $s, $kids] = stepGroupWithKids();
    $h->addChildBlock($s, "children", "multi-columns");
    $mc = zoneOf($h, $s, "children")[3];
    $h->addChildBlock($mc, "col_1_zone", "paragraph");
    $h->removeNestedBlock($s, "children", $mc);
    check("cucu ikut terhapus, tidak ada yatim", orphans($h) === []);
    check("3 anak lain di Step Group tidak terpengaruh", count(zoneOf($h, $s, "children")) === 3);

    // =====================================================================
    section("5. duplicateNestedBlock pada anak container");
    [$h, $s, $kids] = stepGroupWithKids();
    $h->addChildBlock($s, "children", "multi-columns");
    $mc = zoneOf($h, $s, "children")[3];
    $h->addChildBlock($mc, "col_1_zone", "paragraph");
    $grand = zoneOf($h, $mc, "col_1_zone")[0];
    $h->duplicateNestedBlock($s, "children", $mc);
    $order = zoneOf($h, $s, "children");
    check("salinan disisipkan tepat setelah asli", count($order) === 5 && $order[3] === $mc);
    $mcCopy = $order[4] ?? null;
    check("cucu salinan punya ID baru", $mcCopy !== null && zoneOf($h, $mcCopy, "col_1_zone") !== [] && !in_array($grand, zoneOf($h, $mcCopy, "col_1_zone"), true));
    check("tidak ada blok yatim / hantu", orphans($h) === [] && ghosts($h) === []);

    // =====================================================================
    section("6. normalizeBlocksData tidak menghidupkan lagi item yang dihapus");
    $h = new Harness();
    $in = [
        "a" => ["type" => "stats-group", "data" => ["align" => "left", "stats" => [["value" => ["id" => "9"], "label" => ["id" => "x"]], ["value" => ["id" => "8"], "label" => ["id" => "y"]]]]],
        "b" => ["type" => "button-group", "data" => ["align" => "left", "buttons" => []]],
        "c" => ["type" => "heading", "data" => ["text" => ["id" => "Halo"]]],
    ];
    $out = $h->normalizeBlocksData($in);
    check("stats-group dengan 2 item tetap 2 (bukan 3)", count($out["a"]["data"]["stats"]) === 2);
    check("button-group yang sengaja dikosongkan tetap kosong", $out["b"]["data"]["buttons"] === []);
    check("kunci yang hilang tetap ditambal (margin_bottom, level)", ($out["c"]["data"]["margin_bottom"] ?? null) === "mb-4 md:mb-6" && ($out["c"]["data"]["level"] ?? null) === "h2");
    check("teks pengguna tidak tertimpa, bahasa yang kurang ditambah", $out["c"]["data"]["text"]["id"] === "Halo" && ($out["c"]["data"]["text"]["en"] ?? null) === "");
    check("kunci id ditambahkan bila belum ada", ($out["c"]["id"] ?? null) === "c");
    check("idempoten (normalisasi dua kali = sekali)", $h->normalizeBlocksData($out) === $out);

    // =====================================================================
    section("7. Ketahanan: data siklik dan ID hantu");
    $h = new Harness();
    $h->content = [
        "A" => ["id" => "A", "type" => "step-group", "data" => ["children" => ["B"]]],
        "B" => ["id" => "B", "type" => "step-group", "data" => ["children" => ["A"]]],
    ];
    $h->blockOrder = ["A"];
    $h->duplicateBlock("A");
    check("duplikat data siklik selesai (tidak loop tanpa akhir)", true);
    $h->removeBlock("A");
    check("hapus data siklik selesai dan bersih", !isset($h->content["A"]) && !isset($h->content["B"]));

    [$h, $s, $kids] = stepGroupWithKids();
    $h->content[$s]["data"]["children"][] = "blk_tidak_ada";
    $h->duplicateBlock($s);
    $copy = $h->blockOrder[1];
    check("ID hantu di zona tidak ikut terbawa ke salinan", count(zoneOf($h, $copy, "children")) === 3);

    // =====================================================================
    section("8. duplicateBlock dipanggil untuk blok yang bukan root");
    [$h, $s, $kids] = stepGroupWithKids();
    $rootBefore = $h->blockOrder;
    $h->duplicateBlock($kids[1]);
    check("urutan root tidak tercemar ID anak", $h->blockOrder === $rootBefore);
    check("salinan masuk ke zona induknya, tepat setelah asli", count(zoneOf($h, $s, "children")) === 4 && zoneOf($h, $s, "children")[0] === $kids[0] && zoneOf($h, $s, "children")[1] === $kids[1]);
    check("tidak ada blok yatim", orphans($h) === []);

    echo "\n==> $pass lulus, $fail gagal\n";
    exit($fail ? 1 : 0);
}
