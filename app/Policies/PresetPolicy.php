<?php

namespace App\Policies;

use App\Models\Preset;
use App\Models\User;

class PresetPolicy
{

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Preset $preset): bool
    {
        return $user->id === $preset->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Preset $preset): bool
    {
        return $user->id === $preset->user_id;
    }
}
