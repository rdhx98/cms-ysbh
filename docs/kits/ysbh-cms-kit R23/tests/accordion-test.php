<?php
/**
 * Pemeriksaan blok Akordion/FAQ dan sistem modul blok (logika murni):  php tests/accordion-test.php [akar-kit]
 */
$root = $argv[1] ?? dirname(__DIR__);
foreach (['Content/LocaleMap', 'Content/Links/LinkResolver', 'Content/Blocks/ButtonStyle', 'Content/Blocks/RichText', 'Content/Blocks/CoreBlocks', 'Content/Blocks/BlockSanitizer', 'Content/Blocks/FaqText', 'Content/Blocks/AccordionStyle',
          'Editor/Options', 'Editor/Field', 'Editor/BlockType', 'Editor/Defaults', 'Editor/BlockRegistry', 'Editor/BlockPalette', 'Editor/Modules'] as $f) {
    require_once "$root/app/$f.php";
}
$GLOBALS['__cfg'] = ['cms' => ['design' => ['margin_bottom' => [], 'bg_colors' => [], 'text_colors' => [], 'mini_bg_colors' => [], 'avatar_border_colors' => [], 'text_transform' => []], 'fonts' => []]];
if (!function_exists('config')) {
    function config(string $key, $default = null) { $v = $GLOBALS['__cfg']; foreach (explode('.', $key) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) return $default; $v = $v[$k]; } return $v; }
}

use App\Content\Blocks\{AccordionStyle, BlockSanitizer as S, FaqText as F};
use App\Editor\{BlockPalette, BlockRegistry, Defaults, Modules};
use App\Editor\Blocks\AccordionBlock as A;

App\Editor\Modules::all(); // memuat modul (tanpa autoload di skrip mandiri ini)

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function section(string $t): void { echo "\n$t\n"; }

section('FaqText: struktur');
check('kosong -> kosong', F::toHtml('') === '' && F::toHtml("  \n\n ") === '');
check('satu baris -> satu paragraf', F::toHtml('Halo dunia') === '<p>Halo dunia</p>');
check('baris kosong = paragraf baru; baris baru = <br>', F::toHtml("Satu\nDua\n\nTiga") === '<p>Satu<br>Dua</p><p>Tiga</p>');
check('CRLF dinormalkan', F::toHtml("A\r\nB\r\n\r\nC") === '<p>A<br>B</p><p>C</p>');
check('daftar poin: "- ", "* ", "•"', F::toHtml("- a\n* b\n• c") === '<ul><li>a</li><li>b</li><li>c</li></ul>');
check('paragraf + daftar + paragraf dalam satu blok, urutan terjaga', F::toHtml("Gejala:\n- demam\n- batuk\nSegera ke puskesmas") === '<p>Gejala:</p><ul><li>demam</li><li>batuk</li></ul><p>Segera ke puskesmas</p>');
check('"-5 derajat" (tanpa spasi) bukan poin', F::toHtml('-5 derajat') === '<p>-5 derajat</p>');

section('FaqText: keamanan (escape dulu, baru struktur)');
check('tag dari penulis tampil sebagai TEKS', F::toHtml('<script>alert(1)</script>') === '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>' && str_contains(F::toHtml('<img src=x onerror=alert(1)>'), '&lt;img'));
check('teks medis "usia <5 tahun & >2 dosis" utuh dan aman', F::toHtml('usia <5 tahun & >2 dosis') === '<p>usia &lt;5 tahun &amp; &gt;2 dosis</p>');
check('tanda kutip di-escape', F::toHtml('Dia berkata "ya" dan \'tidak\'') === '<p>Dia berkata &quot;ya&quot; dan &#039;tidak&#039;</p>');

