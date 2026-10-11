<?php

namespace App\Content;

/**
 * Riwayat perubahan (audit trail) satu record dari tabel `activity_log` milik spatie/laravel-activitylog (rilis 42).
 *
 * Kelas ini hanya MEMBACA dan MEMFORMAT; tidak ada yang ditulis ke tabel itu (penulisnya paket Spatie lewat trait LogsActivity di model).
 * Murni selain fetch(): baris masuk sebagai larik, hasilnya larik siap tampil, jadi seluruh aturan bisa diuji tanpa Laravel.
 *
 * Dua bentuk penyimpanan perubahan dibaca sama:
 *   - spatie/laravel-activitylog v5: kolom `attribute_changes` = {"attributes": {baru}, "old": {lama}}
 *   - v4 ke bawah: kolom `properties` = {"attributes": {baru}, "old": {lama}}
 * Bila keduanya ada, attribute_changes didahulukan.
 *
 * Yang SENGAJA tidak ditampilkan: kata sandi/token (HIDDEN, dibuang), cap waktu rutin (updated_at/created_at/deleted_at), dan isi panjang
 * (BULKY: `content`) yang hanya ditulis "diubah" tanpa nilai: isi halaman bukan untuk dibaca sebagai teks mentah di panel samping, dan dilarang
 * disimpan penuh ke log (lihat contoh-kode/audit-log-model.php).
 */
final class AuditTrail
{
    /** Banyak baris perubahan yang ditampilkan per entri; sisanya "N lainnya". */
    public const MAX_CHANGES = 8;

    /** Panjang nilai yang ditampilkan (karakter). */
    public const MAX_VALUE = 80;

    /** Tidak pernah ditampilkan (dibuang sebelum apa pun). */
    public const HIDDEN = ['password', 'remember_token', 'api_token', 'token', 'secret', 'two_factor_secret', 'two_factor_recovery_codes', 'updated_at', 'created_at', 'deleted_at'];

    /** Hanya ditulis "diubah", tanpa nilai lama/baru. */
    public const BULKY = ['content'];

    /** Label kolom yang dikenal (Indonesia). Kolom lain: nama kolom diubah menjadi kata biasa. */
    public const LABELS = [
        'title' => 'Judul',
        'slug' => 'Slug',
        'status' => 'Status',
        'meta_title' => 'Judul SEO',
        'meta_description' => 'Deskripsi SEO',
        'category_id' => 'Kategori',
        'user_id' => 'Penulis',
        'published_at' => 'Waktu terbit',
        'featured_image' => 'Thumbnail',
        'content' => 'Isi',
        'key' => 'Kunci',
        'description' => 'Catatan admin',
        'is_closing' => 'Penutup halaman',
        'sort_order' => 'Urutan',
        'name' => 'Nama',
    ];

    /**
     * Deskripsi baris log yang dikenali (rilis 44): deskripsi => [label, nada]. Ditulis oleh App\Models\Concerns\RecordsChanges
     * (`status_<nilai>` saat status berubah) dan, untuk `status_*` artikel, sudah dipakai model Post lama. Hanya berlaku untuk kejadian `updated`.
     * Deskripsi `status_*` lain (status kustom) = "Status diubah".
     */
    public const DESCRIPTIONS = [
        'status_draft' => ['Dikembalikan ke draf', 'warn'],
        'status_review' => ['Diajukan untuk ditinjau', 'info'],
        'status_published' => ['Diterbitkan', 'success'],
        'status_scheduled' => ['Dijadwalkan terbit', 'info'],
        'status_rejected' => ['Ditolak', 'danger'],
        'status_archived' => ['Diarsipkan', 'neutral'],
        'status_online' => ['Diaktifkan (online)', 'success'],
        'status_offline' => ['Dinonaktifkan (offline)', 'warn'],
    ];

    /** Deskripsi bawaan model lama yang sama artinya dengan "tanpa keterangan". */
    public const PLAIN_DESCRIPTIONS = ['updated_general', 'unknown'];

    /** Cap waktu tampil. */
    public const DATE_FORMAT = 'd M Y H:i';

    /**
     * Baris `activity_log` untuk satu record, terbaru dulu. $query = pembangun kueri atas tabel itu (DB::table('activity_log')); dibuat di
     * pemanggil agar kelas ini tidak bergantung pada fasad. $subjectTypes: nama morph DAN nama kelas (log lama sebelum morph map dipasang).
     * Meminta satu baris lebih untuk mengetahui masih ada lagi atau tidak.
     *
     * @param list<string> $subjectTypes
     * @return array{rows:list<array<string,mixed>>,more:bool}
     */
    public static function fetch(object $query, array $subjectTypes, int|string $subjectId, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min(100, $limit));
        $rows = $query
            ->whereIn('subject_type', array_values(array_unique(array_filter($subjectTypes, fn ($t) => is_string($t) && $t !== ''))))
            ->where('subject_id', $subjectId)
            ->orderByDesc('id')
            ->offset(max(0, $offset))
            ->limit($limit + 1)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
        $more = count($rows) > $limit;

