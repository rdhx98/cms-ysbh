<?php

namespace App\Console\Commands;

use App\Content\Languages;
use App\Content\Names;
use App\Content\SitePages;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * php artisan cms:convert-photos [--apply]   (rilis 37)
 * Halaman yang SUDAH dibuat cms:seed-pages sebelum rilis 37 memuat tempat foto berupa paragraf "[ISI-DULU] <em>Photo: ...</em>". Perintah ini
 * menggantinya dengan blok Gambar KOSONG yang keterangannya sudah terisi (id blok dan urutan sama), supaya foto cukup dipilih dari File Manager.
 * Bawaannya hanya MELIHAT rencana. Hanya paragraf yang masih berupa penanda foto utuh yang diganti; blok yang sudah Anda sunting, status halaman,
 * judul, slug, dan blok lain tidak disentuh. Aman dijalankan ulang (yang sudah menjadi Gambar tidak cocok lagi).
 */
class ConvertPhotoPlaceholders extends Command
{
    protected $signature = 'cms:convert-photos {--apply : benar-benar mengganti (tanpa ini hanya rencana)}';

    protected $description = 'Mengganti paragraf penanda foto di halaman yang sudah dibuat dengan blok Gambar kosong berketerangan';

    public function handle(): int
    {
        $locales = Languages::fromConfig()['locales'];
        $rows = [];
        $todo = [];
        foreach (Page::query()->orderBy('id')->get() as $page) {
            $doc = json_decode((string) $page->getRawOriginal('content'), true);
            if (!is_array($doc)) {
                continue;
            }
            [$new, $n] = SitePages::convertPhotos($doc, $locales);
            if ($n > 0) {
                $title = Names::exact($page->getRawOriginal('title'), $locales[0]);
                $rows[] = [$page->id, mb_substr($title, 0, 50), (string) $page->status, $n];
                $todo[] = [$page, $new];
            }
        }

        if ($todo === []) {
            $this->info('Tidak ada paragraf penanda foto yang perlu diganti.');

            return self::SUCCESS;
        }
        $this->table(['ID', 'Halaman', 'Status', 'Foto → Gambar'], $rows);
        if (!$this->option('apply')) {
            $this->line("\nIni baru rencana. Untuk mengganti: php artisan cms:convert-photos --apply");

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($todo as [$page, $new]) {
            try {
                $page->forceFill(['content' => $new])->save();
                $this->info("Diganti: halaman #{$page->id}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Gagal pada halaman #{$page->id}: " . $e->getMessage());
            }
        }
        $this->line("\nSelesai. Buka halaman di editor: tiap tempat foto tampil sebagai blok Gambar kosong; pilih berkasnya di panel kanan (Jelajahi File Manager).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
