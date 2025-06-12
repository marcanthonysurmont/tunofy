<?php

namespace App\Events;

use App\Http\Resources\SongResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mix;
use App\Models\Song;

class SongAddedEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Mix $mix, public Song $song) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('mix.' . $this->mix->id),
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
