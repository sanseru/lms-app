<?php

namespace Modules\Certificates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;

class Certificate extends Model
{
    protected $fillable = [
        'number', 'code', 'student_id', 'course_id', 'class_room_id', 'title', 'achievement',
        'issued_at', 'issued_by', 'signer_name', 'revoked_at',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'revoked_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    /** Visible certificates: your own, your child's, students in classes you teach, or all (admin). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('student_id', $user->id)
            ->orWhereIn('student_id', $user->children()->select('users.id'))
            ->orWhereIn('class_room_id', $user->teachingClasses()->select('id')));
    }

    /** Running number per month, e.g. ALP/2026/10/0007. */
    public static function nextNumber(): string
    {
        $prefix = 'ALP/'.now()->format('Y/m').'/';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');

        return $prefix.str_pad((string) ((int) Str::afterLast((string) $last, '/') + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function toItem(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'code' => $this->code,
            'title' => $this->title,
            'achievement' => $this->achievement,
            'student' => ['id' => $this->student->id, 'name' => $this->student->name, 'initials' => $this->student->initials()],
            'class' => $this->classRoom ? ['id' => $this->classRoom->id, 'name' => $this->classRoom->name, 'color' => $this->classRoom->color] : null,
            'color' => $this->course?->color ?? $this->classRoom?->color ?? 'violet',
            'issued_at' => $this->issued_at->translatedFormat('d F Y'),
            'signer_name' => $this->signer_name,
            'revoked' => $this->revoked_at !== null,
            'verify_url' => route('certificates.verify', $this->code),
        ];
    }
}
