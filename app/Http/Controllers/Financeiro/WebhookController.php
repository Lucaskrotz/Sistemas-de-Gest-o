<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvento;
use App\Services\Financeiro\WebhookProcessor;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /** Endpoint público chamado pelo "banco" (sem sessão/CSRF; autenticado pela assinatura HMAC). */
    public function receber(Request $request, WebhookProcessor $processor)
    {
        $evento = $processor->processar($request->getContent(), $request->header('X-Assinatura'));

        // Duplicados/ignorados respondem 200 para o emissor parar de reenviar.
        $http = ['processado' => 200, 'ignorado' => 200, 'rejeitado' => 401, 'erro' => 422][$evento->status];

        return response()->json(['status' => $evento->status, 'mensagem' => $evento->mensagem], $http);
    }

    public function index()
    {
        return view('financeiro.webhooks', ['eventos' => WebhookEvento::with('titulo')->latest('id')->limit(200)->get()]);
    }

    /** Reenvia o mesmo corpo e assinatura, como um banco faria num retry — demonstra a idempotência. */
    public function reenviar(WebhookEvento $evento, WebhookProcessor $processor)
    {
        $novo = $processor->processar($evento->payload, $evento->assinatura);

        return back()->with($novo->status === 'processado' ? 'success' : 'error', "Reenvio {$novo->status}: {$novo->mensagem}");
    }
}
