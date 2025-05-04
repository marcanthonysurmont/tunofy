<?php

namespace App\Events;

use App\Models\Mix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MixStatusChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Mix $mix, public bool $isActive)
    {}
    
    public function broadcastOn(): array
    {
        return [
            new Channel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mix-status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'mix_id' => $this->mix->id,
            'isActive' => $this->isActive,
            'timestamp' => now()->timestamp
        ];
    }
}
