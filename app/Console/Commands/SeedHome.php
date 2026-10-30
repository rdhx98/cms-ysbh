<?php

namespace App\Console\Commands;

use App\Content\HomeLayout;
use App\Content\Languages;
use App\Content\Names;
use App\Content\SitePages;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * php artisan cms:seed-home [--apply] [--replace]
 * Menyusun isi BERANDA dari blok (docs/BERANDA.md): Hero, mitra, enam kartu program, kisah lapangan, angka, cara donasi, artikel terbaru, penutup.
 * Bawaannya hanya MELIHAT rencana. Beranda yang belum ada dibuat OFFLINE. Beranda lama yang masih benih rilis 26 (belum Anda ubah) diganti; yang sudah
 * Anda ubah TIDAK disentuh kecuali dengan --replace (cadangan JSON ditulis lebih dulu). Status halaman tidak pernah diubah.
 */
class SeedHome extends Command
{
    protected $signature = 'cms:seed-home {--apply : benar-benar menulis (tanpa ini hanya rencana)} {--replace : ganti beranda yang sudah Anda ubah (cadangan dibuat lebih dulu)}';

    protected $description = 'Menyusun isi beranda dari blok (hero, program, angka, cara donasi, artikel terbaru, penutup); tidak menimpa suntingan Anda tanpa --replace';

    public function handle(): int
    {
        $cfg = Languages::fromConfig();
        $locales = $cfg['locales'];
        $homeSlugs = [];
        foreach ($locales as $l) {
            $homeSlugs[$l] = Languages::homeSlug(config('cms.home_slug'), $l);
        }
        $defs = SitePages::pages($homeSlugs);
        $homeDef = null;
        $enSlug = [];
        foreach ($defs as $d) {
            $enSlug[$d['key']] = (string) ($d['slug']['en'] ?? '');
            if ($d['key'] === 'home') {
                $homeDef = $d;
            }
        }
        if ($homeDef === null) {
            $this->error('Definisi halaman beranda tidak ditemukan di SitePages::pages().');

            return self::FAILURE;
        }

        // halaman yang sudah ada: slug (bahasa mana pun) => model
        $bySlug = [];
        foreach (Page::query()->get() as $page) {
            foreach ($locales as $l) {
                $s = Names::exact($page->getRawOriginal('slug'), $l);
                if ($s !== '') {
                    $bySlug[$s] = $page;
                }
            }
        }
        $home = null;
        foreach ($locales as $l) {
            $home ??= $bySlug[(string) ($homeDef['slug'][$l] ?? '')] ?? null;
        }

        $missing = [];
        $pageId = function (string $key) use ($enSlug, $bySlug, &$missing): ?int {
            $page = $bySlug[$enSlug[$key] ?? ''] ?? null;
            if ($page === null) {
                $missing[$key] = $enSlug[$key] ?? $key;

                return null;
            }

            return (int) $page->getKey();
        };
        $doc = HomeLayout::content($locales, $pageId);

        $mode = 'create';
        $note = 'beranda belum ada: dibuat OFFLINE';
        if ($home !== null) {
            $existing = $home->content;
            $existing = is_array($existing) ? $existing : (is_string($existing) ? (json_decode($existing, true) ?: []) : []);
            $seed = SitePages::content($homeDef, $locales, null);
            if (HomeLayout::isCurrent($existing)) {
                $mode = $this->option('replace') ? 'replace' : 'skip';
                $note = $mode === 'skip' ? 'sudah berupa susunan baru: dilewati (pakai --replace untuk menyusun ulang)' : 'susunan baru disusun ulang atas permintaan (--replace)';
            } elseif (HomeLayout::isUntouched($existing, $seed)) {
                $mode = 'replace';
                $note = 'masih benih rilis 26 (belum Anda ubah): diganti';
            } else {
                $mode = $this->option('replace') ? 'replace' : 'skip';
                $note = $mode === 'skip' ? 'sudah Anda ubah: TIDAK disentuh (pakai --replace untuk menggantinya; cadangan dibuat lebih dulu)' : 'sudah Anda ubah: diganti atas permintaan (--replace), cadangan dibuat lebih dulu';
            }
        }

        $this->table(['Beranda', 'Rencana'], [[
            implode(' | ', array_map(fn ($l) => ($l === $cfg['default'] ? '' : '/' . $l) . '/' . ($homeDef['slug'][$l] ?? ''), $locales)),
            $note,
        ]]);
        $this->line('Isi baru: ' . count($doc['order']) . ' blok di tingkat atas, ' . (count($doc['blocks']) - count($doc['order'])) . ' blok di dalam kolom/langkah.');
        if ($missing !== []) {
            $this->warn('Halaman tujuan tombol yang belum ada (tombolnya dibuat tanpa tautan; jalankan cms:seed-pages --apply lalu cms:seed-home --apply --replace): ' . implode(', ', $missing));
        }
        if (!$this->option('apply')) {
            $this->line("\nIni baru rencana. Untuk menulis: php artisan cms:seed-home --apply");

            return self::SUCCESS;
        }
        if ($mode === 'skip') {
            $this->line("\nTidak ada yang ditulis.");

            return self::SUCCESS;
        }

        try {
            if ($mode === 'replace' && $home !== null) {
                $dir = storage_path('app/cms-backup');
                if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $this->error('Folder cadangan tidak bisa dibuat: ' . $dir . '. Beranda tidak diubah.');

                    return self::FAILURE;
                }
                $file = $dir . '/beranda-' . date('Ymd-His') . '.json';
                if (@file_put_contents($file, json_encode($home->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR)) === false) {
                    $this->error('Cadangan tidak bisa ditulis: ' . $file . '. Beranda tidak diubah.');

                    return self::FAILURE;
                }
                $this->info('Cadangan beranda lama: ' . $file);
                $home->forceFill(['content' => $doc])->save();
                $this->info('Beranda diganti dengan susunan baru (status tidak diubah).');
            } else {
                $page = new Page();
                $page->forceFill([
                    'title' => array_intersect_key($homeDef['title'], array_flip($locales)),
                    'slug' => array_intersect_key($homeDef['slug'], array_flip($locales)),
                    'status' => 'offline',
                    'content' => $doc,
                ])->save();
                $this->info('Beranda dibuat (offline).');
            }
        } catch (\Throwable $e) {
            $this->error('Gagal menulis beranda: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line("\nSelesai. Buka beranda di editor: pilih foto di dua blok Gambar (hero dan kisah lapangan), lalu php artisan cms:audit-placeholders sebelum menjadikannya online.");

        return self::SUCCESS;
    }
}
