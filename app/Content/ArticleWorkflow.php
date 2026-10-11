<?php

namespace App\Content;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Jalur persetujuan artikel (rilis 38). Murni, tanpa Laravel: satu-satunya sumber "siapa boleh mengubah status apa".
 *
 *   penulis (bukan admin/editor): menulis, menyimpan sebagai DRAF, mengajukan ke tinjauan, menarik kembali pengajuannya.
 *   admin & editor (peninjau)   : dari "Ditinjau" memutuskan: terbitkan sekarang / jadwalkan / kembalikan ke draf (wajib alasan) /
 *                                 tolak total (wajib alasan; TIDAK bisa dilanjutkan lagi).
 *   tidak ada jalan pintas      : draf tidak bisa langsung terbit atau terjadwal, untuk siapa pun (admin juga harus melewati
 *                                 "Ditinjau"; yang menerbitkan boleh diri sendiri atau tidak, diatur `cms.article_self_review`).
 *
 * Status:  draft -> review -> published | scheduled | draft (dikembalikan) | rejected (final)
 *          published/scheduled -> draft (ditarik) ; published -> archived ; archived -> draft
 * Status ditentukan HANYA lewat aksi di sini (builder memanggil plan() di server); menyimpan isi tidak pernah mengubah status.
 *
 * Waktu terbit memakai kolom `published_at` yang sudah ada: Terjadwal = status `scheduled` + `published_at` di masa depan; artikel
 * terjadwal yang waktunya tiba dianggap terbit oleh situs publik (isPublic()) walau perintah `cms:publish-scheduled` belum berjalan.
 */
final class ArticleWorkflow
{
    public const DRAFT = 'draft';
    public const REVIEW = 'review';
    public const PUBLISHED = 'published';
    public const SCHEDULED = 'scheduled';
    public const ARCHIVED = 'archived';
    public const REJECTED = 'rejected';

    public const NOTE_MAX = 500;
    public const SCHEDULE_MAX_DAYS = 1095;

    /** status => [aksi => status tujuan] */
    public const TRANSITIONS = [
        self::DRAFT => ['submit' => self::REVIEW],
        self::REVIEW => ['recall' => self::DRAFT, 'publish' => self::PUBLISHED, 'schedule' => self::SCHEDULED, 'return' => self::DRAFT, 'reject' => self::REJECTED],
        self::SCHEDULED => ['publish' => self::PUBLISHED, 'schedule' => self::SCHEDULED, 'withdraw' => self::DRAFT],
        self::PUBLISHED => ['withdraw' => self::DRAFT, 'archive' => self::ARCHIVED],
        self::ARCHIVED => ['restore' => self::DRAFT],
        self::REJECTED => [],
    ];

    /** Urutan tampil = urutan di sini. Kunci: label, tone (primary|success|info|warn|danger|neutral), note (none|optional|required), date, reviewer (hanya peninjau). */
    public const ACTIONS = [
        'submit' => ['label' => 'Ajukan review', 'tone' => 'primary', 'note' => 'optional', 'date' => false, 'reviewer' => false],
        'recall' => ['label' => 'Tarik kembali ke draf', 'tone' => 'neutral', 'note' => 'none', 'date' => false, 'reviewer' => false],
        'publish' => ['label' => 'Terbitkan sekarang', 'tone' => 'success', 'note' => 'optional', 'date' => false, 'reviewer' => true],
        'schedule' => ['label' => 'Jadwalkan terbit', 'tone' => 'info', 'note' => 'optional', 'date' => true, 'reviewer' => true],
        'return' => ['label' => 'Kembalikan ke draf', 'tone' => 'warn', 'note' => 'required', 'date' => false, 'reviewer' => true],
        'reject' => ['label' => 'Tolak (tidak bisa dilanjutkan)', 'tone' => 'danger', 'note' => 'required', 'date' => false, 'reviewer' => true],
        'withdraw' => ['label' => 'Tarik ke draf', 'tone' => 'warn', 'note' => 'optional', 'date' => false, 'reviewer' => true],
        'archive' => ['label' => 'Arsipkan', 'tone' => 'neutral', 'note' => 'none', 'date' => false, 'reviewer' => true],
        'restore' => ['label' => 'Pulihkan ke draf', 'tone' => 'neutral', 'note' => 'none', 'date' => false, 'reviewer' => true],
    ];

    // ------------------------------------------------------------------ peran

    /** @param array{id?:int|string|null,reviewer?:bool} $actor */
    public static function isReviewer(array $actor): bool
    {
        return (bool) ($actor['reviewer'] ?? false);
    }

    /** $ownerId null = artikel baru (belum punya penulis): pemakai saat ini akan menjadi penulisnya. */
    public static function isOwner(array $actor, int|string|null $ownerId): bool
    {
        $id = $actor['id'] ?? null;

        return $ownerId === null || ($id !== null && (string) $id === (string) $ownerId);
    }

