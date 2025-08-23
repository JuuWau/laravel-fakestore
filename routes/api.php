<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StatisticsController;

Route::middleware('integration')->group(function () {
    Route::post('/integracoes/fakestore/sync', [IntegrationController::class, 'sync']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/statistics', [StatisticsController::class, 'stats']);
});
