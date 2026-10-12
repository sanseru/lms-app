<?php

namespace Modules\Lab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LabProject extends Model
{
    /** Project types, in the client prototype's order. */
    public const LANGUAGES = [
        'python' => 'Python',
        'scratch' => 'Scratch',
        'presentation' => 'Presentation',
        'html' => 'Website',
        'javascript' => 'JavaScript',
    ];

    public const DESCRIPTIONS = [
        'python' => 'Write Python and run it right here.',
        'scratch' => 'Build games and stories with blocks.',
        'presentation' => 'Make slides and play them as a slideshow.',
        'html' => 'Build a web page with HTML, CSS, and JS.',
        'javascript' => 'Experiment with JavaScript in the console.',
    ];

    /** Types saved as a file instead of text (Scratch .sb3). */
    public const FILE_TYPES = ['scratch'];

    /** Largest .sb3 accepted, in kilobytes (sounds and costumes make them big). */
    public const MAX_FILE_KB = 20480;

    public const STARTERS = [
        'python' => "name = \"Alprogmatics\"\nprint(\"Hello, \" + name + \"!\")\n\nfor i in range(1, 4):\n    print(\"Step\", i)\n",
        'javascript' => "const name = 'Alprogmatics';\nconsole.log(`Hello, \${name}!`);\n\nfor (let i = 1; i <= 3; i++) {\n  console.log('Step', i);\n}\n",
        'html' => "<!doctype html>\n<html>\n<head>\n  <style>\n    body { font-family: sans-serif; text-align: center; padding: 2rem; }\n    h1 { color: #5b4185; }\n  </style>\n</head>\n<body>\n  <h1>My first page</h1>\n  <p>Change this text and press Run.</p>\n</body>\n</html>\n",
        'presentation' => "# Welcome!\nMy first presentation\n\n---\n\n# About me\n- I love coding\n- I am learning Python\n\n---\n\n# Thank you!\nSee you next time\n",
    ];

    protected $fillable = ['user_id', 'title', 'description', 'language', 'code'];

    protected static function booted(): void
    {
        // The .sb3 file goes with the project.
        static::deleted(fn (LabProject $project) => $project->file_path && Storage::disk('local')->delete($project->file_path));
    }

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

    public function usesFile(): bool
    {
        return in_array($this->language, self::FILE_TYPES, true);
    }

    /** Store a new .sb3 for this project, replacing the old one. */
    public function storeFile(UploadedFile $file): void
    {
        $old = $this->file_path;
        $this->forceFill(['file_path' => $file->storeAs("lab/{$this->id}", Str::random(20).'.sb3', 'local')])->save();

        if ($old && $old !== $this->file_path) {
            Storage::disk('local')->delete($old);
        }
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
            'file_url' => $this->usesFile() && $this->file_path ? route('community.file', [$this, 'v' => $this->updated_at?->timestamp]) : null,
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
