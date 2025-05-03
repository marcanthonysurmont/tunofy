<?php

namespace App\Jobs;

use App\Models\Mix;
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

    protected $mix;
    protected $maxIterations;
    protected $intervalSeconds;

    public function __construct(Mix $mix, int $maxIterations = 720, int $intervalSeconds = 5)
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
        $cacheKey = "mix_poll_{$mixId}_lastrun"; // CHANGED: Replaced hyphens with underscores
        $lastRun = Cache::get($cacheKey);
        $now = now()->timestamp;

        // Only allow one run every 4 seconds minimum
        if ($lastRun && (($now - $lastRun) < 4)) { // FIXED: Added extra parentheses
            Log::warning("Rate limiting poll for mix {$mixId} - last run was " . ($now - $lastRun) . " seconds ago"); // FIXED: Used concatenation
            return;
        }

        // Set current timestamp
        Cache::put($cacheKey, $now, 60);

        Log::info("Started polling job for mix {$mixId} with PID=" . getmypid());

        // Immediately check if still active
        $this->mix = Mix::find($mixId);

        if (!$this->mix || !$this->mix->is_active) {
            Log::info("Mix {$mixId} is not active, exiting job immediately");
            return;
        }

        // Single poll only - don't loop
        try {
            Log::info("Single polling iteration for mix {$mixId}");
            $pollingService->pollPlayback($this->mix);

            // Check if we should schedule another job
            $this->mix->refresh();
            if ($this->mix->is_active) {
                Log::info("Scheduling next poll for mix {$mixId} in {$this->intervalSeconds} seconds");
                $nextJob = new PollSpotifyMix($this->mix);
                dispatch($nextJob->delay(now()->addSeconds($this->intervalSeconds)));
            } else {
                Log::info("Mix {$mixId} is no longer active, not scheduling next poll");
            }

        } catch (\Exception $e) {
            Log::error("Error polling mix {$mixId}: " . $e->getMessage());
        }
    }
}
