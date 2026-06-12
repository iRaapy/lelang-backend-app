<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BidderOutbid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $outbidUserId,
        public Auction $auction,
        public float $newHighestBid,
    ) {}

    /**
     * Kanal privat personal — hanya user dengan ID ini yang bisa subscribe
     * (otorisasi di routes/channels.php).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->outbidUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'BidderOutbid';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'auction_title' => $this->auction->title,
            'new_highest_bid' => $this->newHighestBid,
            'message' => 'Tawaran Anda telah tergeser pada lelang "'.$this->auction->title.'"',
        ];
    }
}