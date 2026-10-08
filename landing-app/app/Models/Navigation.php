<?php

namespace App\Models;

use App\Content\Links\LinkResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Spatie\Translatable\HasTranslations;

class Navigation extends Model
{
    //
    use HasTranslations; // 🌟 2. Aktifkan trait di sini

    protected $fillable = ['label', 'route_name', 'url', 'order', 'is_active'];
    public $translatable = ['label'];

    protected $casts = [
        'label' => 'array',
        'published_at'     => 'datetime',
    ];

    /**
     * Tujuan menu. Urutan: (1) rute bernama (route_name) bila ada di aplikasi ini dan tidak butuh parameter;
     * (2) kolom `url` (halaman CMS, mis. "/tentang-kami"), hanya bila aman: http(s), /jalur, mailto:, tel:, #anchor (aturan LinkResolver);
     * (3) "#". Tidak pernah melempar galat: rute yang hilang atau salah ketik tidak menjatuhkan seluruh situs.
     */
    public function getHrefAttribute(): string
    {
        $name = trim((string) $this->route_name);
        if ($name !== '') {
            try {
                if (Route::has($name)) {
                    return route($name);
                }
            } catch (\Throwable) {
                // rute butuh parameter, atau router tidak siap: lanjut ke url
            }
        }

        $url = trim((string) $this->url);
        if ($url !== '') {
            // awalan menentukan jenis tautan di pemecah: mailto:, tel:, #anchor punya aturan sendiri; selebihnya http(s) atau /jalur
            $kind = 'url';
            $ref = $url;
            if (str_starts_with($url, 'mailto:')) {
                [$kind, $ref] = ['mailto', substr($url, 7)];
            } elseif (str_starts_with($url, 'tel:')) {
                [$kind, $ref] = ['tel', substr($url, 4)];
            } elseif (str_starts_with($url, '#')) {
                [$kind, $ref] = ['anchor', substr($url, 1)];
            }
            $safe = LinkResolver::make()->url(['kind' => $kind, 'ref' => $ref], app()->getLocale());
            if ($safe !== null) {
                return $safe;
            }
        }

        return '#';
    }

    /** Menu yang sedang dibuka. Rute bernama: rute itu atau turunannya ("programs" aktif di "programs-malaria"). Url: jalur yang sama atau di bawahnya. */
    public function isCurrent(): bool
    {
        $name = trim((string) $this->route_name);
        try {
            if ($name !== '' && Route::has($name)) {
                return request()->routeIs($name, $name . '-*');
            }
        } catch (\Throwable) {
            return false;
        }

        $href = $this->href;
        if (str_starts_with($href, '#') || preg_match('/^(?:mailto|tel):/i', $href)) {
            return false; // anchor, surel, dan telepon bukan halaman: tidak pernah menjadi menu aktif
        }
        $host = parse_url($href, PHP_URL_HOST);
        if ($host !== null && $host !== false && strcasecmp($host, request()->getHost()) !== 0) {
            return false;
        }
        $path = rtrim((string) parse_url($href, PHP_URL_PATH), '/');
        $current = '/' . trim(request()->path(), '/');
        if ($path === '') {
            return $current === '/';
        }

        return $current === $path || str_starts_with($current, $path . '/');
    }
}
