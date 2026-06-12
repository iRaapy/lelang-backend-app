<?php

namespace App\Http\Requests\Auction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        return $this->user()
            && $this->user()->id === $auction->seller_id
            && $auction->status === 'scheduled';
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'bid_increment' => ['sometimes', 'required', 'numeric', 'min:1'],
            'buy_now_price' => ['nullable', 'numeric', 'gt:starting_price'],
            'starts_at' => ['sometimes', 'required', 'date', 'after_or_equal:now'],
            'ends_at' => ['sometimes', 'required', 'date', 'after:starts_at'],

            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'max:4096'],
        ];
    }
}