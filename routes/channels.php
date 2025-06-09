<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Mix;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('mix.{id}', function ($user, $id) {
    // First check if mix is public
    $mix = Mix::find($id);
    if ($mix && $mix->is_public) {
        return true;
    }

    // Otherwise check for user-specific permissions
    return $user->mixes->contains($id) ||
           $user->accessibleMixes->contains($id) ||
           $user->mixAccesses->contains('mix_id', $id);
});
