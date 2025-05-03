<?php

namespace App\Events;

use App\Models\Mix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MixStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $mix;
    public $isActive;

    /**
     * Create a new event instance.
     */
    public function __construct(Mix $mix, bool $isActive)
    {
        $this->mix = $mix;
        $this->isActive = $isActive;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MixStatusChanged';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'mixId' => $this->mix->id,
            'isActive' => $this->isActive,
            'timestamp' => now()->timestamp
        ];
    }
}
