<?php

namespace App\Http\Controllers\Chamados;

use App\Http\Controllers\Controller;
use App\Models\Chamado;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChamadoController extends Controller
{
    public function index()
    {
        // Resolvidos só dos últimos 7 dias, para a coluna não crescer sem fim.
        $chamados = Chamado::with('cliente')
            ->where(fn ($q) => $q->where('status', '!=', 'resolvido')->orWhere('resolvido_em', '>=', now()->subDays(7)))
            ->orderBy('prazo_sla')
            ->get();

        $abertos = $chamados->where('status', '!=', 'resolvido');
        $resolvidos30 = Chamado::whereNotNull('resolvido_em')->where('resolvido_em', '>=', now()->subDays(30));
        $noPrazo = (clone $resolvidos30)->whereColumn('resolvido_em', '<=', 'prazo_sla')->count();
        $totalResolvidos = $resolvidos30->count();

        return view('chamados.index', [
            'colunas' => $chamados->groupBy('status'),
            'abertos' => $abertos->count(),
            'vencidos' => $abertos->filter(fn ($c) => $c->prazo_sla->isPast())->count(),
            'emRisco' => $abertos->filter(fn ($c) => $c->sla()[1] === 'warning')->count(),
            'slaCumprido' => $totalResolvidos ? round($noPrazo / $totalResolvidos * 100) : null,
        ]);
    }

    public function create()
    {
        return view('chamados.create', ['clientes' => Cliente::where('ativo', true)->orderBy('nome')->get()]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:5000'],
            'prioridade' => ['required', Rule::in(array_keys(Chamado::PRIORIDADES))],
            'responsavel' => ['nullable', Rule::in(Chamado::ATENDENTES)],
        ]);

        $chamado = DB::transaction(function () use ($dados, $request) {
            $chamado = Chamado::create($dados + ['prazo_sla' => Chamado::prazoPara($dados['prioridade'])]);
            $chamado->registrar('Chamado aberto', $request->user()->name);

            return $chamado;
        });

        return redirect()->route('chamados.show', $chamado)->with('success', "Chamado {$chamado->numero} aberto.");
    }

    public function show(Chamado $chamado)
    {
        return view('chamados.show', ['chamado' => $chamado->load('cliente', 'historicos')]);
    }

    /** Status e/ou responsável. Usado pelo Kanban (JSON) e pelo formulário do detalhe. */
    public function update(Request $request, Chamado $chamado)
    {
        $dados = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(Chamado::STATUS))],
            'responsavel' => ['sometimes', 'nullable', Rule::in(Chamado::ATENDENTES)],
        ]);
        $autor = $request->user()->name;

        DB::transaction(function () use ($chamado, $dados, $autor) {
            if (isset($dados['status'])) {
                $chamado->mudarStatus($dados['status'], $autor);
            }
            if (array_key_exists('responsavel', $dados) && $dados['responsavel'] !== $chamado->responsavel) {
                $chamado->update(['responsavel' => $dados['responsavel']]);
                $chamado->registrar('Responsável: '.($dados['responsavel'] ?? 'ninguém'), $autor);
            }
        });

        if ($request->expectsJson()) {
            [$texto, $cor] = $chamado->sla();

            return response()->json(['sla' => compact('texto', 'cor')]);
        }

        return back()->with('success', 'Chamado atualizado.');
    }

    public function comentar(Request $request, Chamado $chamado)
    {
        $dados = $request->validate(['comentario' => ['required', 'string', 'max:5000']]);
        $chamado->registrar($dados['comentario'], $request->user()->name, 'comentario');
        $chamado->touch();

        return back()->with('success', 'Comentário adicionado.');
    }
}
