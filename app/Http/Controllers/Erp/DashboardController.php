<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Produto;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $doMes = Pedido::where('status', '!=', 'cancelado')->where('created_at', '>=', now()->startOfMonth());
        $faturamento = (clone $doMes)->sum('total');
        $qtdPedidos = (clone $doMes)->count();

        return view('erp.dashboard', [
            'faturamento' => $faturamento,
            'qtdPedidos' => $qtdPedidos,
            'ticketMedio' => $qtdPedidos ? $faturamento / $qtdPedidos : 0,
            'clientesAtivos' => Cliente::where('ativo', true)->count(),
            'ultimosPedidos' => Pedido::with('cliente')->latest()->limit(8)->get(),
            'estoqueBaixo' => Produto::where('ativo', true)->where('estoque', '<', Produto::ESTOQUE_BAIXO)
                ->orderBy('estoque')->get(),
        ]);
    }
}
