<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalUserStat extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'user_id',
        'mixes_created',
        'mixes_played',
        'songs_added',
        'like_count',
        'dislike_count',
        'kill_count',
        'total_votes',
    ];

    /**************************************/
    /*           Relationships            */
    /**************************************/
    
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
