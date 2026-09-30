<?php

use App\Http\Controllers\Pagamentos\PagamentoController;
use Illuminate\Support\Facades\Route;

// Prefixo /pagamentos, nomes "pagamentos.*" e auth vêm de bootstrap/app.php.
Route::get('/', [PagamentoController::class, 'index'])->name('index');
Route::get('nova', [PagamentoController::class, 'create'])->name('create');
Route::post('/', [PagamentoController::class, 'store'])->name('store');
Route::get('eventos', [PagamentoController::class, 'eventos'])->name('eventos');
Route::get('{pagamento}', [PagamentoController::class, 'show'])->name('show');
Route::post('{pagamento}/acao', [PagamentoController::class, 'acao'])->name('acao');
