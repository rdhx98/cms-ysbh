<?php

namespace App\Content;

use App\Content\Rules\ReservedSlug;
use App\Content\Rules\UniqueLocaleValue;
use Illuminate\Validation\Rule;

/**
 * Aturan validasi simpan, satu tempat untuk halaman / artikel / snippet.
 * Menggantikan rules() di editor lama (yang tidak memeriksa format slug, keunikan, maupun metadata).
 */
final class ContentRules
{
    /**
     * @param string[]        $locales
     * @param int|string|null $recordId   id record yang sedang diedit (diabaikan oleh aturan unik), null = baru
     * @param bool            $canPublish pengguna boleh menerbitkan (admin/editor); penulis biasa dibatasi di sisi SERVER
     * @param string|null     $currentStatus status yang tersimpan saat ini (tetap sah)
     */
    public static function for(ContentType $type, array $locales, int|string|null $recordId = null, bool $canPublish = true, ?string $currentStatus = null): array
    {
        $rules = [
            'status' => 'required|in:' . implode(',', $type->statusesFor($canPublish, $currentStatus)),
            'content' => 'array',
        ];

        foreach (array_values($locales) as $i => $locale) {
            // Judul halaman/artikel wajib di semua bahasa (sama seperti editor lama); judul snippet hanya label admin
            $rules["titles.{$locale}"] = [($type->usesSlug() || $i === 0 ? 'required' : 'nullable'), 'string', 'max:255'];
            // posts.title punya indeks unik di database: judul kembar harus ditolak di sini, bukan berakhir sebagai galat SQL 500
            if ($type === ContentType::Article) {
                $rules["titles.{$locale}"][] = new UniqueLocaleValue($type->table(), 'title', $locale, $recordId);
            }

            if ($type->usesSlug()) {
                $rules["slug.{$locale}"] = [
                    'required', 'string', 'max:255', 'regex:' . Slug::PATTERN,
                    // slug unik PER BAHASA pada kolom JSON; tahan terhadap baris lama berisi slug polos (bukan JSON)
                    new UniqueLocaleValue($type->table(), 'slug', $locale, $recordId),
                ];
                // Halaman: slug tidak boleh sama dengan alamat tetap situs (rute statis menang atas /{slug}). Artikel ada di /artikel/{slug}: aman.
                if ($type === ContentType::Page) {
                    // per bahasa: daftar larangan dan kepala daftar artikel bahasa itu; kode bahasa lain terlarang di bahasa bawaan
                    $rules["slug.{$locale}"][] = new ReservedSlug(self::configured('reserved_slugs', []), self::indexSlug($locale), $locale, $locales, Languages::fromConfig()['default']);
                }
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
            // Sama dengan editor lama: minimal satu tag. Isian: integer = ID tag lama, string = nama tag baru.
            $rules['tags'] = 'required|array|min:1';
            $rules['tags.*'] = ['required', function ($attribute, $value, $fail) {
                if (!is_int($value) && !(is_string($value) && trim($value) !== '' && mb_strlen(trim($value)) <= 60)) {
                    $fail('Tag tidak valid (maksimal 60 karakter).');
                }
            }];
        }

        return $rules;
    }

    /** Slug halaman CMS yang menjadi kepala daftar artikel pada bahasa $locale (boleh dipakai walau alamatnya rute tetap). */
    public static function indexSlug(string $locale = ''): string
    {
        return Languages::indexSlug(self::configured('articles_index_slug', null), $locale);
    }

    /** Membaca config('cms.*') tanpa melempar galat di luar aplikasi Laravel (pengujian murni). */
    private static function configured(string $key, mixed $default): mixed
    {
        try {
            $value = config('cms.' . $key, $default);
        } catch (\Throwable) {
            return $default;
        }

        return is_array($default) ? (is_array($value) ? $value : []) : $value;
    }

    /** Nama ramah untuk pesan galat ("Slug (ID) wajib diisi", bukan "slug.id wajib diisi"). */
    public static function attributes(ContentType $type, array $locales): array
    {
        $names = ['status' => 'Status', 'key' => 'Kunci', 'description' => 'Deskripsi', 'sort_order' => 'Urutan', 'category_id' => 'Kategori', 'tags' => 'Tag', 'tags.*' => 'Tag'];
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
