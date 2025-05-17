<?php

namespace App\Jobs;

use App\Models\Mix;
use App\Services\PlaybackStateManager;
use App\Services\SpotifyPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollSpotifyMixJob implements ShouldQueue
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
    public function handle(SpotifyPollingService $pollingService, PlaybackStateManager $stateManager)
    {
        $mixId = $this->mix->id;

        // Don't poll if queue is completed - use state manager
        if ($stateManager->isQueueCompleted($this->mix)) {
            Log::info("Mix {$mixId} queue completed, exiting poll job");
            return;
        }

        // Don't poll if paused - use state manager
        if ($stateManager->isPaused($this->mix)) {
            Log::info("Mix {$mixId} is paused, skipping polling");
            $this->scheduleNextPoll(null, $stateManager);
            return;
        }

        // Check for manual change - use state manager AND consume the flag immediately
        $wasManuallyChanged = $stateManager->has($this->mix, PlaybackStateManager::MANUAL_CHANGE);
        if ($wasManuallyChanged) {
            Log::info("Mix {$mixId} was just manually changed, skipping this poll");

            // CRUCIAL: Consume the flag immediately to prevent it from persisting
            $stateManager->forget($this->mix, PlaybackStateManager::MANUAL_CHANGE);

            $this->scheduleNextPoll(null, $stateManager);
            return;
        }

        // Use a polling lock to prevent concurrent polling
        if (!$stateManager->startPolling($this->mix)) {
            Log::debug("Another poll already in progress for mix {$mixId}, skipping");
            // Reschedule with normal interval
            $this->scheduleNextPoll(null, $stateManager);
            return;
        }

        try {
            // Get a fresh instance of the mix
            $freshMix = Mix::find($mixId);

            // Check if still active
            if (!$freshMix || !$freshMix->is_active) {
                Log::info("Mix {$mixId} is not active, exiting job immediately");
                return; // Don't schedule next poll
            }

            // Pass the fresh mix to polling service
            $result = $pollingService->pollPlayback($freshMix);

            // Update last poll time in state manager
            $stateManager->updatePollTime($freshMix);

            // Consume any one-time flags (like manual_change)
            $stateManager->consumePollFlags($freshMix);

            // Check for special stop polling signal
            if ($result === "stop_polling") {
                Log::info("Stopping polling for mix {$mixId} as queue has completed");
                $stateManager->setQueueCompleted($freshMix); // Mark as completed in state manager
                return;  // Don't schedule next poll
            }

            // Schedule the next poll with intelligent interval
            $this->scheduleNextPoll($freshMix, $stateManager);
        } catch (\Exception $e) {
            Log::error("Error polling mix {$mixId}: " . $e->getMessage(), [
                'exception' => $e,
                'mix_id' => $mixId
            ]);

            // Even if there's an error, schedule next poll
            $this->scheduleNextPoll($freshMix, $stateManager);
        } finally {
            // Always release polling lock
            $stateManager->endPolling($this->mix);
        }
    }

    /**
     * Schedule the next polling job with appropriate interval
     */
    private function scheduleNextPoll(?Mix $mix = null, ?PlaybackStateManager $stateManager = null): void
    {
        // Use provided mix or fallback to the stored one
        $mixToUse = $mix ?? $this->mix;

        // CRITICAL FIX: Check if mix is still active
        $freshCheck = Mix::find($mixToUse->id);
        if (!$freshCheck || !$freshCheck->is_active) {
            Log::info("Not scheduling next poll for inactive mix {$mixToUse->id}");
            return;
        }

        // Determine optimal polling interval
        $interval = $this->determinePollingIntervalWithStateManager($mixToUse, $stateManager);

        // Create a new job with the fresh mix
        PollSpotifyMixJob::dispatch($mixToUse, $this->maxIterations, $interval)
            ->delay(now()->addSeconds($interval));

        Log::debug("Scheduling next poll for mix {$mixToUse->id} in {$interval} seconds");
    }

    /**
     * Determine the optimal polling interval based on playback state manager
     */
    private function determinePollingIntervalWithStateManager(Mix $mix, ?PlaybackStateManager $stateManager = null): float
    {
        // If no state manager provided, use default interval
        if (!$stateManager) {
            return $this->intervalSeconds;
        }

        // Priority flags for immediate polling
        if ($stateManager->has($mix, PlaybackStateManager::MANUAL_CHANGE) ||
            $stateManager->has($mix, PlaybackStateManager::DEVICE_CHANGED) ||
            $stateManager->has($mix, PlaybackStateManager::PLAYBACK_CHANGED)) {
            return 1; // Poll quickly but not instantly to avoid rate limiting
        }

        // Check if song is nearing end
        if ($stateManager->has($mix, PlaybackStateManager::SONG_NEARING_END)) {
            // Song is nearing end, poll more frequently
            $nextInterval = 1.5;
            Log::debug("Song nearing end for mix {$mix->id} - using tighter interval of {$nextInterval}s");
            return $nextInterval;
        }

        // Default interval
        return $this->intervalSeconds;
    }
}
