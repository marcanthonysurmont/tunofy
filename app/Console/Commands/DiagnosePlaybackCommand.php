<?php

namespace App\Console\Commands;

use App\Models\Mix;
use App\Services\PlaybackStateManager;
use Illuminate\Console\Command;
use Carbon\CarbonInterval;

class DiagnosePlaybackCommand extends Command
{
    protected $signature = 'tunofy:diagnose-playback {mix?} {--all}';
    protected $description = 'Diagnose playback state for a mix or all active mixes';

    public function handle(PlaybackStateManager $playbackState)
    {
        if ($this->option('all')) {
            $mixes = Mix::where('is_active', true)->get();
            if ($mixes->isEmpty()) {
                $this->info('No active mixes found');
                return 0;
            }

            foreach ($mixes as $mix) {
                $this->displayMixState($mix, $playbackState);
            }
        } else {
            $mixId = $this->argument('mix');
            if (!$mixId) {
                $this->error('Please provide a mix ID or use --all');
                return 1;
            }

            $mix = Mix::find($mixId);
            if (!$mix) {
                $this->error("Mix with ID {$mixId} not found");
                return 1;
            }

            $this->displayMixState($mix, $playbackState);
        }

        return 0;
    }

    /**
     * Display all state information for a mix
     */
    private function displayMixState(Mix $mix, PlaybackStateManager $playbackState)
    {
        $this->info("\n=== Mix: {$mix->name} (ID: {$mix->id}) ===");
        $this->info("Active: " . ($mix->is_active ? 'Yes' : 'No'));
        $this->info("Owner: {$mix->user->name} (ID: {$mix->user_id})");

        if ($mix->co_dj_id) {
            $this->info("Co-DJ: {$mix->coDj->name} (ID: {$mix->co_dj_id})");
        }

        $states = $playbackState->getAllStates($mix);

        if (empty($states)) {
            $this->warn("No playback state found");
            return;
        }

        // Show song playback info
        $this->newLine();
        $this->info("--- Playback Information ---");

        // Display playback progress if available
        $progress = $states[PlaybackStateManager::SONG_PROGRESS] ?? null;
        $duration = $states[PlaybackStateManager::SONG_DURATION] ?? null;

        if ($progress !== null && $duration !== null) {
            $progressFormatted = $this->formatTimeMs($progress);
            $durationFormatted = $this->formatTimeMs($duration);
            $percentage = round(($progress / $duration) * 100, 1);

            $this->info("Position: {$progressFormatted} / {$durationFormatted} ({$percentage}%)");

            // Create a progress bar visualization
            $barWidth = 30;
            $filledWidth = round(($progress / $duration) * $barWidth);
            $bar = '[' . str_repeat('=', $filledWidth) . str_repeat(' ', $barWidth - $filledWidth) . ']';
            $this->info($bar);
        }

        // Display key playback states
        $this->info("Is Paused: " . ($playbackState->has($mix, PlaybackStateManager::PAUSED) ? 'Yes' : 'No'));
        $this->info("Queue Completed: " . ($playbackState->has($mix, PlaybackStateManager::QUEUE_COMPLETED) ? 'Yes' : 'No'));
        $this->info("Song Nearing End: " . ($playbackState->has($mix, PlaybackStateManager::SONG_NEARING_END) ? 'Yes' : 'No'));

        // Display polling information
        $this->newLine();
        $this->info("--- Polling Information ---");
        $lastPollTime = $states[PlaybackStateManager::LAST_POLL_TIME] ?? null;

        if ($lastPollTime) {
            $lastPollFormatted = $lastPollTime->format('Y-m-d H:i:s');
            $secondsSinceLastPoll = now()->diffInSeconds($lastPollTime);
            $this->info("Last Poll: {$lastPollFormatted} ({$secondsSinceLastPoll}s ago)");
        } else {
            $this->info("Last Poll: Never");
        }

        $this->info("Currently Polling: " . ($playbackState->has($mix, PlaybackStateManager::POLLING_ACTIVE) ? 'Yes' : 'No'));
        $this->info("Should Poll Now: " . ($playbackState->shouldPoll($mix) ? 'Yes' : 'No'));

        // Display flag states that affect polling
        $this->info("Manual Change Flag: " . ($playbackState->has($mix, PlaybackStateManager::MANUAL_CHANGE) ? 'Yes' : 'No'));
        $this->info("Device Changed Flag: " . ($playbackState->has($mix, PlaybackStateManager::DEVICE_CHANGED) ? 'Yes' : 'No'));
        $this->info("Playback Changed Flag: " . ($playbackState->has($mix, PlaybackStateManager::PLAYBACK_CHANGED) ? 'Yes' : 'No'));

        // Show all other playback states
        $this->newLine();
        $this->info("--- All Playback States ---");

        // Sort states by key for easier scanning
        ksort($states);

        foreach ($states as $key => $value) {
            // Skip states we've already displayed in detail
            if (in_array($key, [
                PlaybackStateManager::SONG_PROGRESS,
                PlaybackStateManager::SONG_DURATION,
                PlaybackStateManager::LAST_POLL_TIME
            ])) {
                continue;
            }

            $displayValue = $this->formatValue($value);
            $this->line("$key: $displayValue");
        }
    }

    /**
     * Format a value for display
     */
    private function formatValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        } elseif (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        } else {
            return (string) $value;
        }
    }

    /**
     * Format milliseconds into mm:ss format using Carbon
     */
    private function formatTimeMs(int $ms): string
    {
        // Convert milliseconds to seconds and create a CarbonInterval
        $interval = CarbonInterval::seconds($ms / 1000)->cascade();

        // Format as mm:ss
        return sprintf('%d:%02d', $interval->minutes, $interval->seconds);
    }
}
