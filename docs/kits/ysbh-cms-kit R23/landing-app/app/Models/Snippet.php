<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Snippet extends ReadOnlyModel
{
    protected $table = 'snippets';

    protected function casts(): array
    {
        return ['is_closing' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    /** Snippet yang tampil otomatis di akhir halaman, berurutan. */
    public function scopeClosing(Builder $query): Builder
    {
        return $query->online()->where('is_closing', true)->orderBy('sort_order')->orderBy('id');
    }
}
