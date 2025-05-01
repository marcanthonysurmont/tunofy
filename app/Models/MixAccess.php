<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixAccess extends Model
{
    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'mix_id',
        'user_id',
        'permission',
    ];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }
    
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
