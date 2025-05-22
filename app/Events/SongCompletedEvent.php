<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mix;

class SongCompletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mix $mix)
    {}

    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'song.completed';
    }
}
