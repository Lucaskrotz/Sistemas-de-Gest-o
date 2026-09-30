<?php

use App\Http\Controllers\Chamados\ChamadoController;
use Illuminate\Support\Facades\Route;

// Prefixo /chamados, nomes "chamados.*" e auth vêm de bootstrap/app.php.
Route::get('/', [ChamadoController::class, 'index'])->name('index');
Route::get('novo', [ChamadoController::class, 'create'])->name('create');
Route::post('/', [ChamadoController::class, 'store'])->name('store');
Route::get('{chamado}', [ChamadoController::class, 'show'])->name('show');
Route::patch('{chamado}', [ChamadoController::class, 'update'])->name('update');
Route::post('{chamado}/comentarios', [ChamadoController::class, 'comentar'])->name('comentar');
