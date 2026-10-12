<?php

namespace App\Content;

use App\Models\ArticleReview;
use App\Models\PostAuthor;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menerapkan transisi status artikel ke database: status + published_at + baris riwayat dalam SATU transaksi
 * (tanpa catatan riwayat, alasan pengembalian/penolakan hilang; maka bila log gagal, status ikut batal).
 * Aturannya sendiri ada di ArticleWorkflow (murni); kelas ini hanya menerapkan hasil plan().
 */
final class ArticleTransitions
{
    /**
     * @param array{id?:int|string|null,reviewer?:bool} $actor
     * @param array{block_id?:?string,block_type?:?string,block_excerpt?:?string,ref_id?:?int} $extra (rilis 50) penanda blok pada pesan, atau pesan yang ditandai selesai/dibuka lagi; hanya kunci berisi yang ditulis
     * @return array{ok:bool,error:?string,to:?string,note:?string,publishedAt:DateTimeImmutable|null|false}
     */
    public static function apply(Model $post, string $action, array $actor, ?string $note = null, ?string $at = null, bool $selfReview = true, ?DateTimeInterface $now = null, bool $reviewerEdits = false, array $extra = []): array
    {
        $now ??= Carbon::now();
        $from = ArticleWorkflow::normalize($post->status);
        // Aturan empat mata: siapa yang mengajukan dibaca dari riwayat di SERVER (bukan dari kiriman browser); tanpa kueri bila aturan mati
        $plan = ArticleWorkflow::plan($from, $action, $actor, self::authorsOf($post), $note, $at, $now, self::publishedAt($post, $now), $selfReview, $selfReview ? null : self::submitterOf($post), $reviewerEdits);
        if (!$plan['ok']) {
            return $plan;
        }

        $pin = array_filter(array_intersect_key($extra, array_flip(['block_id', 'block_type', 'block_excerpt', 'ref_id'])), fn ($v) => $v !== null && $v !== '');

        DB::transaction(function () use ($post, $plan, $from, $action, $actor, $now, $pin) {
            if (!in_array($action, ArticleWorkflow::CHAT_ACTIONS, true)) {   // pesan dan tanda selesai (rilis 49/50) hanya menambah baris riwayat: artikelnya tidak disentuh (updated_at, log perubahan)
                $post->status = $plan['to'];
                if ($plan['publishedAt'] !== false) {
                    $post->published_at = $plan['publishedAt']?->format('Y-m-d H:i:s');
                }
                $post->save();
            }

            ArticleReview::query()->create([
                'post_id' => $post->getKey(),
                'user_id' => $actor['id'] ?? null,
                'action' => $action,
                'from_status' => $from,
                'to_status' => $plan['to'],
                'note' => $plan['note'],
                'scheduled_for' => $action === 'schedule' ? $plan['publishedAt']->format('Y-m-d H:i:s') : null,
                'created_at' => Carbon::instance($now),
            ] + $pin);
        });

        return $plan;
    }

    /**
     * Semua penulis artikel: penulis utama (`posts.user_id`) lalu penulis tambahan (tabel pivot `post_users`), sebagai daftar id string; null bila
     * tidak ada seorang pun (artikel baru / yatim: siapa pun dianggap penulisnya). Tabel belum dimigrasi = hanya penulis utama.
     *
     * @return list<string>|null
     */
    public static function authorsOf(Model $post): ?array
    {
        $ids = [$post->user_id ?: null];
        if ($post->exists) {
            try {
                foreach (PostAuthor::query()->where('post_id', $post->getKey())->orderBy('user_id')->pluck('user_id') as $uid) {
                    $ids[] = $uid;
                }
            } catch (\Illuminate\Database\QueryException) {
                // tabel post_users belum ada (php artisan migrate): hanya penulis utama
            }
        }

        return ArticleWorkflow::authorIds($ids);
    }

    /**
     * Akun yang TERAKHIR mengajukan artikel ini untuk ditinjau (baris riwayat `submit` terbaru), atau null bila belum pernah / tabel riwayat
     * belum ada / pengajuan oleh sistem. Dipakai aturan empat mata (`cms.article_self_review` = false).
     */
    public static function submitterOf(Model $post): int|string|null
    {
        if (!$post->exists) {
            return null;
        }
        try {
            $id = ArticleReview::query()->where('post_id', $post->getKey())->where('action', 'submit')->orderByDesc('id')->value('user_id');
        } catch (\Illuminate\Database\QueryException) {
            return null;   // migrasi belum dijalankan: aturan penulis tetap berlaku; apply() sendiri akan gagal dengan pesan migrate
        }

        return $id === null ? null : $id;
    }

    /**
     * Terbitkan artikel terjadwal yang waktunya sudah tiba (dipanggil `cms:publish-scheduled`). Mengembalikan jumlahnya.
     * Situs publik sudah menganggapnya terbit sejak waktunya (PublicLookup::visible); ini merapikan statusnya di admin.
     */
    public static function publishDue(string $postClass, ?DateTimeInterface $now = null): int
    {
        $now ??= Carbon::now();
        $count = 0;
        $postClass::query()->where('status', ArticleWorkflow::SCHEDULED)->whereNotNull('published_at')->where('published_at', '<=', Carbon::instance($now))
            ->orderBy('id')->each(function (Model $post) use (&$count, $now) {
                DB::transaction(function () use ($post, $now, &$count) {
                    $post->status = ArticleWorkflow::PUBLISHED;
                    $post->save();
                    ArticleReview::query()->create([
                        'post_id' => $post->getKey(), 'user_id' => null, 'action' => 'auto-publish',
                        'from_status' => ArticleWorkflow::SCHEDULED, 'to_status' => ArticleWorkflow::PUBLISHED,
                        'note' => null, 'scheduled_for' => $post->getRawOriginal('published_at'),
                        'created_at' => Carbon::instance($now),
                    ]);
                    $count++;
                });
            });

        return $count;
    }

    /** published_at tersimpan (nilai mentah, dalam zona $now) atau null. */
    public static function publishedAt(Model $post, DateTimeInterface $now): ?DateTimeImmutable
    {
        $raw = $post->getRawOriginal('published_at');
        if ($raw === null || $raw === '') {
            return null;
        }
        try {
            return new DateTimeImmutable((string) $raw, $now->getTimezone());
        } catch (\Throwable) {
            return null;
        }
    }
}