section('FaqText: tautan otomatis');
$a = fn (string $in) => F::toHtml($in);
check('https:// menjadi tautan baru-tab dengan rel noopener noreferrer', $a('Lihat https://ysbh.org/donasi') === '<p>Lihat <a href="https://ysbh.org/donasi" target="_blank" rel="noopener noreferrer">https://ysbh.org/donasi</a></p>');
check('titik/koma/kurung penutup di ujung kalimat TIDAK ikut tautan', str_contains($a('Buka https://ysbh.org/a.'), '">https://ysbh.org/a</a>.') && str_contains($a('(https://ysbh.org/a)'), '</a>)') && str_contains($a('cek https://ysbh.org/a, lalu'), '</a>, lalu'));
check('& di URL menjadi &amp; di href dan tidak terpotong', str_contains($a('https://x.org/?a=1&b=2'), 'href="https://x.org/?a=1&amp;b=2"'));
check('HTTPS huruf besar juga dikenali', str_contains($a('HTTPS://X.ORG/A'), '<a href="HTTPS://X.ORG/A"'));
foreach (['javascript:alert(1)', 'data:text/html,x', 'ftp://x.org/f', 'https://', 'https:// kosong', 'mailto:a@b.co', '//evil.com'] as $bad) {
    check('BUKAN tautan: ' . json_encode($bad), !str_contains($a($bad), '<a '), $a($bad));
}
$q = $a('"https://a.com" dan \'https://b.com\'');
check('URL dalam tanda kutip: kutipnya tidak masuk ke href', str_contains($q, 'href="https://a.com"') && str_contains($q, 'href="https://b.com"'), $q);
$inj = $a('https://a.com/" onclick="alert(1)');
check('percobaan menyisipkan atribut lewat tanda kutip tidak berhasil', !preg_match('/<a [^>]*onclick/i', $inj) && str_contains($inj, 'href="https://a.com/"'), $inj);

section('FaqText: uji sifat, 1500 teks acak berisi karakter berbahaya');
mt_srand(20261007);
$pool = ['<', '>', '"', "'", '&', '&amp;', '<script>', '</p>', 'javascript:', 'https://x.org', 'http://a.b/c?d=1&e=2', 'https://', "\n", "\n\n", '- ', '* ', '• ', ' ', 'onerror=', '<img src=x>', '&lt;', '`', '\\', 'é', '日本', "\t", '(', ')', '.', ','];
// Pemeriksa daftar putih: setelah semua tag SAH dibuang, sisa (teks) tidak boleh memuat < > " ' mentah.
// Tag sah hanya <p> <br> <ul> <li> </...> dan <a href="http(s)://…" target="_blank" rel="noopener noreferrer">; tidak ada atribut lain.
$allowed = '~</?(?:p|ul|li|a)>|<br>|<a href="https?://[^"<>\s]+" target="_blank" rel="noopener noreferrer">~i';
$strict = fn (string $html): bool => !preg_match('/[<>"\']/', preg_replace($allowed, '', $html));
$bad = 0; $first = null; $bad2 = 0;
for ($n = 0; $n < 1500; $n++) {
    $str = ''; for ($i = 0, $k = mt_rand(1, 14); $i < $k; $i++) $str .= $pool[array_rand($pool)];
    $html = F::toHtml($str);
    if ($html === '') continue;
    if (!$strict($html)) { $bad++; $first ??= $str; }
    if (class_exists('DOMDocument')) {   // tambahan bila ext-dom tersedia: urai sebagai DOM sungguhan
        $dom = new DOMDocument(); libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        foreach ($dom->getElementsByTagName('*') as $el) {
            $tag = strtolower($el->nodeName);
            if (in_array($tag, ['html', 'body'], true)) continue;
            $ok = in_array($tag, ['p', 'br', 'ul', 'li', 'a'], true);
            foreach ($el->attributes as $attr) $ok = $ok && $tag === 'a' && in_array($attr->name, ['href', 'target', 'rel'], true) && ($attr->name !== 'href' || preg_match('~^https?://~i', $attr->value));
            if (!$ok) { $bad2++; break; }
        }
    }
}
check('hanya tag daftar putih dan teks ter-escape (tidak ada < > " \' mentah di luar tag sah)', $bad === 0, "$bad pelanggaran; contoh: " . json_encode($first));
check(class_exists('DOMDocument') ? 'diurai sebagai DOM: hanya p/br/ul/li/a, atribut a hanya href/target/rel, href selalu http(s)://' : 'DOM tidak tersedia di PHP ini: pemeriksa daftar putih di atas yang berlaku', $bad2 === 0);
$mut = '<p>x</p><img src=x onerror=alert(1)><a href="javascript:1">y</a>';
check('(pemeriksa itu sendiri peka) HTML berbahaya DITOLAK olehnya', !$strict($mut) && !$strict('<p onclick="x">a</p>') && !$strict('<p>a < b</p>') && $strict('<p>a &lt; b</p>'));

section('AccordionStyle');
$all = []; foreach (AccordionStyle::STYLES as $st) foreach (AccordionStyle::COLORS as $c) $all[] = AccordionStyle::item($st, $c) . '|' . AccordionStyle::summary($st, $c) . '|' . AccordionStyle::marker($c);
check('2 gaya x 4 warna = 8 kombinasi berbeda, tanpa sisa pola template', count(array_unique($all)) === 8 && !array_filter($all, fn ($x) => str_contains($x, '{') || str_contains($x, '$')));
check('nilai tak sah jatuh ke bawaan; tidak ada teks sisipan', AccordionStyle::item('x" onclick="1', ['a']) === AccordionStyle::item('boxed', 'foresty') && !str_contains(AccordionStyle::summary('zz', 'red"><b>'), '<b>'));
check('judul terbuka warna emas memakai charcoal (kontras)', str_contains(AccordionStyle::summary('boxed', 'aurum'), 'group-open:text-charcoal'));

