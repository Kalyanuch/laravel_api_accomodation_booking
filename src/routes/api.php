<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::controller(ImportController::class)
    ->name('api.imports.')
    ->prefix('imports')
    ->group(function () {
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/{import}', [ImportController::class, 'show'])->name('show');
});

