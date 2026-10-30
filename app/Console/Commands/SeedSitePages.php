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
 * Membuat halaman situs (docs/HALAMAN-SITUS.md): beranda, tentang, program (indeks + lima program), kredibilitas, dampak, transparansi,
 * kontak, dan snippet `legal-details` (tempat NPWP/rekening). Semua OFFLINE; berisi DRAF teks dua bahasa (SiteText) dengan penanda [ISI-DULU] pada hal yang belum diputuskan; tidak ada nilai NPWP/rekening.
 * Bawaannya hanya MELIHAT rencana. Halaman yang slug-nya sudah dipakai (bahasa mana pun) dilewati, tidak pernah ditimpa; dijalankan ulang aman.
 * Rilis 105: --replace=kunci,kunci mengganti ISI halaman yang SUDAH ada dengan draf terbaru (mis. setelah data legal baru masuk ke SiteText). Hanya halaman
 * yang masih OFFLINE; cadangan JSON isi lama dibuat lebih dulu (storage/app/cms-backup); judul, slug, dan status tidak diubah.
 */
class SeedSitePages extends Command
{
    protected $signature = 'cms:seed-pages {--apply : benar-benar membuat (tanpa ini hanya rencana)} {--replace= : kunci halaman yang SUDAH ada dan isinya diganti draf terbaru, dipisah koma (mis. about,credibility); hanya halaman offline, cadangan dibuat lebih dulu}';

    protected $description = 'Membuat halaman situs berisi draf teks (offline, penanda [ISI-DULU] pada yang belum diputuskan) dan snippet NPWP/rekening kosong';

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

        $replaceKeys = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('replace')))));
        $unknown = array_diff($replaceKeys, array_column($pages, 'key'));
        if ($unknown !== []) {
            $this->error('Kunci --replace tidak dikenal: ' . implode(', ', $unknown) . '. Kunci yang ada: ' . implode(', ', array_column($pages, 'key')));

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
        $replace = [];
        foreach ($pages as $p) {
            $clash = null;
            $existingId = null;
            foreach ($locales as $l) {
                $hit = $used["$l|" . ($p['slug'][$l] ?? '')] ?? null;
                if ($hit !== null) {
                    $clash ??= $p['slug'][$l];
                    $existingId ??= $hit;
                }
            }
            $plan = $clash === null ? 'BARU' : "ADA (slug \"$clash\"), dilewati";
            if ($clash !== null && in_array($p['key'], $replaceKeys, true)) {
                $existing = Page::query()->find($existingId);
                if ($existing !== null && (string) $existing->status !== 'offline') {
                    $plan = "ADA (slug \"$clash\"), TIDAK diganti: halaman sedang online (jadikan offline dulu)";
                } else {
                    $plan = "ADA (slug \"$clash\"), ISI DIGANTI draf terbaru (cadangan dibuat lebih dulu; judul, slug, status tetap)";
                    $replace[] = [$p, $existingId];
                }
            }
            $rows[] = [$p['key'], implode(' | ', array_map(fn ($l) => ($l === $cfg['default'] ? '' : '/' . $l) . '/' . ($p['slug'][$l] ?? ''), $locales)), $plan];
            if ($clash === null) {
                $todo[] = $p;
            }
        }
        $snippetExists = Snippet::query()->where('key', SitePages::SNIPPET_KEY)->exists();
        $rows[] = ['snippet ' . SitePages::SNIPPET_KEY, 'NPWP dan rekening (kosong)', $snippetExists ? 'ADA, dilewati' : 'BARU'];

        $this->table(['Halaman', 'Alamat', 'Rencana'], $rows);
        if (!$this->option('apply')) {
            $this->line("\nIni baru rencana. Semua dibuat OFFLINE. Untuk membuat: php artisan cms:seed-pages --apply (halaman yang sudah ada: tambahkan --replace=kunci,kunci untuk mengganti isinya)");

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

        foreach ($replace as [$p, $pageId]) {
            try {
                $page = Page::query()->findOrFail($pageId);
                $dir = storage_path('app/cms-backup');
                if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new \RuntimeException('folder cadangan tidak bisa dibuat: ' . $dir);
                }
                $file = $dir . '/' . $p['key'] . '-' . date('Ymd-His') . '.json';
                if (@file_put_contents($file, json_encode($page->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR)) === false) {
                    throw new \RuntimeException('cadangan tidak bisa ditulis: ' . $file);
                }
                $page->forceFill(['content' => SitePages::content($p, $locales, $snippetId)])->save();
                $this->info("Isi diganti: {$p['key']} (cadangan: $file)");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Gagal mengganti {$p['key']}: " . $e->getMessage() . ' (halaman itu tidak diubah)');
            }
        }

        $this->line("\nSelesai. Semua halaman OFFLINE berisi draf teks. Tinjau dan lengkapi di editor, ganti foto dan penanda, lalu jadikan online satu per satu. php artisan cms:audit-placeholders menunjukkan yang masih berpenanda.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
