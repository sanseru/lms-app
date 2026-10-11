<?php

namespace Modules\Reports\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan guru per siswa, per kelas, per bulan untuk laporan orang tua. */
class MonthlyReportNote extends Model
{
    protected $fillable = ['student_id', 'class_room_id', 'month', 'author_id', 'highlights', 'focus'];

    protected function casts(): array
    {
        return ['month' => 'date'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
