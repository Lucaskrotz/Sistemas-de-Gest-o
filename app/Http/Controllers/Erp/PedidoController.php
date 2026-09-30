<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function index()
    {
        return view('erp.pedidos.index', [
            'pedidos' => Pedido::with('cliente')->withCount('itens')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('erp.pedidos.create', [
            'clientes' => Cliente::where('ativo', true)->orderBy('nome')->get(),
            'produtos' => Produto::where('ativo', true)->where('estoque', '>', 0)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('ativo', true)],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'distinct', Rule::exists('produtos', 'id')->where('ativo', true)],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
        ], ['itens.*.produto_id.distinct' => 'O mesmo produto foi adicionado duas vezes.']);

        $pedido = DB::transaction(function () use ($dados) {
            // Trava as linhas dos produtos para o estoque não ser vendido duas vezes em paralelo.
            $produtos = Produto::whereIn('id', array_column($dados['itens'], 'produto_id'))
                ->lockForUpdate()->get()->keyBy('id');

            $pedido = Pedido::create(['cliente_id' => $dados['cliente_id'], 'observacao' => $dados['observacao'] ?? null]);
            $total = 0;

            foreach ($dados['itens'] as $i => $item) {
                $produto = $produtos[$item['produto_id']];
                if ($produto->estoque < $item['quantidade']) {
                    throw ValidationException::withMessages([
                        "itens.$i.quantidade" => "Estoque insuficiente de {$produto->nome} (disponível: {$produto->estoque}).",
                    ]);
                }
                $pedido->itens()->create([
                    'produto_id' => $produto->id,
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $produto->preco,
                ]);
                $produto->decrement('estoque', $item['quantidade']);
                $total += $item['quantidade'] * $produto->preco;
            }

            $pedido->update(['total' => $total]);

            return $pedido;
        });

        return redirect()->route('erp.pedidos.show', $pedido)->with('success', "Pedido {$pedido->numero} criado.");
    }

    public function show(Pedido $pedido)
    {
        return view('erp.pedidos.show', ['pedido' => $pedido->load('cliente', 'itens.produto')]);
    }

    public function status(Request $request, Pedido $pedido)
    {
        $novo = $request->validate(['status' => ['required', Rule::in(array_keys(Pedido::STATUS))]])['status'];

        if ($pedido->status === 'cancelado') {
            return back()->with('error', 'Pedido cancelado não pode mudar de status.');
        }

        DB::transaction(function () use ($pedido, $novo) {
            if ($novo === 'cancelado') {
                foreach ($pedido->itens as $item) {
                    Produto::whereKey($item->produto_id)->increment('estoque', $item->quantidade);
                }
            }
            $pedido->update(['status' => $novo]);
        });

        return back()->with('success', "Pedido {$pedido->numero} marcado como {$novo}.");
    }
}
