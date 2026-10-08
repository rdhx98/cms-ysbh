<?php

namespace App\Editor;

use App\Editor\Blocks\BlockModule;

/**
 * Penemu modul blok: setiap berkas app/Editor/Blocks/*Block.php yang kelasnya mengimplementasikan BlockModule otomatis terdaftar
 * di registri (inspektur), palet (menu tambah blok), dan pembersih data. Hasil diingat selama permintaan.
 */
final class Modules
{
    /** @var array<string, class-string<BlockModule>>|null tipe-kanonik => kelas */
    private static ?array $map = null;

    /** @return array<string, class-string<BlockModule>> */
    public static function all(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        // bergantung pada berkas ini saja; require_once tidak berbuat apa-apa bila sudah dimuat autoload
        foreach (['Options', 'Field', 'BlockType', 'Blocks/BlockModule'] as $dependency) {
            require_once __DIR__ . "/{$dependency}.php";
        }

        $map = [];
        foreach (glob(__DIR__ . '/Blocks/*Block.php') ?: [] as $file) {
            require_once $file;
            $class = 'App\\Editor\\Blocks\\' . basename($file, '.php');
            if (class_exists($class, false) && is_subclass_of($class, BlockModule::class)) {
                $map[BlockRegistry::canonical($class::definition()->type)] = $class;
            }
        }

        return self::$map = $map;
    }

    /** @return class-string<BlockModule>|null */
    public static function for(?string $type): ?string
    {
        return self::all()[BlockRegistry::canonical((string) $type)] ?? null;
    }

    public static function reset(): void
    {
        self::$map = null;
    }
}
