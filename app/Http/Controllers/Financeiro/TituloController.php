<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Titulo;
use App\Services\Financeiro\BancoFake;
use App\Services\Financeiro\WebhookProcessor;
use Illuminate\Http\Request;

class TituloController extends Controller
{
    public function index()
    {
        $titulos = Titulo::with('cliente')->orderByDesc('vencimento')->get();
        $abertos = $titulos->where('status', 'aberto');

        return view('financeiro.index', [
            'titulos' => $titulos,
            'aReceber' => $abertos->where('situacao', 'aberto')->sum('valor'),
            'vencido' => $abertos->where('situacao', 'vencido')->sum('valor'),
            'qtdVencidos' => $abertos->where('situacao', 'vencido')->count(),
            'recebidoMes' => $titulos->where('status', 'pago')->filter(fn ($t) => $t->pago_em->isCurrentMonth())->sum('valor_pago'),
            'semCobranca' => $abertos->whereNull('codigo_cobranca')->count(),
            'pedidosSemTitulo' => $this->pedidosSemTitulo()->count(),
        ]);
    }

    public function show(Titulo $titulo)
    {
        return view('financeiro.show', ['titulo' => $titulo->load('cliente', 'pedido', 'eventos')]);
    }

    /** Gera um título (vencimento em 30 dias da data do pedido) para cada pedido do ERP ainda sem título. */
    public function importar()
    {
        $pedidos = $this->pedidosSemTitulo()->get();
        foreach ($pedidos as $pedido) {
            Titulo::create([
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'descricao' => "Pedido {$pedido->numero}",
                'valor' => $pedido->total,
                'vencimento' => $pedido->created_at->copy()->addDays(30),
            ]);
        }

        return back()->with('success', $pedidos->count()
            ? "{$pedidos->count()} título(s) gerado(s) a partir do ERP."
            : 'Nenhum pedido novo para importar.');
    }

    public function registrarCobranca(Titulo $titulo, BancoFake $banco)
    {
        if ($titulo->status !== 'aberto' || $titulo->codigo_cobranca) {
            return back()->with('error', 'Cobrança só pode ser registrada para título em aberto e sem cobrança.');
        }
        $resposta = $banco->registrarCobranca($titulo);
        $titulo->update(['codigo_cobranca' => $resposta['id'], 'linha_digitavel' => $resposta['linha_digitavel']]);

        return back()->with('success', "Cobrança {$resposta['id']} registrada no banco simulado.");
    }

    /** Simula o banco enviando o webhook de pagamento (válido ou com assinatura adulterada). */
    public function simularPagamento(Request $request, Titulo $titulo, BancoFake $banco, WebhookProcessor $processor)
    {
        if (! $titulo->codigo_cobranca) {
            return back()->with('error', 'Registre a cobrança antes de simular o pagamento.');
        }
        [$corpo, $assinatura] = $banco->webhookPagamento($titulo, assinaturaValida: ! $request->boolean('invalida'));
        $evento = $processor->processar($corpo, $assinatura);

        return back()->with($evento->status === 'processado' ? 'success' : 'error', "Webhook {$evento->status}: {$evento->mensagem}");
    }

    private function pedidosSemTitulo()
    {
        return Pedido::where('status', '!=', 'cancelado')->whereDoesntHave('titulo');
    }
}
