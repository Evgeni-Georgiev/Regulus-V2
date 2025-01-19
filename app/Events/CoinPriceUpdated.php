<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoinPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $coinData;

    public function __construct(array $coinData)
    {
        $this->coinData = $coinData;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('my-channel');
    }

    public function broadcastAs(): string
    {
        return 'my-event';
    }
}
