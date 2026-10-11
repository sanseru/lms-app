<?php

namespace Modules\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** "Me too" on questions, "Helpful" on answers. */
class Vote extends Model
{
    protected $table = 'forum_votes';

    protected $fillable = ['user_id', 'votable_type', 'votable_id'];

    public function votable(): MorphTo
    {
        return $this->morphTo();
    }
}
