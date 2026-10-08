<?php
/**
 * Pemindai tabrakan direktif Blade:  php tests/blade-scan.php [akar-kit]
 *
 * MASALAH YANG DICEGAH: Blade memproses direktif (@nama) SEBELUM ekspresi echo, dan setiap versi Laravel menambah direktif baru.
 * Teks seperti '@context' (JSON-LD) di dalam {{ }} / {!! !!} atau di dalam tanda kutip dikompilasi sebagai DIREKTIF dan merusak keluaran
 * diam-diam (kasus nyata: Laravel 12.69 menambah @context, sehingga JSON-LD FAQPage kehilangan "@context").
 *
 * Aturan (di luar blok @php...@endphp, @verbatim, dan komentar {{-- --}}):
 *   1. @nama yang sama dengan nama direktif DI DALAM ekspresi {{ }} atau {!! !!}  -> GALAT
 *   2. @nama yang sama dengan nama direktif dan DIAPIT tanda kutip ('@nama' / "@nama") -> GALAT
 * Penulisan literal yang benar: letakkan di dalam @php...@endphp, atau tulis @@nama.
 * Daftar nama diambil dari Laravel 12.69.3 (Concerns/Compiles*.php + BladeCompiler); tambahkan nama baru bila framework Anda lebih baru.
 */
$root = $argv[1] ?? dirname(__DIR__);

$directives = array_flip([
    'append', 'attributeEchos', 'auth', 'aware', 'bool', 'break', 'can', 'canany', 'cannot', 'case',
    'checked', 'choice', 'class', 'closingTags', 'comments', 'component', 'componentFirst', 'componentTags', 'context', 'continue',
    'csrf', 'dd', 'default', 'disabled', 'dump', 'each', 'echos', 'else', 'elseAuth', 'elseGuest',
    'elsePush', 'elsePushIf', 'elsecan', 'elsecanany', 'elsecannot', 'elseif', 'empty', 'endAuth', 'endComponent', 'endComponentClass',
    'endComponentFirst', 'endEmpty', 'endEnv', 'endGuest', 'endIsset', 'endOnce', 'endProduction', 'endPushIf', 'endSlot', 'endSwitch',
    'endcan', 'endcanany', 'endcannot', 'endcontext', 'enderror', 'endfor', 'endforeach', 'endforelse', 'endfragment', 'endif',
    'endlang', 'endphp', 'endprepend', 'endprependOnce', 'endpush', 'endpushOnce', 'endsection', 'endsession', 'endunless', 'endverbatim',
    'endwhile', 'env', 'error', 'escapedEchos', 'extends', 'extendsFirst', 'extensions', 'for', 'foreach', 'forelse',
    'fragment', 'guest', 'hasSection', 'hasStack', 'if', 'include', 'includeFirst', 'includeIf', 'includeIsolated', 'includeUnless',
    'includeWhen', 'inject', 'isset', 'js', 'json', 'lang', 'method', 'once', 'openingTags', 'overwrite',
    'parent', 'php', 'prepend', 'prependOnce', 'production', 'props', 'push', 'pushIf', 'pushOnce', 'rawEchos',
    'readonly', 'regularEchos', 'required', 'section', 'sectionMissing', 'selected', 'selfClosingTags', 'session', 'show', 'slot',
    'slots', 'stack', 'statement', 'statements', 'stop', 'string', 'style', 'switch', 'tags', 'unless',
    'unset', 'use', 'verbatim', 'vite', 'viteReactRefresh', 'while', 'yield'
]);

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/resources/views", FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (str_ends_with($f->getFilename(), '.blade.php')) {
        $files[] = $f->getPathname();
    }
}
sort($files);

$bad = 0;
foreach ($files as $file) {
    $src = file_get_contents($file);
    $clean = preg_replace_callback('/@php\b.*?@endphp|@verbatim.*?@endverbatim|\{\{--.*?--\}\}/s', fn ($m) => preg_replace('/[^\n]/', ' ', $m[0]), $src);

    // aturan 1: di dalam ekspresi echo
    preg_match_all('/\{\{(?!--).*?\}\}|\{!!.*?!!\}/s', $clean, $echos, PREG_OFFSET_CAPTURE);
    foreach ($echos[0] as [$expr, $offset]) {
        if (preg_match_all('/(?<![\w@])@(\w+)/', $expr, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as [$name, $pos]) {
                if (isset($directives[$name])) {
                    $line = substr_count($clean, "\n", 0, $offset + $pos) + 1;
                    echo "  GALAT  " . substr($file, strlen($root) + 1) . ":$line  @$name di dalam ekspresi echo (dikompilasi sebagai direktif)\n";
                    $bad++;
                }
            }
        }
    }

    // aturan 2: diapit tanda kutip, di mana pun
    if (preg_match_all('/([\'"])@(\w+)\1/', $clean, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[2] as $i => [$name, $pos]) {
            if (isset($directives[$name])) {
                $line = substr_count($clean, "\n", 0, $pos) + 1;
                echo "  GALAT  " . substr($file, strlen($root) + 1) . ":$line  '@$name' dalam tanda kutip (dikompilasi sebagai direktif)\n";
                $bad++;
            }
        }
    }
}

echo "\n==> " . count($files) . " berkas Blade dipindai, " . ($bad === 0 ? 'tidak ada tabrakan direktif' : "$bad tabrakan") . "\n";
exit($bad ? 1 : 0);
