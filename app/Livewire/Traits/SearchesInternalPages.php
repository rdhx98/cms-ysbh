<?php

namespace App\Livewire\Traits;

use App\Content\JsonSql;
use App\Content\Names;

/**
 * searchInternalPages($keyword): pencarian halaman + artikel untuk tautan di dalam teks berformat (Tiptap), dipindahkan dari page-editor lama.
 * Bentuk keluaran SAMA dengan yang lama, jadi kode Alpine/Tiptap yang memakainya tidak berubah:
 *     [['title' => '📄 Tentang Kami', 'url' => 'internal://page/tentang-kami'], ['title' => '📝 …', 'url' => 'internal://article/…']]
 * URL semu internal:// diterjemahkan Page::getParsedContentAttribute saat ditampilkan.
 *
 * Bedanya dengan versi lama: (1) tahan baris yang kolomnya bukan JSON (tidak melempar "Invalid JSON text"); (2) karakter % dan _ dari
 * pengguna diperlakukan literal; (3) slug dibaca dari nilai mentah per bahasa — versi lama mencetak "Array" untuk artikel (Post tidak
 * memakai HasTranslations) dan mencetak "internal://page/" kosong bila slug belum ada; (4) hanya untuk pengguna yang sudah masuk.
 */
trait SearchesInternalPages
{
    /** @return list<array{title:string,url:string}> */
    public function searchInternalPages($keyword)
    {
        abort_unless(auth()->check(), 403);

        $keyword = mb_strtolower(trim(mb_substr((string) $keyword, 0, 80)));
        if ($keyword === '') {
            return [];
        }

        $locales = config('app.supported_locales', ['id', 'en']);
        $locale = app()->getLocale();
        $like = JsonSql::like($keyword);

        $search = function (string $model, string $kind, string $icon) use ($locales, $locale, $like): array {
            $expr = 'LOWER(' . JsonSql::locale($model::query()->getConnection()->getDriverName(), 'title') . ") LIKE ? ESCAPE '!'";

            $out = [];
            $rows = $model::query()
                ->where(function ($where) use ($locales, $like, $expr) {
                    foreach ($locales as $l) {
                        $where->orWhereRaw($expr, [JsonSql::path($l), $like]);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            foreach ($rows as $row) {
                $slug = Names::of($row->getRawOriginal('slug'), $locale);
                if ($slug === '') {
                    continue; // tanpa slug tidak ada tujuan tautan
                }
                $out[] = [
                    'title' => $icon . ' ' . (Names::of($row->getRawOriginal('title'), $locale) ?: '#' . $row->getKey()),
                    'url' => "internal://{$kind}/{$slug}",
                ];
            }

            return $out;
        };

        return [...$search(\App\Models\Page::class, 'page', '📄'), ...$search(\App\Models\Post::class, 'article', '📝')];
    }
}
