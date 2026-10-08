<?php
/**
 * Kerangka halaman situs dan penanda (rilis 25):  php tests/site-pages-test.php [akar-kit]
 * SitePages, Placeholders (murni) + kontrak perintah cms:seed-pages dan cms:audit-placeholders.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
foreach (['Languages', 'Slug', 'LocaleMap', 'Names', 'TranslationAudit', 'Placeholders', 'SitePages'] as $f) { require_once "$root/app/Content/$f.php"; }

use App\Content\{Placeholders as P, SitePages as S, TranslationAudit, Slug};

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

$L = ['en', 'id'];
$pages = S::pages();
$byKey = array_column($pages, null, 'key');

echo "\nDaftar halaman\n";
check('12 halaman: beranda, tentang, indeks program, lima program, kredibilitas, dampak, transparansi, kontak', array_column($pages, 'key') === ['home', 'about', 'programs', 'malaria', 'immunization', 'maternal-child-health', 'tb', 'hiv', 'credibility', 'impact', 'transparency', 'contact']);
check('slug program EN memakai pola "[nama]-program"', array_map(fn ($k) => $byKey[$k]['slug']['en'], ['malaria', 'immunization', 'maternal-child-health', 'tb', 'hiv']) === ['malaria-program', 'immunization-program', 'maternal-child-health-program', 'tb-program', 'hiv-program']);
check('slug program ID memakai urutan Indonesia "program-[nama]"; indeks program: programs / program', array_map(fn ($k) => $byKey[$k]['slug']['id'], ['malaria', 'immunization', 'maternal-child-health', 'tb', 'hiv']) === ['program-malaria', 'program-imunisasi', 'program-kesehatan-ibu-anak', 'program-tbc', 'program-hiv'] && $byKey['programs']['slug'] === ['en' => 'programs', 'id' => 'program']);
check('halaman program DATAR (satu segmen), tanpa garis miring', !array_filter($pages, fn ($p) => str_contains($p['slug']['en'], '/') || str_contains($p['slug']['id'], '/')));
check('beranda memakai slug dari config (peta) atau bawaan home / beranda', $byKey['home']['slug'] === ['en' => 'home', 'id' => 'beranda'] && S::pages(['en' => 'start', 'id' => 'mulai'])[0]['slug'] === ['en' => 'start', 'id' => 'mulai']);
check('semua judul dan slug terisi di kedua bahasa; slug sah; tidak terlarang; tidak kembar', S::problems($pages, $L, 'en', ['en' => [], 'id' => []], ['en' => 'articles', 'id' => 'artikel']) === [], json_encode(S::problems($pages, $L, 'en', [], null)));
check('daftar lama reserved_slugs (enam slug rilis ≤ 22) MENOLAK halaman yang direncanakan, sehingga perintah berhenti dengan pesan', count(S::problems($pages, $L, 'en', ['about', 'contact', 'programs', 'credibility', 'transparancies', 'impact'], null)) >= 3);
check('problems: slug tidak sah, kosong, terlarang ("id" di EN), dan kembar terdeteksi', (function () use ($L) {
    $p = [['key' => 'a', 'title' => ['en' => 'A', 'id' => 'A'], 'slug' => ['en' => 'Bad Slug', 'id' => 'ok']], ['key' => 'b', 'title' => ['en' => 'B', 'id' => ''], 'slug' => ['en' => 'id', 'id' => 'ok']], ['key' => 'c', 'title' => ['en' => 'C', 'id' => 'C'], 'slug' => ['en' => 'x', 'id' => 'ok']]];
    $o = implode('|', S::problems($p, $L, 'en', [], null));
    return str_contains($o, 'a (en): slug "Bad Slug" tidak sah') && str_contains($o, 'b (id): judul atau slug kosong') && str_contains($o, 'b (en): slug "id" terlarang') && str_contains($o, 'c (id): slug "ok" kembar dengan a');
})());
check('slug sah menurut Slug::isValid untuk semua', !array_filter($pages, fn ($p) => !Slug::isValid($p['slug']['en']) || !Slug::isValid($p['slug']['id'])));

echo "\nIsi awal\n";
$doc = S::content($byKey['malaria'], $L);
check('satu paragraf per halaman, teks per bahasa, memuat penanda dan judul halaman; struktur {blocks, order, settings}', array_keys($doc) === ['blocks', 'order', 'settings'] && $doc['order'] === ['p1'] && $doc['blocks']['p1']['type'] === 'paragraph' && str_contains($doc['blocks']['p1']['data']['text']['en'], 'Malaria Program') && str_contains($doc['blocks']['p1']['data']['text']['id'], 'Program Malaria') && P::contains($doc));
check('halaman berisi lengkap di kedua bahasa: cms:audit-translations tidak menemukan apa pun', TranslationAudit::check([['id' => 1, 'title' => json_encode($byKey['malaria']['title']), 'slug' => json_encode($byKey['malaria']['slug']), 'content' => json_encode($doc)]], $L) === []);
check('blok snippet hanya pada halaman transparansi, dan hanya bila id snippet diberikan', !isset(S::content($byKey['transparency'], $L)['blocks']['sn1']) && S::content($byKey['transparency'], $L, 7)['blocks']['sn1'] === ['id' => 'sn1', 'type' => 'snippet', 'data' => ['snippet_id' => 7]] && S::content($byKey['transparency'], $L, 7)['order'] === ['p1', 'sn1'] && !isset(S::content($byKey['malaria'], $L, 7)['blocks']['sn1']));
check('bahasa ketiga tanpa terjemahan: teks kosong, bukan galat', S::content($byKey['about'], ['en', 'id', 'fr'])['blocks']['p1']['data']['text']['fr'] === '');

echo "\nSnippet NPWP / rekening: hanya tempat\n";
$sn = S::legalSnippet();
$snDoc = S::snippetContent($L);
$all = json_encode([$sn, $snDoc]);
check('snippet "legal-details": judul dua bahasa, dua paragraf (NPWP dan rekening) per bahasa, semuanya penanda', $sn['key'] === 'legal-details' && $sn['title']['en'] !== '' && $sn['title']['id'] !== '' && count($snDoc['order']) === 2 && P::contains($snDoc) && substr_count($all, P::TOKEN) >= 4 && str_contains($snDoc['blocks']['p1']['data']['text']['id'], 'NPWP') && str_contains($snDoc['blocks']['p2']['data']['text']['en'], 'account'));
$texts = function (array $doc): string { $o = ''; foreach ($doc['blocks'] as $b) { foreach (($b['data']['text'] ?? []) as $t) { $o .= $t . ' '; } } return $o; };
check('TIDAK ADA angka sama sekali di teks snippet (tidak ada nomor NPWP/rekening)', !preg_match('/\d/', $texts($snDoc) . json_encode($sn['text']) . $sn['description']), $texts($snDoc));
check('tidak ada angka di teks isi awal semua halaman (judul tidak memuat angka)', !preg_match('/\d/', implode(' ', array_map(fn ($p) => $texts(S::content($p, $L)), $pages))));

echo "\nPlaceholders\n";
check('terdeteksi di teks polos, HTML, entitas, dan JSON mentah kolom', P::contains('[ISI-DULU] x') && P::contains('<p>[ISI-<b>DULU</b>]</p>') && P::contains('[ISI-DULU]') && P::contains(json_encode(['blocks' => ['p' => ['data' => ['text' => ['id' => '<p>a</p>', 'en' => '<p>[ISI-DULU] b</p>']]]]])));
check('dalam JSON mentah, token yang dipecah tag tebal (tanda "<\/b>" ter-escape) tetap terdeteksi', P::contains(json_encode(['text' => ['en' => '<p>[ISI-<b>DULU</b>] x</p>']])));
check('larik bersarang: terdeteksi bila SALAH SATU nilai memuatnya (juga bukan yang pertama); larik tanpa penanda = false', P::contains(['a', ['b', 'x [ISI-DULU]']]) && !P::contains(['a', ['b', 'c']]) && !P::contains([]));
check('tidak terdeteksi: kosong, null, angka, teks biasa, "ISI DULU" tanpa kurung, huruf kecil', !P::contains('') && !P::contains(null) && !P::contains(5) && !P::contains('isi biasa') && !P::contains('ISI DULU') && !P::contains('[isi-dulu]'));
check('columns: nama kolom yang memuat penanda saja', P::columns(['title' => '{"en":"A"}', 'content' => '{"x":"[ISI-DULU]"}', 'meta_description' => null]) === ['content']);

echo "\nPerintah (kontrak)\n";
$seed = (string) file_get_contents("$root/app/Console/Commands/SeedSitePages.php");
$audit = (string) file_get_contents("$root/app/Console/Commands/AuditPlaceholders.php");
check('seed: bawaan hanya rencana; --apply diperlukan untuk menulis', str_contains($seed, "{--apply") && str_contains($seed, "if (!\$this->option('apply'))") && strpos($seed, "option('apply')") < strpos($seed, '->save()'));
check('seed: status SELALU offline (snippet dan halaman), tidak pernah online', substr_count($seed, "'status' => 'offline'") === 2 && !str_contains($seed, "'online'"));
check('seed: tidak menimpa (slug dipakai = dilewati), tidak ada update/delete, galat per halaman dilaporkan dan kode keluar 1', !preg_match('/->(update|delete|forceDelete|upsert)\(|updateOrCreate|firstOrCreate/', $seed) && str_contains($seed, 'dilewati') && str_contains($seed, '$failed > 0 ? self::FAILURE'));
check('seed: memeriksa SitePages::problems (config lama dengan enam slug terlarang menghentikan perintah sebelum menulis)', str_contains($seed, 'SitePages::problems(') && strpos($seed, 'SitePages::problems(') < strpos($seed, '->save()'));
check('audit: hanya MEMBACA; bawaan hanya online/terbit; --all untuk daftar pekerjaan; kode keluar 1 bila ada (tanpa --all)', !preg_match('/->(save|update|delete|insert|create|forceDelete)\(/', $audit) && str_contains($audit, 'PublicLookup::PAGE_STATUS') && str_contains($audit, 'PublicLookup::ARTICLE_STATUS') && str_contains($audit, '{--all') && str_contains($audit, 'self::FAILURE'));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
