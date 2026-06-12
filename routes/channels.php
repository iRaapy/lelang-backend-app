<?php

use App\Models\Auction;
use Illuminate\Support\Facades\Broadcast;

/**
 * Kanal publik per-lelang (private channel, tapi semua user terautentikasi boleh subscribe)
 * Dipakai untuk: BidPlaced, AuctionEnded
 */
Broadcast::channel('auction.{auctionId}', function ($user, $auctionId) {
    // Semua user yang sudah login boleh melihat lelang manapun
    return Auction::where('id', $auctionId)->exists();
});

/**
 * Kanal privat personal — hanya pemilik akun yang boleh subscribe.
 * Dipakai untuk: BidderOutbid
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * (Bonus) Presence channel — menampilkan jumlah peserta yang menonton lelang.
 */
Broadcast::channel('presence-auction.{auctionId}', function ($user, $auctionId) {
    if (! Auction::where('id', $auctionId)->exists()) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});