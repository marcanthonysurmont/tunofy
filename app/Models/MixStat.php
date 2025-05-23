<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixStat extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'mix_id',
        'songs_played',
        'songs_liked',
        'songs_disliked',
        'songs_killed',
        'minutes_played',
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
