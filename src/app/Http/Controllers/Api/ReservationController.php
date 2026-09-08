<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reservations\CreateReservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __invoke(StoreReservationRequest $request, Offer $offer, CreateReservation $action): JsonResponse
    {
        return (new ReservationResource($action->handle($offer, $request->toDto())))
            ->response()
            ->setStatusCode(201);
    }
}
