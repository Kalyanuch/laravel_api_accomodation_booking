<?php

use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

Route::controller(ImportController::class)
    ->name('api.imports.')
    ->prefix('imports')
    ->group(function () {
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/{import}', [ImportController::class, 'show'])->name('show');
    });

Route::get('/properties', PropertyController::class)
    ->name('api.properties.index');

Route::post('/offers/{offer}/reservations', ReservationController::class)
    ->name('api.offers.reservations.store');

Route::get('/openapi.json', function () {
    return response()->file(public_path('openapi.json'), [
        'Content-Type' => 'application/json',
    ]);
})->name('api.openapi');

Route::get('/documentation', function () {
    return response()->file(public_path('swagger.html'));
})->name('api.documentation');
