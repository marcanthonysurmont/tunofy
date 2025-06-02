<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Requests\GetMixSongsRequest;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Http\Resources\SongResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetMixSongsController extends Controller
{
    public function __invoke(GetMixSongsRequest $request, Mix $mix): AnonymousResourceCollection
    {
        $this->authorize('view', $mix);

        $validated = $request->validated();
        $page = $validated['page'] ?? 1;
        $perPage = 15;

        // Get the songs with pagination and order by latest
        $songs = $mix->songs()
            ->with('user')
            ->latest()  // Order by latest to ensure new songs appear at the top
            ->paginate($perPage, ['*'], 'page', $page);

        return SongResource::collection($songs);
    }
}
