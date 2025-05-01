<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Mix extends Model
{
    use HasSlug;

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

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
