<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengelolaan Kategori dan Tag dua bahasa (rilis 46). Nama dan slug diisi PER BAHASA; semua aturan ada di sini (diuji dengan SQLite),
 * komponen `content.taxonomy` hanya tampilan.
 *
 *   - Bahasa bawaan WAJIB punya nama. Bahasa lain boleh kosong: nama tampil memakai bahasa bawaan (Names::of), dan kategori tidak punya
 *     halaman / slug di bahasa itu (lihat docs/KATEGORI.md: kategori tanpa slug di suatu bahasa tidak punya alamat di bahasa itu).
 *   - Slug otomatis dari nama bahasa itu (Slug::make), boleh diubah. Slug dan nama harus unik PER BAHASA.
 *   - Bentuk kolom mengikuti model Anda: `name` ber-cast array/json = peta bahasa; selain itu teks polos (hanya bahasa bawaan tersimpan).
 *     `slug` bisa tidak ada (kategori), peta bahasa, atau teks polos. Kunci bahasa lain yang sudah ada di data tidak dihapus.
 *   - Kategori yang masih dipakai artikel tidak bisa dihapus. Tag yang dihapus dilepas dari artikelnya lebih dulu (tabel post_tags).
 */
final class Taxonomy
{
    public const KINDS = [
        'category' => ['label' => 'Kategori', 'max' => 100, 'model' => 'App\\Models\\Category'],
        'tag' => ['label' => 'Tag', 'max' => 60, 'model' => 'App\\Models\\Tag'],
    ];

    private const SLUG_MAX = 190;

    /** @return array{label:string,max:int,model:string}|null */
    public static function kind(string $kind): ?array
    {
        return self::KINDS[$kind] ?? null;
    }

    /** Nama bersih: spasi dirapikan; untuk tag, awalan "#" dibuang ("#stunting" -> "stunting"). */
    public static function cleanName(string $kind, mixed $value): string
    {
        $s = trim((string) preg_replace('/\s+/u', ' ', is_scalar($value) ? (string) $value : ''));

        return $kind === 'tag' ? ltrim($s, "# \t") : $s;
    }

    /**
     * Murni: validasi dan bentuk akhir nama + slug per bahasa.
     *
     * @param array<string,mixed> $names isian nama per bahasa
     * @param array<string,mixed> $slugs isian slug per bahasa ('' = otomatis dari nama)
     * @param string[] $locales bahasa aktif
     * @param list<array{id:int,names:array<string,string>,slugs:array<string,string>}> $others baris LAIN (yang diedit tidak ikut)
     * @return array{names:array<string,string>,slugs:array<string,string>,errors:array<string,string>}
     */
    public static function prepare(string $kind, array $names, array $slugs, array $locales, string $default, array $others, bool $withSlug = true): array
    {
        $max = self::KINDS[$kind]['max'] ?? 100;
        $label = self::KINDS[$kind]['label'] ?? 'Item';
        $outNames = [];
        $outSlugs = [];
        $errors = [];

        foreach ($locales as $locale) {
            $name = self::cleanName($kind, $names[$locale] ?? '');
            $outNames[$locale] = $name;
            $outSlugs[$locale] = '';
            $tag = strtoupper($locale);

            if ($name === '') {
                if ($locale === $default) {
                    $errors["name.$locale"] = "Nama $label ($tag) wajib diisi.";
                }
                continue;
            }
            if (mb_strlen($name) > $max) {
                $errors["name.$locale"] = "Nama $label ($tag) maksimal $max karakter.";
                continue;
            }
            foreach ($others as $o) {
                if (mb_strtolower((string) ($o['names'][$locale] ?? '')) === mb_strtolower($name)) {
                    $errors["name.$locale"] = "Nama \"$name\" ($tag) sudah dipakai $label lain.";
                    break;
                }
            }

            if (!$withSlug) {
                continue;
            }
            $typed = is_scalar($slugs[$locale] ?? null) ? trim((string) $slugs[$locale]) : '';
            $slug = Slug::make($typed !== '' ? $typed : $name);
            if ($slug === '' || strlen($slug) > self::SLUG_MAX || !Slug::isValid($slug)) {
                $errors["slug.$locale"] = $typed !== ''
                    ? "Slug ($tag) tidak sah: pakai huruf, angka, dan tanda hubung."
                    : "Slug ($tag) tidak bisa dibentuk dari nama ini; ketik slug sendiri (huruf, angka, tanda hubung).";
                continue;
            }
            foreach ($others as $o) {
                if (($o['slugs'][$locale] ?? '') === $slug) {
                    $errors["slug.$locale"] = "Slug \"$slug\" ($tag) sudah dipakai $label lain.";
                    break;
                }
            }
            $outSlugs[$locale] = $slug;
        }

        return ['names' => $outNames, 'slugs' => $outSlugs, 'errors' => $errors];
    }

    /**
     * Semua baris untuk daftar dan pemeriksaan duplikat.
     *
     * @param string[] $locales bahasa aktif (bahasa bawaan terlebih dulu)
     * @return list<array{id:int,names:array<string,string>,slugs:array<string,string>,missing:string[],uses:int}>
     */
    public static function rows(string $kind, array $locales, string $default, ?string $model = null): array
    {
        $class = $model ?? (self::KINDS[$kind]['model'] ?? '');
        if ($class === '' || !class_exists($class)) {
            return [];
        }
        $ordered = array_values(array_unique(array_merge([$default], $locales)));
        $uses = self::usage($kind);

        $out = [];
        foreach ($class::query()->get() as $row) {
            $id = (int) $row->getKey();
            $raw = ['id' => $id, 'name' => $row->getRawOriginal('name'), 'slug' => $row->getRawOriginal('slug')];
            $names = LocaleMap::from($raw['name'], $ordered);   // teks polos ditempatkan di bahasa bawaan
            $slugs = CategoryLookup::slugs($raw, $ordered);
            foreach ($ordered as $l) {
                $names[$l] = trim((string) ($names[$l] ?? ''));
                $slugs[$l] = $slugs[$l] ?? '';
            }
            $out[] = [
                'id' => $id,
                'names' => $names,
                'slugs' => $slugs,
                'missing' => array_values(array_filter($locales, fn (string $l) => $names[$l] === '')),
                'uses' => $uses[$id] ?? 0,
            ];
        }
        usort($out, fn ($a, $b) => strnatcasecmp($a['names'][$default] ?: (string) reset($a['names']), $b['names'][$default] ?: (string) reset($b['names'])));

        return $out;
    }

