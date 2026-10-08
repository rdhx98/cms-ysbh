<?php
/**
 * Audit konsistensi: registri (app/Editor) <-> data bawaan trait HasContentBlocks <-> config/cms.php
 *
 *   php tests/registry-audit.php path/ke/HasContentBlocks.php path/ke/config/cms.php [path/ke/app/Editor]
 *
 * Hanya butuh PHP CLI. Exit 1 bila ada KESALAHAN (nilai bawaan di luar pilihan kontrol).
 */

namespace Illuminate\Support {
    class Str
    {
        public static function random(int $length = 16): string
        {
            $c = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $s = "";
            for ($i = 0; $i < $length; $i++) { $s .= $c[random_int(0, strlen($c) - 1)]; }
            return $s;
        }
    }
}

namespace {
    [$_, $traitFile, $cmsFile] = $argv + [null, null, null];
    $editorDir = $argv[3] ?? __DIR__ . '/../app/Editor';
    if (!$traitFile || !$cmsFile) { fwrite(STDERR, "Pemakaian: php registry-audit.php <HasContentBlocks.php> <config/cms.php> [app/Editor]\n"); exit(2); }

    $GLOBALS['__cfg'] = ['cms' => require $cmsFile];
    function config(string $key, $default = null)
    {
        $v = $GLOBALS['__cfg'];
        foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; }
        return $v;
    }

    require $traitFile;
    foreach (['Options', 'Field', 'BlockType', 'BlockRegistry'] as $f) require_once "$editorDir/$f.php";

    use App\Editor\BlockRegistry;
    use App\Editor\Field;

    class Harness
    {
        use \App\Livewire\Traits\HasContentBlocks;
        public array $content = [];
        public array $blockOrder = [];
        public array $activeLocales = ['id', 'en'];
        public function dispatch(string $e, ...$a): void {}
        public function defaults(string $t) { return $this->getDefaultDataForType($t); }
    }

    $errors = [];
    $infos = [];
    $err = function (string $where, string $msg) use (&$errors) { $errors[] = "[$where] $msg"; };
    $info = function (string $where, string $msg) use (&$infos) { $infos[] = "[$where] $msg"; };
    $show = fn ($v) => is_scalar($v) || $v === null ? var_export($v, true) : json_encode($v);

    // ------------------------------------------------------------------ A. registri konsisten dengan dirinya sendiri
    $registry = BlockRegistry::all();
    foreach ($registry as $panel => $def) {
        $keys = [];
        foreach ($def->fields as $f) {
            if (in_array($f->key, $keys, true)) $err($panel, "kunci ganda: {$f->key}");
            $keys[] = $f->key;
            $allowed = $f->allowedValues();
            if ($allowed !== null) {
                if (!$allowed) $err($panel, "{$f->key}: daftar opsi kosong");
                if ($f->default !== null && !in_array($f->default, $allowed, true)) {
                    $err($panel, "{$f->key}: default {$show($f->default)} tidak ada di opsi");
                }
                if (count($allowed) !== count(array_unique($allowed))) $err($panel, "{$f->key}: nilai opsi ganda");
            }
        }
        foreach ($def->fields as $f) {
            if ($f->when && !in_array($f->when[0], $keys, true)) $err($panel, "{$f->key}: kondisi merujuk ke kunci yang tidak ada ({$f->when[0]})");
        }
    }

    // ------------------------------------------------------------------ B. registri <-> data bawaan trait
    $get = function (array $data, string $path) {
        foreach (explode('.', $path) as $k) { if (!is_array($data) || !array_key_exists($k, $data)) return [false, null]; $data = $data[$k]; }
        return [true, $data];
    };
    $covered = function (array $defs, array $actual, string $prefix = '') use (&$covered): array {
        // daftar kunci daun pada data nyata (objek bahasa {id,en} dihitung satu daun)
        $out = [];
        foreach ($actual as $k => $v) {
            $p = $prefix === '' ? (string) $k : "$prefix.$k";
            if (is_array($v) && !array_is_list($v) && array_diff(array_keys($v), ['id', 'en', 'fr', 'ar']) !== []) $out = array_merge($out, $covered($defs, $v, $p));
            else $out[] = $p;
        }
        return $out;
    };

    $compare = function (string $panel, $def, array $data) use ($err, $info, $get, $show, $covered) {
        $fieldKeys = array_map(fn (Field $f) => $f->key, $def->fields);
        foreach ($def->fields as $f) {
            [$has, $val] = $get($data, $f->key);
            $allowed = $f->allowedValues();
            if ($allowed !== null && $has && !in_array($val, $allowed, true)) {
                $sample = implode(', ', array_slice($allowed, 0, 4)) . (count($allowed) > 4 ? ', …' : '');
                $err($panel, "{$f->key}: trait menulis {$show($val)}, tidak ada di opsi kontrol ({$sample})");
            }
            if (!$has && $f->type !== 'media') $info($panel, "{$f->key}: kontrol ada, tetapi trait tidak menulis nilai bawaannya");
        }
        foreach ($covered([], $data['data'] ?? [], 'data') as $leaf) {
            // kunci zona kontainer (children, col_N_zone) adalah struktur pohon, bukan properti yang diedit lewat kontrol
            $last = substr($leaf, (int) strrpos($leaf, '.') + (str_contains($leaf, '.') ? 1 : 0));
            if ($last === 'children' || str_ends_with($last, '_zone')) continue;
            $ok = false;
            foreach ($fieldKeys as $fk) { if ($leaf === $fk || str_starts_with($leaf, $fk . '.')) { $ok = true; break; } }
            if (!$ok) $info($panel, "{$leaf}: trait menulis kunci ini, tetapi tidak ada kontrolnya");
        }
    };

    // blok: heading, paragraph, section-divider (kedua ejaan)
    foreach (['heading', 'paragraph', 'section-divider', 'section_divider', 'multi-columns', 'step-group'] as $t) {
        $h = new Harness();
        $h->addBlock($t);
        $id = $h->blockOrder[0] ?? null;
        $data = $id ? $h->content[$id] : ['data' => []];
        $def = BlockRegistry::block($t);
        if (!$def) { $err("block:$t", 'tidak ada di registri'); continue; }
        if (($data['data'] ?? []) === []) { $err("block:$t", "addBlock('$t') menghasilkan data kosong (tidak ada nilai bawaan)"); continue; }
        $compare("block:$t", $def, $data);
    }

    // elemen: dibuat lewat addElementToColumn() persis seperti tombol "+ Elemen" di editor
    foreach (['text', 'icon', 'initials', 'profile_photo', 'accordion'] as $t) {
        $h = new Harness();
        $h->addBlock('card-builder');
        $b = $h->blockOrder[0];
        $h->addCardItem($b, 'stack');
        $col = $h->content[$b]['data']['cards'][0]['layout']['children'][0]['id'];
        $h->addElementToColumn($b, 0, $col, $t);
        $el = $h->content[$b]['data']['cards'][0]['layout']['children'][0]['children'][0] ?? null;
        $def = BlockRegistry::element($t);
        if (!$def) { $err("element:$t", 'tidak ada di registri'); continue; }
        if (!$el || ($el['data'] ?? []) === []) { $err("element:$t", "addElementToColumn('$t') menghasilkan data kosong"); continue; }
        $compare("element:$t", $def, $el);
    }

    // ------------------------------------------------------------------ laporan
    echo "KESALAHAN (" . count($errors) . ") — nilai yang ditulis trait tidak bisa dipilih/ditampilkan oleh kontrol:\n";
    foreach ($errors as $e) echo "  ✗ $e\n";
    echo "\nCATATAN (" . count($infos) . ") — kunci tak berpasangan (belum tentu bug):\n";
    foreach ($infos as $i) echo "  · $i\n";
    echo "\n==> " . count($registry) . " tipe di registri, " . count($errors) . " kesalahan, " . count($infos) . " catatan\n";
    exit($errors ? 1 : 0);
}
