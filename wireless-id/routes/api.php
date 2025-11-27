<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccessController;
use App\Http\Controllers\Api\SensorController;

Route::post('/log-access', [AccessController::class, 'logAccess']);

// Ruta para crear un nuevo usuario
Route::post('/users', [AccessController::class, 'storeUser']);

// Ruta para asignar una tarjeta a un usuario
Route::post('/cards/assign', [AccessController::class, 'assignCard']);


Route::post("/reportar-ocupacion", [SensorController::class, "store"]);
Route::get("/estado-actual", [SensorController::class, "latest"]);