    /** Penulis hanya melihat artikelnya sendiri; admin/editor melihat semuanya. */
    public static function canView(array $actor, int|string|null $ownerId): bool
    {
        return self::isReviewer($actor) || self::isOwner($actor, $ownerId);
    }

    /**
     * Boleh MENGUBAH isi (menyimpan)? Ditolak tidak bisa diubah siapa pun. Penulis: hanya draf miliknya (dalam tinjauan terkunci).
     * Peninjau: semua status lain (memperbaiki salah ketik sebelum terbit, dll.); status tidak berubah oleh penyimpanan.
     */
    public static function canEdit(?string $status, array $actor, int|string|null $ownerId): bool
    {
        $status = self::normalize($status);
        if ($status === self::REJECTED || !self::canView($actor, $ownerId)) {
            return false;
        }

        return self::isReviewer($actor) || $status === self::DRAFT;
    }

    public static function isFinal(?string $status): bool
    {
        return self::normalize($status) === self::REJECTED;
    }

    // ------------------------------------------------------------------ aksi

    public static function normalize(?string $status): string
    {
        return $status !== null && isset(self::TRANSITIONS[$status]) ? $status : self::DRAFT;
    }

    /** Keputusan tinjauan: aksi yang dibatasi aturan empat mata (`cms.article_self_review` = false). */
    public const DECISIONS = ['publish', 'schedule', 'return', 'reject'];

    /** Akun ini yang mengajukan artikel (pengajuan TERAKHIR)? $submitterId null = tidak diketahui/belum pernah diajukan. */
    public static function isSubmitter(array $actor, int|string|null $submitterId): bool
    {
        $id = $actor['id'] ?? null;

        return $id !== null && $submitterId !== null && (string) $id === (string) $submitterId;
    }

    /**
     * Aturan empat mata (rilis 41): bila `cms.article_self_review` mati, akun yang MENULIS artikel atau yang MENGAJUKANNYA (pengajuan terakhir)
     * tidak boleh memutuskannya. Mengembalikan 'author' | 'submitter' | null (null = tidak terhalang). Hanya relevan untuk peninjau.
     */
    public static function conflict(array $actor, int|string|null $ownerId, int|string|null $submitterId, bool $selfReview): ?string
    {
        if ($selfReview) {
            return null;
        }
        if ($ownerId !== null && self::isOwner($actor, $ownerId)) {
            return 'author';
        }

        return self::isSubmitter($actor, $submitterId) ? 'submitter' : null;
    }

    /**
     * @param int|string|null $submitterId akun yang terakhir mengajukan artikel ini (ArticleTransitions::submitterOf); hanya dipakai bila $selfReview = false
     */
    public static function allows(?string $status, string $action, array $actor, int|string|null $ownerId, bool $selfReview = true, int|string|null $submitterId = null): bool
    {
        $status = self::normalize($status);
        if (!isset(self::TRANSITIONS[$status][$action]) || !self::canView($actor, $ownerId)) {
            return false;
        }
        $owner = self::isOwner($actor, $ownerId);
        if ($action === 'recall') {
            // menarik pengajuan = hak penulisnya. Bila aturan empat mata menyala, yang MENGAJUKAN (mis. admin yang mengajukan draf orang lain)
            // juga boleh menariknya: ia tidak boleh "Kembalikan ke draf" karena itu keputusan tinjauan.
            return $owner || (!$selfReview && self::isSubmitter($actor, $submitterId));
        }
        if ($action === 'submit') {
            return $owner || self::isReviewer($actor);   // admin/editor boleh mengajukan draf yang tertahan (mis. penulisnya sudah tidak ada)
        }
        if (!self::isReviewer($actor)) {
            return false;
        }
        // keputusan atas artikel yang Anda tulis atau ajukan sendiri: boleh hanya bila `cms.article_self_review` menyala. Menarik/
        // mengarsipkan/memulihkan bukan "keputusan tinjauan", jadi tidak dibatasi.
        if (in_array($action, self::DECISIONS, true) && self::conflict($actor, $ownerId, $submitterId, $selfReview) !== null) {
            return false;
        }

        return true;
    }

