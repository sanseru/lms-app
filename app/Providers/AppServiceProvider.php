<?php

namespace App\Providers;

use App\Support\Modules\ModuleManager;
use App\Support\Navigation\NavigationRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(ModuleManager::class);

        foreach (config('modules.enabled', []) as $provider) {
            $module = $this->app->register($provider);
            $this->app->make(ModuleManager::class)->register($module);
        }
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Model::preventLazyLoading(! $this->app->isProduction());

        // Admin memiliki semua akses.
        Gate::before(fn ($user) => $user->hasRole('admin') ? true : null);
    }
}
