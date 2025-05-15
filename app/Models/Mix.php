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
        'preset_id',
        'theme_setting_definition_id',
        'avatar',
        'mix_count',
    ];

    protected $hidden = [
        'session_code',
        'session_code_expires_at',
        'session_code_permission',
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

    public function presets()
    {
        return $this->hasMany(Preset::class);
    }

    public function preset()
    {
        return $this->belongsTo(Preset::class);
    }

    public function themes()
    {
        return $this->hasMany(Theme::class);
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'mix_accesses')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function queueSongs()
    {
        return $this->hasMany(QueueSong::class);
    }

    public function mixAcceses()
    {
        return $this->hasMany(MixAccess::class);
    }

    public function coDJ()
    {
        return $this->belongsTo(User::class, 'co_dj_id');
    }

    public function themeSettingDefinition()
    {
        return $this->belongsTo(ThemeSettingDefinition::class);
    }

    public function theme()
    {
        return $this->hasOne(Theme::class);
    }

    /**************************************/
    /*       Accessors / Mutators         */
    /**************************************/

    protected function authorized(): Attribute
    {
        $user = Auth::user();

        return new Attribute(fn () => [
            'canView' => $user ? $user->can('view', $this) : false,
            'canAddSong' => $user ? $user->can('addSongs', $this) : false,
            'canRemoveSong' => $user ? $user->can('removeSongs', $this) : false,
            'canDelete' => $user ? $user->can('delete', $this) : false,
            'canManageCollaborators' => $user ? $user->can('manageCollaborators', $this) : false,
            'canGenerateSessionCode' => $user ? $user->can('generateSessionCode', $this) : false,
            'canCopySessionCode' => $user ? $user->can('copySessionCode', $this) : false,
            'canControlPlayback' => $user ? $user->can('controlPlayback', $this) : false,
            'isOwner' => $user ? $user->id === $this->user_id : false,
        ]);
    }

    protected function allPresets(): Attribute
    {
        return new Attribute(function () {
            // Get and sort mix-specific presets
            $mixPresets = $this->relationLoaded('presets')
                ? $this->presets
                : $this->presets()->get();
            $mixPresets = $mixPresets->sortBy('created_at');

            // Get and sort default presets
            $defaultPresets = Preset::where('is_system', true)
                ->where('mix_id', null)
                ->get()->sortBy('created_at');

            // Combine collections in desired order: default presets first, then mix presets
            return $defaultPresets->concat($mixPresets)->values();
        });
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

    public function getThemeSettings()
    {
        $definitions = ThemeSettingDefinition::all();
        $customThemes = $this->themes()->get()->keyBy(function ($theme) {
            return (string) $theme->theme_setting_definition_id;
        });

        return $definitions->map(function ($definition) use ($customThemes) {
            $definitionId = (string) $definition->id;
            $defaultSettings = $definition->settings ?? [];
            $mergedSettings = $defaultSettings;

            // Check for custom overrides
            if ($customThemes->has($definitionId)) {
                $customTheme = $customThemes->get($definitionId);
                $customSettings = $customTheme->settings ?? [];

                if (!empty($customSettings)) {
                    $mergedSettings = array_replace_recursive($defaultSettings, $customSettings);
                }
            }

            // Determine if this theme is the active one
            $isActive = (string)$this->theme_setting_definition_id === $definitionId;

            return [
                'id' => $definition->id,
                'name' => $definition->name,
                'settings' => $mergedSettings,
                'is_active' => $isActive,
            ];
        });
    }
}