        return ['rows' => array_slice($rows, 0, $limit), 'more' => $more];
    }

    /**
     * Baris -> entri tampil.
     *
     * @param list<array<string,mixed>>  $rows
     * @param array<int|string,string>   $names   id pengguna -> nama
     * @param array<string,array<int|string,string>> $lookups kolom -> (nilai -> teks), mis. ['category_id' => [3 => 'Kesehatan'], 'status' => [...]]
     * @return list<array{id:int,event:string,label:string,tone:string,who:string,at:string,note:string,changes:list<array{field:string,label:string,old:?string,new:?string,bulk:bool}>,more:int}>
     */
    public static function entries(array $rows, array $names = [], array $lookups = []): array
    {
        return array_map(fn (array $row) => self::entry($row, $names, $lookups), $rows);
    }

    /** @return array{id:int,event:string,label:string,tone:string,who:string,at:string,note:string,changes:list<array{field:string,label:string,old:?string,new:?string,bulk:bool}>,more:int} */
    public static function entry(array $row, array $names = [], array $lookups = []): array
    {
        $event = self::event($row);
        $causer = $row['causer_id'] ?? null;
        $changes = self::changes(self::attributes($row), $event, $lookups);
        $more = max(0, count($changes) - self::MAX_CHANGES);
        $known = self::knownDescription($row, $event);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'event' => $event,
            'label' => $known[0] ?? self::eventLabel($event),
            'tone' => $known[1] ?? self::tone($event),
            'who' => ($causer === null || $causer === '') ? 'Sistem' : (string) ($names[$causer] ?? ('#' . $causer)),
            'at' => self::when($row['created_at'] ?? null),
            'note' => self::note($row, $event),
            'changes' => array_slice($changes, 0, self::MAX_CHANGES),
            'more' => $more,
        ];
    }

    /** Jenis kejadian: kolom `event`; log lama tanpa kolom itu: dari deskripsi ("created"/"updated"/"deleted"). */
    public static function event(array $row): string
    {
        $event = strtolower(trim((string) ($row['event'] ?? '')));
        if ($event === '') {
            $event = strtolower(trim((string) ($row['description'] ?? '')));
        }

        return $event === '' ? 'updated' : $event;
    }

    /** Label + nada dari deskripsi yang dikenali (hanya untuk kejadian "updated"), atau null. @return array{0:string,1:string}|null */
    public static function knownDescription(array $row, string $event): ?array
    {
        if ($event !== 'updated') {
            return null;
        }
        $d = strtolower(trim((string) ($row['description'] ?? '')));
        if (isset(self::DESCRIPTIONS[$d])) {
            return self::DESCRIPTIONS[$d];
        }

        return str_starts_with($d, 'status_') ? ['Status diubah', 'info'] : null;
    }

    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'content_updated' => 'Isi diubah',
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'forceDeleted', 'force_deleted' => 'Dihapus permanen',
            default => ucfirst(str_replace('_', ' ', mb_substr($event, 0, 40))),
        };
    }

    public static function tone(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted', 'forceDeleted', 'force_deleted' => 'danger',
            'restored' => 'warn',
            'content_updated' => 'info',
            default => 'neutral',
        };
    }

    /**
     * Perubahan mentah satu baris: ['attributes' => [...baru], 'old' => [...lama]]. Dua bentuk (v5 attribute_changes / v4 properties),
     * kolom boleh berupa teks JSON atau larik. Bentuk yang rusak = kosong, tidak pernah galat.
     *
     * @return array{attributes:array<string,mixed>,old:array<string,mixed>}
     */
    public static function attributes(array $row): array
    {
        foreach (['attribute_changes', 'properties'] as $column) {
            $data = self::decode($row[$column] ?? null);
            if (is_array($data) && (is_array($data['attributes'] ?? null) || is_array($data['old'] ?? null))) {
                return ['attributes' => is_array($data['attributes'] ?? null) ? $data['attributes'] : [], 'old' => is_array($data['old'] ?? null) ? $data['old'] : []];
            }
        }

        return ['attributes' => [], 'old' => []];
    }

    /**
     * @param array{attributes:array<string,mixed>,old:array<string,mixed>} $attrs
     * @return list<array{field:string,label:string,old:?string,new:?string,bulk:bool}>
     */
    private static function changes(array $attrs, string $event, array $lookups): array
    {
        $fields = array_values(array_unique(array_merge(array_keys($attrs['attributes']), array_keys($attrs['old']))));
        $out = [];
        foreach ($fields as $field) {
            $field = (string) $field;
            if (in_array($field, self::HIDDEN, true) || !preg_match('/^[A-Za-z0-9_.\-]{1,64}$/D', $field)) {
                continue;
            }
            $hasNew = array_key_exists($field, $attrs['attributes']);
            $hasOld = array_key_exists($field, $attrs['old']);
            $new = $attrs['attributes'][$field] ?? null;
            $old = $attrs['old'][$field] ?? null;
            if ($hasNew && $hasOld && self::same($old, $new)) {
                continue;
            }
            if (in_array($field, self::BULKY, true)) {
                $out[] = ['field' => $field, 'label' => self::label($field), 'old' => null, 'new' => null, 'bulk' => true];
                continue;
            }
            $out[] = [
                'field' => $field,
                'label' => self::label($field),
                'old' => $hasOld ? self::value($field, $old, $lookups) : null,
                'new' => $hasNew ? self::value($field, $new, $lookups) : null,
                'bulk' => false,
            ];
        }

        return $out;
    }

    public static function label(string $field): string
    {
        return self::LABELS[$field] ?? ucfirst(str_replace(['_', '.'], ' ', $field));
    }

    /**
     * Satu nilai untuk dibaca manusia: satu baris, tanpa tag, dipendekkan. Peta bahasa ({"id":"…","en":"…"}) -> "ID: … · EN: …".
     * $lookups[kolom][nilai] menggantikan ID dengan nama (kategori, penulis, label status).
     */
    public static function value(string $field, mixed $value, array $lookups = []): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_string($value) && ($decoded = self::decode($value)) !== null && is_array($decoded)) {
            $value = $decoded;
        }
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $k => $v) {
                if (is_scalar($v) && trim((string) $v) !== '') {
                    $parts[] = (is_string($k) && preg_match('/^[a-z]{2,3}$/D', $k) ? strtoupper($k) . ': ' : '') . self::clean((string) $v);
                }
            }

            return $parts === [] ? '—' : self::cut(implode(' · ', $parts));
        }

        $key = is_scalar($value) ? (string) $value : '';
        if ($key !== '' && isset($lookups[$field][$key])) {
            return self::cut(self::clean((string) $lookups[$field][$key]));
        }
        if ($field === 'featured_image') {
            return ctype_digit($key) ? 'Media #' . $key : ($key === 'default.webp' ? '—' : self::cut(self::clean($key)));
        }
        if ($field === 'published_at') {
            return self::when($key) ?: self::cut(self::clean($key));
        }
        if ($field === 'is_closing') {
            return in_array($key, ['1', 'true'], true) ? 'Ya' : 'Tidak';
        }
        if (in_array($field, ['category_id', 'user_id'], true) && ctype_digit($key)) {
            return '#' . $key;
        }

        return self::cut(self::clean($key));
    }

    /** Cap waktu tampil dari teks / DateTimeInterface; '' bila tidak terbaca. */
    public static function when(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(self::DATE_FORMAT);
        }
        if (!is_string($value) || trim($value) === '' || ($t = strtotime($value)) === false) {
            return '';
        }

        return date(self::DATE_FORMAT, $t);
    }

    /** Keterangan tambahan: deskripsi bila bukan sekadar nama kejadian (mis. entri kustom "Artikel diajukan"). */
    private static function note(array $row, string $event): string
    {
        $d = trim((string) ($row['description'] ?? ''));
        if ($d === '' || strtolower($d) === $event || in_array(strtolower($d), self::PLAIN_DESCRIPTIONS, true) || self::knownDescription($row, $event) !== null) {
            return '';   // sama dengan nama kejadian, bawaan model lama, atau sudah dipakai sebagai label
        }

        return self::cut(self::clean($d), 120);
    }

    private static function same(mixed $a, mixed $b): bool
    {
        $norm = fn ($v) => is_array($v) ? json_encode($v) : (is_bool($v) ? (int) $v : (is_scalar($v) ? (string) $v : json_encode($v)));

        return $norm($a) === $norm($b);
    }

    private static function clean(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private static function cut(string $text, int $max = self::MAX_VALUE): string
    {
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }

    private static function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '' || !in_array($value[0], ['{', '['], true)) {
            return null;
        }
        $data = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }
}
