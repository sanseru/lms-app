<?php

namespace Modules\Forum\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Answer extends Model
{
    protected $table = 'forum_answers';

    protected $fillable = ['question_id', 'author_id', 'body', 'code', 'by_staff'];

    protected function casts(): array
    {
        return ['by_staff' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function toItem(User $viewer, array $votedIds = []): array
    {
        return [
            'id' => $this->id,
            'body_html' => Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'body' => $this->body,
            'code' => $this->code,
            'by_staff' => $this->by_staff,
            'accepted' => $this->question->accepted_answer_id === $this->id,
            'author' => ['id' => $this->author->id, 'name' => $this->author->name, 'initials' => $this->author->initials()],
            'votes_count' => $this->votes_count,
            'voted' => in_array($this->id, $votedIds, true),
            'created_at' => $this->created_at->diffForHumans(),
            'edited' => $this->updated_at->gt($this->created_at->addMinute()),
            'can' => [
                'update' => $viewer->can('update', $this),
                'delete' => $viewer->can('delete', $this),
                'vote' => $viewer->can('vote', $this),
            ],
        ];
    }
}
