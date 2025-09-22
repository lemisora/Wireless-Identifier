<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccessController;

Route::post('/log-access', [AccessController::class, 'logAccess']);

// Ruta para crear un nuevo usuario
Route::post('/users', [AccessController::class, 'storeUser']);

// Ruta para asignar una tarjeta a un usuario
Route::post('/cards/assign', [AccessController::class, 'assignCard']);