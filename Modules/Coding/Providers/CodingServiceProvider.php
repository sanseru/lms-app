<?php

namespace Modules\Coding\Providers;

use App\Support\Modules\ModuleServiceProvider;
use Modules\Coding\Grading\CodeRunner;
use Modules\Coding\Grading\Drivers\BrowserRunner;
use Modules\Coding\Grading\Drivers\Judge0Runner;
use Modules\Coding\Support\CodeLessonType;
use Modules\Learning\Support\LessonTypes;

/**
 * Coding module: auto-graded coding exercises (Python, JavaScript, HTML/CSS,
 * arrange lines). An exercise is one kind of course content in the Learning module.
 */
class CodingServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Coding';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config.php'), 'coding');

        $this->app->bind(CodeRunner::class, fn ($app) => match ($app['config']['coding.driver']) {
            'judge0' => new Judge0Runner($app['config']['coding.judge0']),
            default => new BrowserRunner,
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(LessonTypes::class)->register(new CodeLessonType);
    }
}
