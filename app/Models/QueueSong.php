<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueSong extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'mix_id',
        'song_id',
        'round_number',
        'order',
        'priority_boost',
        'vote_score',
        'is_killed',
        'is_playing',
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
