<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PropertySearchResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->property_code,
            'name' => $this->property_name,
            'city' => $this->property_city,
            'best_offer' => [
                'id' => $this->offer_id,
                'supplier' => $this->supplier_code,
                'price' => (int) $this->offer_price,
                'currency' => $this->offer_currency,
                'available_units' => (int) $this->offer_available_units,
                'expires_at' => Carbon::parse($this->offer_expires_at)->toISOString(),
            ],
        ];
    }
}
