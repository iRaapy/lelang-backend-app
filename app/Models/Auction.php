<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'winner_id',
        'title',
        'description',
        'starting_price',
        'bid_increment',
        'current_price',
        'buy_now_price',
        'bids_count',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'bid_increment' => 'decimal:2',
            'current_price' => 'decimal:2',
            'buy_now_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class)->orderByDesc('amount');
    }

    public function images()
    {
        return $this->hasMany(AuctionImage::class)->orderBy('order');
    }

    public function highestBid()
    {
        return $this->hasOne(Bid::class)->orderByDesc('amount');
    }

    public function isActive(): bool
    {
        $now = Carbon::now();

        return $this->status === 'active'
            && $now->betweenIncluded($this->starts_at, $this->ends_at);
    }

    public function isExpired(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->ends_at);
    }

    public function minimumNextBid(): float
    {
        return (float) $this->current_price + (float) $this->bid_increment;
    }
}