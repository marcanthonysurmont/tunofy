<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSession extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'mix_id',
        'started_at',
        'ended_at',
        'is_active',
    ];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function queueSongs()
    {
        return $this->hasMany(QueueSong::class);
    }

    /**************************************/
    /*       Accessors / Mutators         */
    /**************************************/

    /**************************************/
    /*              Scopes                */
    /**************************************/

    public function scopeActiveSession($query, Mix $mix): ?PlaybackSession
    {
        return $query->where('mix_id', $mix->id)
            ->where('is_active', true)
            ->first();
    }

    /**************************************/
    /*              Helpers               */
    /**************************************/
}
