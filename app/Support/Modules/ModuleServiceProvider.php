<?php

namespace App\Support\Modules;

use App\Support\Navigation\NavigationRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Basis service provider untuk setiap modul di folder Modules/.
 *
 * Konvensi folder modul:
 *   Modules/<Nama>/
 *     Providers/<Nama>ServiceProvider.php
 *     Http/Controllers, Http/Requests
 *     Models, Policies, Actions
 *     Database/Migrations, Database/Factories
 *     routes/web.php          (otomatis memakai middleware web + auth)
 *     routes/public.php       (hanya middleware web, untuk halaman publik)
 *     resources/js/Pages      (halaman Inertia: Inertia::render('<Nama>::Halaman'))
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Nama modul, sama dengan nama folder. */
    abstract public function name(): string;

    /**
     * Permission milik modul ini, dibuat otomatis oleh seeder.
     *
     * @return array<string, array<int, string>> permission => role yang mendapatkannya
     */
    public function permissions(): array
    {
        return [];
    }

    /** Daftarkan menu navigasi modul. */
    public function navigation(NavigationRegistry $nav): void {}

    public function boot(): void
    {
        $path = $this->modulePath();

        if (is_dir($path.'/Database/Migrations')) {
            $this->loadMigrationsFrom($path.'/Database/Migrations');
        }

        if (! $this->app->routesAreCached()) {
            if (is_file($path.'/routes/public.php')) {
                Route::middleware('web')->group($path.'/routes/public.php');
            }

            if (is_file($path.'/routes/web.php')) {
                Route::middleware(['web', 'auth'])->group($path.'/routes/web.php');
            }
        }

        $this->navigation($this->app->make(NavigationRegistry::class));
    }

    public function modulePath(string $append = ''): string
    {
        $dir = dirname((new ReflectionClass($this))->getFileName(), 2);

        return $append ? $dir.'/'.$append : $dir;
    }
}
