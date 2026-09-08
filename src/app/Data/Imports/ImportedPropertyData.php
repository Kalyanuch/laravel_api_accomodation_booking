<?php

namespace App\Data\Imports;

final readonly class ImportedPropertyData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $city,
    ) {}

    /** @param array{code: string, name: string, city: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(code: $data['code'], name: $data['name'], city: $data['city']);
    }

    /** @return array{code: string, name: string, city: string} */
    public function toArray(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'city' => $this->city];
    }
}
