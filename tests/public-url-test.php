<?php
/**
 * Pemeriksaan alamat publik halaman/artikel (dua aplikasi):  php tests/public-url-test.php [akar-kit]
 * LinkResolver::publicUrl(): templat jalur + alamat dasar, ketat, tidak pernah melempar galat.
 */
$root = $argv[1] ?? dirname(__DIR__);
require_once "$root/app/Content/Links/LinkResolver.php";
use App\Content\Links\LinkResolver as L;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }

echo "\nTemplat + alamat dasar\n";
check('artikel: https://ysbh.org + /artikel/{slug}', L::publicUrl('imunisasi-dasar', '/artikel/{slug}', 'https://ysbh.org') === 'https://ysbh.org/artikel/imunisasi-dasar');
check('halaman: templat /{slug}', L::publicUrl('tentang-kami', '/{slug}', 'https://ysbh.org') === 'https://ysbh.org/tentang-kami');
check('tanpa alamat dasar (landing sendiri): jalur relatif', L::publicUrl('x', '/artikel/{slug}', '') === '/artikel/x' && L::publicUrl('x', '/artikel/{slug}') === '/artikel/x' && L::publicUrl('x', '/artikel/{slug}', null) === '/artikel/x');
check('garis miring di ujung alamat dasar dibuang; port diperbolehkan', L::publicUrl('x', '/a/{slug}', 'https://ysbh.org/') === 'https://ysbh.org/a/x' && L::publicUrl('x', '/a/{slug}', 'http://localhost:8001') === 'http://localhost:8001/a/x');
check('templat bertingkat dan berakhiran: /id/berita/{slug}/', L::publicUrl('x', '/id/berita/{slug}/', 'https://ysbh.org') === 'https://ysbh.org/id/berita/x/');
check('slug di-encode (tidak bisa menyisipkan jalur, query, atau skrip)', L::publicUrl('a/b?c=1#d "e"', '/artikel/{slug}', 'https://ysbh.org') === 'https://ysbh.org/artikel/a%2Fb%3Fc%3D1%23d%20%22e%22' && !str_contains((string) L::publicUrl('<script>', '/a/{slug}', ''), '<'));

echo "\nTidak sah -> null (tautan tidak dirender), bukan galat\n";
foreach ([
    ['slug kosong', '', '/a/{slug}', 'https://ysbh.org'],
    ['templat bukan teks', 'x', ['x'], 'https://ysbh.org'], ['templat null', 'x', null, ''], ['templat kosong', 'x', '', ''],
    ['templat tanpa {slug}', 'x', '/artikel', ''], ['templat dua {slug}', 'x', '/{slug}/{slug}', ''],
    ['templat tidak diawali /', 'x', 'artikel/{slug}', ''], ['templat protokol-relatif //', 'x', '//evil.test/{slug}', ''],
    ['templat berisi skema', 'x', '/a/{slug}?u=javascript:alert(1)', ''], ['templat berisi spasi/kutip', 'x', '/a b/{slug}', ''], ['templat berisi tanda kutip', 'x', '/a"/{slug}', ''],
    ['alamat dasar berisi jalur', 'x', '/a/{slug}', 'https://ysbh.org/cms'], ['alamat dasar javascript:', 'x', '/a/{slug}', 'javascript:alert(1)'], ['alamat dasar tanpa skema', 'x', '/a/{slug}', 'ysbh.org'],
    ['alamat dasar dengan userinfo', 'x', '/a/{slug}', 'https://user@ysbh.org'], ['alamat dasar bukan teks', 'x', '/a/{slug}', ['https://ysbh.org']],
] as [$label, $slug, $tpl, $base]) {
    check("ditolak: $label", L::publicUrl($slug, $tpl, $base) === null, json_encode(L::publicUrl($slug, $tpl, $base)));
}

echo "\nUji acak: hasil yang diterima selalu berbentuk alamat aman\n";
mt_srand(20261008);
$tpls = ['/{slug}', '/artikel/{slug}', '/a/{slug}/', '{slug}', '//x/{slug}', '/{slug}?x=1', '/a b/{slug}', '/ok/{slug}', '/<s>/{slug}', "/a\n/{slug}", '/{slug}/{slug}'];
$bases = ['', 'https://ysbh.org', 'https://ysbh.org/', 'http://a.b:80', 'ysbh.org', 'javascript:x', 'https://x.test/p', "https://x\n.test", 'https://u@x.test'];
$slugs = ['a', 'imunisasi', 'a/b', '../../etc', '<b>', "x\0y", 'é', str_repeat('a', 300), '?x=1', 'ok-1'];
$acc = $bad = 0;
for ($i = 0; $i < 4000; $i++) {
    $u = L::publicUrl($slugs[array_rand($slugs)], $tpls[array_rand($tpls)], $bases[array_rand($bases)]);
    if ($u === null) continue;
    $acc++;
    if (!preg_match('#^(?:https?://[A-Za-z0-9.-]+(?::\d{1,5})?)?/[A-Za-z0-9/_.\-%]*$#D', $u)) { $bad++; if ($bad < 3) echo "    ! $u\n"; }
}
check("4000 kombinasi: $acc diterima, semuanya berbentuk jalur/URL aman ($bad menyimpang)", $bad === 0 && $acc > 100 && $acc < 3900, "$acc/$bad");

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
