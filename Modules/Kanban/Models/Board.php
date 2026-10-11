<?php

namespace Modules\Kanban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $table = 'project_boards';

    protected $fillable = ['project_id', 'student_id', 'result_url', 'result_note', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    /** Card count per column and percentage done. */
    public function progress(): array
    {
        $cards = $this->relationLoaded('cards') ? $this->cards : $this->cards()->get(['id', 'column']);
        $counts = collect(Card::COLUMNS)->map(fn ($label, $key) => $cards->where('column', $key)->count());
        $total = $cards->count();

        return [
            'total' => $total,
            'counts' => $counts,
            'done' => $counts['done'],
            'percent' => $total ? (int) round($counts['done'] / $total * 100) : 0,
        ];
    }

    /** A board is complete when every card is in the Done column. */
    public function refreshCompletion(): void
    {
        $cards = $this->cards()->get(['id', 'column']);
        $complete = $cards->isNotEmpty() && $cards->every(fn (Card $c) => $c->column === 'done');

        if ($complete !== ($this->completed_at !== null)) {
            $this->forceFill(['completed_at' => $complete ? now() : null])->save();
        }
    }

    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'student' => ['id' => $this->student->id, 'name' => $this->student->name, 'initials' => $this->student->initials()],
            'progress' => $this->progress(),
            'result_url' => $this->result_url,
            'completed' => $this->completed_at !== null,
            'updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}
