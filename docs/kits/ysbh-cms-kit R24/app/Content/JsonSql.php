<?php

namespace App\Content;

/**
 * Potongan SQL untuk kolom JSON per bahasa ({"id":"…","en":"…"}) pada MariaDB/MySQL dan SQLite.
 * Satu-satunya tempat yang tahu cara membacanya dengan AMAN: baris yang isinya teks biasa (bukan JSON) tidak membuat MySQL/MariaDB
 * melempar "Invalid JSON text"; nilainya cukup dianggap NULL.
 */
final class JsonSql
{
    /** Ungkapan yang membaca satu bahasa dari $column; mengikat SATU parameter, yaitu path(). Nama kolom hanya huruf kecil dan garis bawah. */
    public static function locale(string $driver, string $column): string
    {
        if (!preg_match('/^[a-z_]+$/D', $column)) {
            throw new \InvalidArgumentException("Nama kolom tidak sah: {$column}");
        }

        return $driver === 'sqlite'
            ? "CASE WHEN json_valid({$column}) THEN json_extract({$column}, ?) END"
            : "CASE WHEN JSON_VALID({$column}) THEN JSON_UNQUOTE(JSON_EXTRACT({$column}, ?)) END";
    }

    public static function path(string $locale): string
    {
        return '$."' . str_replace(['"', '\\'], '', $locale) . '"';
    }

    /** Pola LIKE yang aman dengan ESCAPE '!' (portabel: SQLite tidak menerima ESCAPE '\'). Pakai: "... LIKE ? ESCAPE '!'". */
    public static function like(string $q): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    }
}
