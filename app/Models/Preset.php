<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preset extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'name',
        'mix_id',
        'batch_size',
        'max_songs',
        'num_rounds',
        'requires_approval',
        'voting_enabled',
        'kill_percentage_percent',
        'priority_boost_new',
        'auto_remove_negative',
        'emoji_chat_enabled',
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
