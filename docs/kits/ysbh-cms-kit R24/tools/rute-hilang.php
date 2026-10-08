<?php
/**
 * Mencari pemanggilan route('nama') ke rute landing yang DIHAPUS di rilis 23 (halaman statis lama). Dipakai pemasang landing
 * (sebelum menulis apa pun) dan pemeriksa landing (sesudahnya). Berkas ini hanya mendefinisikan fungsi: aman di-require dua kali.
 *
 * Mengapa: route('about') ke rute yang tidak ada MELEMPAR galat dan menjatuhkan SELURUH halaman yang memuatnya (biasanya kaki halaman
 * atau menu, jadi seluruh situs). Itulah akibat menghapus rute statis; pemasang menolak lebih dulu daripada membiarkan situs rusak.
 */
if (!function_exists('ysbh_removed_route_names')) {
    /** Nama rute statis landing sebelum rilis 23. 'home' dan 'articles' TIDAK termasuk: keduanya masih ada. */
    function ysbh_removed_route_names(): array
    {
        return ['about', 'contact', 'programs', 'programs-malaria', 'programs-imunisasi', 'programs-kia', 'programs-tbc', 'programs-hiv', 'credibility', 'transparancies', 'impact'];
    }

    /** Tautan yang menggantikan rute lama (untuk pesan): route('about') -> url('/about'). */
    function ysbh_removed_route_path(string $name): string
    {
        return match ($name) {
            'programs-malaria' => '/programs/malaria', 'programs-imunisasi' => '/programs/imunisasi', 'programs-kia' => '/programs/kia',
            'programs-tbc' => '/programs/tbc', 'programs-hiv' => '/programs/hiv',
            default => '/' . $name,
        };
    }

    /** Membuang komentar (Blade {{-- --}}, PHP /* *\/, baris penuh // atau #, dan // sesudah <?php, @php, atau ;) tanpa menggeser nomor baris. */
    function ysbh_strip_comments(string $code): string
    {
        $blank = fn (array $m) => str_repeat("\n", substr_count($m[0], "\n"));
        $code = (string) preg_replace_callback('/\{\{--.*?--\}\}/s', $blank, $code);
        $code = (string) preg_replace_callback('#/\*.*?\*/#s', $blank, $code);

        $code = (string) preg_replace('#^[ \t]*(?://|\#(?!\[)).*$#m', '', $code);   // baris penuh
        // komentar di ujung baris, hanya sesudah pembuka PHP (<?php, @php) atau akhir pernyataan (;): tidak menyentuh "https://..." atau teks HTML
        return (string) preg_replace('#((?:<\?php|@php)[ \t]*|;[ \t]*)//.*$#m', '$1', $code);
    }

    /**
     * @param  list<string> $skipRel jalur relatif (garis miring depan, tanpa awalan) berkas yang akan diganti atau dihapus pemasang: tidak diperiksa
     * @return list<array{file:string,line:int,name:string}>
     */
    function ysbh_find_removed_route_refs(string $project, array $skipRel = []): array
    {
        $names = implode('|', array_map('preg_quote', ysbh_removed_route_names()));
        $pattern = '/(?<![A-Za-z0-9_])(?:route|to_route)\(\s*[\'"](' . $names . ')[\'"]/';
        $skip = array_flip($skipRel);
        $out = [];
        foreach (['resources/views', 'app', 'routes'] as $dir) {
            if (!is_dir("$project/$dir")) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$project/$dir", FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile() || $f->isLink() || !str_ends_with($f->getFilename(), '.php') || $f->getSize() > 2_000_000) {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($project) + 1));
                if (isset($skip[$rel])) {
                    continue;
                }
                $code = ysbh_strip_comments((string) file_get_contents($f->getPathname()));
                // seluruh teks (bukan baris demi baris): pemanggilan yang dipecah ke beberapa baris, route(\n 'about'\n), juga terbaca
                if (preg_match_all($pattern, $code, $m, PREG_OFFSET_CAPTURE)) {
                    foreach ($m[1] as $k => [$name]) {
                        $out[] = ['file' => $rel, 'line' => substr_count($code, "\n", 0, $m[0][$k][1]) + 1, 'name' => $name];   // baris tempat route( dimulai
                    }
                }
            }
        }
        usort($out, fn ($a, $b) => [$a['file'], $a['line']] <=> [$b['file'], $b['line']]);

        return $out;
    }
}
