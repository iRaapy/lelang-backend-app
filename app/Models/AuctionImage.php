<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionImage extends Model
{
    protected $fillable = [
        'auction_id',
        'path',
        'order',
    ];
    protected $appends = ['url'];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return asset('storage/'.$this->path);
    }
}