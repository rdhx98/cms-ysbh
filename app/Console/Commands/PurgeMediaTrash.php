<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

/**
 * cms:purge-media-trash — membuang PERMANEN berkas di Sampah File Manager yang sudah lebih dari 30 hari (berkas fisik ikut dihapus).
 *
 * Tidak wajib: File Manager juga membuang yang kedaluwarsa setiap kali Sampah dibuka. Perintah ini untuk membersihkan tanpa menunggu ada
 * yang membuka Sampah; jadwalkan harian lewat scheduler Laravel (lihat docs/MEDIA-MANAGER.md). `--dry-run` hanya menghitung.
 */
class PurgeMediaTrash extends Command
{
    protected $signature = 'cms:purge-media-trash {--days=30 : batas hari di Sampah} {--dry-run : hanya hitung, jangan hapus}';

    protected $description = 'Buang permanen berkas di Sampah File Manager yang sudah melewati batas hari';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');

        $r = Media::purgeExpiredTrash($days, $dry);

        $this->info(($dry ? '[uji coba] ' : '')."{$r['purged']} berkas ".($dry ? 'akan dibuang' : 'dibuang permanen')." (di Sampah lebih dari {$days} hari).");
        if ($r['skipped'] > 0) {
            $this->warn("{$r['skipped']} berkas dilewati karena ternyata masih dipakai.");
        }

        return self::SUCCESS;
    }
}
