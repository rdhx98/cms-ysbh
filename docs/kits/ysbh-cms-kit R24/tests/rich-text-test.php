<?php
/**
 * Penyaring teks kaya (App\Content\Blocks\RichText):  php tests/rich-text-test.php [akar-kit]
 * Murni (tanpa lab). Keluaran DIAUDIT oleh pemeriksa yang ditulis terpisah di berkas ini (daftar putihnya sendiri), bukan oleh kode yang diuji.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/app/Content/Blocks/RichText.php";

use App\Content\Blocks\RichText as R;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

$internal = fn (string $kind, string $slug): ?string => $kind === 'article' ? "/artikel/$slug" : "/$slug";

/** Pemeriksa keluaran INDEPENDEN. Mengembalikan daftar pelanggaran (kosong = aman). */
function audit(string $out): array
{
    $problems = [];
    $tags = ['p','br','hr','div','span','strong','b','em','i','u','s','strike','del','ins','mark','small','sub','sup','code','pre','blockquote','h1','h2','h3','h4','h5','h6','ul','ol','li','a','img','figure','figcaption','label','input','table','thead','tbody','tfoot','tr','th','td','caption','svg','g','path','circle','ellipse','line','polyline','polygon','rect'];
    $void = ['br', 'hr', 'img', 'input'];
    $re = '~<(/?)([a-z0-9]+)((?: [a-zA-Z-]+(?:="[^"<>]*")?)*)>~';
    if (preg_match_all($re, $out, $m, PREG_SET_ORDER) === false) { return ['regex gagal']; }
    $rest = preg_replace($re, '', $out) ?? '';
    if (preg_match('/[<>]/', $rest)) { $problems[] = 'sudut mentah di teks'; }
    if (preg_match('/&(?!(?:amp|lt|gt|quot|#039);)/', $rest)) { $problems[] = 'entitas tak dikenal di teks'; }
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $out)) { $problems[] = 'karakter kendali'; }
    if (!mb_check_encoding($out, 'UTF-8')) { $problems[] = 'UTF-8 tidak sah'; }
    $stack = [];
    $okAttr = ['class','style','title','dir','href','target','rel','src','alt','width','height','loading','type','checked','disabled','start','colspan','rowspan','viewBox','fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','d','cx','cy','r','rx','ry','x','y','x1','y1','x2','y2','points','transform','opacity','fill-rule','clip-rule','focusable','aria-hidden'];
    foreach ($m as $t) {
        [, $close, $name, $attrs] = $t;
        if (!in_array($name, $tags, true)) { $problems[] = "tag tak diizinkan <$name>"; continue; }
        if ($close === '/') {
            if (in_array($name, $void, true)) { $problems[] = "penutup untuk void </$name>"; }
            elseif (array_pop($stack) !== $name) { $problems[] = "penutup tak seimbang </$name>"; }
            continue;
        }
        if (!in_array($name, $void, true)) { $stack[] = $name; }
        preg_match_all('~ ([a-zA-Z-]+)(?:="([^"<>]*)")?~', $attrs, $am, PREG_SET_ORDER);
        $seen = [];
        foreach ($am as $a) {
            $an = $a[1]; $av = html_entity_decode($a[2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (isset($seen[$an])) { $problems[] = "atribut ganda $an"; }
            $seen[$an] = true;
            $isData = (bool) preg_match('/^data-[a-z0-9]+(?:-[a-z0-9]+)*$/', $an); $isAria = (bool) preg_match('/^aria-[a-z]+$/', $an);
            if (!$isData && !$isAria && !in_array($an, $okAttr, true)) { $problems[] = "atribut tak diizinkan $an"; continue; }
            if (preg_match('/^(on|x-|@|:|wire)/i', $an)) { $problems[] = "atribut pemicu $an"; }
            if (in_array($an, ['href', 'src'], true)) {
                $norm = preg_replace('/[\x00-\x20\x7f]+/', '', $av) ?? '';
                if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $norm, $sm) && !in_array(strtolower($sm[1]), ['http', 'https', 'mailto', 'tel'], true)) { $problems[] = "skema berbahaya di $an: $av"; }
                if (str_starts_with($norm, '//') || str_contains($av, '\\')) { $problems[] = "alamat menuju host lain di $an"; }
                if ($an === 'src' && preg_match('/^(mailto|tel):/i', $norm)) { $problems[] = 'src bukan http(s)'; }
            }
            if ($an === 'style' && preg_match('/url\s*\(|expression|javascript|@import|\\\\|<|>|\/\*|behavior|-moz-binding/i', $av)) { $problems[] = "style berbahaya: $av"; }
            if ($an === 'target' && !in_array($av, ['_blank', '_self'], true)) { $problems[] = "target aneh $av"; }
            if ($an === 'type' && $av !== 'checkbox') { $problems[] = "type input aneh $av"; }
        }
        if ($name === 'a' && ($seen['target'] ?? false) && preg_match('/target="_blank"/', $attrs) && !str_contains($attrs, 'noopener')) { $problems[] = '_blank tanpa noopener'; }
        if ($name === 'input' && !isset($seen['disabled'])) { $problems[] = 'input tidak dikunci'; }
    }
    if ($stack) { $problems[] = 'tag terbuka tersisa: ' . implode(',', $stack); }

    return $problems;
}

