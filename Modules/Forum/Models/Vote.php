<?php

namespace Modules\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** "Aku juga bingung" pada pertanyaan, "Membantu" pada jawaban. */
class Vote extends Model
{
    protected $table = 'forum_votes';

    protected $fillable = ['user_id', 'votable_type', 'votable_id'];

    public function votable(): MorphTo
    {
        return $this->morphTo();
    }
}
