<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mix;
use App\Http\Resources\SongResource;
use App\Models\Song;

class ImportedPlaylistEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mix $mix, public Collection $songs, public bool $hasMore) 
    {}

    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'playlist.imported';
    }

    public function broadcastWith(): array
    {
        return [
            'songs' => SongResource::collection($this->songs),
            'has_more' => $this->hasMore,
        ];
    }
}
