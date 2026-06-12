<?php

namespace App\Http\Requests\Bid;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Validasi tambahan yang butuh akses ke model Auction.
     * Ini adalah validasi "pre-check" cepat; validasi DEFINITIF dengan
     * DB lock dilakukan di BidController (lihat 6.5) untuk hindari race condition.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $auction = $this->route('auction');

            if (! $auction) {
                return;
            }

            // Aturan 2: Penawar tidak boleh menawar lelang miliknya sendiri
            if ($auction->seller_id === $this->user()->id) {
                $validator->errors()->add('auction', 'Anda tidak dapat menawar pada lelang milik Anda sendiri.');
                return;
            }

            // Aturan 3 & 4: hanya saat status aktif & dalam rentang waktu
            if (! $auction->isActive()) {
                $validator->errors()->add('auction', 'Lelang ini tidak sedang berjalan / sudah berakhir.');
                return;
            }

            // Aturan 1: tawaran >= harga tertinggi saat ini + kelipatan minimum
            $minimum = $auction->minimumNextBid();
            if ((float) $this->input('amount') < $minimum) {
                $validator->errors()->add(
                    'amount',
                    'Tawaran minimum adalah '.number_format($minimum, 0, ',', '.')
                );
            }
        });
    }
}