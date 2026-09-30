<?php

namespace App\Http\Controllers\Pagamentos;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pagamento;
use App\Models\PagamentoEvento;
use App\Services\Pagamentos\GatewayFake;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PagamentoController extends Controller
{
    public function index()
    {
        $pagamentos = Pagamento::with('cliente')->latest()->get();
        $cartoesDecididos = $pagamentos->where('metodo', 'cartao')->where('status', '!=', 'pendente');

        return view('pagamentos.index', [
            'pagamentos' => $pagamentos,
            'volume' => $pagamentos->whereIn('status', ['confirmado', 'recebido'])->sum('valor'),
            'liquido' => $pagamentos->whereIn('status', ['confirmado', 'recebido'])->sum('valor_liquido'),
            'aprovacao' => $cartoesDecididos->count()
                ? round($cartoesDecididos->where('status', '!=', 'recusado')->count() / $cartoesDecididos->count() * 100, 1)
                : null,
            'pendentes' => $pagamentos->where('status', 'pendente')->count(),
            'estornado' => $pagamentos->where('status', 'estornado')->sum('valor'),
        ]);
    }

    public function create()
    {
        return view('pagamentos.create', [
            'clientes' => Cliente::where('ativo', true)->orderBy('nome')->get(),
            'chave' => (string) Str::uuid(), // idempotency key deste formulário
        ]);
    }

    public function store(Request $request, GatewayFake $gateway)
    {
        $request->merge(['numero' => preg_replace('/\D/', '', (string) $request->input('numero'))]);
        $cartao = $request->input('metodo') === 'cartao';

        $dados = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:1', 'max:100000'],
            'metodo' => ['required', Rule::in(array_keys(Pagamento::METODOS))],
            'numero' => [Rule::requiredIf($cartao), 'nullable', 'digits_between:13,19',
                fn ($attr, $v, $fail) => $cartao && ! GatewayFake::luhn($v) && $fail('Número de cartão inválido.')],
            'nome' => [Rule::requiredIf($cartao), 'nullable', 'string', 'max:100'],
            'validade' => [Rule::requiredIf($cartao), 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/',
                fn ($attr, $v, $fail) => $cartao && $v && now()->startOfMonth()->gt(\Carbon\Carbon::createFromFormat('!m/y', $v)) && $fail('Cartão vencido.')],
            'cvv' => [Rule::requiredIf($cartao), 'nullable', 'digits_between:3,4'],
            'parcelas' => ['nullable', 'integer', 'between:1,12'],
        ], ['validade.regex' => 'Validade no formato MM/AA.']);

        if (! $cartao) {
            unset($dados['numero'], $dados['parcelas']);
        }
        unset($dados['nome'], $dados['validade'], $dados['cvv']); // não saem daqui

        $pagamento = $gateway->criar($dados, $dados['idempotency_key']);
        $mensagem = match ($pagamento->status) {
            'confirmado' => 'Pagamento aprovado.',
            'recusado' => 'Pagamento recusado: '.($pagamento->eventos->last()->dados['motivo'] ?? ''),
            default => 'Cobrança Pix gerada. Aguardando pagamento.',
        };

        return redirect()->route('pagamentos.show', $pagamento)
            ->with($pagamento->status === 'recusado' ? 'error' : 'success', $mensagem);
    }

    public function show(Pagamento $pagamento)
    {
        return view('pagamentos.show', ['pagamento' => $pagamento->load('cliente', 'eventos')]);
    }

    public function acao(Request $request, Pagamento $pagamento, GatewayFake $gateway)
    {
        $acao = $request->validate(['acao' => ['required', Rule::in(['receber-pix', 'liquidar', 'expirar', 'estornar'])]])['acao'];

        try {
            match ($acao) {
                'receber-pix' => $gateway->receberPix($pagamento),
                'liquidar' => $gateway->liquidar($pagamento),
                'expirar' => $gateway->expirar($pagamento),
                'estornar' => $gateway->estornar($pagamento),
            };
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Evento '.$pagamento->eventos()->latest('id')->value('tipo').' registrado.');
    }

    public function eventos()
    {
        return view('pagamentos.eventos', [
            'eventos' => PagamentoEvento::with('pagamento.cliente')->latest('id')->limit(500)->get(),
        ]);
    }
}
