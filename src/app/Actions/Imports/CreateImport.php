<?php

namespace App\Actions\Imports;

use App\Data\Imports\CreateImportData;
use App\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class CreateImport
{
    public function handle(CreateImportData $data): Import
    {
        $supplier = Supplier::query()->where('code', $data->supplier)->firstOrFail();

        return DB::transaction(function () use ($data, $supplier): Import {
            $import = Import::query()->createOrFirst(
                ['supplier_id' => $supplier->getKey(), 'external_import_id' => $data->externalImportId],
                [
                    'sent_at' => $data->sentAt,
                    'status' => ImportStatus::Pending,
                    'total_offers' => count($data->offers),
                    'processed_offers' => 0,
                    'payload' => $data->toPayload(),
                ],
            );

            if ($import->wasRecentlyCreated) {
                ProcessImport::dispatch($import->getKey())->afterCommit();
            }

            return $import;
        });
    }
}
