<?php

namespace App\Events;

use App\Http\Resources\SongResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mix;
use App\Models\Song;

class SongAddedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mix $mix, public Song $song)
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'song.added';
    }

    public function broadcastWith(): array
    {
        return [
            'song' => SongResource::make($this->song),
        ];
    }
}
