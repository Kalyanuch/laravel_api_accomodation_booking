<?php

namespace App\Http\Controllers\Api;

use App\Actions\Imports\CreateImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportAcceptedResource;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request, CreateImport $action): JsonResponse
    {
        return (new ImportAcceptedResource($action->handle($request->toDto())))
            ->response()
            ->setStatusCode(202);
    }

    public function show(Import $import): ImportResource
    {
        return new ImportResource($import->load('supplier'));
    }
}
