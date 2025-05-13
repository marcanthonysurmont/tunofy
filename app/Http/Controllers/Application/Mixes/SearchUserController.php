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

        // Apply premium filter if requested
        if ($premiumOnly) {
            $searchResults = $searchResults->where('type', 'premium');
        }

        $searchResults = $searchResults->take(10)->get();

        if ($searchResults->isNotEmpty()) {
            // Use the relationship instead of a join to ensure pivot data is available
            $query = $mix->collaborators()
                ->whereIn('users.id', $searchResults->pluck('id'));

            // Apply premium filter if requested
            if ($premiumOnly) {
                $query = $query->where('users.type', 'premium');
            }

            $results = $query->get();
        } else {
            $results = collect([]);
        }

        return response()->json([
            'data' => UserResource::collection($results),
        ]);
    }
}
