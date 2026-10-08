<?php
// Penopang pengujian: Capsule + SQLite, fasad Schema, event model, dan stub Spatie (paket tidak ikut terunduh).
namespace Spatie\Translatable\Attributes { #[\Attribute(\Attribute::TARGET_CLASS)] class Translatable { public array $attributes; public function __construct(string ...$attributes) { $this->attributes = $attributes; } } }
namespace Spatie\Translatable {
    trait HasTranslations {
        public function getTranslations(?string $key = null, ?array $allowedLocales = null): array {
            $raw = $this->getAttributes()[$key] ?? null; $v = is_string($raw) ? json_decode($raw, true) : $raw; return is_array($v) ? $v : [];
        }
    }
}
namespace Spatie\Activitylog\Support {
    class LogOptions { public static function defaults(): static { return new static; } public function logOnly(array $a): static { return $this; } public function logOnlyDirty(): static { return $this; } public function useLogName(string $n): static { return $this; } }
}
namespace Spatie\Activitylog\Models\Concerns { trait LogsActivity {} }
namespace App\Models { class User extends \Illuminate\Database\Eloquent\Model { protected $table = 'users'; protected $guarded = []; } }
namespace {
    if (!function_exists('app')) { function app($x = null) { return new class { public function getLocale() { return 'id'; } }; } }

    spl_autoload_register(function ($c) {
        $extra = [
            'App\\Models\\Media' => '/mnt/user-data/outputs/media-manager-backend/app/Models/Media.php',
            'App\\Models\\MediaFolder' => '/mnt/user-data/outputs/media-manager-backend/app/Models/MediaFolder.php',
            'App\\Models\\MediaUsage' => '/mnt/user-data/outputs/media-manager-backend/app/Models/MediaUsage.php',
            'App\\Traits\\SyncsMediaUsage' => '/mnt/user-data/outputs/media-manager-backend/app/Traits/SyncsMediaUsage.php',
        ];
        if (isset($extra[$c])) require_once $extra[$c];
    });

    function boot_database(): \Illuminate\Database\Capsule\Manager {
        $container = new \Illuminate\Container\Container;
        \Illuminate\Container\Container::setInstance($container);
        $db = new \Illuminate\Database\Capsule\Manager($container);
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $db->setEventDispatcher(new \Illuminate\Events\Dispatcher($container));
        $db->setAsGlobal();
        $db->bootEloquent();
        $container->instance('db', $db->getDatabaseManager());
        $container->instance('db.schema', $db->getConnection()->getSchemaBuilder());
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
        return $db;
    }
}
