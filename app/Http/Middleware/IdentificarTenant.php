<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define o tenant da requisição = empresa do usuário logado.
 * Roda antes do SubstituteBindings (prioridade em bootstrap/app.php), para que o
 * route model binding já aconteça com o global scope do tenant aplicado.
 */
class IdentificarTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;
        abort_unless($tenant, 403, 'Usuário sem empresa vinculada.');

        Tenant::definir($tenant);

        return $next($request);
    }
}
