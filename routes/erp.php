<?php

use App\Http\Controllers\Erp\ClienteController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\PedidoController;
use App\Http\Controllers\Erp\ProdutoController;
use Illuminate\Support\Facades\Route;

// Prefixo /erp, nomes "erp.*" e auth vêm de bootstrap/app.php.
Route::get('/', DashboardController::class)->name('index');

Route::resource('clientes', ClienteController::class)->except('show')->parameters(['clientes' => 'cliente']);
Route::resource('produtos', ProdutoController::class)->except('show')->parameters(['produtos' => 'produto']);
Route::resource('pedidos', PedidoController::class)->only(['index', 'create', 'store', 'show'])->parameters(['pedidos' => 'pedido']);
Route::patch('pedidos/{pedido}/status', [PedidoController::class, 'status'])->name('pedidos.status');
