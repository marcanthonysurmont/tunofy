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
