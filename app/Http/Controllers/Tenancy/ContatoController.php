<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Contato;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContatoController extends Controller
{
    public function index()
    {
        return view('tenancy.contatos.index', ['contatos' => Contato::withCount('oportunidades')->orderBy('nome')->get()]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            // Regras de validação consultam o banco direto (sem global scope): filtrar pelo tenant explicitamente.
            'email' => ['required', 'email', 'max:255', Rule::unique('contatos')->where('tenant_id', Tenant::atual()->id)],
            'empresa' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ], ['email.unique' => 'Já existe um contato com este e-mail nesta empresa.']);

        Contato::create($dados); // tenant_id preenchido pelo BelongsToTenant

        return back()->with('success', 'Contato cadastrado.');
    }

    public function show(Contato $contato) // binding já respeita o tenant: id de outra empresa → 404
    {
        return view('tenancy.contatos.show', ['contato' => $contato->load('oportunidades.responsavel')]);
    }

    public function destroy(Contato $contato)
    {
        $contato->delete();

        return redirect()->route('tenancy.contatos.index')->with('success', 'Contato excluído.');
    }
}
