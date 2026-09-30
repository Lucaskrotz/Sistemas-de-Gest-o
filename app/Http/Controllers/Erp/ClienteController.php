<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        return view('erp.clientes.index', ['clientes' => Cliente::withCount('pedidos')->orderBy('nome')->get()]);
    }

    public function create()
    {
        return view('erp.clientes.form', ['cliente' => new Cliente(['ativo' => true])]);
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return redirect()->route('erp.clientes.index')->with('success', 'Cliente cadastrado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('erp.clientes.form', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('erp.clientes.index')->with('success', 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->pedidos()->exists()) {
            return back()->with('error', 'Cliente possui pedidos; inative-o em vez de excluir.');
        }
        $cliente->delete();

        return redirect()->route('erp.clientes.index')->with('success', 'Cliente excluído.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['required', 'string', 'max:18', Rule::unique('clientes')->ignore($cliente)],
            'email' => ['required', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
        ]) + ['ativo' => $request->boolean('ativo')];
    }
}
