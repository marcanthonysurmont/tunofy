<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Events\SongAddedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddSongToMixRequest;
use App\Models\GlobalUserStat;
use App\Models\Mix;
use App\Models\MixUserStat;
use App\Models\Song;
use App\Models\UserSongHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\PlaybackSession;
use App\Models\QueueSong;
use App\Services\QueueManagementService;

class AddSongToMixController extends Controller
{
    public function __invoke(AddSongToMixRequest $request, Mix $mix): RedirectResponse
    {
        $this->authorize('addSongs', $mix);

        $validated = $request->validated();

        try {
            $user = Auth::user();

            $song = Song::create([
                'mix_id' => $mix->id,
                'spotify_id' => $validated['spotify_id'],
                'user_id' => $user->id,
                'duration_ms' => $validated['duration_ms'],
                'last_fetched_at' => now(),
                'name' => $validated['name'],
                'artist' => $validated['artist'],
                'image_url' => $validated['image_url'],
            ]);

            $mix->update(['mix_count' => $mix->mix_count + 1]);

            SongAddedEvent::dispatch($mix, $song);

            GlobalUserStat::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'songs_added' => DB::raw('songs_added + 1'),
                ],
            );

            UserSongHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'spotify_id' => $validated['spotify_id'],
                ],
                [
                    'song_name' => $validated['name'],
                    'artist' => $validated['artist'],
                    'times_added' => DB::raw('times_added + 1'),
                ],
            );

            MixUserStat::updateOrCreate(
                [
                    'mix_id' => $mix->id,
                    'user_id' => $user->id,
                ],
                [
                    'songs_added' => DB::raw('songs_added + 1'),
                ],
            );

            $session = PlaybackSession::where('mix_id', $mix->id)
                ->where('is_active', true)
                ->latest('started_at')
                ->first();


            if ($session && $mix->is_active) {
                // Update the cached shuffled IDs to include the new song
                $shuffledIds = Cache::get("mix_{$mix->id}_shuffled_ids", []);
                if (!empty($shuffledIds)) {
                    // Add the new song ID to the cache
                    $shuffledIds[] = $song->id;

                    // Add at a semi-random position (in the first half of non-queued songs)
                    if (count($shuffledIds) > 1) {
                        $queuedSongIds = QueueSong::where('mix_id', $mix->id)->pluck('song_id')->toArray();
                        $nonQueuedSongIds = array_diff($shuffledIds, $queuedSongIds);

                        if (count($nonQueuedSongIds) > 1) {
                            array_pop($shuffledIds);
                            $insertPosition = count($queuedSongIds) + rand(0, floor(count($nonQueuedSongIds) / 2));
                            array_splice($shuffledIds, $insertPosition, 0, [$song->id]);
                            Log::info("Added song ID {$song->id} at position {$insertPosition} in the shuffled IDs cache for mix {$mix->id}");
                        }
                    }

                    // Save the updated shuffled IDs
                    Cache::put("mix_{$mix->id}_shuffled_ids", $shuffledIds, now()->addHours(6));

                    // Check if we should add directly to queue
                    $batchSize = $mix->preset->batch_size ?? 5;
                    $pendingSongCount = QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'pending')
                        ->count();
                    $directAdditionThreshold = max(5, $batchSize);

                    // Only add directly if we're under the threshold
                    if ($pendingSongCount <= $directAdditionThreshold) {
                        // Get current playing round
                        $playingRound = DB::table('queue_songs')
                            ->where('mix_id', $mix->id)
                            ->where('status', 'playing')
                            ->value('round_number');

                        // Get latest round number
                        $latestRound = DB::table('queue_songs')
                            ->where('mix_id', $mix->id)
                            ->max('round_number') ?? 0;

                        // Get all pending rounds ordered by round_number
                        $pendingRounds = DB::table('queue_songs')
                            ->where('mix_id', $mix->id)
                            ->where('status', 'pending')
                            ->groupBy('round_number')
                            ->pluck('round_number')
                            ->sort()
                            ->values();

                        // Find a suitable round
                        $targetRound = null;
                        foreach ($pendingRounds as $round) {
                            if ($round <= $playingRound) {
                                continue;
                            }

                            $songsInRound = DB::table('queue_songs')
                                ->where('mix_id', $mix->id)
                                ->where('round_number', $round)
                                ->count();

                            if ($songsInRound < $batchSize) {
                                $targetRound = $round;
                                Log::info("Using existing pending round {$round} for song {$song->id}");
                                break;
                            }
                        }

                        if ($targetRound === null) {
                            $targetRound = $playingRound ? ($playingRound + 1) : ($latestRound + 1);
                            Log::info("Creating new round {$targetRound} for song {$song->id}");
                        }

                        // Get order in round
                        $songsInTargetRound = DB::table('queue_songs')
                            ->where('mix_id', $mix->id)
                            ->where('round_number', $targetRound)
                            ->count();
                        $nextOrder = $songsInTargetRound + 1;

                        // Check if already queued
                        $alreadyQueued = DB::table('queue_songs')
                            ->where('mix_id', $mix->id)
                            ->where('song_id', $song->id)
                            ->exists();

                        if (!$alreadyQueued) {
                            try {
                                // Add directly to queue
                                DB::table('queue_songs')->insert([
                                    'mix_id' => $mix->id,
                                    'song_id' => $song->id,
                                    'playback_session_id' => $session->id,
                                    'round_number' => $targetRound,
                                    'order' => $nextOrder,
                                    'status' => 'pending',
                                    'is_killed' => false,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);

                                Log::info("Added song {$song->id} directly to queue for mix {$mix->id} in round {$targetRound} (position {$nextOrder}/{$batchSize})");

                                // Make sure the event class exists and is properly namespaced
                                if (class_exists(\App\Events\QueueStateUpdatedEvent::class)) {
                                    event(new \App\Events\QueueStateUpdatedEvent($mix));
                                } else {
                                    Log::warning("QueueStateUpdatedEvent class not found, skipping event dispatch");
                                }

                                Log::info("Added song {$song->id} directly to queue because pending count ({$pendingSongCount}) is below threshold");
                            } catch (\Exception $e) {
                                Log::error("Error adding song {$song->id} to queue: " . $e->getMessage());
                            }
                        } else {
                            Log::warning("Song {$song->id} already exists in queue for mix {$mix->id}, skipping direct addition");
                        }
                    } else {
                        Log::info("Not adding song {$song->id} directly to queue since pending count ({$pendingSongCount}) is above threshold. Will be added through queue extension when appropriate.");
                    }

                    // Extend the queue if needed
                    $threshold = max(3, ($batchSize * 0.5));
                    if ($pendingSongCount <= $threshold) {
                        try {
                            app(QueueManagementService::class)->appendRoundsToQueue($mix, 1);
                            Log::info("Extended queue after adding new song to mix {$mix->id}");
                        } catch (\Exception $e) {
                            Log::error("Error extending queue: " . $e->getMessage());
                        }
                    }
                }
            }


            return redirect()->back()
                ->with('success', 'Song added to mix successfully.');
        } catch (Exception $e) {

            if ($e->getCode() == 23000) {
                return redirect()->back()
                    ->with('error', 'Song already exists in the mix.');
            }

            return redirect()->back()
                ->with('error', 'Failed to add song to mix');
        }
    }
}
