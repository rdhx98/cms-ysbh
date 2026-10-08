<?php

namespace App\Content\Rules;

use App\Content\Slug;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Menolak slug halaman yang alamatnya sudah dimiliki rute tetap di landing (lihat Slug::RESERVED dan config('cms.reserved_slugs')).
 * Tanpa ini halaman tersimpan, tampak online, tetapi tidak pernah bisa dibuka (rute statis menang atas "/{slug}"), tanpa galat apa pun.
 * Hanya untuk HALAMAN: artikel berada di bawah "/artikel/{slug}" dan tidak bertabrakan.
 */
final class ReservedSlug implements ValidationRule
{
    /** @param array<int,mixed> $configured config('cms.reserved_slugs')  @param string|null $allowed slug kepala daftar artikel (boleh) */
    public function __construct(private readonly array $configured = [], private readonly ?string $allowed = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Slug yang formatnya salah ditolak aturan regex; di sini hanya yang sah, sehingga isi pesan aman dan pasti slug biasa
        if (!is_string($value) || !Slug::isValid($value)) {
            return;
        }

        if (Slug::isReserved($value, $this->configured, $this->allowed)) {
            $fail(":attribute \"{$value}\" dipakai oleh alamat tetap situs, sehingga halaman ini tidak akan pernah terbuka. Pilih slug lain.");
        }
    }
}