    /**
     * Aksi yang tersedia untuk pengguna ini pada status ini, siap dipakai tampilan.
     *
     * @return list<array{action:string,label:string,tone:string,note:string,date:bool,to:string}>
     */
    public static function actionsFor(?string $status, array $actor, int|string|null $ownerId, bool $selfReview = true, int|string|null $submitterId = null): array
    {
        $status = self::normalize($status);
        $out = [];
        foreach (self::ACTIONS as $action => $meta) {
            if (!self::allows($status, $action, $actor, $ownerId, $selfReview, $submitterId)) {
                continue;
            }
            $label = $meta['label'];
            if ($action === 'schedule' && $status === self::SCHEDULED) {
                $label = 'Ubah jadwal';
            }
            $out[] = ['action' => $action, 'label' => $label, 'tone' => $meta['tone'], 'note' => $meta['note'], 'date' => $meta['date'], 'to' => self::TRANSITIONS[$status][$action]];
        }

        return $out;
    }

    /**
     * Rencana sebuah transisi, dihitung di SERVER. Tidak menyentuh apa pun; pemanggil menerapkan hasilnya.
     *
     * @param array{id?:int|string|null,reviewer?:bool} $actor
     * @param string|null $at waktu terbit (aksi "schedule"): "2026-10-20T09:00" atau "2026-10-20 09:00[:00]", zona waktu = zona $now
     * @return array{ok:bool,error:?string,to:?string,note:?string,publishedAt:DateTimeImmutable|null|false}
     *         publishedAt: false = jangan diubah; null = kosongkan; DateTimeImmutable = isi dengan itu
     */
    public static function plan(?string $status, string $action, array $actor, int|string|null $ownerId, ?string $note, ?string $at, DateTimeInterface $now, ?DateTimeInterface $publishedAt = null, bool $selfReview = true, int|string|null $submitterId = null): array
    {
        $status = self::normalize($status);
        $fail = fn (string $e) => ['ok' => false, 'error' => $e, 'to' => null, 'note' => null, 'publishedAt' => false];

        if (!isset(self::ACTIONS[$action])) {
            return $fail('Aksi tidak dikenal.');
        }
        if ($status === self::REJECTED) {
            return $fail('Artikel yang ditolak tidak bisa dilanjutkan.');
        }
        if (!self::allows($status, $action, $actor, $ownerId, $selfReview, $submitterId)) {
            if (in_array($action, self::DECISIONS, true) && self::isReviewer($actor) && ($why = self::conflict($actor, $ownerId, $submitterId, $selfReview)) !== null && isset(self::TRANSITIONS[$status][$action])) {
                return $fail('Aturan empat mata: artikel yang ' . ($why === 'author' ? 'Anda tulis' : 'Anda ajukan') . ' harus diputuskan admin atau editor lain.');
            }

            return $fail(isset(self::TRANSITIONS[$status][$action]) ? 'Anda tidak berwenang melakukan aksi ini.' : 'Aksi ini tidak tersedia untuk status "' . $status . '".');
        }

        $meta = self::ACTIONS[$action];
        $note = $note === null ? '' : trim(preg_replace('/\R{3,}/u', "\n\n", str_replace("\r\n", "\n", $note)) ?? '');
        if ($meta['note'] === 'required' && $note === '') {
            return $fail('Alasan wajib diisi.');
        }
        if (mb_strlen($note) > self::NOTE_MAX) {
            return $fail('Catatan maksimal ' . self::NOTE_MAX . ' karakter.');
        }
        if ($meta['note'] === 'none') {
            $note = '';
        }

        $nowI = DateTimeImmutable::createFromInterface($now);
        $to = self::TRANSITIONS[$status][$action];
        $published = false;

        if ($action === 'schedule') {
            $when = self::parseAt($at, $nowI);
            if ($when === null) {
                return $fail('Tanggal dan jam terbit wajib diisi dengan benar.');
            }
            if ($when <= $nowI) {
                return $fail('Waktu terbit harus di masa depan.');
            }
            if ($when > $nowI->modify('+' . self::SCHEDULE_MAX_DAYS . ' days')) {
                return $fail('Waktu terbit terlalu jauh (maksimal ' . self::SCHEDULE_MAX_DAYS . ' hari).');
            }
            $published = $when;
        } elseif ($action === 'publish') {
            // tanggal terbit pertama dipertahankan; jadwal yang belum tiba diganti "sekarang"
            $published = ($publishedAt === null || $publishedAt > $nowI) ? $nowI : false;
        } elseif ($action === 'withdraw' && $status === self::SCHEDULED && $publishedAt !== null && $publishedAt > $nowI) {
            $published = null;   // jadwal yang belum tiba dibatalkan: tidak ada tanggal terbit
        }

        return ['ok' => true, 'error' => null, 'to' => $to, 'note' => $note === '' ? null : $note, 'publishedAt' => $published];
    }

    /** "2026-10-20T09:00", "2026-10-20 09:00" atau dengan detik -> waktu di zona $ref; selain itu (termasuk tanggal mustahil) null. */
    public static function parseAt(?string $at, DateTimeImmutable $ref): ?DateTimeImmutable
    {
        $at = trim((string) $at);
        if ($at === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/D', $at, $m)) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) ($m[6] ?? 0) > 59) {
            return null;
        }

        return (new DateTimeImmutable('now', $ref->getTimezone()))->setDate((int) $m[1], (int) $m[2], (int) $m[3])->setTime((int) $m[4], (int) $m[5], (int) ($m[6] ?? 0));
    }

