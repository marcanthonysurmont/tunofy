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
        'status',
        'is_killed',
        'like_count',
        'dislike_count',
        'order',
        'round_number',
        'priority_boost',
        'played_at',
    ];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function song()
    {
        return $this->belongsTo(Song::class);
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
