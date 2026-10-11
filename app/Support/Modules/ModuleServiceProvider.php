<?php

namespace App\Support\Modules;

use App\Support\Navigation\NavigationRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base service provider for every module in Modules/.
 *
 * Module folder convention:
 *   Modules/<Name>/
 *     Providers/<Name>ServiceProvider.php
 *     Http/Controllers, Http/Requests
 *     Models, Policies, Actions
 *     Database/Migrations, Database/Factories
 *     routes/web.php          (uses web + auth middleware automatically)
 *     routes/public.php       (web middleware only, for public pages)
 *     resources/js/Pages      (Inertia pages: Inertia::render('<Name>::Page'))
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Module name, same as the folder name. */
    abstract public function name(): string;

    /**
     * Permissions owned by this module, created by the seeder.
     *
     * @return array<string, array<int, string>> permission => roles that get it
     */
    public function permissions(): array
    {
        return [];
    }

    /** Register the module's navigation items. */
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
