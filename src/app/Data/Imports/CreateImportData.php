<?php

namespace App\Data\Imports;

final readonly class CreateImportData
{
    /** @param list<ImportedOfferData> $offers */
    public function __construct(
        public string $supplier,
        public string $externalImportId,
        public string $sentAt,
        public array $offers,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            supplier: $data['supplier'],
            externalImportId: $data['external_import_id'],
            sentAt: $data['sent_at'],
            offers: array_map(
                static fn (array $offer): ImportedOfferData => ImportedOfferData::fromArray($offer),
                $data['offers'],
            ),
        );
    }

    /** @return array{offers: list<array<string, mixed>>} */
    public function toPayload(): array
    {
        return [
            'offers' => array_map(
                static fn (ImportedOfferData $offer): array => $offer->toArray(),
                $this->offers,
            ),
        ];
    }
}
