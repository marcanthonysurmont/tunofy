<?php

namespace App\Jobs;

use App\Models\Mix;
use App\Services\Playback\PlaybackStateManager;
use App\Services\Playback\SpotifyPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PollSpotifyMixJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $mix;
    protected $intervalSeconds = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Mix $mix)
    {
        $this->mix = $mix;
    }

    /**
     * Execute the job.
     */
    public function handle(SpotifyPollingService $pollingService, PlaybackStateManager $stateManager)
    {
        // Fresh load from DB
        $this->mix = Mix::find($this->mix->id);

        // Stop polling if mix is inactive
        if (!$this->mix || !$this->mix->is_active) {
            Log::info("Mix {$this->mix->id} is no longer active, stopping polling");
            return;
        }

        // Get lock to prevent concurrent polling
        $lock = Cache::lock("mix:{$this->mix->id}:polling_lock", 30);
        if (!$lock->get()) {
            // Another poll in progress, schedule next one
            $this->scheduleNextPoll();
            return;
        }

        try {
            // Let the polling service handle all the logic
            $pollingService->pollPlayback($this->mix);
        } catch (\Exception $e) {
            Log::error("Error during polling: " . $e->getMessage());
        } finally {
            $lock->release();

            // Schedule next poll
            $this->scheduleNextPoll();
        }
    }

    /**
     * Schedule the next poll
     */
    private function scheduleNextPoll(): void
    {
        // Check if mix is still active
        $mix = Mix::find($this->mix->id);

        if ($mix && $mix->is_active) {
            self::dispatch($this->mix)
                ->delay(now()->addSeconds($this->intervalSeconds));
        }
    }
}
