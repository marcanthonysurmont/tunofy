<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSongHistory extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'user_id',
        'spotify_id',
        'song_name',
        'artist',
        'image_url',
        'times_added',
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

    public static function highestAddedSong(User $user): ?UserSongHistory
    {
        return UserSongHistory::where('user_id', $user->id)
            ->orderBy('times_added', 'desc')
            ->first();
    }
}
