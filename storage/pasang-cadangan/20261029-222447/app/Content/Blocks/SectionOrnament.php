<?php

namespace App\Content\Blocks;

/**
 * Hiasan latar Pemisah Seksi (rilis 107). Satu gambar SVG dari File Manager diletakkan di belakang isi seksi.
 *
 * Data (kunci datar pada blok Pemisah Seksi):
 *   orn_on      bool      menyala? (bawaan MATI: seksi lama tidak berubah)
 *   orn_svg     {url, media_id}  berkas dari File Manager; hanya media_id yang dipakai (alamat dibentuk ulang dari Media)
 *   orn_x       kiri | tengah | kanan        orn_y   atas | tengah | bawah
 *   orn_edge    tepi (menempel & terpotong di tepi) | dalam (utuh di dalam seksi)
 *   orn_size    kecil | sedang | besar       orn_motion   putar-pelan | putar | melayang | diam
 *   orn_color   asli | satu | dua            orn_c1 / orn_c2   var(--color-slot) dari palet
 *   orn_opacity 10 | 20 | 40 | 70 | 100
 *
 * Ukuran TIDAK tetap dan TIDAK ikut membengkak: tinggi = min(% tinggi seksi, % tinggi layar, batas px). Hiasan diposisikan mutlak di dalam
 * pembungkus yang memotong (overflow hidden) seukuran seksi, jadi ia tidak pernah menambah tinggi seksi dan tidak menimbulkan gulir mendatar.
 * Animasi berhenti pada prefers-reduced-motion. Murni kecuali load() (membaca berkas lewat model Media); semua nilai yang dicetak berasal
 * dari daftar putih di bawah, SVG-nya lewat SvgSanitizer.
 */
final class SectionOrnament
{
    public const X = ['kiri' => 'Kiri', 'tengah' => 'Tengah', 'kanan' => 'Kanan'];
    public const Y = ['atas' => 'Atas', 'tengah' => 'Tengah', 'bawah' => 'Bawah'];
    public const EDGE = ['tepi' => 'Terpotong di tepi', 'dalam' => 'Utuh di dalam'];
    public const SIZE = ['kecil' => 'Kecil', 'sedang' => 'Sedang', 'besar' => 'Besar'];
    public const MOTION = ['putar-pelan' => 'Putar pelan', 'putar' => 'Putar', 'melayang' => 'Melayang', 'diam' => 'Diam'];
    public const COLOR = ['asli' => 'Warna asli', 'satu' => 'Satu warna', 'dua' => 'Dua warna'];
    public const OPACITY = [10, 20, 40, 70, 100];

    public const DEFAULTS = [
        'x' => 'kanan', 'y' => 'tengah', 'edge' => 'tepi', 'size' => 'sedang', 'motion' => 'putar-pelan', 'color' => 'asli',
        'c1' => 'var(--color-aurum)', 'c2' => 'var(--color-paper)', 'opacity' => 40,
    ];

    /** "Terpotong di tepi": hiasan bergeser sebesar ini (% ukurannya) ke luar seksi. 30% = pusatnya masih 20% ukuran di dalam tepi, jadi garis yang melewati pusat tidak jatuh persis di tepi. */
    private const EDGE_SHIFT = 30;

    /** ukuran: [% tinggi seksi, % tinggi layar (vh), batas px] -> tinggi = min(...) */
    private const SIZES = ['kecil' => [35, 30, 200], 'sedang' => [60, 50, 380], 'besar' => [90, 75, 640]];
    private const MOTION_CLASS = ['putar-pelan' => 'ysbh-orn-spin-slow', 'putar' => 'ysbh-orn-spin', 'melayang' => 'ysbh-orn-float', 'diam' => ''];

    /** Hanya untuk pengujian: pengganti load() (callable int -> ?string). */
    public static $loaderOverride = null;

    /** @var array<int,?string> */
    private static array $raw = [];

    private static function pick(mixed $v, array $allowed, string $default): string
    {
        return is_string($v) && isset($allowed[$v]) ? $v : $default;
    }

    private static function on(mixed $v): bool
    {
        return $v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on';
    }

