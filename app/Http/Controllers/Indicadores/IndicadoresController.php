<?php

namespace App\Http\Controllers\Indicadores;

use App\Http\Controllers\Controller;
use App\Models\Chamado;
use App\Models\Pedido;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndicadoresController extends Controller
{
    private const MAX_DIAS = 400;

    public function index()
    {
        return view('indicadores.index');
    }

    public function dados(Request $request)
    {
        $dados = $request->validate([
            'inicio' => ['required', 'date'],
            'fim' => ['required', 'date', 'after_or_equal:inicio'],
        ]);
        $inicio = Carbon::parse($dados['inicio'])->startOfDay();
        $fim = Carbon::parse($dados['fim'])->endOfDay();
        $dias = (int) $inicio->diffInDays($fim) + 1;
        if ($dias > self::MAX_DIAS) {
            throw ValidationException::withMessages(['fim' => 'O período máximo é de '.self::MAX_DIAS.' dias.']);
        }

        // Período anterior de mesmo tamanho, para a variação dos KPIs.
        $antFim = $inicio->copy()->subSecond();
        $antInicio = $antFim->copy()->subDays($dias - 1)->startOfDay();

        // Até ~2 meses: por dia; acima: por mês.
        $mensal = $dias > 62;
        $chave = fn (CarbonInterface $d) => $d->format($mensal ? 'Y-m' : 'Y-m-d');
        $baldes = collect(CarbonPeriod::create($mensal ? $inicio->copy()->startOfMonth() : $inicio, $mensal ? '1 month' : '1 day', $fim))
            ->mapWithKeys(fn ($d) => [$chave($d) => $d->format($mensal ? 'm/Y' : 'd/m')]);

        // ponytail: agrupa em PHP (portável MySQL/SQLite); trocar por GROUP BY no banco se passar de ~100k pedidos no período.
        $pedidos = $this->pedidos($inicio, $fim)->get(['total', 'created_at']);
        $fatPorBalde = $pedidos->groupBy(fn ($p) => $chave($p->created_at))->map->sum('total');

        $chamados = Chamado::whereBetween('created_at', [$inicio, $fim])->get(['created_at']);
        $resolvidos = Chamado::whereBetween('resolvido_em', [$inicio, $fim])->get(['resolvido_em']);
        $abertosPorBalde = $chamados->countBy(fn ($c) => $chave($c->created_at));
        $resolvidosPorBalde = $resolvidos->countBy(fn ($c) => $chave($c->resolvido_em));

        return response()->json([
            'periodo' => [
                'inicio' => $inicio->toDateString(), 'fim' => $fim->toDateString(),
                'anterior' => [$antInicio->toDateString(), $antFim->toDateString()],
                'granularidade' => $mensal ? 'mês' : 'dia',
            ],
            'kpis' => $this->kpis($inicio, $fim, $antInicio, $antFim),
            'faturamento' => [
                'labels' => $baldes->values(),
                'valores' => $baldes->keys()->map(fn ($k) => round($fatPorBalde[$k] ?? 0, 2)),
            ],
            'chamados' => [
                'labels' => $baldes->values(),
                'abertos' => $baldes->keys()->map(fn ($k) => $abertosPorBalde[$k] ?? 0),
                'resolvidos' => $baldes->keys()->map(fn ($k) => $resolvidosPorBalde[$k] ?? 0),
            ],
            'topProdutos' => $this->vendasPor('produtos.nome', $inicio, $fim)->limit(5)->get(),
            'categorias' => $this->vendasPor('produtos.categoria', $inicio, $fim)->get(),
        ]);
    }

    private function pedidos(CarbonInterface $inicio, CarbonInterface $fim)
    {
        return Pedido::where('status', '!=', 'cancelado')->whereBetween('created_at', [$inicio, $fim]);
    }

    private function kpis($inicio, $fim, $antInicio, $antFim): array
    {
        $resumo = function ($ini, $fi) {
            $fat = (float) $this->pedidos($ini, $fi)->sum('total');
            $qtd = $this->pedidos($ini, $fi)->count();
            $resolvidos = Chamado::whereBetween('resolvido_em', [$ini, $fi]);
            $totalResolvidos = (clone $resolvidos)->count();
            $noPrazo = $resolvidos->whereColumn('resolvido_em', '<=', 'prazo_sla')->count();

            return [
                'faturamento' => round($fat, 2),
                'pedidos' => $qtd,
                'ticket' => $qtd ? round($fat / $qtd, 2) : 0,
                'sla' => $totalResolvidos ? round($noPrazo / $totalResolvidos * 100, 1) : null,
            ];
        };
        $atual = $resumo($inicio, $fim);
        $anterior = $resumo($antInicio, $antFim);

        return collect($atual)->map(fn ($valor, $k) => ['valor' => $valor, 'anterior' => $anterior[$k]])->all();
    }

    private function vendasPor(string $coluna, $inicio, $fim)
    {
        return DB::table('pedido_itens')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_itens.pedido_id')
            ->join('produtos', 'produtos.id', '=', 'pedido_itens.produto_id')
            ->where('pedidos.status', '!=', 'cancelado')
            ->whereBetween('pedidos.created_at', [$inicio, $fim])
            ->groupBy($coluna)
            ->selectRaw("{$coluna} as nome, ROUND(SUM(pedido_itens.quantidade * pedido_itens.preco_unitario), 2) as valor")
            ->orderByDesc('valor');
    }
}
