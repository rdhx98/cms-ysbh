<?php

namespace App\Console\Commands;

use App\Content\Languages;
use App\Content\SlugAudit;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * php artisan cms:audit-slugs
 * Daftar halaman yang slug-nya terlarang (alamatnya sudah dimiliki rute tetap di landing): tampak online di CMS tetapi tidak pernah
 * terbuka di situs. Hanya MEMBACA. Kode keluar 1 bila ada yang bermasalah (bisa dipakai di skrip).
 */
class AuditSlugs extends Command
{
    protected $signature = 'cms:audit-slugs';

    protected $description = 'Daftar halaman dengan slug yang bertabrakan dengan alamat tetap situs';

    public function handle(): int
    {
        $rows = Page::query()->get(['id', 'title', 'slug'])->map(fn ($p) => ['id' => $p->id, 'title' => $p->getRawOriginal('title'), 'slug' => $p->getRawOriginal('slug')]);
        $lang = Languages::fromConfig();
        $found = SlugAudit::conflicts($rows, (array) config('cms.reserved_slugs', []), config('cms.articles_index_slug'), $lang['locales'], $lang['default']);

        if ($found === []) {
            $this->info('Tidak ada halaman dengan slug terlarang.');

            return self::SUCCESS;
        }

        $this->error(count($found) . ' slug bertabrakan dengan alamat tetap situs (halaman ini tidak akan pernah terbuka). Ganti slug-nya di editor:');
        $this->table(['ID', 'Judul', 'Bahasa', 'Slug'], array_map(fn ($f) => [$f['id'], $f['title'], $f['locale'], $f['slug']], $found));

        return self::FAILURE;
    }
}
