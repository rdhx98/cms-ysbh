<?php

namespace App\Livewire\Traits;

use App\Content\JsonSql;
use App\Content\Names;

/**
 * Pencarian tujuan tautan (halaman / artikel) untuk pemilih tautan di inspektur. Dipanggil dari browser lewat
 * $wire.searchLinkTargets(jenis, kata) dan mengembalikan larik kecil [{id, label, hint, slug}] (maks. 8). Hanya id, judul, status, dan slug keluar (slug = alamat publik, dipakai
 * tautan internal://page|article/{slug} di editor teks kaya).
 */
trait SearchesLinkTargets
{
    /** @return list<array{id:int,label:string,hint:string,slug:string}> */
    public function searchLinkTargets(string $kind, string $q): array
    {
        abort_unless(auth()->check(), 403);

        $q = trim(mb_substr($q, 0, 80));
        if ($kind === 'category') {
            return $this->searchCategories($q);
        }
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
                'slug' => Names::of($row->getRawOriginal('slug'), 'en'),   // slug bahasa bawaan (EN); resolver menerima slug bahasa mana pun
            ])
            ->all();
    }

    /**
     * Kategori untuk pemilih kategori blok Artikel Terbaru (rilis 48). Tabel kecil: dibaca (maks. 200) lalu disaring di PHP supaya nama
     * berbentuk apa pun (peta bahasa / teks polos) tercocokkan sama. $q kosong = daftar awal. `hint` = jumlah artikel kategori itu.
     *
     * @return list<array{id:int,label:string,hint:string,slug:string}>
     */
    private function searchCategories(string $q): array
    {
        $locales = ($this->activeLocales ?? []) ?: ['id', 'en'];
        $needle = mb_strtolower($q);
        $out = [];
        foreach (\App\Models\Category::query()->orderBy('id')->limit(200)->get() as $row) {
            $raw = $row->getRawOriginal('name');
            $label = Names::of($raw, app()->getLocale()) ?: '#' . $row->getKey();
            $hay = mb_strtolower($label);
            foreach ($locales as $locale) {
                $hay .= ' ' . mb_strtolower((string) Names::of($raw, $locale));
            }
            if ($needle !== '' && !str_contains($hay, $needle)) {
                continue;
            }
            $out[] = ['id' => (int) $row->getKey(), 'label' => $label, 'hint' => '', 'slug' => ''];
            if (count($out) >= 8) {
                break;
            }
        }

        return $out;
    }
}