    // ------------------------------------------------------------------ situs publik

    /** Tampil di situs publik? Terbit, atau terjadwal yang waktunya sudah tiba. (Padanan SQL: PublicLookup::visible().) */
    public static function isPublic(?string $status, ?DateTimeInterface $publishedAt, DateTimeInterface $now): bool
    {
        return $status === self::PUBLISHED || ($status === self::SCHEDULED && $publishedAt !== null && $publishedAt <= $now);
    }

    // ------------------------------------------------------------------ tampilan

    /** Warna lencana status. */
    public static function tone(?string $status): string
    {
        return match (self::normalize($status)) {
            self::DRAFT => 'neutral',
            self::REVIEW => 'warn',
            self::SCHEDULED => 'info',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'neutral',
            self::REJECTED => 'danger',
        };
    }

    /** Kata kerja untuk baris riwayat: "<nama> <kata kerja>". */
    public static function verb(string $action): string
    {
        return [
            'submit' => 'mengajukan untuk ditinjau', 'recall' => 'menarik kembali ke draf', 'publish' => 'menerbitkan', 'schedule' => 'menjadwalkan terbit',
            'return' => 'mengembalikan ke draf', 'reject' => 'menolak', 'withdraw' => 'menarik ke draf', 'archive' => 'mengarsipkan',
            'restore' => 'memulihkan ke draf', 'auto-publish' => 'menerbitkan otomatis sesuai jadwal',
        ][$action] ?? $action;
    }

    /** Pesan sukses setelah aksi. $when: waktu terbit untuk "schedule". */
    public static function done(string $action, ?DateTimeInterface $when = null): string
    {
        return match ($action) {
            'submit' => 'Artikel diajukan untuk ditinjau.',
            'recall' => 'Pengajuan ditarik. Artikel kembali menjadi draf.',
            'publish' => 'Artikel diterbitkan.',
            'schedule' => 'Artikel dijadwalkan terbit' . ($when ? ' pada ' . $when->format('d M Y H:i') : '') . '.',
            'return' => 'Artikel dikembalikan ke draf bersama alasannya.',
            'reject' => 'Artikel ditolak dan tidak bisa dilanjutkan.',
            'withdraw' => 'Artikel ditarik ke draf.',
            'archive' => 'Artikel diarsipkan.',
            'restore' => 'Artikel dipulihkan ke draf.',
            default => 'Selesai.',
        };
    }

    /** Kalimat penjelas untuk pengguna ini pada status ini. */
    public static function hint(?string $status, array $actor, int|string|null $ownerId, bool $selfReview = true, int|string|null $submitterId = null): string
    {
        $status = self::normalize($status);
        $reviewer = self::isReviewer($actor);
        $own = self::isOwner($actor, $ownerId);
        $why = $reviewer ? self::conflict($actor, $ownerId, $submitterId, $selfReview) : null;   // aturan empat mata (rilis 41)

        return match ($status) {
            self::DRAFT => $own || $reviewer
                ? ($reviewer && !$own ? 'Draf milik penulis lain. Penulis yang mengajukannya untuk ditinjau.' : 'Tulis dan simpan sebagai draf. Bila sudah siap, ajukan agar ditinjau admin atau editor.')
                : '',
            self::REVIEW => $why !== null
                ? 'Menunggu keputusan admin atau editor LAIN: artikel ini ' . ($why === 'author' ? 'Anda tulis' : 'Anda ajukan') . ', dan aturan empat mata tidak membolehkan Anda memutuskannya.' . ($why === 'submitter' || $own ? ' Salah ajukan? Tarik kembali ke draf.' : '')
                : ($reviewer
                    ? 'Menunggu keputusan Anda: terbitkan, jadwalkan, kembalikan ke penulis dengan alasan, atau tolak total.'
                    : 'Sedang ditinjau admin atau editor. Isi terkunci; tarik kembali ke draf bila ingin mengubahnya.'),
            self::SCHEDULED => 'Sudah disetujui dan menunggu waktu terbit. Tampil di situs otomatis saat waktunya tiba.',
            self::PUBLISHED => 'Tampil di situs. Mengubah artikel terbit hanya oleh admin atau editor; menariknya ke draf berarti harus ditinjau lagi.',
            self::ARCHIVED => 'Tidak tampil di situs.',
            self::REJECTED => 'Ditolak dan tidak bisa dilanjutkan. Isi tidak bisa diubah. Buat artikel baru bila perlu.',
        };
    }
}
