<?php

use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/inventory/history', [InventoryController::class, 'history']);
    Route::post('/inventory/movement', [InventoryController::class, 'store']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
});
