<?php

namespace App\Http\Controllers\Api;

use App\Actions\Properties\SearchBestOffers;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPropertiesRequest;
use App\Http\Resources\PropertySearchResource;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __invoke(SearchPropertiesRequest $request, SearchBestOffers $action): JsonResponse
    {
        $properties = $action->handle($request->toDto());

        return response()->json([
            'data' => PropertySearchResource::collection($properties->items())->resolve($request),
            'next' => $properties->nextPageUrl(),
            'prev' => $properties->previousPageUrl(),
            'per_page' => $properties->perPage(),
        ]);
    }
}
