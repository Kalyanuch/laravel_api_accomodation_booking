<?php

namespace App\Data\Imports;

final readonly class ImportedOfferData
{
    public function __construct(
        public string $externalId,
        public ImportedPropertyData $property,
        public string $checkIn,
        public string $checkOut,
        public int $maxGuests,
        public int $price,
        public string $currency,
        public int $availableUnits,
        public string $expiresAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'],
            property: ImportedPropertyData::fromArray($data['property']),
            checkIn: $data['check_in'],
            checkOut: $data['check_out'],
            maxGuests: $data['max_guests'],
            price: $data['price'],
            currency: $data['currency'],
            availableUnits: $data['available_units'],
            expiresAt: $data['expires_at'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'property' => $this->property->toArray(),
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'max_guests' => $this->maxGuests,
            'price' => $this->price,
            'currency' => $this->currency,
            'available_units' => $this->availableUnits,
            'expires_at' => $this->expiresAt,
        ];
    }
}