section('AccordionBlock::sanitize');
$good = ['style' => 'lines', 'color' => 'coral', 'marker' => 'plus', 'first_open' => true, 'allow_multiple' => true, 'schema' => true,
         'items' => [['id' => 'itm_a1b2c3d4', 'question' => ['id' => 'Apa itu malaria?', 'en' => 'What is malaria?'], 'answer' => ['id' => "Penyakit...\n\n- demam\n- menggigil", 'en' => 'A disease']]]];
check('data sah TIDAK berubah; idempoten', A::sanitize($good, ['id', 'en']) === $good && A::sanitize(A::sanitize($good, ['id', 'en']), ['id', 'en']) === $good, json_encode(A::sanitize($good, ['id', 'en'])));
$evil = A::sanitize(['style' => 'neon" onload="1', 'color' => ['x'], 'marker' => 'z', 'first_open' => 'ya', 'items' => [
    ['id' => 'a b"c', 'question' => ["id" => "  Apa\n\tini?  ", 'en' => str_repeat('x', 400), 'fr' => 'bocor'], 'answer' => ['id' => "usia <5 tahun\r\n\r\n\r\n\r\nSatu\x00\x07 dua", 'en' => str_repeat('y', 6000)]],
    'bukan-larik', 5, null]], ['id', 'en']);
check('gaya/warna/penanda tak sah -> bawaan; bendera menjadi boolean; kunci tak dikenal tidak ada', $evil['style'] === 'boxed' && $evil['color'] === 'foresty' && $evil['marker'] === 'chevron' && $evil['first_open'] === true && $evil['allow_multiple'] === false && $evil['schema'] === false && array_keys($evil) === ['style', 'color', 'marker', 'first_open', 'allow_multiple', 'schema', 'items']);
$it = $evil['items'][0];
check('entri bukan larik dibuang; ID tak sah diganti', count($evil['items']) === 1 && preg_match('/^itm_[a-f0-9]{8}$/', $it['id']) === 1);
check('pertanyaan: satu baris (spasi/tab/baris baru diringkas), dipotong 300, bahasa di luar daftar dibuang', $it['question'] === ['id' => 'Apa ini?', 'en' => str_repeat('x', 300)], json_encode($it['question']));
check('jawaban: baris baru dipertahankan (maks. dua), karakter kontrol dibuang, dipotong 5000, "<5" TIDAK terpotong', $it['answer']['id'] === "usia <5 tahun\n\nSatu dua" && mb_strlen($it['answer']['en']) === 5000, json_encode($it['answer']['id']));
check('maksimal 30 pertanyaan', count(A::sanitize(['items' => array_fill(0, 50, ['question' => ['id' => 'x']])], ['id', 'en'])['items']) === 30);
check('tanpa data sama sekali -> bawaan aman', A::sanitize([], ['id', 'en']) === ['style' => 'boxed', 'color' => 'foresty', 'marker' => 'chevron', 'first_open' => false, 'allow_multiple' => false, 'schema' => false, 'items' => []]);
check('baris baru di ujung ID tidak lolos', preg_match('/^itm_[a-f0-9]{8}$/', A::sanitize(['items' => [['id' => "itm_1\n"]]], ['id'])['items'][0]['id']) === 1);

