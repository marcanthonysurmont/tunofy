<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mix;

class RemoveUserAccessEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mix $mix)
    {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('mix.' . $this->mix->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'remove-user-access';
    }
}
