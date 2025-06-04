<?php

namespace App\Jobs;

use App\Models\Mix;
use App\Services\Playback\SpotifyPollingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

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
    public function handle(SpotifyPollingService $pollingService): void
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
            // Only schedule if still active
            if ($this->mix && $this->mix->is_active) {
                $this->scheduleNextPoll($this->mix);
            }
            return;
        }

        try {
            // Let the polling service handle all the logic
            $pollingService->pollPlayback($this->mix);
        } catch (Exception $e) {
            Log::error("Error during polling: " . $e->getMessage());
        } finally {
            $lock->release();

            // Schedule next poll
            $this->scheduleNextPoll($this->mix);
        }
    }

    /**
     * Schedule the next poll
     */
    private function scheduleNextPoll(Mix $mix): void
    {
        if ($mix && $mix->is_active) {
            self::dispatch($mix)
                ->delay(now()->addSeconds($this->intervalSeconds));
        }
    }
}
