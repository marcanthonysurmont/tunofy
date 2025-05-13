<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mix;

class SearchUserController extends Controller
{
    public function __invoke(Request $request, Mix $mix): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        // Get IDs of collaborators for this mix
        $collaboratorIds = $mix->collaborators()->pluck('users.id')->toArray();

        // Search users with Scout and filter by the collaborator IDs
        $searchResults = User::search($query)
            ->whereIn('id', $collaboratorIds)
            ->take(10)
            ->get();

        if ($searchResults->isNotEmpty()) {
            // Use the relationship instead of a join to ensure pivot data is available
            $results = $mix->collaborators()
                ->whereIn('users.id', $searchResults->pluck('id'))
                ->get();
        } else {
            $results = collect([]);
        }

        return response()->json([
            'data' => UserResource::collection($results),
        ]);
    }
}
