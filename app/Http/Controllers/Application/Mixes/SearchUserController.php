<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Models\Mix;

class SearchUserController extends Controller
{
    public function __invoke(SearchUserRequest $request, Mix $mix): JsonResponse
    {
        $validated = $request->validated();
        $query = $validated['q'];
        $premiumOnly = $validated['premium'] ?? false;

        // Get IDs of collaborators for this mix
        $collaboratorIds = $mix->collaborators()->pluck('users.id')->toArray();

        // Search users with Scout and filter by the collaborator IDs
        $searchResults = User::search($query)
            ->whereIn('id', $collaboratorIds);

        // Get search results
        $searchUsers = $searchResults->take(10)->get();

        // If we have no search results, return an empty collection
        if ($searchUsers->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        // Use the relationship to get users with pivot data
        $query = $mix->collaborators()
            ->whereIn('users.id', $searchUsers->pluck('id'));

        // Apply premium filter if requested
        if ($premiumOnly) {
            $query->where('users.type', 'premium');
        }

        $results = $query->get();

        return response()->json([
            'data' => UserResource::collection($results),
        ]);
    }
}
