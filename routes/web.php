<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\Financeiro\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/demo/{modulo}', DemoController::class)->name('demo');

// Webhook do banco simulado: público, sem CSRF (exceção em bootstrap/app.php), autenticado por HMAC.
Route::post('/financeiro/webhook', [WebhookController::class, 'receber'])
    ->middleware('throttle:60,1')->name('financeiro.webhook');

Route::post('/sair', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');
