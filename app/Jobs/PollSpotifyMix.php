<?php

namespace App\Jobs;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Services\SpotifyPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PollSpotifyMix implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 30; // Add explicit timeout to prevent job hanging
    public $tries = 3;    // Add retry logic for resilience

    protected $mix;
    protected $maxIterations;
    protected $intervalSeconds;

    /**
     * Create a new job instance with a more frequent polling interval.
     */
    public function __construct(Mix $mix, int $maxIterations = 720, int $intervalSeconds = 3)
    {
        $this->mix = $mix;
        $this->maxIterations = $maxIterations;
        $this->intervalSeconds = $intervalSeconds;
    }

    /**
     * Execute the job.
     */
    public function handle(SpotifyPollingService $pollingService)
    {
        $mixId = $this->mix->id;

        // Use a cache-based rate limiter
        $cacheKey = "mix_poll_{$mixId}_lastrun";
        $lastRun = Cache::get($cacheKey);
        $now = now()->timestamp;

        // Only allow one run every 2 seconds minimum to prevent overpolling
        if ($lastRun && (($now - $lastRun) < 2)) {
            Log::debug("Rate limiting poll for mix {$mixId} - last run was " . ($now - $lastRun) . " seconds ago");

            // Still reschedule if rate limited to maintain polling sequence
            $this->scheduleNextPoll();
            return;
        }

        // Set current timestamp
        Cache::put($cacheKey, $now, 60);

        // Get a fresh instance of the mix ONCE
        $freshMix = Mix::find($mixId);

        // Check if still active
        if (!$freshMix || !$freshMix->is_active) {
            Log::info("Mix {$mixId} is not active, exiting job immediately");
            return;
        }

        try {
            // Pass the fresh mix to polling service
            $pollingService->pollPlayback($freshMix);

            // Schedule the next poll
            $this->scheduleNextPoll($freshMix);
        } catch (\Exception $e) {
            Log::error("Error polling mix {$mixId}: " . $e->getMessage(), [
                'exception' => $e,
                'mix_id' => $mixId
            ]);

            // Even if there's an error, schedule next poll
            $this->scheduleNextPoll($freshMix);
        }
    }

    /**
     * Schedule the next polling job with appropriate interval
     */
    private function scheduleNextPoll(?Mix $mix = null): void
    {
        // Use provided mix or fallback to the stored one
        $mixToUse = $mix ?? $this->mix;

        // Determine if we should adjust the polling interval
        $interval = $this->intervalSeconds;

        // Create a new job with the fresh mix
        PollSpotifyMix::dispatch($mixToUse, $this->maxIterations, $interval)
            ->delay(now()->addSeconds($interval));

        Log::debug("Scheduling next poll for mix {$mixToUse->id} in {$interval} seconds");
    }

    /**
     * Determine the optimal polling interval based on playback state
     */
    private function determinePollingInterval(): float
    {
        $mixId = $this->mix->id;

        // Default interval
        $nextInterval = $this->intervalSeconds;

        // Check if there's a currently playing song
        $hasPlayingSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->exists();

        // If a song is playing, check if it's near completion
        if ($hasPlayingSong) {
            $songNearingEndKey = "queue:song-ending:{$mixId}";
            if (Cache::has($songNearingEndKey)) {
                // Song is nearing end, poll more frequently
                $nextInterval = 1.5;
                Log::debug("Song nearing end for mix {$mixId} - using tighter interval of {$nextInterval}s");
            }
        } else {
            // No song playing - could use slightly longer interval to save resources
            // Keeping the normal interval is also fine
        }

        return $nextInterval;
    }
}
