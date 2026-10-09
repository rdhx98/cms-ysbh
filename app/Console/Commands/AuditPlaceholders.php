<?php

namespace App\Console\Commands;

use App\Content\Placeholders;
use App\Content\PublicLookup;
use App\Models\Page;
use App\Models\Post;
use App\Models\Snippet;
use Illuminate\Console\Command;

/**
 * php artisan cms:audit-placeholders [--all]
 * Halaman online, artikel terbit, dan snippet online yang MASIH memuat penanda [ISI-DULU] (kerangka dari cms:seed-pages: isi program, NPWP, rekening).
 * Hanya MEMBACA. Kode keluar 1 bila ada. --all juga memeriksa yang offline/draf (daftar pekerjaan yang tersisa).
 */
class AuditPlaceholders extends Command
{
    protected $signature = 'cms:audit-placeholders {--all : termasuk halaman offline, artikel draf, dan snippet offline}';

    protected $description = 'Daftar halaman/artikel/snippet yang masih memuat penanda [ISI-DULU]';

    public function handle(): int
    {
        $found = 0;
        $table = [];
        foreach ([['Halaman', Page::class, PublicLookup::PAGE_STATUS, ['title', 'slug', 'content', 'meta_title', 'meta_description']],
                  ['Artikel', Post::class, PublicLookup::ARTICLE_STATUS, ['title', 'slug', 'content', 'meta_title', 'meta_description']],
                  ['Snippet', Snippet::class, 'online', ['title', 'content']]] as [$label, $model, $status, $columns]) {
            $query = $model::query();
            if (!$this->option('all')) {
                $query->where('status', $status);
            }
            foreach ($query->orderBy('id')->get() as $row) {
                $raw = [];
                foreach ($columns as $c) {
                    $raw[$c] = $row->getRawOriginal($c);
                }
                $where = Placeholders::columns($raw);
                if ($where !== []) {
                    $found++;
                    $table[] = [$label, $row->id, mb_substr(trim((string) preg_replace('/\s+/', ' ', (string) $row->getRawOriginal('title'))), 0, 60), (string) $row->status, implode(', ', $where)];
                }
            }
        }

        if ($found === 0) {
            $this->info('Tidak ada penanda ' . Placeholders::TOKEN . ' pada konten yang diperiksa.');

            return self::SUCCESS;
        }
        $this->table(['Jenis', 'ID', 'Judul', 'Status', 'Kolom'], $table);
        $this->line($this->option('all') ? "\n$found record masih berpenanda (daftar pekerjaan)." : "\n$found record ONLINE masih memuat " . Placeholders::TOKEN . ': tulis isinya atau jadikan offline.');

        return $this->option('all') ? self::SUCCESS : self::FAILURE;
    }
}
