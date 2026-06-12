<?php

namespace App\Events;

use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Auction $auction,
        public Bid $bid,
    ) {}

    /**
     * Kanal privat per-lelang — semua viewer yang membuka halaman detail lelang
     * berlangganan kanal ini.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auction.'.$this->auction->id),
        ];
    }

    /**
     * Nama event di sisi frontend: Echo.private('auction.X').listen('BidPlaced', ...)
     */
    public function broadcastAs(): string
    {
        return 'BidPlaced';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'bid_id' => $this->bid->id,
            'amount' => (float) $this->bid->amount,
            'bidder_id' => $this->bid->bidder_id,
            'bidder_name' => $this->bid->bidder->name,
            'highest_bid' => (float) $this->auction->current_price,
            'bids_count' => $this->auction->bids_count,
            'minimum_next_bid' => $this->auction->minimumNextBid(),
            'ends_at' => $this->auction->ends_at->toIso8601String(),
            'created_at' => $this->bid->created_at->toIso8601String(),
        ];
    }
}