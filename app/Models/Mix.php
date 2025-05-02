<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Auth;

class Mix extends Model
{
    use HasSlug;

    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'session_code',
        'session_code_expires_at',
        'session_code_permission',
        'is_public',
        'is_active',
        'co_dj_id',
        'playback_device_id',
        'preset_id',
        'avatar',
    ];

    protected $appends = ['authorized'];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function songs()
    {
        return $this->hasMany(Song::class);
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'mix_accesses')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function mixAcceses()
    {
        return $this->hasMany(MixAccess::class);
    }

    /**************************************/
    /*       Accessors / Mutators         */
    /**************************************/

    protected function authorized(): Attribute
    {
        $user = Auth::user();

        return new Attribute(fn() => [
            'canView' => $user->can('view', $this),
            'canAddSong' => $user->can('addSongs', $this),
            'canRemoveSong' => $user->can('removeSongs', $this),
            'canUpdate' => $user->can('update', $this),
            'canDelete' => $user->can('delete', $this),
            'canManageCollaborators' => $user->can('manageCollaborators', $this),
            'canJoin' => $user->can('join', $this),
            'canGenerateSessionCode' => $user->can('generateSessionCode', $this),
        ]);
    }

    /**************************************/
    /*              Scopes                */
    /**************************************/

    public function scopeValidSessionCode($query, $sessionCode)
    {
        return $query->where('session_code', $sessionCode)
            ->where('session_code_expires_at', '>', now());
    }

    /**************************************/
    /*              Helpers               */
    /**************************************/

    public function hasUserJoined($userId)
    {
        return $this->mixAcceses()
            ->where('user_id', $userId)
            ->exists();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
