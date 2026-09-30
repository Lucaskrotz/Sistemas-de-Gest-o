<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Contato;
use App\Models\Oportunidade;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PainelController extends Controller
{
    public function index()
    {
        $abertas = Oportunidade::whereNotIn('etapa', ['ganho', 'perdido']);

        return view('tenancy.index', [
            'tenant' => Tenant::atual(),
            'contatos' => Contato::count(),
            'abertas' => (clone $abertas)->count(),
            'pipeline' => $abertas->sum('valor'),
            'ganho' => Oportunidade::where('etapa', 'ganho')->sum('valor'),
            'equipe' => User::where('tenant_id', Tenant::atual()->id)->count(),
            'recentes' => Oportunidade::with('contato')->latest()->limit(5)->get(),
            'auditoria' => $this->auditoria(),
        ]);
    }

    public function equipe()
    {
        return view('tenancy.equipe', [
            'membros' => User::where('tenant_id', Tenant::atual()->id)->orderBy('id')->get(),
            'porResponsavel' => Oportunidade::selectRaw('responsavel_id, count(*) as total, sum(valor) as valor')
                ->groupBy('responsavel_id')->get()->keyBy('responsavel_id'),
        ]);
    }

    /** Demo: entra como o usuário principal da outra empresa. */
    public function trocar(Request $request, Tenant $tenant)
    {
        Auth::login($tenant->usuarios()->orderBy('id')->firstOrFail());
        $request->session()->regenerate();

        return redirect()->route('tenancy.index')->with('success', "Você agora está logado na {$tenant->nome}.");
    }

    /**
     * Auditoria (código de admin): contagem agregada por tenant direto no query builder, sem global scope.
     * Mostra que o banco é único e que o usuário só enxerga a parte dele. Não lê registros de outros tenants.
     */
    private function auditoria()
    {
        $contar = fn (string $tabela) => DB::table($tabela)->selectRaw('tenant_id, count(*) as total')->groupBy('tenant_id')->pluck('total', 'tenant_id');
        $contatos = $contar('contatos');
        $oportunidades = $contar('oportunidades');
        $usuarios = $contar('users');

        return Tenant::orderBy('id')->get()->map(fn ($t) => [
            'tenant' => $t,
            'contatos' => $contatos[$t->id] ?? 0,
            'oportunidades' => $oportunidades[$t->id] ?? 0,
            'usuarios' => $usuarios[$t->id] ?? 0,
        ]);
    }
}
