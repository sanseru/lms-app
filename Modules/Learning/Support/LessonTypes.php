<?php

namespace Modules\Learning\Support;

use Modules\Learning\Contracts\LessonTypeHandler;

/** Daftar jenis isi kursus tambahan yang didaftarkan modul lain. */
class LessonTypes
{
    /** @var array<string, LessonTypeHandler> */
    private array $handlers = [];

    public function register(LessonTypeHandler $handler): void
    {
        $this->handlers[$handler->key()] = $handler;
    }

    public function get(?string $key): ?LessonTypeHandler
    {
        return $this->handlers[$key] ?? null;
    }

    /** @return array<string, LessonTypeHandler> */
    public function all(): array
    {
        return $this->handlers;
    }
}
