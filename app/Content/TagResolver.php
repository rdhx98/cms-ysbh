<?php

namespace App\Content;

/**
 * Mengubah isian tag dari editor menjadi daftar ID tag.
 *   integer  -> ID tag yang sudah ada (diabaikan bila tidak ada di database)
 *   string   -> NAMA tag baru; memakai tag yang sudah ada bila namanya (bahasa mana pun) atau slug-nya sama, jika tidak dibuat
 * Penentu bedanya adalah TIPE, bukan isi: tag bernama "2026" tetap tag baru, bukan ID 2026.
 *
 * Kolom name/slug Tag boleh berupa teks polos, string JSON, atau peta bahasa; pencocokan dilakukan di PHP terhadap NILAI MENTAH
 * (jumlah tag kecil), supaya tidak bergantung pada bentuk kolom. Tag baru dibuat mengikuti bentuk kolom: bila Tag meng-cast
 * name sebagai array, name dan slug ditulis untuk SEMUA bahasa ({"id": "X", "en": "X"}).
 */
final class TagResolver
{
    /**
     * @param array<int,int|string> $inputs
     * @param string[] $locales bahasa aktif (untuk tag baru bila kolomnya per bahasa)
     * @param class-string<\Illuminate\Database\Eloquent\Model> $tagModel
     * @return int[]
     */
    public static function resolve(array $inputs, array $locales = ['id', 'en'], string $tagModel = \App\Models\Tag::class): array
    {
        $wanted = [];
        $newNames = [];

        foreach ($inputs as $input) {
            if (is_int($input)) {
                $wanted[] = $input;
                continue;
            }
            $name = trim((string) $input);
            if ($name !== '' && Slug::make($name) !== '') {
                $newNames[] = $name;
            }
        }

        if (!$wanted && !$newNames) {
            return [];
        }

        $valid = [];
        $byName = [];
        $bySlug = [];
        foreach ($tagModel::query()->get() as $tag) {
            $id = (int) $tag->getKey();
            $valid[$id] = true;
            foreach (self::texts($tag->getRawOriginal('name')) as $text) {
                $byName[mb_strtolower($text)] ??= $id;
            }
            foreach (self::texts($tag->getRawOriginal('slug')) as $text) {
                $bySlug[$text] ??= $id;
            }
        }

        // ID palsu dari browser tidak boleh sampai ke tabel pivot
        $ids = array_values(array_filter($wanted, fn (int $id) => isset($valid[$id])));

        $perLocale = (new $tagModel())->hasCast('name', ['array', 'json']);

        foreach ($newNames as $name) {
            $slug = Slug::make($name);
            $id = $bySlug[$slug] ?? $byName[mb_strtolower($name)] ?? null;

            if ($id === null) {
                $tag = $tagModel::create($perLocale
                    ? ['name' => array_fill_keys($locales, $name), 'slug' => array_fill_keys($locales, $slug)]
                    : ['name' => $name, 'slug' => $slug]);
                $id = (int) $tag->getKey();
                // nama yang sama muncul lagi dalam permintaan yang sama: pakai yang baru dibuat
                $byName[mb_strtolower($name)] = $id;
                $bySlug[$slug] = $id;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /** Semua teks pada sebuah nilai mentah (teks polos, string JSON, atau peta bahasa). */
    private static function texts(mixed $raw): array
    {
        $value = LocaleMap::decode($raw);
        if (is_string($value)) {
            return trim($value) === '' ? [] : [trim($value)];
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $value),
            fn ($v) => $v !== '',
        ));
    }
}
