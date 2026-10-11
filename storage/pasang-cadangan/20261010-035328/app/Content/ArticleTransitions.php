<?php

namespace App\Content;

use App\Models\ArticleReview;
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
     * @return array{ok:bool,error:?string,to:?string,note:?string,publishedAt:DateTimeImmutable|null|false}
     */
    public static function apply(Model $post, string $action, array $actor, ?string $note = null, ?string $at = null, bool $selfReview = true, ?DateTimeInterface $now = null): array
    {
        $now ??= Carbon::now();
        $from = ArticleWorkflow::normalize($post->status);
        $plan = ArticleWorkflow::plan($from, $action, $actor, $post->user_id ?: null, $note, $at, $now, self::publishedAt($post, $now), $selfReview);
        if (!$plan['ok']) {
            return $plan;
        }

        DB::transaction(function () use ($post, $plan, $from, $action, $actor, $now) {
            $post->status = $plan['to'];
            if ($plan['publishedAt'] !== false) {
                $post->published_at = $plan['publishedAt']?->format('Y-m-d H:i:s');
            }
            $post->save();

            ArticleReview::query()->create([
                'post_id' => $post->getKey(),
                'user_id' => $actor['id'] ?? null,
                'action' => $action,
                'from_status' => $from,
                'to_status' => $plan['to'],
                'note' => $plan['note'],
                'scheduled_for' => $action === 'schedule' ? $plan['publishedAt']->format('Y-m-d H:i:s') : null,
                'created_at' => Carbon::instance($now),
            ]);
        });

        return $plan;
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
