<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class OfferUnavailableException extends Exception
{
    public function __construct()
    {
        parent::__construct('The offer is no longer available.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
