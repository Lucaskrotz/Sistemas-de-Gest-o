<?php

use App\Http\Controllers\Financeiro\TituloController;
use App\Http\Controllers\Financeiro\WebhookController;
use Illuminate\Support\Facades\Route;

// Prefixo /financeiro, nomes "financeiro.*" e auth vêm de bootstrap/app.php.
// O endpoint público do webhook fica em routes/web.php (sem login).
Route::get('/', [TituloController::class, 'index'])->name('index');
Route::post('importar', [TituloController::class, 'importar'])->name('importar');
Route::get('titulos/{titulo}', [TituloController::class, 'show'])->name('titulos.show');
Route::post('titulos/{titulo}/cobranca', [TituloController::class, 'registrarCobranca'])->name('titulos.cobranca');
Route::post('titulos/{titulo}/simular', [TituloController::class, 'simularPagamento'])->name('titulos.simular');

Route::get('webhooks', [WebhookController::class, 'index'])->name('webhooks');
Route::post('webhooks/{evento}/reenviar', [WebhookController::class, 'reenviar'])->name('webhooks.reenviar');
