<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
// use App\Livewire\Counter;
// use App\Livewire\ConnectionTest;

/*
// Al visitar la raíz '/', Laravel renderizará el componente Counter
Route::get('/', Counter::class);
// La ruta principal ahora cargará nuestro componente de prueba
Route::get('/', ConnectionTest::class);
*/

// Route::get('/', fn () => View::make('panel'));
Route::get('/', function () { 
    return view('panel');
});