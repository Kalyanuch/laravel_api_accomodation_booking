<?php

namespace Database\Seeders;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PropertyOfferSeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            ['code' => 'BCN-0001', 'name' => 'Sagrada Familia Boutique Apartment', 'city' => 'Barcelona'],
            ['code' => 'BER-0001', 'name' => 'Mitte Courtyard Loft', 'city' => 'Berlin'],
            ['code' => 'KYV-0001', 'name' => 'Podil Riverside Residence', 'city' => 'Kyiv'],
            ['code' => 'LIS-0001', 'name' => 'Alfama Terraced Guest House', 'city' => 'Lisbon'],
            ['code' => 'LON-0001', 'name' => 'Notting Hill Garden Suites', 'city' => 'London'],
            ['code' => 'PAR-0001', 'name' => 'Le Marais Atelier Hotel', 'city' => 'Paris'],
            ['code' => 'PRG-0001', 'name' => 'Old Town Clock Apartments', 'city' => 'Prague'],
            ['code' => 'ROM-0001', 'name' => 'Trastevere Historic Residence', 'city' => 'Rome'],
            ['code' => 'VIE-0001', 'name' => 'Belvedere City Rooms', 'city' => 'Vienna'],
            ['code' => 'WAW-0001', 'name' => 'Vistula Modern Apartments', 'city' => 'Warsaw'],
        ];

        $suppliers = Supplier::query()->orderBy('id')->get();

        if ($suppliers->isEmpty()) {
            $this->call(SupplierSeeder::class);
            $suppliers = Supplier::query()->orderBy('id')->get();
        }

        $imports = $suppliers->mapWithKeys(function (Supplier $supplier): array {
            $import = Import::query()->firstOrCreate(
                [
                    'supplier_id' => $supplier->getKey(),
                    'external_import_id' => 'demo-catalog-2026',
                ],
                [
                    'sent_at' => now(),
                    'status' => ImportStatus::Completed,
                    'total_offers' => 0,
                    'processed_offers' => 0,
                    'payload' => ['offers' => []],
                    'completed_at' => now(),
                ],
            );

            return [$supplier->getKey() => $import];
        });

        foreach ($properties as $propertyIndex => $propertyData) {
            $property = Property::factory()->create($propertyData);
            $offerCount = ($propertyIndex % 5) + 1;

            foreach (range(1, $offerCount) as $offerIndex) {
                $supplier = $suppliers[($propertyIndex + $offerIndex - 1) % $suppliers->count()];
                $checkIn = now()->startOfDay()->addDays(7 + ($propertyIndex * 5) + ($offerIndex * 3));
                $nights = (($propertyIndex + $offerIndex) % 10) + 2;

                Offer::factory()->create([
                    'supplier_id' => $supplier->getKey(),
                    'property_id' => $property->getKey(),
                    'import_id' => $imports[$supplier->getKey()]->getKey(),
                    'external_id' => sprintf('demo-%s-%02d', strtolower($propertyData['code']), $offerIndex),
                    'check_in' => $checkIn,
                    'check_out' => $checkIn->copy()->addDays($nights),
                    'max_guests' => [1, 2, 3, 4, 6, 8][($propertyIndex + $offerIndex) % 6],
                    'price' => 8500 + ($propertyIndex * 4750) + ($offerIndex * 2300),
                    'currency' => ['EUR', 'EUR', 'UAH', 'EUR', 'GBP', 'EUR', 'CZK', 'EUR', 'EUR', 'PLN'][$propertyIndex],
                    'available_units' => ($propertyIndex + $offerIndex) % 6,
                    'expires_at' => $checkIn->copy()->subDays(2),
                ]);
            }
        }

        $imports->each(function (Import $import): void {
            $offerCount = Offer::query()->where('import_id', $import->getKey())->count();

            $import->update([
                'total_offers' => $offerCount,
                'processed_offers' => $offerCount,
            ]);
        });
    }
}