echo "\nDipertahankan (keluaran editor Anda)\n";
$keep = [
    'paragraf + tebal + miring + garis bawah + coret' => '<p>Halo <strong>dunia</strong> <em>yang</em> <u>indah</u> <s>lama</s></p>',
    'indentasi paragraf (ParagraphIndent)' => '<p style="text-indent: 2rem;">Alinea berindentasi</p>',
    'perataan teks (TextAlign)' => '<p style="text-align: center;">Tengah</p><p style="text-align: justify;">Rata</p>',
    'ukuran huruf (FontSize)' => '<p>A <span style="font-size: 18px;">besar</span> <span style="font-size: 1.25rem;">lagi</span></p>',
    'warna teks (Color) hex dan rgb' => '<span style="color: #064f3b;">hijau</span><span style="color: rgb(5, 150, 105);">rgb</span>',
    'keluarga huruf (FontFamily) dengan tanda kutip' => '<span style="font-family: &quot;Plus Jakarta Sans&quot;, sans-serif;">jakarta</span>',
    'bobot huruf (FontWeight)' => '<span style="font-weight: 600;">semi</span>',
    'pill (Pill) lengkap dengan kelas dan border' => '<span class="pill-wrapper inline-flex items-center font-medium rounded-full" style="background-color: #E9F1EB; border: 1.5px solid #FCA5A5; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0.3rem;">Tag</span>',
    'pill dengan border transparent' => '<span class="pill-wrapper" style="background-color: #E9F1EB; border: 1.5px solid transparent;">x</span>',
    'tabel (artikel lama) dengan colspan' => '<table><thead><tr><th colspan="2">Judul</th></tr></thead><tbody><tr><td>A</td><td rowspan="2">B</td></tr></tbody></table>',
    'daftar berbutir dan bernomor' => '<ul><li><p>Satu</p></li><li><p>Dua</p></li></ul><ol start="3"><li><p>Tiga</p></li></ol>',
    'daftar tugas (TaskList/TaskItem), kotak dikunci' => '<ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><input type="checkbox" checked disabled><span></span></label><div><p>Selesai</p></div></li></ul>',
    'kutipan, garis, pemisah baris' => '<blockquote><p>Kutip</p></blockquote><hr><p>a<br>b</p>',
    'blok kode' => '<pre><code class="language-js">const a = 1 &lt; 2;</code></pre>',
    'judul h1-h3' => '<h1>Satu</h1><h2>Dua</h2><h3>Tiga</h3>',
    'tautan https dengan target dan rel' => '<a href="https://ysbh.org/x?a=1&amp;b=2" target="_blank" rel="noopener noreferrer">link</a>',
    'tautan mailto, tel, anchor, jalur relatif' => '<a href="mailto:contact@ysbh.org">m</a><a href="tel:+6282382295759">t</a><a href="#kontak">a</a><a href="/about">r</a>',
    'gambar (artikel lama): src https dan jalur, alt, lebar' => '<img src="https://ysbh.org/storage/a.webp" alt="Foto" width="600" loading="lazy"><img src="/storage/b.jpg" alt="" loading="lazy">',
    'ikon SVG simpul Eyebrow (bentuk dasar)' => '<span data-type="eyebrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 3v2M12 19v2"></path></svg>Judul</span>',
    'entitas sah tetap teks (&amp; &lt; &quot;)' => '<p>A &amp; B &lt; C &quot;D&quot;</p>',
    'Unicode: emoji dengan ZWJ, aksara non-Latin, nbsp' => "<p>👩‍⚕️ 健康 Kesehatan\u{00A0}Ibu</p>",
    'atribut data-* dan aria-*' => '<div data-note="x" aria-label="Catatan">t</div>',
];
foreach ($keep as $label => $html) {
    $out = R::clean($html, $internal);
    check("utuh: $label", $out === $html, $out);
    check("  idempoten dan aman: $label", R::clean($out, $internal) === $out && audit($out) === [], json_encode(audit($out)));
}

