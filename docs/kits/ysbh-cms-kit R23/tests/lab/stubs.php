<?php
namespace Psr\Container { interface ContainerExceptionInterface extends \Throwable {} interface NotFoundExceptionInterface extends ContainerExceptionInterface {} interface ContainerInterface { public function get(string $id); public function has(string $id): bool; } }
namespace Psr\Clock { interface ClockInterface { public function now(): \DateTimeImmutable; } }
namespace Psr\SimpleCache { interface CacheInterface {} }
namespace { }
namespace {
    // Polyfill PHP 8.4/8.5 yang dipakai Laravel 13 di PHP 8.3 (dari symfony/polyfill-php84/85)
    if (!function_exists('array_first')) { function array_first(array $a, ?callable $cb = null, $default = null) { foreach ($a as $k => $v) { if ($cb === null || $cb($v, $k)) return $v; } return $default; } }
    if (!function_exists('array_last')) { function array_last(array $a, ?callable $cb = null, $default = null) { foreach (array_reverse($a, true) as $k => $v) { if ($cb === null || $cb($v, $k)) return $v; } return $default; } }
    if (!function_exists('array_find')) { function array_find(array $a, callable $cb) { foreach ($a as $k => $v) { if ($cb($v, $k)) return $v; } return null; } }
    if (!function_exists('array_find_key')) { function array_find_key(array $a, callable $cb) { foreach ($a as $k => $v) { if ($cb($v, $k)) return $k; } return null; } }
    if (!function_exists('array_any')) { function array_any(array $a, callable $cb): bool { foreach ($a as $k => $v) { if ($cb($v, $k)) return true; } return false; } }
    if (!function_exists('array_all')) { function array_all(array $a, callable $cb): bool { foreach ($a as $k => $v) { if (!$cb($v, $k)) return false; } return true; } }
}
namespace Symfony\Component\Clock {
    interface ClockInterface extends \Psr\Clock\ClockInterface { public function sleep(float|int $seconds): void; public function withTimeZone(\DateTimeZone|string $timezone): static; }
    class NativeClock implements ClockInterface {
        private \DateTimeZone $tz;
        public function __construct(\DateTimeZone|string|null $tz = null) { $this->tz = is_string($tz) ? new \DateTimeZone($tz) : ($tz ?? new \DateTimeZone(date_default_timezone_get())); }
        public function now(): \DateTimeImmutable { return new \DateTimeImmutable('now', $this->tz); }
        public function sleep(float|int $seconds): void { usleep((int) ($seconds * 1e6)); }
        public function withTimeZone(\DateTimeZone|string $timezone): static { $c = clone $this; $c->tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone; return $c; }
    }
}
namespace {
    if (!function_exists('mb_ucfirst')) { function mb_ucfirst(string $s, ?string $enc = null): string { return mb_strtoupper(mb_substr($s, 0, 1, $enc), $enc) . mb_substr($s, 1, null, $enc); } }
    if (!function_exists('mb_lcfirst')) { function mb_lcfirst(string $s, ?string $enc = null): string { return mb_strtolower(mb_substr($s, 0, 1, $enc), $enc) . mb_substr($s, 1, null, $enc); } }
    if (!function_exists('mb_trim')) { function mb_trim(string $s, ?string $chars = null, ?string $enc = null): string { return trim($s); } }
    if (!function_exists('mb_ltrim')) { function mb_ltrim(string $s, ?string $chars = null, ?string $enc = null): string { return ltrim($s); } }
    if (!function_exists('mb_rtrim')) { function mb_rtrim(string $s, ?string $chars = null, ?string $enc = null): string { return rtrim($s); } }
}
namespace Laravel\SerializableClosure\Support {
    // Stub minimal untuk laravel/serializable-closure (dipakai Onceable); cukup untuk hash yang stabil
    class ReflectionClosure {
        private \ReflectionFunction $r;
        public function __construct(\Closure $c) { $this->r = new \ReflectionFunction($c); }
        public function getClosureUsedVariables(): array { return $this->r->getStaticVariables(); }
        public function getClosureCalledClass(): ?\ReflectionClass { return $this->r->getClosureCalledClass(); }
    }
}
namespace {
    // Polyfill enum global SortDirection (PHP 8.6 / symfony/polyfill-php86) yang dipakai query builder Laravel 13
    if (!enum_exists('SortDirection', false)) { enum SortDirection { case Ascending; case Descending; } }
}
namespace Doctrine\Inflector {
    // Stub pluralisasi bahasa Inggris sederhana (cukup untuk menurunkan nama tabel di lab)
    class Inflector {
        public function pluralize(string $w): string {
            if (preg_match('/[^aeiou]y$/i', $w)) return substr($w, 0, -1) . 'ies';
            if (preg_match('/(s|x|z|ch|sh)$/i', $w)) return $w . 'es';
            return $w . 's';
        }
        public function singularize(string $w): string {
            if (preg_match('/ies$/i', $w)) return substr($w, 0, -3) . 'y';
            if (preg_match('/(ses|xes|zes|ches|shes)$/i', $w)) return substr($w, 0, -2);
            return preg_replace('/s$/i', '', $w);
        }
    }
    class InflectorFactory {
        public static function create(): static { return new static; }
        public static function createForLanguage($l): static { return new static; }
        public function build(): Inflector { return new Inflector; }
    }
}
namespace Brick\Math\Exception { class MathException extends \RuntimeException {} }
namespace Brick\Math {
    // Stub minimal brick/math: hanya perbandingan numerik yang dipakai aturan size (max/min/between)
    class BigNumber {
        private function __construct(private float|int|string $v) {}
        public static function of(mixed $v): static { if (!is_numeric($v)) throw new Exception\MathException('bukan angka'); return new static($v + 0); }
        public function isGreaterThan(mixed $o): bool { return $this->v > $o + 0; }
        public function isLessThan(mixed $o): bool { return $this->v < $o + 0; }
        public function isGreaterThanOrEqualTo(mixed $o): bool { return $this->v >= $o + 0; }
        public function isLessThanOrEqualTo(mixed $o): bool { return $this->v <= $o + 0; }
    }
    class BigDecimal extends BigNumber {}
}
