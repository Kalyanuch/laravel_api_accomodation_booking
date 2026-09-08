<?php

namespace App\Data\Reservations;

final readonly class CreateReservationData
{
    public function __construct(
        public string $clientReference,
        public string $customerName,
        public string $customerEmail,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            clientReference: $data['client_reference'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
        );
    }
}
