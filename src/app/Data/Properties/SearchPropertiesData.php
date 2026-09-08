<?php

namespace App\Data\Properties;

final readonly class SearchPropertiesData
{
    public function __construct(
        public string $checkIn,
        public string $checkOut,
        public int $guests,
        public ?string $city,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            checkIn: $data['check_in'],
            checkOut: $data['check_out'],
            guests: $data['guests'],
            city: $data['city'] ?? null,
        );
    }
}
