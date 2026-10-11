<?php

namespace Modules\Coding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeSubmission extends Model
{
    public const STATUSES = ['passed' => 'Correct', 'failed' => 'Not quite', 'error' => 'Error'];

    protected $fillable = [
        'lesson_id', 'student_id', 'code', 'status', 'score', 'passed_tests', 'total_tests',
        'results', 'graded_by', 'hints_used', 'tests_hash', 'exercise_version',
    ];

    protected function casts(): array
    {
        return ['results' => 'array', 'score' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function toItem(bool $withCode = false): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'score' => $this->score,
            'passed_tests' => $this->passed_tests,
            'total_tests' => $this->total_tests,
            'at' => $this->created_at->locale('en')->translatedFormat('d M, H:i'),
            'code' => $withCode ? $this->code : null,
            'version' => $this->exercise_version,
        ];
    }
}
