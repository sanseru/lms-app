<?php

namespace Modules\Leads\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Classes\Models\ClassRoom;

/** A prospective student from the free trial form, or logged by an admin from WhatsApp. */
class Lead extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'trial_scheduled' => 'Trial scheduled',
        'trial_done' => 'Trial done',
        'won' => 'Enrolled',
        'lost' => 'Not continuing',
    ];

    /** Pipeline board columns (closed leads are shown separately). */
    public const OPEN = ['new', 'contacted', 'trial_scheduled', 'trial_done'];

    public const INTERESTS = [
        'Scratch Junior & Scratch', 'Game Maker', 'Python Pro', 'Web Developer', 'AI & Data Explorer', 'Android & IoT', 'Not sure yet, please advise',
    ];

    public const SOURCES = [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'google' => 'Google', 'teman' => 'Friends or family',
        'sekolah' => 'School', 'whatsapp' => 'WhatsApp', 'lainnya' => 'Other',
    ];

    protected $fillable = [
        'parent_name', 'phone', 'email', 'child_name', 'child_age', 'child_grade', 'interest', 'preferred_time', 'source', 'message',
        'status', 'lost_reason', 'trial_at', 'trial_teacher_id', 'trial_zoom_url', 'trial_result', 'trial_interest', 'recommended_class_id',
        'student_id', 'contacted_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['trial_at' => 'datetime', 'contacted_at' => 'datetime', 'closed_at' => 'datetime', 'child_age' => 'integer', 'trial_interest' => 'integer'];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest('id');
    }

    public function trialTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trial_teacher_id');
    }

    public function recommendedClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'recommended_class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** Number for wa.me links: 08xx becomes 628xx. */
    public function waNumber(): string
    {
        $digits = preg_replace('/\D/', '', $this->phone);

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'parent_name' => $this->parent_name,
            'child_name' => $this->child_name,
            'child_age' => $this->child_age,
            'interest' => $this->interest,
            'source' => self::SOURCES[$this->source] ?? $this->source,
            'status' => $this->status,
            'trial_at' => $this->trial_at?->translatedFormat('D, d M H:i'),
            'trial_teacher' => $this->trialTeacher?->name,
            'age_days' => (int) $this->created_at->diffInDays(now()),
            'created' => $this->created_at->diffForHumans(),
        ];
    }
}
