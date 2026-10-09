<?php

namespace App\Console\Commands;

use App\Content\Languages;
use App\Content\Names;
use App\Content\SitePages;
use App\Models\Page;
use App\Models\Snippet;
use Illuminate\Console\Command;

/**
 * php artisan cms:seed-pages [--apply]
 * Membuat KERANGKA halaman situs (docs/HALAMAN-SITUS.md): beranda, tentang, program (indeks + lima program), kredibilitas, dampak, transparansi,
 * kontak, dan snippet `legal-details` (tempat NPWP/rekening). Semua OFFLINE dan berisi penanda [ISI-DULU]; tidak ada nilai NPWP/rekening.
 * Bawaannya hanya MELIHAT rencana. Halaman yang slug-nya sudah dipakai (bahasa mana pun) dilewati, tidak pernah ditimpa; dijalankan ulang aman.
 */
class SeedSitePages extends Command
{
    protected $signature = 'cms:seed-pages {--apply : benar-benar membuat (tanpa ini hanya rencana)}';

    protected $description = 'Membuat kerangka halaman situs (offline, berpenanda [ISI-DULU]) dan snippet NPWP/rekening kosong';

    public function handle(): int
    {
        $cfg = Languages::fromConfig();
        $locales = $cfg['locales'];
        $homeSlugs = [];
        foreach ($locales as $l) {
            $homeSlugs[$l] = Languages::homeSlug(config('cms.home_slug'), $l);
        }
        $pages = SitePages::pages($homeSlugs);

        $problems = SitePages::problems($pages, $locales, $cfg['default'], config('cms.reserved_slugs'), config('cms.articles_index_slug'));
        if ($problems !== []) {
            $this->error('Rencana halaman ditolak (periksa config/cms.php: reserved_slugs, home_slug):');
            foreach ($problems as $p) {
                $this->line("  - $p");
            }

            return self::FAILURE;
        }

        // slug yang sudah dipakai halaman mana pun, per bahasa
        $used = [];
        foreach (Page::query()->get(['id', 'slug']) as $page) {
            foreach ($locales as $l) {
                $s = Names::exact($page->getRawOriginal('slug'), $l);
                if ($s !== '') {
                    $used["$l|$s"] = $page->id;
                }
            }
        }

        $rows = [];
        $todo = [];
        foreach ($pages as $p) {
            $clash = null;
            foreach ($locales as $l) {
                $clash ??= isset($used["$l|" . ($p['slug'][$l] ?? '')]) ? ($p['slug'][$l] ?? '') : null;
            }
            $rows[] = [$p['key'], implode(' | ', array_map(fn ($l) => ($l === $cfg['default'] ? '' : '/' . $l) . '/' . ($p['slug'][$l] ?? ''), $locales)), $clash === null ? 'BARU' : "ADA (slug \"$clash\"), dilewati"];
            if ($clash === null) {
                $todo[] = $p;
            }
        }
        $snippetExists = Snippet::query()->where('key', SitePages::SNIPPET_KEY)->exists();
        $rows[] = ['snippet ' . SitePages::SNIPPET_KEY, 'NPWP dan rekening (kosong)', $snippetExists ? 'ADA, dilewati' : 'BARU'];

        $this->table(['Halaman', 'Alamat', 'Rencana'], $rows);
        if (!$this->option('apply')) {
            $this->line("\nIni baru rencana. Semua dibuat OFFLINE. Untuk membuat: php artisan cms:seed-pages --apply");

            return self::SUCCESS;
        }

        $failed = 0;
        $snippetId = null;
        try {
            $snippet = Snippet::query()->where('key', SitePages::SNIPPET_KEY)->first();
            if ($snippet === null) {
                $s = SitePages::legalSnippet();
                $snippet = Snippet::query()->create([
                    'key' => $s['key'], 'title' => $s['title'], 'description' => $s['description'],
                    'content' => SitePages::snippetContent($locales), 'status' => 'offline', 'is_closing' => false, 'sort_order' => 0,
                ]);
                $this->info('Snippet dibuat: ' . SitePages::SNIPPET_KEY);
            }
            $snippetId = (int) $snippet->id;
        } catch (\Throwable $e) {
            $failed++;
            $this->error('Snippet gagal dibuat: ' . $e->getMessage());
        }

        foreach ($todo as $p) {
            try {
                $page = new Page();
                $page->forceFill([
                    'title' => array_intersect_key($p['title'], array_flip($locales)),
                    'slug' => array_intersect_key($p['slug'], array_flip($locales)),
                    'status' => 'offline',
                    'content' => SitePages::content($p, $locales, $snippetId),
                ])->save();
                $this->info("Dibuat (offline): {$p['key']}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Gagal membuat {$p['key']}: " . $e->getMessage());
            }
        }

        $this->line("\nSelesai. Tulis isinya di editor, lalu jadikan online satu per satu. php artisan cms:audit-placeholders menunjukkan yang masih berpenanda.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
