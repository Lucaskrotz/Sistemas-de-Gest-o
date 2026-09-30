<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // routes/{modulo}.php → prefixo config('modulos.X.rota'), nome "X.", exige login.
        then: function () {
            foreach (config('modulos') as $chave => $modulo) {
                if (is_file($arquivo = base_path("routes/{$chave}.php"))) {
                    Route::middleware(['web', 'auth'])
                        ->prefix($modulo['rota'])
                        ->name("{$chave}.")
                        ->group($arquivo);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestForgery(except: ['financeiro/webhook']);

        // Render termina o HTTPS no proxy: confiar no X-Forwarded-* para gerar URLs https.
        $middleware->trustProxies(at: '*');

        // Tenancy: tenant definido antes do route model binding (senão {id} de outra empresa seria resolvido).
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\IdentificarTenant::class,
        );

        // Visitante que abre /erp direto cai no login demo do próprio módulo.
        $middleware->redirectGuestsTo(function (Request $request) {
            $chave = collect(config('modulos'))->search(fn ($m) => $m['rota'] === $request->segment(1));

            return $chave ? route('demo', $chave) : route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Dados de cartão (Pagamentos) nunca vão para a sessão como "old input".
        $exceptions->dontFlash(['numero', 'cvv', 'password', 'password_confirmation']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
