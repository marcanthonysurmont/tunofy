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
        'description',
        'mix_id',
        'batch_size',
        'requires_approval',
        'voting_enabled',
        'kill_percentage',
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