section('Sistem modul: penemuan dan penyambungan');
check('Modules menemukan AccordionBlock berdasarkan tipenya', (Modules::all()['accordion-builder'] ?? null) === A::class && Modules::for('accordion_builder') === A::class && Modules::for('heading') === null);
$def = BlockRegistry::block('accordion-builder');
check("registri memuat 'block:accordion-builder' dan tipe bawaan tidak hilang", $def !== null && $def->panelKey() === 'block:accordion-builder' && BlockRegistry::block('heading') && BlockRegistry::block('button-builder') && BlockRegistry::block('multi-columns'));
check('palet: ada, berlabel, berikon, di grup Konten, tingkat atas', BlockPalette::has('accordion-builder') && BlockPalette::label('accordion-builder') === 'Akordion / FAQ' && BlockPalette::icon('accordion-builder') === 'message-circle-question' && BlockPalette::allowedAtRoot('accordion-builder') && in_array(['type' => 'accordion-builder', 'label' => 'Akordion / FAQ', 'icon' => 'message-circle-question', 'group' => 'Konten'], BlockPalette::rootTypes(), true));
check('boleh di dalam kolom; TIDAK di dalam step-group (hanya card-builder)', BlockPalette::allowsChild('multi-columns', 'accordion-builder') && !BlockPalette::allowsChild('step-group', 'accordion-builder') && array_column(BlockPalette::childTypes('step-group'), 'type') === ['card-builder']);
check('urutan tipe bawaan di menu TIDAK berubah; modul ditambahkan di akhir', array_slice(array_column(BlockPalette::rootTypes(), 'type'), 0, 9) === ['heading', 'paragraph', 'eyebrow', 'image', 'button-builder', 'card-builder', 'step-group', 'multi-columns', 'section-divider'] && array_column(BlockPalette::rootTypes(), 'type')[9] === 'accordion-builder');
check('ikon modul ikut daftar ikon sprite', in_array('message-circle-question', BlockPalette::icons(), true));
$blocks = ['a' => ['id' => 'a', 'type' => 'accordion_builder', 'data' => ['style' => 'x', 'items' => [['question' => ['id' => 'Q']]]]], 'h' => ['id' => 'h', 'type' => 'heading', 'data' => ['text' => ['id' => '<b>x</b>']]], 'z' => ['id' => 'z', 'type' => 'tipe-baru', 'data' => ['bebas' => 1]]];
$cl = S::clean($blocks, ['id', 'en']);
check("BlockSanitizer::clean membersihkan blok modul (ejaan 'accordion_builder' juga), blok lain TIDAK disentuh", $cl['a']['data']['style'] === 'boxed' && $cl['h'] === $blocks['h'] && $cl['z'] === $blocks['z']);
check('forType: jenis tak dikenal dikembalikan apa adanya', S::forType('tidak-ada', ['x' => 1]) === ['x' => 1]);

section('Registri <-> bawaan <-> pembersih (tidak boleh saling bertentangan)');
$mat = Defaults::materialize($def->defaults, ['id', 'en']);
check('bawaan: satu pertanyaan kosong per bahasa dengan ID baru (tanpa isi palsu)', count($mat['items']) === 1 && preg_match('/^itm_[a-f0-9]{8}$/', $mat['items'][0]['id']) && $mat['items'][0]['question'] === ['id' => '', 'en' => ''] && $mat['items'][0]['answer'] === ['id' => '', 'en' => '']);
check('nilai bawaan lolos pembersih TANPA berubah', A::sanitize($mat, ['id', 'en']) === $mat, json_encode(A::sanitize($mat, ['id', 'en'])));
$rep = array_values(array_filter($def->fields, fn ($f) => $f->type === 'repeater'))[0];
$itemTop = array_values(array_unique(array_map(fn ($f) => explode('.', $f->key)[0], $rep->extra['fields']))); sort($itemTop);
$sanTop = array_values(array_diff(array_keys($mat['items'][0]), ['id'])); sort($sanTop);
check('bidang item di inspektur = bidang item yang dikenal pembersih', $itemTop === $sanTop, json_encode([$itemTop, $sanTop]));
$byKey = fn ($k) => array_values(array_filter($def->fields, fn ($f) => $f->key === $k))[0];
check('pilihan gaya/warna/penanda di inspektur = daftar AccordionStyle', $byKey('data.style')->allowedValues() === AccordionStyle::STYLES && $byKey('data.color')->allowedValues() === AccordionStyle::COLORS && $byKey('data.marker')->allowedValues() === AccordionStyle::MARKERS);
check('bendera di inspektur = bendera yang dikenal pembersih', array_values(array_filter(array_map(fn ($f) => str_starts_with($f->key, 'data.') ? substr($f->key, 5) : null, $def->fields))) === ['items', 'style', 'color', 'marker', 'first_open', 'allow_multiple', 'schema'] && array_keys($mat) === ['style', 'color', 'marker', 'first_open', 'allow_multiple', 'schema', 'items'] || count(array_diff(array_keys($mat), ['style', 'color', 'marker', 'first_open', 'allow_multiple', 'schema', 'items'])) === 0);
check('nilai bawaan setiap kontrol berpilihan ada di pilihannya', !array_filter($def->fields, fn ($f) => $f->allowedValues() !== null && $f->default !== null && !in_array($f->default, $f->allowedValues(), true)));
check('jawaban memakai kolom banyak-baris; pertanyaan satu baris', ($rep->extra['fields'][1]->extra['multi'] ?? false) === true && ($rep->extra['fields'][0]->extra['multi'] ?? true) === false);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
