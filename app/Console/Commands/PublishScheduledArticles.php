<?php

namespace App\Console\Commands;

use App\Content\ArticleTransitions;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * cms:publish-scheduled — mengubah artikel berstatus Terjadwal yang waktunya sudah tiba menjadi Terbit (dan mencatatnya di riwayat).
 *
 * Tidak WAJIB agar artikel tampil tepat waktu: situs publik sudah menganggap artikel terjadwal yang waktunya tiba sebagai terbit.
 * Perintah ini hanya merapikan status di admin. Jadwalkan tiap menit lewat scheduler Laravel (lihat docs/ARTIKEL-ALUR.md).
 */
class PublishScheduledArticles extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Terbitkan artikel terjadwal yang waktunya sudah tiba';

    public function handle(): int
    {
        $n = ArticleTransitions::publishDue(Post::class);
        $this->info($n === 0 ? 'Tidak ada artikel terjadwal yang jatuh tempo.' : "{$n} artikel diterbitkan.");

        return self::SUCCESS;
    }
}
