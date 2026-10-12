<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    public const STATUSES = ['submitted' => 'Awaiting grade', 'revision' => 'Needs revision', 'graded' => 'Graded'];

    protected $fillable = [
        'lesson_id', 'student_id', 'body', 'link_url', 'file_path', 'file_name',
        'status', 'score', 'feedback', 'graded_by', 'graded_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['graded_at' => 'datetime', 'submitted_at' => 'datetime', 'score' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** Submissions the user may grade: admins all, teachers those from students in their classes. */
    public function scopeGradableBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        // The student is in this teacher's class, and that class uses this assignment's course.
        return $query->whereExists(fn ($q) => $q->selectRaw('1')
            ->from('class_students')
            ->join('class_rooms', 'class_rooms.id', '=', 'class_students.class_room_id')
            ->join('lessons', 'lessons.course_id', '=', 'class_rooms.course_id')
            ->whereColumn('class_students.student_id', 'assignment_submissions.student_id')
            ->whereColumn('lessons.id', 'assignment_submissions.lesson_id')
            ->where('class_rooms.teacher_id', $user->id)
            ->whereNull('class_rooms.deleted_at'));
    }

    public function toItem(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'body' => $this->body,
            'link_url' => $this->link_url,
            'file_name' => $this->file_name,
            'file_url' => $this->file_path ? route('learning.submissions.file', $this) : null,
            // A Scratch project can be played right on the page instead of downloaded.
            'is_scratch' => $this->file_path && str_ends_with(strtolower((string) $this->file_name), '.sb3'),
            'score' => $this->score,
            'feedback' => $this->feedback,
            'submitted_at' => $this->submitted_at?->format('d M Y, H:i'),
            'graded_at' => $this->graded_at?->format('d M Y, H:i'),
            'grader' => $this->relationLoaded('grader') ? $this->grader?->name : null,
            'student' => $this->relationLoaded('student') && $this->student ? ['id' => $this->student->id, 'name' => $this->student->name, 'initials' => $this->student->initials()] : null,
        ];
    }
}
