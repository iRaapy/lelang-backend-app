<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo (sesuai kebutuhan README: penjual & penawar)
        $seller = User::create([
            'name' => 'Demo Penjual',
            'email' => 'penjual@demo.com',
            'password' => Hash::make('password'),
        ]);

        $bidder1 = User::create([
            'name' => 'Demo Penawar 1',
            'email' => 'penawar1@demo.com',
            'password' => Hash::make('password'),
        ]);

        $bidder2 = User::create([
            'name' => 'Demo Penawar 2',
            'email' => 'penawar2@demo.com',
            'password' => Hash::make('password'),
        ]);

        // Lelang aktif dengan beberapa bid
        $auction1 = Auction::factory()->create([
            'seller_id' => $seller->id,
            'title' => 'Lukisan Pemandangan Bali',
            'description' => 'Lukisan cat minyak karya seniman lokal Ubud, ukuran 60x80cm.',
            'starting_price' => 500000,
            'bid_increment' => 50000,
            'current_price' => 600000,
            'bids_count' => 2,
        ]);

        Bid::create([
            'auction_id' => $auction1->id,
            'bidder_id' => $bidder1->id,
            'amount' => 550000,
            'status' => 'outbid',
        ]);

        Bid::create([
            'auction_id' => $auction1->id,
            'bidder_id' => $bidder2->id,
            'amount' => 600000,
            'status' => 'active',
        ]);

        // Lelang aktif tanpa bid, dengan Buy Now
        Auction::factory()->create([
            'seller_id' => $seller->id,
            'title' => 'Keris Pusaka Antik',
            'description' => 'Keris antik koleksi pribadi, dijual karena pindah rumah.',
            'starting_price' => 1000000,
            'bid_increment' => 100000,
            'current_price' => 1000000,
            'buy_now_price' => 2000000,
            'bids_count' => 0,
        ]);

        // Lelang terjadwal (belum mulai)
        Auction::factory()->scheduled()->create([
            'seller_id' => $seller->id,
            'title' => 'Vinyl Record Koleksi 80an',
            'description' => 'Koleksi vinyl original era 80an, kondisi masih bagus.',
            'starting_price' => 250000,
            'bid_increment' => 25000,
            'current_price' => 250000,
            'bids_count' => 0,
        ]);

        // Lelang yang sudah selesai (untuk lihat tampilan hasil)
        $auction4 = Auction::factory()->ended()->create([
            'seller_id' => $seller->id,
            'title' => 'Patung Kayu Garuda',
            'description' => 'Patung kayu ukir Garuda, tinggi 50cm.',
            'starting_price' => 300000,
            'bid_increment' => 25000,
            'current_price' => 375000,
            'bids_count' => 3,
            'winner_id' => $bidder1->id,
        ]);

        Bid::create([
            'auction_id' => $auction4->id,
            'bidder_id' => $bidder1->id,
            'amount' => 375000,
            'status' => 'active',
        ]);
    }
}