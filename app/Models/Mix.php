<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mix extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'user_id',
        'name',
        'session_code',
        'is_public',
        'is_active',
        'co_dj_id',
        'playback_device_id',
        'batch_size',
        'max_songs',
        'preset_id',
        'avatar',
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
