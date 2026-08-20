<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Blade;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Support\activity;

use App\Models\Navigation;
use Illuminate\Support\Facades\View;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        // 1. Daftarkan folder 'layouts' (Jalur: resources/views/layouts)
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts'); // [!code highlight]

        // 2. Daftarkan folder 'auth' (Jalur: resources/views/auth)
        Blade::anonymousComponentPath(resource_path('views/auth'), 'auth'); // [!code highlight]

        Event::listen(function (Login $event) {
            // 3. Catat menggunakan Spatie Activitylog
            activity('security')
                ->causedBy($event->user) // Otomatis mengaitkan ke user yang sedang login
                ->withProperties([
                    'ip_address' => request()->ip(), // Catat IP address
                    'browser' => request()->userAgent() // Catat jenis peramban (browser)
                ])
                ->log('Pengguna berhasil masuk ke sistem');
        });
        // Menangkap event Logout manual
        Event::listen(function (Logout $event) {
            activity('security')
                ->causedBy($event->user)
                ->withProperties([
                    'ip_address' => request()->ip(),
                    'browser' => request()->userAgent(),
                    'type' => 'manual_logout' // Penanda bahwa ini keluar sendiri
                ])
                ->log('Pengguna keluar dari sistem (Logout)');
        });

        View::composer('layouts.landing.header', function ($view) {
            // Ambil menu dari database, urutkan berdasarkan kolom 'order'
            $navLinks = Navigation::where('is_active', true)
                            ->orderBy('order', 'asc')
                            ->get();

            $view->with('navLinks', $navLinks);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
