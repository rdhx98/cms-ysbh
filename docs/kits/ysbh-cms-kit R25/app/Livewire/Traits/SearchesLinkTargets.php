<?php

namespace App\Livewire\Traits;

use App\Content\JsonSql;
use App\Content\Names;

/**
 * Pencarian tujuan tautan (halaman / artikel) untuk pemilih tautan di inspektur. Dipanggil dari browser lewat
 * $wire.searchLinkTargets(jenis, kata) dan mengembalikan larik kecil [{id, label, hint}] (maks. 8). Hanya id dan judul keluar.
 */
trait SearchesLinkTargets
{
    /** @return list<array{id:int,label:string,hint:string}> */
    public function searchLinkTargets(string $kind, string $q): array
    {
        abort_unless(auth()->check(), 403);

        $q = trim(mb_substr($q, 0, 80));
        if (mb_strlen($q) < 2 || !in_array($kind, ['page', 'article'], true)) {
            return [];
        }

        $model = $kind === 'page' ? \App\Models\Page::class : \App\Models\Post::class;
        $locales = ($this->activeLocales ?? []) ?: ['id', 'en'];
        $like = JsonSql::like($q);
        $expr = JsonSql::locale($model::query()->getConnection()->getDriverName(), 'title') . " LIKE ? ESCAPE '!'";

        return $model::query()
            ->where(function ($where) use ($locales, $like, $expr) {
                foreach ($locales as $locale) {
                    $where->orWhereRaw($expr, [JsonSql::path($locale), $like]);
                }
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->getKey(),
                'label' => Names::of($row->getRawOriginal('title'), app()->getLocale()) ?: '#' . $row->getKey(),
                'hint' => (string) $row->status,
            ])
            ->all();
    }
}
