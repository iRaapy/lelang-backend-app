<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AuctionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Auction $auction,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auction.'.$this->auction->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AuctionEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'status' => $this->auction->status,
            'winner_id' => $this->auction->winner_id,
            'winner_name' => $this->auction->winner?->name,
            'final_price' => (float) $this->auction->current_price,
        ];
    }
}