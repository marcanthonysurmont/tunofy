<?php

namespace App\Policies;

use App\Http\Enums\Permission;
use App\Models\Mix;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MixPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the mix.
     */
    public function view(User $user, Mix $mix): bool
    {
        // Owner can always view
        if ($user->id === $mix->user_id) {
            return true;
        }

        // Public mixes are visible to everyone
        if ($mix->is_public) {
            return true;
        }

        // Check if user has any access permission
        return $user->accessibleMixes()
            ->where('mix_id', $mix->id)
            ->exists();
    }

    /**
     * Determine whether the user can add songs to the mix.
     */
    public function addSongs(User $user, Mix $mix): bool
    {
        // Owner can always add songs
        if ($user->id === $mix->user_id) {
            return true;
        }

        // Check if user has contribute or edit permission
        return $user->accessibleMixes()
            ->where('mix_id', $mix->id)
            ->whereIn('permission', [Permission::CONTRIBUTE->value, Permission::EDIT->value])
            ->exists();
    }

    public function removeSongs(User $user, Mix $mix): bool
    {
        // Owner can always remove songs
        if ($user->id === $mix->user_id) {
            return true;
        }

        // Check if user has edit permission
        return $user->accessibleMixes()
            ->where('mix_id', $mix->id)
            ->where('permission', Permission::EDIT->value)
            ->exists();
    }

    /**
     * Determine whether the user can update the mix.
     */
    public function update(User $user, Mix $mix): bool
    {
        // Owner can always update
        if ($user->id === $mix->user_id) {
            return true;
        }

        // Check if user has edit permission
        return $user->accessibleMixes()
            ->where('mix_id', $mix->id)
            ->where('permission', Permission::EDIT->value)
            ->exists();
    }

    /**
     * Determine whether the user can delete the mix.
     */
    public function delete(User $user, Mix $mix): bool
    {
        // Only the owner can delete a mix
        return $user->id === $mix->user_id;
    }

    /**
     * Determine whether the user can manage collaborators.
     */
    public function manageCollaborators(User $user, Mix $mix): bool
    {
        // Only the owner can manage collaborators
        return $user->id === $mix->user_id;
    }

    /**
     * Determine whether a user can join a mix via session code.
     */
    public function join(?User $user, Mix $mix): bool
    {
        // Anyone can join a mix with a valid session code
        return !empty($mix->session_code);
    }

    /**
     * Determine whether a user can generate a session code.
     */
    public function generateSessionCode(User $user, Mix $mix): bool
    {
        // Only the owner can generate session codes
        return $user->id === $mix->user_id;
    }
}
