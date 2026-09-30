<?php

use App\Http\Controllers\Indicadores\IndicadoresController;
use Illuminate\Support\Facades\Route;

// Prefixo /indicadores, nomes "indicadores.*" e auth vêm de bootstrap/app.php.
Route::get('/', [IndicadoresController::class, 'index'])->name('index');
Route::get('dados', [IndicadoresController::class, 'dados'])->name('dados');
