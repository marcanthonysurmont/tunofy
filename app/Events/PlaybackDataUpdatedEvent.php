<?php

namespace App\Events;

use App\Models\Mix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Http\Resources\SpotifyPlaybackResource;

class PlaybackDataUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Mix $mix, public array $playbackData)
    {}

    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'playback-data';
    }

    public function broadcastWith(): array
    {
        return [
            'mix_id' => $this->mix->id,
            'playback_data' => new SpotifyPlaybackResource($this->playbackData),
            'timestamp' => now()->timestamp
        ];
    }
}
