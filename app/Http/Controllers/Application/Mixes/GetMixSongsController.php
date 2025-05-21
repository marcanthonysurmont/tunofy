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

        $songs = $mix->songs()
            ->with('user')
            ->paginate(20, ['*'], 'page', $validated['page']);

        return SongResource::collection($songs);
    }

}
