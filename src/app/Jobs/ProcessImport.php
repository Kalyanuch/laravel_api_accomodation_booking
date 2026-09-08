<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessImport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(public int $importId) {}

    public function uniqueId(): string
    {
        return (string) $this->importId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 30];
    }

    public function handle(): void
    {
        $import = Import::query()->findOrFail($this->importId);

        if (in_array($import->status, [ImportStatus::Completed, ImportStatus::Failed], true)) {
            return;
        }

        $import->update(['status' => ImportStatus::Processing, 'error' => null]);

        DB::transaction(function () use ($import): void {
            $processedOffers = 0;

            foreach ($import->payload['offers'] as $offerData) {
                $property = Property::query()->updateOrCreate(
                    ['code' => $offerData['property']['code']],
                    ['name' => $offerData['property']['name'], 'city' => $offerData['property']['city']],
                );

                Offer::query()->updateOrCreate(
                    ['supplier_id' => $import->supplier_id, 'external_id' => $offerData['external_id']],
                    [
                        'property_id' => $property->getKey(),
                        'import_id' => $import->getKey(),
                        'check_in' => $offerData['check_in'],
                        'check_out' => $offerData['check_out'],
                        'max_guests' => $offerData['max_guests'],
                        'price' => $offerData['price'],
                        'currency' => $offerData['currency'],
                        'available_units' => $offerData['available_units'],
                        'expires_at' => $offerData['expires_at'],
                    ],
                );

                $processedOffers++;
            }

            $import->update([
                'status' => ImportStatus::Completed,
                'processed_offers' => $processedOffers,
                'completed_at' => now(),
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        Import::query()
            ->whereKey($this->importId)
            ->where('status', '!=', ImportStatus::Completed->value)
            ->update([
                'status' => ImportStatus::Failed->value,
                'error' => Str::limit($exception?->getMessage() ?? 'Import processing failed.', 1000),
                'completed_at' => null,
            ]);
    }
}
