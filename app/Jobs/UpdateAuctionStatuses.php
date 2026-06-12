<?php

namespace App\Jobs;

use App\Events\AuctionEnded;
use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateAuctionStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        // 1. scheduled -> active (waktu mulai sudah tercapai, belum berakhir)
        Auction::where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->update(['status' => 'active']);

        // 2. (scheduled atau active) -> ended (waktu berakhir sudah lewat)
        $expiredAuctions = Auction::whereIn('status', ['scheduled', 'active'])
            ->where('ends_at', '<=', $now)
            ->get();

        foreach ($expiredAuctions as $auction) {
            $this->closeAuction($auction);
        }
    }

    /**
     * Tutup satu lelang: set status ended, tentukan pemenang, broadcast.
     */
    protected function closeAuction(Auction $auction): void
    {
        DB::transaction(function () use ($auction) {
            $auction = Auction::where('id', $auction->id)->lockForUpdate()->first();

            if ($auction->status === 'ended') {
                return;
            }

            $winnerId = $auction->highestBid()->first()?->bidder_id;

            $auction->update([
                'status' => 'ended',
                'winner_id' => $winnerId,
            ]);

            $auction->refresh();

            broadcast(new AuctionEnded($auction->load('winner')));
        });
    }
}