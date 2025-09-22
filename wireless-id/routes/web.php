<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Counter;

// Al visitar la raíz '/', Laravel renderizará el componente Counter
Route::get('/', Counter::class);
