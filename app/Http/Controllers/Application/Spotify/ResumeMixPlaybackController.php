<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\PlaybackStateManager;
use App\Events\DeviceUpdatedEvent;

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, Request $request): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // Get PlaybackStateManager instance
        $playbackState = app(PlaybackStateManager::class);

        // RACE CONDITION CHECK: Only allow one command per second per mix
        // Move this to PlaybackStateManager
        if ($playbackState->isThrottled($mix, 'command', 0.5)) {
            Log::info("Throttling resume command - too soon after previous command");
            return response()->json([
                'success' => true,
                'is_playing' => true,
                'action' => 'resume',
                'throttled' => true
            ]);
        }

        // Get device_id from request if provided
        $deviceId = $request->input('device_id');

        // Set device ID if provided
        if ($deviceId) {
            $playbackState->setDeviceId($mix, $deviceId);
            Log::info("Using device ID {$deviceId} to resume playback for mix {$mix->id}");
        } else {
            // Get stored device ID if none provided
            $deviceId = $playbackState->getDeviceId($mix);
        }

        // Get playback data through PlaybackStateManager
        $playbackData = $playbackState->getPlaybackData($mix) ?? [];

        // Ensure we have the minimum required fields
        if (!isset($playbackData['item'])) {
            // Get current playing song to populate missing data
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();

            if ($currentSong) {
                $playbackData['item'] = [
                    'id' => $currentSong->song->spotify_id,
                    'name' => $currentSong->song->name,
                    'duration_ms' => $currentSong->song->duration_ms,
                    'artists' => [['name' => $currentSong->song->artist]],
                    'album' => [
                        'images' => [['url' => $currentSong->song->image_url]]
                    ]
                ];
            }
        }

        // Set playing state and timestamp but DON'T broadcast yet
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'resume';

        // FIRST call Spotify API
        try {
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            Log::info("Resume mix using user ID: {$user->id}, co_dj_id: " . ($mix->co_dj_id ?? 'none') .
                      ", device ID: " . ($deviceId ?? 'none'));

            $success = false;

            // Check if we're in a takeback situation
            $resumingFromTakeback = !$mix->co_dj_id && $playbackState->has($mix, 'recent_owner_takeback');

            // Get the specific song we should play
            $specificTrackId = null;
            if ($resumingFromTakeback) {
                $specificTrackId = $playbackState->get($mix, 'switch_track_id');
                Log::info("Owner takeback - looking for specific track: " . ($specificTrackId ?? 'none'));
            }

            // Get the current playing song from the queue
            $currentSong = null;

            // First try to find the exact song that was playing during handoff
            if ($specificTrackId) {
                $currentSong = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'pending')
                    ->whereHas('song', function ($query) use ($specificTrackId) {
                        $query->where('spotify_id', $specificTrackId);
                    })
                    ->with('song')
                    ->first();

                if ($currentSong) {
                    Log::info("Found the specific pre-switch song (ID: {$currentSong->id}) to resume");
                    $currentSong->update(['status' => 'playing']);
                }
            }

            // If no specific song found, get any playing song
            if (!$currentSong) {
                $currentSong = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'playing')
                    ->with('song')
                    ->first();
            }

            // Get the position from PlaybackStateManager
            $positionMs = $playbackState->getPausedPosition($mix);

            // CRITICAL: Always use position 0 after owner takeback
            if (!$mix->co_dj_id && $playbackState->has($mix, 'recent_owner_takeback')) {
                $positionMs = 0;
                Log::info("Owner takeback detected - starting song from beginning");
                $playbackState->forget($mix, 'recent_owner_takeback');
            }

            // Log position info
            if ($positionMs > 0) {
                Log::info("Resuming playback at saved position {$positionMs}ms for mix {$mix->id}");
            } else {
                Log::info("Resuming playback from start for mix {$mix->id}");
            }

            // Always directly play the track instead of resuming after co-DJ handoff
            if ($currentSong) {
                // CRITICAL: Always use playTrackOnDevice for reliability across users
                Log::info("Using direct song play for mix {$mix->id} with track {$currentSong->song->spotify_id}");
                $success = $spotifyService->playTrackOnDevice(
                    $user,
                    $currentSong->song->spotify_id,
                    $deviceId,
                    $positionMs
                );

                // HANDLE DEVICE ERRORS HERE
                if (!$success) {
                    // Check if the device might be inactive
                    if ($deviceId) {
                        // Try to verify device status
                        $devices = $spotifyService->getUserDevices($user);
                        $deviceFound = false;

                        foreach ($devices as $device) {
                            if ($device['id'] === $deviceId) {
                                $deviceFound = true;
                                break;
                            }
                        }

                        if (!$deviceFound) {
                            // Device not found in available devices - likely went inactive
                            Log::warning("Device {$deviceId} not found in available devices for user {$user->id}");

                            // Dispatch event to notify UI that device is inactive
                            event(new DeviceUpdatedEvent($mix));
                        }
                    }
                }
            } else {
                // Only try a generic resume if we don't have a specific track
                Log::info("No specific track found, using generic resume for mix {$mix->id}");
                $success = $spotifyService->resumePlayback($user, $deviceId);

                // Apply same error handling here
                if (!$success && $deviceId) {
                    // Check device availability
                    $devices = $spotifyService->getUserDevices($user);
                    $deviceFound = false;

                    foreach ($devices as $device) {
                        if ($device['id'] === $deviceId) {
                            $deviceFound = true;
                            break;
                        }
                    }

                    if (!$deviceFound) {
                        event(new DeviceUpdatedEvent($mix));
                    }
                }
            }

            // ONLY AFTER success, update cache and broadcast
            if ($success) {
                // Remove paused flag
                $playbackState->setPaused($mix, false);
                $playbackState->setManualChange($mix);

                // CRITICAL: Set a flag to ignore the next track change detection
                if ($resumingFromTakeback) {
                    $playbackState->set($mix, 'recent_takeback_track_change', true);
                    Log::info("Set recent takeback track change flag to prevent auto-advance");

                    // Schedule removal of the flag after 5 seconds
                    dispatch(function () use ($mix, $playbackState) {
                        $playbackState->forget($mix, 'recent_takeback_track_change');
                        Log::info("Cleared recent takeback track change flag");
                    })->delay(now()->addSeconds(5));
                }

                // Update cache and broadcast AFTER API success
                $playbackState->setPlaybackData($mix, $playbackData);
                event(new PlaybackDataUpdatedEvent($mix, $playbackData));

                return response()->json([
                    'success' => true,
                    'is_playing' => true
                ]);
            } else {
                Log::error("Failed to resume playback on Spotify for mix {$mix->id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resume playback on Spotify'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error resuming playback: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume playback: ' . $e->getMessage()
            ]);
        }
    }
}
