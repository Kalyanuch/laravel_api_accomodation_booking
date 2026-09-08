<?php

namespace App\Http\Requests;

use App\Data\Reservations\CreateReservationData;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_reference' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email:rfc', 'max:255'],
        ];
    }

    public function toDto(): CreateReservationData
    {
        return CreateReservationData::fromArray($this->validated());
    }
}
