<?php

namespace Modules\Audit\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Audit\Models\Activity;

/**
 * Watched models and their important columns. Other modules can
 * add their own: app(AuditTrail::class)->watch(...).
 */
class AuditTrail
{
    /** These values are never stored, only the fact that they changed. */
    private const SECRET = ['password', 'remember_token', 'calendar_token'];

    /** @var array<class-string, array{category: string, fields: array<int, string>, events: array<int, string>, label: Closure, values: array<string, array<string, string>|Closure>}> */
    private array $watched = [];

    private bool $paused = false;

    /**
     * @param  array<int, string>  $fields  columns recorded when they change
     * @param  array<int, string>  $events  created, updated, deleted
     * @param  Closure(Model): string  $label  short label for the object, e.g. invoice number
     * @param  array<string, array<string, string>|Closure>  $values  how to display column values, e.g. status => STATUSES
     */
    public function watch(string $model, string $category, array $fields, Closure $label, array $events = ['created', 'updated', 'deleted'], array $values = []): void
    {
        $this->watched[$model] = compact('category', 'fields', 'events', 'label', 'values');

        foreach ($events as $event) {
            $model::{$event}(fn (Model $m) => $this->record($m, $event));
        }
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return collect($this->watched)->pluck('category')->push('Account')->unique()->sort()->values()->all();
    }

    /** Stop recording until the process ends (used while seeding). */
    public function pause(): void
    {
        $this->paused = true;
    }

    /** Run without recording, e.g. while filling demo data. */
    public function withoutRecording(Closure $callback): mixed
    {
        $this->paused = true;
        try {
            return $callback();
        } finally {
            $this->paused = false;
        }
    }

    public function log(string $event, string $category, ?Model $subject = null, ?string $label = null, ?array $changes = null, ?int $causerId = null): void
    {
        if ($this->paused) {
            return;
        }

        Activity::create([
            'causer_id' => $causerId ?? Auth::id(),
            'event' => $event,
            'category' => $category,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $label,
            'changes' => $changes ?: null,
            'ip' => app()->runningInConsole() ? null : Request::ip(),
        ]);
    }

    private function record(Model $model, string $event): void
    {
        $config = $this->watched[$model::class] ?? null;
        if (! $config || $this->paused) {
            return;
        }

        $changes = match ($event) {
            'created' => collect($config['fields'])->filter(fn ($f) => $model->getAttribute($f) !== null)
                ->mapWithKeys(fn ($f) => [$f => [null, $this->value($config, $f, $model->getAttribute($f))]])->all(),
            'updated' => collect($config['fields'])->filter(fn ($f) => $model->wasChanged($f))
                ->mapWithKeys(fn ($f) => [$f => [$this->value($config, $f, $model->getOriginal($f)), $this->value($config, $f, $model->getAttribute($f))]])->all(),
            default => [],
        };

        if ($event === 'updated' && ! $changes) {
            return;
        }

        $this->log($event, $config['category'], $model, ($config['label'])($model), $changes);
    }

    private function value(array $config, string $field, mixed $value): mixed
    {
        if (in_array($field, self::SECRET, true)) {
            return $value === null ? null : '••••';
        }

        $display = $config['values'][$field] ?? null;
        if ($value !== null && $display instanceof Closure) {
            return $display($value);
        }
        if ($value !== null && is_array($display)) {
            $key = $value instanceof \BackedEnum ? $value->value : $value;

            return $display[$key] ?? $key;
        }

        return match (true) {
            $value instanceof \DateTimeInterface => $value->format($value->format('H:i:s') === '00:00:00' ? 'Y-m-d' : 'Y-m-d H:i'),
            $value instanceof \BackedEnum => $value->value,
            is_bool($value) => $value ? 'yes' : 'no',
            default => $value,
        };
    }
}
