<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris riwayat alur persetujuan artikel (lihat App\Content\ArticleWorkflow dan ArticleTransitions). Hanya ditambah, tidak diubah.
 */
class ArticleReview extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'article_reviews';

    protected $guarded = [];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'created_at' => 'datetime',
    ];
}
