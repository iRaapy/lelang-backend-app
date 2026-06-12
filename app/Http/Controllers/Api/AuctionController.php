<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\StoreAuctionRequest;
use App\Http\Requests\Auction\UpdateAuctionRequest;
use App\Models\Auction;
use App\Models\AuctionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuctionController extends Controller
{
    /**
     * Daftar lelang aktif (publik) — untuk halaman daftar lelang bidder.
     * Mendukung filter status via query param ?status=active|scheduled|ended
     */
    public function index(Request $request)
    {
        $query = Auction::with(['seller', 'images'])
            ->withCount('bids');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // default: tampilkan yang aktif & terjadwal
            $query->whereIn('status', ['active', 'scheduled']);
        }

        $auctions = $query->latest()->paginate(12);

        return response()->json($auctions);
    }

    /**
     * Buat lelang baru.
     */
    public function store(StoreAuctionRequest $request)
    {
        $auction = Auction::create([
            'seller_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'starting_price' => $request->starting_price,
            'bid_increment' => $request->bid_increment,
            'current_price' => $request->starting_price, // current_price awal = starting_price
            'buy_now_price' => $request->buy_now_price,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'status' => 'scheduled',
        ]);

        // Bonus: upload multi-foto
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('auctions', 'public');

                AuctionImage::create([
                    'auction_id' => $auction->id,
                    'path' => $path,
                    'order' => $index,
                ]);
            }
        }

        return response()->json($auction->load('images'), 201);
    }

    /**
     * Detail lelang — termasuk daftar bid, harga tertinggi, dll.
     */
    public function show(Auction $auction)
    {
        $auction->load(['seller', 'images', 'bids.bidder', 'winner']);

        return response()->json($auction);
    }

    /**
     * Update lelang — hanya selama status scheduled (dicek di UpdateAuctionRequest).
     */
    public function update(UpdateAuctionRequest $request, Auction $auction)
    {
        $data = $request->only([
            'title', 'description', 'starting_price', 'bid_increment',
            'buy_now_price', 'starts_at', 'ends_at',
        ]);

        // Jika starting_price diubah, current_price ikut menyesuaikan
        // (selama belum ada bid, current_price = starting_price)
        if (isset($data['starting_price'])) {
            $data['current_price'] = $data['starting_price'];
        }

        $auction->update($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('auctions', 'public');

                AuctionImage::create([
                    'auction_id' => $auction->id,
                    'path' => $path,
                    'order' => $auction->images()->count() + $index,
                ]);
            }
        }

        return response()->json($auction->load('images'));
    }

    /**
     * Hapus lelang — hanya selama status scheduled.
     */
    public function destroy(Request $request, Auction $auction)
    {
        $this->authorize('delete', $auction);

        // Hapus file gambar dari storage
        foreach ($auction->images as $image) {
            if (! str_starts_with($image->path, 'http')) {
                Storage::disk('public')->delete($image->path);
            }
        }

        $auction->delete();

        return response()->json(['message' => 'Lelang berhasil dihapus.']);
    }

    /**
     * Daftar lelang milik user yang sedang login (dashboard penjual).
     */
    public function myAuctions(Request $request)
    {
        $auctions = Auction::where('seller_id', $request->user()->id)
            ->with(['images', 'winner'])
            ->withCount('bids')
            ->latest()
            ->paginate(12);

        return response()->json($auctions);
    }
}