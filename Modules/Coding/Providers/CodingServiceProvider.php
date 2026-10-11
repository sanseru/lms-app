<?php

namespace Modules\Coding\Providers;

use App\Support\Modules\ModuleServiceProvider;
use Modules\Coding\Grading\CodeRunner;
use Modules\Coding\Grading\Drivers\BrowserRunner;
use Modules\Coding\Grading\Drivers\Judge0Runner;
use Modules\Coding\Support\CodeLessonType;
use Modules\Learning\Support\LessonTypes;

/**
 * Modul Coding: soal coding yang dinilai otomatis (Python, JavaScript, HTML/CSS,
 * susun baris). Soal adalah salah satu jenis isi kursus di modul Belajar.
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
