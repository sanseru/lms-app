<?php

namespace Modules\Kanban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $table = 'project_cards';

    protected $fillable = [
        'board_id', 'template_task_id', 'created_by', 'title', 'description', 'column', 'position',
        'feedback', 'reviewed_by', 'reviewed_at', 'done_at',
    ];

    public const COLUMNS = [
        'todo' => 'To do',
        'doing' => 'Doing',
        'review' => 'Ready for review',
        'done' => 'Done',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'done_at' => 'datetime'];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function toItem(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'column' => $this->column,
            'from_teacher' => $this->template_task_id !== null,
            'feedback' => $this->feedback,
            'reviewer' => $this->reviewer?->name,
            'reviewed_at' => $this->reviewed_at?->diffForHumans(),
            'done_at' => $this->done_at?->translatedFormat('d M'),
        ];
    }
}
