<?php

namespace App\Http\Requests\Auction;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['required', 'numeric', 'min:0'],
            'bid_increment' => ['required', 'numeric', 'min:1'],
            'buy_now_price' => ['nullable', 'numeric', 'gt:starting_price'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],

            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'Waktu berakhir harus setelah waktu mulai.',
            'starts_at.after_or_equal' => 'Waktu mulai tidak boleh di masa lampau.',
            'buy_now_price.gt' => 'Harga Beli Sekarang harus lebih besar dari harga awal.',
        ];
    }
}