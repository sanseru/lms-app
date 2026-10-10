<?php

namespace Modules\Identity\Providers;

use App\Support\Modules\ModuleServiceProvider;

class IdentityServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Identity';
    }
}
