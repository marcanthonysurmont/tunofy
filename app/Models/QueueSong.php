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
        'playback_session_id',
        'status',
        'is_killed',
        'like_count',
        'dislike_count',
        'kill_count',
        'order',
        'round_number',
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

    public function playbackSession()
    {
        return $this->belongsTo(PlaybackSession::class);
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
