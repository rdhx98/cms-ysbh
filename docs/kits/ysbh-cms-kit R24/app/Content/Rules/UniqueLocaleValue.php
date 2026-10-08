<?php

namespace App\Content\Rules;

use App\Content\JsonSql;
use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Nilai unik PER BAHASA pada kolom JSON ({"id":"…","en":"…"}): slug, atau judul artikel. TAHAN terhadap baris lama berisi teks polos.
 *
 * Aturan bawaan Rule::unique('posts', 'slug->id') memanggil JSON_EXTRACT pada SETIAP baris; satu baris dengan nilai teks biasa
 * (bukan JSON) membuat MySQL/MariaDB melempar galat "Invalid JSON text" dan seluruh simpan berakhir 500. Di sini nilai hanya dibaca
 * bila JSON_VALID.
 */
final class UniqueLocaleValue implements ValidationRule
{
    /** Hanya kolom ini yang boleh (nama kolom disisipkan ke SQL). */
    private const COLUMNS = ['slug', 'title'];

    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly string $locale,
        private readonly int|string|null $ignoreId = null,
    ) {
        if (!in_array($column, self::COLUMNS, true)) {
            throw new InvalidArgumentException("Kolom tidak diizinkan: {$column}");
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $query = DB::table($this->table);
        $driver = $query->getConnection()->getDriverName();

        $query->whereRaw(JsonSql::locale($driver, $this->column) . ' = ?', [JsonSql::path($this->locale), $value]);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('validation.unique')->translate();
        }
    }
}
