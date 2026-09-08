<?php

namespace App\Actions\Reservations;

use App\Data\Reservations\CreateReservationData;
use App\Exceptions\OfferUnavailableException;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class CreateReservation
{
    public function handle(Offer $offer, CreateReservationData $data): Reservation
    {
        return DB::transaction(function () use ($offer, $data): Reservation {
            $lockedOffer = Offer::query()->lockForUpdate()->findOrFail($offer->getKey());

            if ($lockedOffer->available_units < 1 || $lockedOffer->expires_at->isPast()) {
                throw new OfferUnavailableException;
            }

            $reservation = $lockedOffer->reservations()->create([
                'client_reference' => $data->clientReference,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
            ]);

            $lockedOffer->decrement('available_units');

            return $reservation;
        }, 3);
    }
}
