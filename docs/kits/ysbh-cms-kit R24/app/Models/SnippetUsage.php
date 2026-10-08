<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Table('snippet_usages')]
#[Fillable('snippet_id', 'usable_type', 'usable_id')]
class SnippetUsage extends Model
{
    public function snippet(): BelongsTo
    {
        return $this->belongsTo(Snippet::class)->withTrashed();
    }

    /** Page, Post, ... yang memuat blok snippet ini. */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
