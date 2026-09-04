<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\MovementController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [ClientController::class, 'store']);

Route::get('/clients/{client}', [ClientController::class, 'show']);

Route::get('/clients/{client}/balance', [
    ClientController::class,
    'balance'
]);

Route::get('/clients/{client}/movements', [
    ClientController::class,
    'movements'
]);

Route::post('/clients/{client}/movements', [
    MovementController::class,
    'store'
]);
