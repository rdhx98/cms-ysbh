<?php

namespace App\Content\Blocks;

/**
 * Keterangan berkas untuk tampilan: ukuran yang mudah dibaca dan jenis berkas. Murni (tanpa database) kecuali lookup().
 */
final class FileInfo
{
    /** Ukuran dalam byte -> "850 KB", "1,2 MB" (id) / "1.2 MB" (en). Basis 1024. */
    public static function size(int $bytes, string $lang = 'id'): string
    {
        $bytes = max(0, $bytes);
        $dec = $lang === 'id' ? ',' : '.';

        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $kb = $bytes / 1024;
        if (round($kb) < 1024) {
            return (int) round($kb) . ' KB';
        }
        $mb = $kb / 1024;
        if (round($mb, 1) < 1024) {
            return rtrim(rtrim(number_format($mb, 1, $dec, ''), '0'), $dec) . ' MB';
        }

        return rtrim(rtrim(number_format($mb / 1024, 2, $dec, ''), '0'), $dec) . ' GB';
    }

    private const BY_EXTENSION = [
        'pdf' => ['PDF', 'pdf'],
        'doc' => ['Word', 'doc'], 'docx' => ['Word', 'doc'], 'odt' => ['Word', 'doc'], 'rtf' => ['Word', 'doc'],
        'xls' => ['Excel', 'sheet'], 'xlsx' => ['Excel', 'sheet'], 'ods' => ['Excel', 'sheet'], 'csv' => ['CSV', 'sheet'],
        'ppt' => ['PowerPoint', 'slides'], 'pptx' => ['PowerPoint', 'slides'], 'odp' => ['PowerPoint', 'slides'],
        'zip' => ['ZIP', 'archive'], 'rar' => ['RAR', 'archive'], '7z' => ['7Z', 'archive'],
        'jpg' => ['Gambar', 'image'], 'jpeg' => ['Gambar', 'image'], 'png' => ['Gambar', 'image'], 'webp' => ['Gambar', 'image'], 'gif' => ['Gambar', 'image'],
        'txt' => ['Teks', 'text'],
    ];

    /**
     * Jenis berkas dari nama (ekstensi), lalu dari mime bila ekstensi tak dikenal.
     *
     * @return array{label:string,kind:string} kind: pdf|doc|sheet|slides|archive|image|text|file
     */
    public static function type(string $name, string $mime = ''): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (isset(self::BY_EXTENSION[$ext])) {
            return ['label' => self::BY_EXTENSION[$ext][0], 'kind' => self::BY_EXTENSION[$ext][1]];
        }
        $mime = strtolower($mime);
        if ($mime === 'application/pdf') {
            return ['label' => 'PDF', 'kind' => 'pdf'];
        }
        if (str_starts_with($mime, 'image/')) {
            return ['label' => 'Gambar', 'kind' => 'image'];
        }
        if ($ext !== '' && preg_match('/^[a-z0-9]{1,5}$/D', $ext)) {
            return ['label' => strtoupper($ext), 'kind' => 'file'];
        }

        return ['label' => 'Berkas', 'kind' => 'file'];
    }

    /**
     * Berkas dari model Media berdasarkan ID, dalam SATU query. Berkas yang sudah dihapus (soft delete) tidak ikut.
     *
     * @param int[] $ids
     * @return array<int, array{url:string,name:string,mime:string,size:int}>
     */
    public static function lookup(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $i) => $i > 0)));
        if (!$ids || !class_exists(\App\Models\Media::class)) {
            return [];
        }

        $out = [];
        foreach (\App\Models\Media::query()->whereIn('id', $ids)->get() as $m) {
            $out[(int) $m->id] = ['url' => $m->url(), 'name' => (string) $m->original_name, 'mime' => (string) $m->mime_type, 'size' => (int) $m->size];
        }

        return $out;
    }
}