    private static function mediaId(mixed $svg): int
    {
        if (!is_array($svg)) {
            return 0;
        }
        $id = $svg['media_id'] ?? null;

        return (is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0 ? (int) $id : 0;
    }

    private static function colorVar(mixed $v, string $default): string
    {
        if (class_exists(\App\Content\Palette::class)) {
            return \App\Content\Palette::colorVar($v) ?? $default;
        }

        return is_string($v) && preg_match('/^var\(--color-[a-z0-9-]{1,20}\)$/D', $v) === 1 ? $v : $default;
    }

    /**
     * Data Pemisah Seksi -> pengaturan hiasan yang sah, atau null bila mati / belum ada SVG. Dipakai SectionBuilder (saat render).
     *
     * @return array{media_id:int,x:string,y:string,edge:string,size:string,motion:string,color:string,c1:string,c2:string,opacity:int}|null
     */
    public static function fromData(array $data): ?array
    {
        $id = self::mediaId($data['orn_svg'] ?? null);
        if (!self::on($data['orn_on'] ?? false) || $id === 0) {
            return null;
        }
        $d = self::DEFAULTS;
        $opacity = $data['orn_opacity'] ?? $d['opacity'];

        return [
            'media_id' => $id,
            'x' => self::pick($data['orn_x'] ?? null, self::X, $d['x']),
            'y' => self::pick($data['orn_y'] ?? null, self::Y, $d['y']),
            'edge' => self::pick($data['orn_edge'] ?? null, self::EDGE, $d['edge']),
            'size' => self::pick($data['orn_size'] ?? null, self::SIZE, $d['size']),
            'motion' => self::pick($data['orn_motion'] ?? null, self::MOTION, $d['motion']),
            'color' => self::pick($data['orn_color'] ?? null, self::COLOR, $d['color']),
            'c1' => self::colorVar($data['orn_c1'] ?? null, $d['c1']),
            'c2' => self::colorVar($data['orn_c2'] ?? null, $d['c2']),
            'opacity' => in_array($opacity, self::OPACITY, true) ? (int) $opacity : (is_string($opacity) && in_array((int) $opacity, self::OPACITY, true) ? (int) $opacity : $d['opacity']),
        ];
    }

    /**
     * Pembersih saat SIMPAN (BlockSanitizer): hanya kunci orn_* yang sudah ada disentuh; nilai tak sah diganti bawaan, orn_svg dipangkas ke
     * {url, media_id}. Non-destruktif untuk kunci lain.
     */
    public static function keep(array $data): array
    {
        foreach (['orn_x' => self::X, 'orn_y' => self::Y, 'orn_edge' => self::EDGE, 'orn_size' => self::SIZE, 'orn_motion' => self::MOTION, 'orn_color' => self::COLOR] as $key => $allowed) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::pick($data[$key], $allowed, self::DEFAULTS[substr($key, 4)]);
            }
        }
        foreach (['orn_c1' => 'c1', 'orn_c2' => 'c2'] as $key => $name) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::colorVar($data[$key], self::DEFAULTS[$name]);
            }
        }
        if (array_key_exists('orn_opacity', $data)) {
            $o = $data['orn_opacity'];
            $data['orn_opacity'] = is_numeric($o) && in_array((int) $o, self::OPACITY, true) ? (int) $o : self::DEFAULTS['opacity'];
        }
        if (array_key_exists('orn_on', $data)) {
            $data['orn_on'] = self::on($data['orn_on']);
        }
        if (array_key_exists('orn_svg', $data)) {
            $id = self::mediaId($data['orn_svg']);
            $url = is_array($data['orn_svg']) && is_string($data['orn_svg']['url'] ?? null) && strlen($data['orn_svg']['url']) <= 2000 ? $data['orn_svg']['url'] : '';
            if ($id === 0) {
                unset($data['orn_svg']);
            } else {
                $data['orn_svg'] = ['url' => $url, 'media_id' => $id];
            }
        }

        return $data;
    }

    /** Isi berkas SVG mentah dari media_id: null bila media tidak ada / bukan SVG / tidak terbaca. Hasil diingat selama satu permintaan. */
    public static function load(int $mediaId): ?string
    {
        if (array_key_exists($mediaId, self::$raw)) {
            return self::$raw[$mediaId];
        }
        $text = null;

        try {
            if (class_exists(\App\Models\Media::class) && ($m = \App\Models\Media::query()->find($mediaId)) !== null) {
                $path = (string) $m->getAttribute('path');
                $mime = strtolower((string) $m->getAttribute('mime_type'));
                $isSvg = str_ends_with(strtolower($path), '.svg') || $mime === 'image/svg+xml';
                if ($isSvg && $path !== '' && !str_contains($path, '..')) {
                    try {
                        $got = \Illuminate\Support\Facades\Storage::disk((string) $m->getAttribute('disk'))->get($path);
                        $text = is_string($got) && $got !== '' ? $got : null;
                    } catch (\Throwable) {
                        $text = null;
                    }
                    if ($text === null && function_exists('public_path')) {
                        // landing produksi: disk 'public' tidak menunjuk folder berkas; berkasnya di public/storage milik landing sendiri
                        $file = public_path('storage/' . ltrim($path, '/'));
                        $got = is_file($file) ? @file_get_contents($file, false, null, 0, SvgSanitizer::MAX_BYTES + 1) : false;
                        $text = is_string($got) && $got !== '' ? $got : null;
                    }
                }
            }
        } catch (\Throwable $e) {
            if (function_exists('report')) {
                report($e);
            }
            $text = null;
        }

        return self::$raw[$mediaId] = $text;
    }

    /** Pengaturan hiasan + awalan id unik -> HTML pembungkus hiasan; '' bila SVG tidak terbaca / tidak lolos penyaring. */
    public static function html(array $orn, string $idPrefix, ?callable $loader = null): string
    {
        $raw = ($loader ?? self::$loaderOverride ?? [self::class, 'load'])((int) $orn['media_id']);
        $clean = is_string($raw) ? SvgSanitizer::clean($raw, $idPrefix, (string) $orn['color']) : null;
        if ($clean === null) {
            return '';
        }

        [$p, $vh, $px] = self::SIZES[$orn['size']];
        $ratio = rtrim(rtrim(number_format(max(0.02, min(50.0, (float) $clean['ratio'])), 4, '.', ''), '0'), '.');
        $edge = $orn['edge'] === 'tepi';
        $css = [];
        $tx = $ty = '0';
        if ($orn['x'] === 'kiri') {
            $css[] = 'left:0';
            $tx = $edge ? '-' . self::EDGE_SHIFT . '%' : '0';
        } elseif ($orn['x'] === 'kanan') {
            $css[] = 'right:0';
            $tx = $edge ? self::EDGE_SHIFT . '%' : '0';
        } else {
            $css[] = 'left:50%';
            $tx = '-50%';
        }
        if ($orn['y'] === 'atas') {
            $css[] = 'top:0';
            $ty = $edge ? '-' . self::EDGE_SHIFT . '%' : '0';
        } elseif ($orn['y'] === 'bawah') {
            $css[] = 'bottom:0';
            $ty = $edge ? self::EDGE_SHIFT . '%' : '0';
        } else {
            $css[] = 'top:50%';
            $ty = '-50%';
        }
        $css[] = "height:min({$p}%,{$vh}vh,{$px}px)";
        $css[] = 'aspect-ratio:' . $ratio;
        $css[] = 'max-width:100%';
        $css[] = "transform:translate({$tx},{$ty})";
        $css[] = 'opacity:' . rtrim(rtrim(number_format($orn['opacity'] / 100, 2, '.', ''), '0'), '.');
        if ($orn['color'] !== 'asli') {
            $css[] = '--orn-a:' . $orn['c1'];
            $css[] = '--orn-b:' . $orn['c2'];
            $css[] = 'color:var(--orn-a)';
        }
        $motion = self::MOTION_CLASS[$orn['motion']];

        return '<div class="ysbh-orn" aria-hidden="true"><div class="ysbh-orn-pos" style="' . implode(';', $css) . '">'
            . '<div class="ysbh-orn-in' . ($motion !== '' ? ' ' . $motion : '') . '">' . $clean['svg'] . '</div></div></div>';
    }

    /** <style> kecil yang berdiri sendiri (tidak bergantung pada app.css atau pemindai Tailwind); dicetak bersama tiap hiasan, ganda tidak berbahaya. */
    public static function css(): string
    {
        return '<style>'
            . '.ysbh-orn{position:absolute;top:0;right:0;bottom:0;left:0;overflow:hidden;pointer-events:none;z-index:0}'
            . '.ysbh-orn+*{position:relative;z-index:1}'
            . '.ysbh-orn-pos{position:absolute}'
            . '.ysbh-orn-in{width:100%;height:100%}'
            . '.ysbh-orn-in svg{display:block;width:100%;height:100%;overflow:visible}'
            . '.ysbh-orn-spin-slow{animation:ysbh-orn-spin 120s linear infinite}'
            . '.ysbh-orn-spin{animation:ysbh-orn-spin 40s linear infinite}'
            . '.ysbh-orn-float{animation:ysbh-orn-float 7s ease-in-out infinite}'
            . '@keyframes ysbh-orn-spin{to{transform:rotate(360deg)}}'
            . '@keyframes ysbh-orn-float{0%,100%{transform:translateY(-3%)}50%{transform:translateY(3%)}}'
            . '@media (prefers-reduced-motion:reduce){.ysbh-orn-in{animation:none!important}}'
            . '</style>';
    }
}
