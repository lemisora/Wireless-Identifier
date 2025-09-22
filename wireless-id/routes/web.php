<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Counter;
use App\Livewire\ConnectionTest;

// Al visitar la raíz '/', Laravel renderizará el componente Counter
Route::get('/', Counter::class);
// La ruta principal ahora cargará nuestro componente de prueba
Route::get('/', ConnectionTest::class);