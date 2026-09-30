<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\PedidoItem;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdutoController extends Controller
{
    public function index()
    {
        return view('erp.produtos.index', ['produtos' => Produto::orderBy('nome')->get()]);
    }

    public function create()
    {
        return view('erp.produtos.form', ['produto' => new Produto(['ativo' => true])]);
    }

    public function store(Request $request)
    {
        Produto::create($this->validar($request));

        return redirect()->route('erp.produtos.index')->with('success', 'Produto cadastrado.');
    }

    public function edit(Produto $produto)
    {
        return view('erp.produtos.form', compact('produto'));
    }

    public function update(Request $request, Produto $produto)
    {
        $produto->update($this->validar($request, $produto));

        return redirect()->route('erp.produtos.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Produto $produto)
    {
        if (PedidoItem::where('produto_id', $produto->id)->exists()) {
            return back()->with('error', 'Produto já foi vendido; inative-o em vez de excluir.');
        }
        $produto->delete();

        return redirect()->route('erp.produtos.index')->with('success', 'Produto excluído.');
    }

    private function validar(Request $request, ?Produto $produto = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:30', Rule::unique('produtos')->ignore($produto)],
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'estoque' => ['required', 'integer', 'min:0'],
        ]) + ['ativo' => $request->boolean('ativo')];
    }
}
