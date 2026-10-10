<?php

namespace Modules\Landing\Providers;

use App\Support\Modules\ModuleServiceProvider;

/**
 * Halaman publik (landing page) di "/".
 */
class LandingServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Landing';
    }
}
