<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        'session_code_qr',
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

    public function mixStats()
    {
        return $this->hasOne(MixStat::class);
    }

    public function mixUserStats()
    {
        return $this->hasMany(MixUserStat::class)->with('user');
    }

    public function playBackSession()
    {
        return $this->hasOne(PlaybackSession::class);
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

    public function scopeConflictingActiveMixes($query, int $mixIdToExclude = null, int $controllingUserId = null): Builder
    {
        // If not provided, try to get the controlling user from this instance
        if ($controllingUserId === null && isset($this->id)) {
            $controllingUserId = $this->co_dj_id ?: $this->user_id;
        }

        // If still not available (static call without user ID), return empty query
        if ($controllingUserId === null) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        $query = $query->where('is_active', true);

        if ($mixIdToExclude) {
            $query = $query->where('id', '!=', $mixIdToExclude);
        }

        return $query->where(function ($query) use ($controllingUserId) {
            $query->where('user_id', $controllingUserId)
                ->orWhere('co_dj_id', $controllingUserId);
        });
    }

    public function scopeOtherMixesForUser($query, int $excludeMixId): Builder
    {
        // Get the controlling user ID (co-DJ if assigned, otherwise owner)
        $controllingUserId = $this->co_dj_id ?: $this->user_id;

        // Find mixes where controlling user is involved (either as owner or co-dj)
        $query->where(function ($query) use ($controllingUserId) {
            $query->where('user_id', $controllingUserId)
                ->orWhere('co_dj_id', $controllingUserId);
        });

        // Exclude the current mix
        if ($excludeMixId) {
            $query->where('id', '!=', $excludeMixId);
        }

        return $query;
    }

    /**************************************/
    /*              Helpers               */
    /**************************************/

    public function hasUserJoined($userId): bool
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

    public function getThemeSettings(): Collection
    {
        $definitions = ThemeSettingDefinition::all();
        $customThemes = $this->themes()->get()->keyBy(function (Theme $theme) {
            return (string) $theme->theme_setting_definition_id;
        });

        return $definitions->map(function (ThemeSettingDefinition $definition) use ($customThemes) {
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

    public function filterVotableSongs(Collection $pendingSongs): Collection
    {
        if ($pendingSongs->isEmpty()) {
            return collect();
        }

        $pendingSongs = $pendingSongs->where('is_killed', false);

        if ($pendingSongs->isEmpty()) {
            return collect();
        }

        $queueSongIds = $pendingSongs->pluck('id')->toArray();
        $votedSongIds = Vote::where('user_id', Auth::id())
            ->whereIn('queue_song_id', $queueSongIds)
            ->pluck('queue_song_id')
            ->toArray();

        return $pendingSongs->whereNotIn('id', $votedSongIds);
    }

    public function getAllPendingSongs()
    {
        // First, get the currently playing song (if any)
        $currentlyPlayingSong = $this->queueSongs()
            ->where('status', 'playing')
            ->first(['id', 'round_number']);

        // Get the minimum round number that has any PLAYABLE pending songs
        // This is the key change - we find the lowest round with non-killed pending songs
        $lowestRoundWithPlayableSongs = $this->queueSongs()
            ->where('status', 'pending')
            ->where('is_killed', false)  // Look for playable songs only
            ->min('round_number');

        // If no playable songs in any round, fall back to the lowest round with any songs
        $lowestRound = $lowestRoundWithPlayableSongs ?? $this->queueSongs()
            ->where('status', 'pending')
            ->min('round_number');

        if ($lowestRound === null) {
            return collect();
        }

        // Get metrics for the active round
        $pendingCountInLowestRound = $this->queueSongs()
            ->where('status', 'pending')
            ->where('round_number', $lowestRound)
            ->count();

        $playablePendingCountInLowestRound = $this->queueSongs()
            ->where('status', 'pending')
            ->where('is_killed', false)
            ->where('round_number', $lowestRound)
            ->count();

        $totalRoundSongsCount = $this->queueSongs()
            ->whereIn('status', ['playing', 'pending'])
            ->where('round_number', $lowestRound)
            ->count();

        // Get the batch size for the current round
        $batchSize = $this->preset ? $this->preset->batch_size : 5;

        $isLastSongOfRound = false;
        if ($currentlyPlayingSong && $currentlyPlayingSong->round_number == $lowestRound) {
            // If playing last playable song of round
            $isLastSongOfRound = ($playablePendingCountInLowestRound === 0);
        }

        Log::debug("Round voting info - Round: {$lowestRound}, Playing: " .
                   ($currentlyPlayingSong ? "Yes (round {$currentlyPlayingSong->round_number})" : 'No') .
                   ", Total pending: {$pendingCountInLowestRound}, Playable pending: {$playablePendingCountInLowestRound}, " .
                   "Total: {$totalRoundSongsCount}, Batch: {$batchSize}, isLastSong: " .
                   ($isLastSongOfRound ? 'Yes' : 'No'));

        // Return songs from the determined round
        $pendingSongs = $this->queueSongs()
            ->where('status', 'pending')
            ->where('round_number', $lowestRound)
            ->with(['song.user'])
            ->get();

        Log::debug("Returning {$pendingSongs->count()} pending songs from round {$lowestRound}");

        return $pendingSongs;
    }
}