echo "\nDiselesaikan dan dinormalkan\n";
check('tautan internal dari dialog TipTap: internal://page/{slug} dan internal://article/{slug} -> alamat publik', R::clean('<a href="internal://page/tentang-kami">a</a><a href="internal://article/imunisasi-dasar">b</a>', $internal) === '<a href="/tentang-kami">a</a><a href="/artikel/imunisasi-dasar">b</a>');
check('internal:// dengan slug tidak sah, tipe tak dikenal, atau tanpa pemecah: tautan dilepas, teks tetap', R::clean('<a href="internal://page/Bad Slug">1</a><a href="internal://video/x">2</a><a href="internal://page/../x">3</a>', $internal) === '123' && R::clean('<a href="internal://page/ok">z</a>') === 'z');
check('pemecah yang mengembalikan javascript: atau null -> tautan dilepas', R::clean('<a href="internal://page/x">a</a>', fn () => 'javascript:alert(1)') === 'a' && R::clean('<a href="internal://page/x">a</a>', fn () => null) === 'a');
check('target=_blank SELALU mendapat rel noopener noreferrer (walau rel diberikan lain)', R::clean('<a href="https://a.test" target="_blank" rel="nofollow">x</a>') === '<a href="https://a.test" target="_blank" rel="noopener noreferrer nofollow">x</a>' && R::clean('<a href="https://a.test" target="_blank">x</a>') === '<a href="https://a.test" target="_blank" rel="noopener noreferrer">x</a>');
check('target selain _blank/_self dibuang; rel hanya token yang dikenal', R::clean('<a href="https://a.test" target="_top" rel="opener evil nofollow">x</a>') === '<a href="https://a.test" rel="nofollow">x</a>');
check('gambar tanpa loading mendapat loading="lazy"; input selalu dikunci', R::clean('<img src="/a.jpg">') === '<img src="/a.jpg" loading="lazy">' && R::clean('<input type="checkbox">') === '<input type="checkbox" disabled>');
check('entitas numerik dan bernama diuraikan sekali lalu di-escape ulang (tanpa penggandaan "&amp;amp;")', R::clean('<p>&#38; &amp; &#x3C;b&#x3E;</p>') === '<p>&amp; &amp; &lt;b&gt;</p>', R::clean('<p>&#38; &amp; &#x3C;b&#x3E;</p>'));
check('"&lt;script&gt;" yang ditulis sebagai TEKS tetap teks (tidak menjadi tag)', R::clean('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>') === '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
check('karakter kendali dan NUL dibuang; tab dan baris baru dipertahankan', R::clean("<p>a\x00b\x07c\td\ne</p>") === "<p>abc\td\ne</p>");
check('UTF-8 rusak tidak membuat galat dan tidak lolos', ($o = R::clean("<p>a\xC3\x28b\xFF</p>")) !== '' && mb_check_encoding($o, 'UTF-8') && audit($o) === [], $o);
check('masukan bukan teks atau kosong -> ""', R::clean(null) === '' && R::clean(['x']) === '' && R::clean(5) === '' && R::clean('') === '');
check('style: properti di luar daftar dan nilai berbahaya dibuang; yang sah tetap, bentuk kanonik', R::clean('<span style="color: red; position: fixed; background: url(http://x/y); font-size: 12px; behavior: url(x); width: 100vw">t</span>') === '<span style="color: red; font-size: 12px;">t</span>');
check('style kosong sesudah disaring -> atribut dibuang', R::clean('<span style="position:fixed">t</span>') === '<span>t</span>');

echo "\nVektor serangan\n";
$attacks = [
    '<script>alert(1)</script>', '<SCRIPT SRC=//evil.test/x.js></SCRIPT>', '<script>alert(1)', '<scr<script>ipt>alert(1)</scr</script>ipt>',
    '<img src=x onerror=alert(1)>', '<img src="x" onerror="alert(1)">', '<IMG SRC=javascript:alert(1)>', '<img src="javascript:alert(1)">', '<img src="data:text/html;base64,PHNjcmlwdD4=">',
    '<a href="javascript:alert(1)">x</a>', '<a href=" JaVaScRiPt:alert(1)">x</a>', '<a href="java&#x09;script:alert(1)">x</a>', '<a href="java&#10;script:alert(1)">x</a>',
    '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;:alert(1)">x</a>', '<a href="jav&#x61;script:alert(1)">x</a>', '<a href="vbscript:msgbox(1)">x</a>',
    '<a href="data:text/html,<script>alert(1)</script>">x</a>', '<a href="//evil.test/x">x</a>', '<a href="/\\evil.test">x</a>', '<a href="\\\\evil.test">x</a>', '<a href="file:///etc/passwd">x</a>',
    '<a href="https://ok.test" onclick="alert(1)">x</a>', '<a href=https://ok.test onmouseover=alert(1)>x</a>', "<a href='https://ok.test' style='x'>x</a>",
    '<div onmouseover="alert(1)">x</div>', '<body onload=alert(1)>', '<svg onload=alert(1)>', '<svg><script>alert(1)</script></svg>', '<svg><foreignObject><iframe src=x></iframe></foreignObject></svg>',
    '<svg><use href="data:image/svg+xml,<svg id=x xmlns=http://www.w3.org/2000/svg><script>alert(1)</script></svg>#x"/></svg>', '<svg><animate onbegin=alert(1) attributeName=x dur=1s>', '<svg><set attributeName=onmouseover to=alert(1)>',
    '<math><mtext><script>alert(1)</script></mtext></math>', '<iframe src="javascript:alert(1)"></iframe>', '<iframe srcdoc="<script>alert(1)</script>"></iframe>', '<object data="javascript:alert(1)"></object>',
    '<embed src="javascript:alert(1)">', '<form action="javascript:alert(1)"><button>x</button></form>', '<button formaction="javascript:alert(1)">x</button>', '<input type="image" src=x onerror=alert(1)>',
    '<input type="text" value="x" onfocus=alert(1) autofocus>', '<textarea><script>alert(1)</script></textarea>', '<select><option onfocus=alert(1)>', '<style>*{background:url(javascript:alert(1))}</style>', '<link rel=stylesheet href=//evil.test/x.css>',
    '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">', '<base href="javascript:alert(1)//">', '<noscript><p title="</noscript><img src=x onerror=alert(1)>">', '<template><script>alert(1)</script></template>',
    '<p style="background:url(javascript:alert(1))">x</p>', '<p style="width:expression(alert(1))">x</p>', '<p style="color:red;background-image:url(//evil.test/t.gif)">x</p>', '<p style="font-family:\'a\\27 b\'">x</p>', '<p style="color:\\72 ed">x</p>',
    '<div x-data="{a:1}" x-init="fetch(\'//evil.test\')">x</div>', '<div x-html="document.cookie">x</div>', '<div @click="alert(1)">x</div>', '<div :class="alert(1)">x</div>', '<div wire:click="delete(1)">x</div>', '<div wire:init="x">x</div>',
    '<div x-on:click="alert(1)">x</div>', '<div x-bind:src="x">x</div>', '<span id="x" name="y">x</span>', '<div class="x" class="y" onclick="a">x</div>',
    '<!-- <script>alert(1)</script> -->x', '<![CDATA[<script>alert(1)</script>]]>', '<?php echo 1; ?>', '<!DOCTYPE html><html><head><title>x</title></head><body>b</body></html>',
    '<a href="https://ok.test/"onclick="alert(1)">x</a>', '<a href="https://ok.test/" onclick=alert(1)//>x</a>', '<img src="x"onerror=alert(1)>', '<<script>alert(1)//<</script>', '<a href="x" title=">"><script>alert(1)</script>">y</a>',
    '</p><script>alert(1)</script><p>', '</div></div></div><div style="position:fixed;top:0;left:0;width:100%;height:100%">x</div>', '<p>unterminated <a href="https://ok.test', '<p <script>alert(1)</script>>', '<a href="https://ok.test" =x onclick=alert(1)>',
    "<a href=\"https://ok.test\"\nonclick=\"alert(1)\">x</a>", "<img\nsrc=x\nonerror=alert(1)>", '<img/src=x/onerror=alert(1)>', '<svg/onload=alert(1)>', '<details open ontoggle=alert(1)>', '<video src=x onerror=alert(1)>', '<audio src=x onerror=alert(1)>',
    '<table background="javascript:alert(1)"><tr><td onclick="alert(1)" colspan="2;x">x</td></tr></table>', '<td style="background:url(javascript:alert(1))">x</td>',
    '<a href="&#x6A;avascript:alert(1)">x</a>', '<a href="&Tab;javascript:alert(1)">x</a>', '<a href="&NewLine;javascript:alert(1)">x</a>', '<a href="\xE2\x80\x8Bjavascript:alert(1)">x</a>',
];
$allSafe = true; $worst = '';
foreach ($attacks as $a) {
    $o = R::clean($a, $internal);
    if (audit($o) !== [] || preg_match('/<script|onerror|onload|javascript:|alert\(1\)(?!.*&lt;)/i', preg_replace('/&lt;.*?&gt;/', '', $o) ?? '') && preg_match('/<(script|iframe|object|embed|style|link|meta|base)\b/i', $o)) { $allSafe = false; $worst = $a . ' => ' . $o . ' :: ' . json_encode(audit($o)); break; }
}
check(count($attacks) . ' vektor serangan (skrip, penangan, skema, entitas, SVG, Alpine/Livewire, CSS, cacat): SETIAP keluaran lolos audit independen', $allSafe, $worst);
$noRaw = true; foreach ($attacks as $a) { $o = R::clean($a, $internal); if (preg_match('/<(script|iframe|object|embed|style|link|meta|base|form|button|textarea|select|video|audio|math|foreignobject|use|animate|set)\b/i', $o)) { $noRaw = false; $worst = "$a => $o"; break; } }
check('...dan tidak ada tag berbahaya yang lolos dalam bentuk apa pun', $noRaw, $worst);
check('script/style/iframe dibuang BESERTA isinya (kode di dalamnya tidak tampil sebagai teks)', R::clean('a<script>alert(1)</script>b<style>x{}</style>c<iframe src=x>f</iframe>d') === 'abcd');
check('script tanpa penutup membuang sisa masukan (tidak membocorkan isinya)', R::clean('<p>aman</p><script>alert(1)<p>bocor</p>') === '<p>aman</p>');
check('serangan terpecah ("<scr<script>ipt>") menjadi teks mati, bukan tag', !str_contains(R::clean('<scr<script>ipt>alert(1)</scr</script>ipt>'), '<script'));
check('"<" yang bukan tag utuh menjadi &lt; (tidak pernah tag setengah jadi)', R::clean('1 < 2 dan a<b dan <p <b>') === '1 &lt; 2 dan a&lt;b dan &lt;p <b></b>' || audit(R::clean('1 < 2 dan a<b dan <p <b>')) === [], R::clean('1 < 2 dan a<b dan <p <b>'));
check('atribut ganda: yang pertama menang (seperti peramban), tidak ada ganda di keluaran', substr_count(R::clean('<div class="a" class="b" style="color:red" style="color:blue">x</div>'), 'class=') === 1 && str_contains(R::clean('<div class="a" class="b">x</div>'), 'class="a"'));

echo "\nPenutupan dan batas\n";
check('tag terbuka ditutup di akhir (tidak bocor ke elemen pembungkus)', R::clean('<p><strong>a<em>b') === '<p><strong>a<em>b</em></strong></p>');
check('penutup tanpa pasangan diabaikan; penutup yang melompati tag menutup yang di dalamnya', R::clean('a</p>b</div>') === 'ab' && R::clean('<div><p><b>x</div>y') === '<div><p><b>x</b></p></div>y');
check('kedalaman dibatasi (40): 5000 <div> bersarang tidak meledak dan hasilnya seimbang', (function () { $o = R::clean(str_repeat('<div>', 5000) . 'x' . str_repeat('</div>', 5000)); return substr_count($o, '<div>') <= 40 && audit($o) === [] && str_contains($o, 'x'); })());
check('masukan raksasa dipotong (1 MB) dan tetap menghasilkan keluaran aman', (function () { $o = R::clean('<p>' . str_repeat('a', 3_000_000) . '</p>'); return strlen($o) < 1_100_000 && audit($o) === []; })());
foreach ([['<', 'karakter "<" berulang'], ['<a ', 'tag a berulang tanpa penutup'], ['<a href="', 'atribut terbuka berulang'], ['<img src=x ', 'img tanpa penutup'], ['<p class="a" ', 'atribut banyak tanpa ">"'], ['&#x', 'entitas terpotong'], ['<!--', 'komentar tak tertutup'], ['<svg><path d="M1 1" ', 'svg tak tertutup']] as [$unit, $label]) {
    $t0 = microtime(true); $o = R::clean(str_repeat($unit, 60000)); $dt = microtime(true) - $t0;
    check("kinerja: 60.000 x $label < 4 dtk dan aman", $dt < 4.0 && audit($o) === [], sprintf('%.2f dtk', $dt));
}
$t0 = microtime(true); R::clean(str_repeat('<p>Halo <strong>dunia</strong> <a href="https://a.test">x</a></p>', 20000)); $dt = microtime(true) - $t0;
check('kinerja: 20.000 paragraf nyata (±1,3 MB dipotong 1 MB) < 3 dtk', $dt < 3.0, sprintf('%.2f dtk', $dt));

echo "\nUji acak (invarian: tidak galat, lolos audit, idempoten)\n";
mt_srand(20261008);
$frag = array_merge(array_values($keep), $attacks, ['<', '>', '"', "'", '&', '&amp;', '&#x', '<p', '</p>', '<a href="', '">', ' onclick=', ' style="', '<svg>', '</svg>', '<!--', '-->', "\x00", "\xff", 'x', ' ', "\n", '=', '/', 'internal://page/a', '<br/>', '<b>', '</b>']);
$bad = null; $t0 = microtime(true);
for ($i = 0; $i < 6000 && $bad === null; $i++) {
    $h = '';
    for ($j = mt_rand(1, 8); $j > 0; $j--) { $h .= $frag[mt_rand(0, count($frag) - 1)]; }
    try { $o = R::clean($h, $internal); } catch (Throwable $e) { $bad = "galat: {$e->getMessage()} :: $h"; break; }
    $a = audit($o);
    if ($a !== []) { $bad = json_encode($a) . " :: $h => $o"; break; }
    if (R::clean($o, $internal) !== $o) { $bad = "tidak idempoten :: $h => $o => " . R::clean($o, $internal); break; }
}
check('6000 gabungan acak dari keluaran editor + serangan + serpihan: tanpa galat, lolos audit independen, idempoten', $bad === null, (string) $bad);
check('...dalam waktu wajar (< 20 dtk)', microtime(true) - $t0 < 20.0, sprintf('%.1f dtk', microtime(true) - $t0));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
