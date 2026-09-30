<?php

use App\Http\Controllers\Tenancy\ContatoController;
use App\Http\Controllers\Tenancy\OportunidadeController;
use App\Http\Controllers\Tenancy\PainelController;
use App\Http\Middleware\IdentificarTenant;
use Illuminate\Support\Facades\Route;

// Prefixo /multitenant, nomes "tenancy.*" e auth vêm de bootstrap/app.php.
Route::middleware(IdentificarTenant::class)->group(function () {
    Route::get('/', [PainelController::class, 'index'])->name('index');
    Route::get('equipe', [PainelController::class, 'equipe'])->name('equipe');
    Route::post('trocar/{tenant:slug}', [PainelController::class, 'trocar'])->name('trocar');

    Route::get('contatos', [ContatoController::class, 'index'])->name('contatos.index');
    Route::post('contatos', [ContatoController::class, 'store'])->name('contatos.store');
    Route::get('contatos/{contato}', [ContatoController::class, 'show'])->name('contatos.show');
    Route::delete('contatos/{contato}', [ContatoController::class, 'destroy'])->name('contatos.destroy');

    Route::get('oportunidades', [OportunidadeController::class, 'index'])->name('oportunidades.index');
    Route::post('oportunidades', [OportunidadeController::class, 'store'])->name('oportunidades.store');
    Route::patch('oportunidades/{oportunidade}', [OportunidadeController::class, 'update'])->name('oportunidades.update');
});
