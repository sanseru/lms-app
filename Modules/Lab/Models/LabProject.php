<?php

namespace Modules\Lab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabProject extends Model
{
    public const LANGUAGES = [
        'python' => 'Python',
        'javascript' => 'JavaScript',
        'html' => 'Website',
    ];

    public const DESCRIPTIONS = [
        'python' => 'Write Python and run it right here.',
        'javascript' => 'Experiment with JavaScript in the console.',
        'html' => 'Build a web page with HTML, CSS, and JS.',
    ];

    public const STARTERS = [
        'python' => "name = \"Alprogmatics\"\nprint(\"Hello, \" + name + \"!\")\n\nfor i in range(1, 4):\n    print(\"Step\", i)\n",
        'javascript' => "const name = 'Alprogmatics';\nconsole.log(`Hello, \${name}!`);\n\nfor (let i = 1; i <= 3; i++) {\n  console.log('Step', i);\n}\n",
        'html' => "<!doctype html>\n<html>\n<head>\n  <style>\n    body { font-family: sans-serif; text-align: center; padding: 2rem; }\n    h1 { color: #5b4185; }\n  </style>\n</head>\n<body>\n  <h1>My first page</h1>\n  <p>Change this text and press Run.</p>\n</body>\n</html>\n",
    ];

    protected $fillable = ['user_id', 'title', 'description', 'language', 'code'];

    protected function casts(): array
    {
        return ['shared_at' => 'datetime', 'hidden_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lab_project_likes')->withPivot('created_at');
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lab_project_views')->withPivot('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LabComment::class)->oldest('id');
    }

    /** Projects that are live in the Community gallery. */
    public function scopeInCommunity(Builder $query): Builder
    {
        return $query->whereNotNull('shared_at')->whereNull('hidden_at');
    }

    public function isShared(): bool
    {
        return $this->shared_at !== null && $this->hidden_at === null;
    }

    /** First name plus last initial, so children are not shown with their full name. */
    public static function publicName(?User $user): string
    {
        if (! $user) {
            return 'Someone';
        }

        $parts = preg_split('/\s+/', trim($user->name)) ?: [];
        $first = $parts[0] ?? $user->name;

        return count($parts) > 1 ? $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $first;
    }

    public function toCard(?User $viewer = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'language' => $this->language,
            'language_label' => self::LANGUAGES[$this->language] ?? $this->language,
            'author' => ['id' => $this->user_id, 'name' => self::publicName($this->owner), 'initials' => $this->owner?->initials() ?? '?'],
            'is_mine' => $viewer?->id === $this->user_id,
            'shared' => $this->isShared(),
            'hidden' => $this->hidden_at !== null,
            'likes_count' => (int) ($this->likers_count ?? 0),
            'views_count' => (int) ($this->viewers_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'liked' => (bool) ($this->liked ?? false),
            'shared_at' => $this->shared_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Only the owner edits, shares or deletes a Laboratory project. Checked directly
     * instead of through the policy, because admins pass every gate via Gate::before;
     * teachers and admins use Community moderation (hide) instead.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
