<?php

namespace App\Content;

use Illuminate\Validation\Rule;

/**
 * Aturan validasi simpan, satu tempat untuk halaman / artikel / snippet.
 * Menggantikan rules() di editor lama (yang tidak memeriksa format slug, keunikan, maupun metadata).
 */
final class ContentRules
{
    /**
     * @param string[]    $locales
     * @param int|string|null $recordId  id record yang sedang diedit (diabaikan oleh aturan unik), null = baru
     */
    public static function for(ContentType $type, array $locales, int|string|null $recordId = null): array
    {
        $rules = [
            'status' => $type->statusRule(),
            'content' => 'array',
        ];

        foreach (array_values($locales) as $i => $locale) {
            // Judul halaman/artikel wajib di semua bahasa (sama seperti editor lama); judul snippet hanya label admin
            $rules["titles.{$locale}"] = ($type->usesSlug() || $i === 0 ? 'required' : 'nullable') . '|string|max:255';

            if ($type->usesSlug()) {
                $rules["slug.{$locale}"] = [
                    'required', 'string', 'max:255', 'regex:' . Slug::PATTERN,
                    // slug unik PER BAHASA: kolom JSON, jadi "slug->id", "slug->en"
                    Rule::unique($type->table(), "slug->{$locale}")->ignore($recordId),
                ];
            }
            if ($type->usesMeta()) {
                $rules["meta_title.{$locale}"] = 'nullable|string|max:255';
                $rules["meta_description.{$locale}"] = 'nullable|string|max:500';
            }
        }

        if ($type->usesKey()) {
            // Sama dengan indeks unik di database (baris yang sudah di Sampah ikut dihitung)
            $rules['key'] = ['required', 'max:80', 'regex:' . ContentDocument::KEY_PATTERN, Rule::unique('snippets', 'key')->ignore($recordId)];
            $rules['description'] = 'nullable|string|max:500';
            $rules['sort_order'] = 'integer|min:0|max:65535';
        }

        if ($type === ContentType::Article) {
            $rules['category_id'] = 'required|integer|exists:categories,id';
        }

        return $rules;
    }

    /** Nama ramah untuk pesan galat ("Slug (ID) wajib diisi", bukan "slug.id wajib diisi"). */
    public static function attributes(ContentType $type, array $locales): array
    {
        $names = ['status' => 'Status', 'key' => 'Kunci', 'description' => 'Deskripsi', 'sort_order' => 'Urutan', 'category_id' => 'Kategori'];
        $title = $type->usesKey() ? 'Nama' : 'Judul';

        foreach ($locales as $locale) {
            $l = strtoupper($locale);
            $names["titles.{$locale}"] = "{$title} ({$l})";
            $names["slug.{$locale}"] = "Slug ({$l})";
            $names["meta_title.{$locale}"] = "Judul SEO ({$l})";
            $names["meta_description.{$locale}"] = "Deskripsi SEO ({$l})";
        }

        return $names;
    }
}