    /** @return array<int,int> id => jumlah artikel yang memakai */
    public static function usage(string $kind): array
    {
        try {
            $q = $kind === 'category'
                ? DB::table('posts')->selectRaw('category_id as k, count(*) as c')->whereNotNull('category_id')->groupBy('category_id')
                : DB::table('post_tags')->selectRaw('tag_id as k, count(*) as c')->groupBy('tag_id');
            $out = [];
            foreach ($q->get() as $r) {
                $out[(int) $r->k] = (int) $r->c;
            }

            return $out;
        } catch (\Throwable $e) {
            if (function_exists('report')) {
                report($e);
            }

            return [];
        }
    }

    /**
     * Simpan (buat bila $id null). Tidak melempar galat validasi: hasilnya ['ok' => false, 'errors' => [...]].
     *
     * @param array<string,mixed> $names
     * @param array<string,mixed> $slugs
     * @param string[] $locales
     * @return array{ok:bool,id:?int,created:bool,errors:array<string,string>}
     */
    public static function save(string $kind, ?int $id, array $names, array $slugs, array $locales, string $default, ?string $model = null): array
    {
        $class = $model ?? (self::KINDS[$kind]['model'] ?? '');
        if ($class === '' || !class_exists($class)) {
            return ['ok' => false, 'id' => null, 'created' => false, 'errors' => ['form' => 'Jenis tidak dikenal.']];
        }
        $table = (new $class())->getTable();
        $withSlug = Schema::hasColumn($table, 'slug');

        $others = array_values(array_filter(self::rows($kind, $locales, $default, $class), fn (array $r) => $r['id'] !== $id));
        $p = self::prepare($kind, $names, $slugs, $locales, $default, $others, $withSlug);
        if ($p['errors'] !== []) {
            return ['ok' => false, 'id' => $id, 'created' => false, 'errors' => $p['errors']];
        }

        $record = $id === null ? new $class() : $class::query()->find($id);
        if ($record === null) {
            return ['ok' => false, 'id' => $id, 'created' => false, 'errors' => ['form' => (self::KINDS[$kind]['label'] ?? 'Data') . ' tidak ditemukan (mungkin sudah dihapus).']];
        }

        $perLocale = $record->hasCast('name', ['array', 'json']);
        $record->name = $perLocale
            ? self::merge($record->exists ? $record->getRawOriginal('name') : null, $p['names'], $locales)
            : $p['names'][$default];

        if ($withSlug) {
            if ($perLocale) {
                $merged = self::merge($record->exists ? $record->getRawOriginal('slug') : null, $p['slugs'], $locales);
                $record->slug = $record->hasCast('slug', ['array', 'json']) ? $merged : json_encode($merged, JSON_UNESCAPED_UNICODE);
            } else {
                $record->slug = $p['slugs'][$default];
            }
        }

        $created = !$record->exists;
        $record->save();

        return ['ok' => true, 'id' => (int) $record->getKey(), 'created' => $created, 'errors' => []];
    }

    /**
     * Hapus. Kategori yang dipakai artikel ditolak; tag dilepas dari artikelnya lebih dulu.
     *
     * @return array{ok:bool,error:?string,detached:int}
     */
    public static function delete(string $kind, int $id, ?string $model = null): array
    {
        $class = $model ?? (self::KINDS[$kind]['model'] ?? '');
        $label = self::KINDS[$kind]['label'] ?? 'Data';
        $record = $class !== '' && class_exists($class) ? $class::query()->find($id) : null;
        if ($record === null) {
            return ['ok' => false, 'error' => "$label tidak ditemukan (mungkin sudah dihapus).", 'detached' => 0];
        }

        $uses = self::usage($kind)[$id] ?? 0;
        if ($kind === 'category' && $uses > 0) {
            return ['ok' => false, 'error' => "Kategori ini dipakai $uses artikel. Pindahkan artikelnya ke kategori lain dulu.", 'detached' => 0];
        }

        $detached = 0;
        DB::transaction(function () use ($kind, $id, $record, &$detached) {
            if ($kind === 'tag') {
                $detached = (int) DB::table('post_tags')->where('tag_id', $id)->delete();
            }
            $record->delete();
        });

        return ['ok' => true, 'error' => null, 'detached' => $detached];
    }

    /**
     * Peta bahasa baru: nilai lama (kunci bahasa yang tidak aktif dipertahankan) ditimpa untuk bahasa aktif; yang kosong dihapus.
     *
     * @param array<string,string> $new
     * @param string[] $locales
     * @return array<string,string>
     */
    private static function merge(mixed $rawOld, array $new, array $locales): array
    {
        $old = LocaleMap::decode($rawOld);
        $map = is_array($old) ? array_filter($old, fn ($v) => is_scalar($v) && trim((string) $v) !== '') : [];
        foreach ($locales as $locale) {
            if (($new[$locale] ?? '') !== '') {
                $map[$locale] = $new[$locale];
            } else {
                unset($map[$locale]);
            }
        }

        return array_map('strval', $map);
    }
}
