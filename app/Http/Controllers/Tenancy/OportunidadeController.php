<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Contato;
use App\Models\Oportunidade;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OportunidadeController extends Controller
{
    public function index()
    {
        return view('tenancy.oportunidades', [
            'colunas' => Oportunidade::with('contato', 'responsavel')->orderByDesc('valor')->get()->groupBy('etapa'),
            'contatos' => Contato::orderBy('nome')->get(),
            'membros' => User::where('tenant_id', Tenant::atual()->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenant = Tenant::atual()->id;
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0', 'max:10000000'],
            // exists() não passa pelo global scope: sem o where, daria para apontar para contato/usuário de outra empresa.
            'contato_id' => ['required', Rule::exists('contatos', 'id')->where('tenant_id', $tenant)],
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant)],
        ]);

        Oportunidade::create($dados + ['etapa' => 'prospeccao']);

        return back()->with('success', 'Oportunidade criada.');
    }

    public function update(Request $request, Oportunidade $oportunidade)
    {
        $oportunidade->update($request->validate(['etapa' => ['required', Rule::in(array_keys(Oportunidade::ETAPAS))]]));

        return back()->with('success', "“{$oportunidade->titulo}” movida para ".Oportunidade::ETAPAS[$oportunidade->etapa][0].'.');
    }
}
