<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**************************************/
    /*             Attributes             */
    /**************************************/

    protected $fillable = [
        'spotify_id',
        'name',
        'email',
        'avatar',
        'type',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'token_expires_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_expires_at' => 'datetime',
        ];
    }

    protected $appends = ['authorized'];

    /**************************************/
    /*           Relationships            */
    /**************************************/

    public function mixes()
    {
        return $this->hasMany(Mix::class);
    }

    public function accessibleMixes()
    {
        return $this->belongsToMany(Mix::class, 'mix_accesses')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**************************************/
    /*       Accessors / Mutators         */
    /**************************************/

    public function authorized(): Attribute
    {
        return new Attribute(fn() => [
            'hasPremium' => $this->type === 'premium',
        ]);
    }

    public function role(): Attribute
    {
        return new Attribute(
            get: function () {
                if(isset($this->pivot) && isset($this->pivot->permission)) {
                    return ucfirst($this->pivot->permission);
                }
            }
        );
    }

    /**************************************/
    /*              Scopes                */
    /**************************************/

    /**************************************/
    /*              Helpers               */
    /**************************************/
}
