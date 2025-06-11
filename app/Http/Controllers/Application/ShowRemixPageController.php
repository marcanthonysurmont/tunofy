<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Models\UserSongHistory;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ShowRemixPageController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $highestAddedSong = UserSongHistory::highestAddedSong($user);

        return Inertia::render('RemixPage', [
            'globalUserStat' => $user->globalUserStat,
            'highestAddedSong' => $highestAddedSong
        ]);
    }
}
