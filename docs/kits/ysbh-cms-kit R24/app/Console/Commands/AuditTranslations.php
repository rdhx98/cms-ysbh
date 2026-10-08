<?php

namespace App\Console\Commands;

use App\Content\Languages;
use App\Content\PublicLookup;
use App\Content\TranslationAudit;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * php artisan cms:audit-translations [--locale=en] [--all] [--strict]
 * Halaman online dan artikel terbit yang BELUM LENGKAP di sebuah bahasa (docs/BAHASA.md). Hanya MEMBACA.
 * Situs berbahasa Inggris dan Indonesia terpisah per alamat, jadi blok yang hanya terisi satu bahasa tampil KOSONG di bahasa lainnya.
 *   --locale=en  hanya bahasa itu       --all  termasuk draf/offline       --strict  record yang belum diterjemahkan sama sekali pun dihitung masalah
 * Kode keluar 1 bila ada terjemahan setengah jalan atau blok kosong (dan, dengan --strict, record yang belum diterjemahkan).
 */
class AuditTranslations extends Command
{
    protected $signature = 'cms:audit-translations {--locale= : hanya periksa bahasa ini} {--all : termasuk halaman offline dan artikel draf} {--strict : record yang belum diterjemahkan juga dihitung masalah}';

    protected $description = 'Daftar halaman/artikel yang terjemahannya belum lengkap di sebuah bahasa';

    public function handle(): int
    {
        $locales = Languages::fromConfig()['locales'];
        $only = trim((string) $this->option('locale'));
        if ($only !== '' && !in_array($only, $locales, true)) {
            $this->error("Bahasa '$only' tidak ada di supported_locales (" . implode(', ', $locales) . ').');

            return self::INVALID;
        }

        $problems = 0;
        $info = 0;
        foreach ([['Halaman', Page::class, PublicLookup::PAGE_STATUS], ['Artikel', Post::class, PublicLookup::ARTICLE_STATUS]] as [$label, $model, $status]) {
            $query = $model::query();
            if (!$this->option('all')) {
                $query->where('status', $status);
            }
            $rows = $query->orderBy('id')->get(['id', 'title', 'slug', 'content'])->map(fn ($r) => [
                'id' => $r->id, 'title' => $r->getRawOriginal('title'), 'slug' => $r->getRawOriginal('slug'), 'content' => $r->getRawOriginal('content'),
            ]);
            $found = array_values(array_filter(TranslationAudit::check($rows, $locales), fn ($f) => $only === '' || $f['locale'] === $only));

            $table = [];
            foreach ($found as $f) {
                $isInfo = $f['problem'] === TranslationAudit::NOT_YET && !$this->option('strict');
                $isInfo ? $info++ : $problems++;
                $table[] = [$f['id'], $f['title'], strtoupper($f['locale']), $isInfo ? 'info' : 'MASALAH', $f['detail']];
            }
            if ($table !== []) {
                $this->line("\n$label:");
                $this->table(['ID', 'Judul', 'Bahasa', 'Tingkat', 'Keterangan'], $table);
            }
        }

        if ($problems === 0) {
            $this->info('Tidak ada terjemahan setengah jalan atau blok kosong.' . ($info ? " ($info record belum diterjemahkan ke sebuah bahasa: itu wajar bila memang hanya satu bahasa; lihat dengan --strict.)" : ''));

            return self::SUCCESS;
        }

        $this->error("$problems masalah: teks yang kosong di satu bahasa tampil KOSONG di situs bahasa itu. Lengkapi di editor.");

        return self::FAILURE;
    }
}
