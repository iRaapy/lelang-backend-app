<?php

namespace App\Http\Controllers\Api;

use App\Events\AuctionEnded;
use App\Events\BidderOutbid;
use App\Events\BidPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bid\PlaceBidRequest;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BidController extends Controller
{
    /**
     * Tempatkan tawaran baru pada sebuah lelang.
     * Menggunakan DB transaction + lockForUpdate untuk mencegah race condition
     * saat beberapa user menawar pada saat yang hampir bersamaan.
     */
    public function store(PlaceBidRequest $request, Auction $auction)
    {
        $user = $request->user();
        $amount = (float) $request->input('amount');

        $result = DB::transaction(function () use ($auction, $user, $amount) {
            // Lock baris auction agar tidak ada proses lain yang membaca/menulis
            // data ini secara bersamaan (mencegah double-bid pada amount yang sama).
            $auction = Auction::where('id', $auction->id)->lockForUpdate()->first();

            // --- Re-validasi DEFINITIF di dalam lock (karena data bisa berubah
            //     antara saat FormRequest validasi dan transaksi ini dimulai) ---

            if ($auction->seller_id === $user->id) {
                throw ValidationException::withMessages([
                    'auction' => ['Anda tidak dapat menawar pada lelang milik Anda sendiri.'],
                ]);
            }

            if (! $auction->isActive()) {
                throw ValidationException::withMessages([
                    'auction' => ['Lelang ini tidak sedang berjalan / sudah berakhir.'],
                ]);
            }

            $minimum = $auction->minimumNextBid();
            if ($amount < $minimum) {
                throw ValidationException::withMessages([
                    'amount' => ['Tawaran minimum adalah '.number_format($minimum, 0, ',', '.')],
                ]);
            }

            // Simpan siapa penawar tertinggi SEBELUM bid ini, untuk dikirim notifikasi outbid
           $previousHighestBid = $auction->highestBid()->first();
            // Tandai semua bid sebelumnya sebagai outbid
            Bid::where('auction_id', $auction->id)
                ->where('status', 'active')
                ->update(['status' => 'outbid']);

            // Simpan bid baru
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'bidder_id' => $user->id,
                'amount' => $amount,
                'status' => 'active',
            ]);

            // --- Bonus: Anti-sniping ---
            // Jika bid masuk dalam N detik terakhir, perpanjang waktu lelang.
            $antiSnipeSeconds = 30; // perpanjang jika bid masuk <30 detik sebelum berakhir
            $extensionSeconds = 60; // tambah 60 detik

            $secondsRemaining = now()->diffInSeconds($auction->ends_at, false);
            $newEndsAt = $auction->ends_at;

            if ($secondsRemaining > 0 && $secondsRemaining <= $antiSnipeSeconds) {
                $newEndsAt = $auction->ends_at->copy()->addSeconds($extensionSeconds);
            }

            // Update auction: current_price, bids_count, ends_at (jika anti-snipe aktif)
            $auction->update([
                'current_price' => $amount,
                'bids_count' => $auction->bids_count + 1,
                'ends_at' => $newEndsAt,
            ]);

            $auction->refresh();

            return [
                'bid' => $bid,
                'auction' => $auction,
                'previous_highest_bid' => $previousHighestBid,
            ];
        });

        $bid = $result['bid'];
        $auction = $result['auction'];
        $previousHighestBid = $result['previous_highest_bid'];

        // --- Broadcast realtime (di luar transaction, setelah commit) ---

        // 1. Broadcast ke semua viewer lelang ini
        broadcast(new BidPlaced($auction, $bid->load('bidder')))->toOthers();

        // 2. Notifikasi outbid ke penawar sebelumnya (jika ada dan bukan dirinya sendiri)
        if ($previousHighestBid && $previousHighestBid->bidder_id !== $user->id) {
            broadcast(new BidderOutbid(
                $previousHighestBid->bidder_id,
                $auction,
                (float) $auction->current_price
            ));
        }

        // 3. Bonus: Buy Now — jika tawaran >= buy_now_price, langsung tutup lelang
        if ($auction->buy_now_price && $amount >= (float) $auction->buy_now_price) {
            $this->closeAuction($auction, $bid->bidder_id);
        }

        return response()->json([
            'bid' => $bid->load('bidder'),
            'auction' => $auction,
        ], 201);
    }

    /**
     * Tutup lelang dan tetapkan pemenang (dipakai oleh Buy Now & Scheduler).
     */
  public function closeAuction(Auction $auction, ?int $winnerId = null): void
{
    $closed = false;
    $auctionToAnnounce = null;

    DB::transaction(function () use ($auction, $winnerId, &$closed, &$auctionToAnnounce) {
        $auction = Auction::where('id', $auction->id)->lockForUpdate()->first();

        if ($auction->status === 'ended') {
            return;
        }

        $winner = $winnerId ?? $auction->highestBid()->first()?->bidder_id;

        $auction->update([
            'status' => 'ended',
            'winner_id' => $winner,
        ]);

        $auction->refresh();
        $closed = true;
        $auctionToAnnounce = $auction->load('winner');
    });

    // Broadcast DI LUAR transaction
    if ($closed && $auctionToAnnounce) {
        broadcast(new AuctionEnded($auctionToAnnounce));
    }
}
}