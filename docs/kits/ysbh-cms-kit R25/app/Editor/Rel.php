<?php

namespace App\Editor;

/**
 * Argumen "rel" untuk wireField() di Blade: string statis (di inspektur), atau UNGKAPAN JavaScript dinamis
 * (di dalam daftar berulang, mis. "'data.buttons.' + i + '.label'", dengan i = indeks item).
 */
final class Rel
{
    public static function js(?string $rel, ?string $expr = null): string
    {
        return $expr ?? (string) \Illuminate\Support\Js::from($rel);
    }

    /** Ungkapan rel dinamis untuk sebuah kunci di dalam item: prefix "'data.buttons.' + i" + kunci. */
    public static function item(?string $prefix, string $key): ?string
    {
        return $prefix ? $prefix . " + '." . addcslashes($key, "'\\") . "'" : null;
    }
}
