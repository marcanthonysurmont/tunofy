<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixUserStat extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'mix_id',
        'user_id',
        'songs_added',
        'songs_liked',
        'songs_disliked',
        'songs_killed',
        'total_votes',
    ];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**************************************/
    /*       Accessors / Mutators         */
    /**************************************/

    /**************************************/
    /*              Scopes                */
    /**************************************/

    /**************************************/
    /*              Helpers               */
    /**************************************/
}
