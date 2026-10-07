<?php

namespace App\Content\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Slug unik PER BAHASA pada kolom JSON ({"id":"…","en":"…"}), TAHAN terhadap baris lama berisi slug polos.
 *
 * Aturan bawaan Rule::unique('posts', 'slug->id') memanggil JSON_EXTRACT pada SETIAP baris; satu baris dengan slug
 * teks biasa dari editor lama ("tentang-kami", bukan JSON) membuat MySQL melempar galat "Invalid JSON text" dan
 * seluruh simpan berakhir 500. Di sini nilai hanya dibaca bila JSON_VALID.
 */
final class UniqueSlug implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $locale,
        private readonly int|string|null $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === "") {
            return;
        }

        $query = DB::table($this->table);
        $sqlite = $query->getConnection()->getDriverName() === "sqlite";

        $query->whereRaw(
            $sqlite
                ? "CASE WHEN json_valid(slug) THEN json_extract(slug, ?) END = ?"
                : "CASE WHEN JSON_VALID(slug) THEN JSON_UNQUOTE(JSON_EXTRACT(slug, ?)) END = ?",
            ['$."' . $this->locale . '"', $value],
        );

        if ($this->ignoreId !== null) {
            $query->where("id", "!=", $this->ignoreId);
        }

        if ($query->exists()) {
            $fail("validation.unique")->translate();
        }
    }
}
