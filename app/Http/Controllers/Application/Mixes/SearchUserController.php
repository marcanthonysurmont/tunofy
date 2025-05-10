<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mix;
use App\Http\Resources\UserResource;


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

        // 1. Make sure to specify the table name to avoid ambiguity
        // 2. Make sure the mix_id is being passed correctly

        $collaboratorIds = $mix->collaborators()
            ->pluck('users.id')
            ->toArray();

        // Update the search to use where() correctly
        $results = User::search($query)
            ->whereIn('id', $collaboratorIds) // Use whereIn instead
            ->take(10)
            ->get();



        return response()->json([
            'data' => UserResource::collection($results),
        ]);
    }
}
