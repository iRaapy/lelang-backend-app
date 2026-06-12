<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Hanya pemilik lelang yang boleh update/delete, dan hanya selama status scheduled.
     */
    public function update(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id && $auction->status === 'scheduled';
    }

    public function delete(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id && $auction->status === 'scheduled';
    }

    /**
     * Hanya pemilik yang boleh melihat detail manajemen lelangnya sendiri (opsional, untuk dashboard).
     */
    public function view(User $user, Auction $auction): bool
    {
        return true; // detail lelang publik bisa dilihat semua user terautentikasi
    }
}