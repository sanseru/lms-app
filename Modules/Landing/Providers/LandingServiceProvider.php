<?php

namespace Modules\Landing\Providers;

use App\Support\Modules\ModuleServiceProvider;

/**
 * Public landing page at "/".
 */
class LandingServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Landing';
    }
}
