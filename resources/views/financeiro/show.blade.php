@extends('layouts.app')

@section('header')
    Título {{ $titulo->numero }}
@endsection
@section('actions')
    <a href="{{ route('financeiro.index') }}" class="btn btn-outline-secondary"><i data-lucide="arrow-left"></i> Títulos</a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <x-card>
                <div class="d-flex flex-wrap align-items-start gap-3">
                    <div>
                        <div class="text-secondary">{{ $titulo->descricao }}</div>
                        <div class="fs-2 fw-semibold tabular">@brl($titulo->valor)</div>
                    </div>
                    <div class="ms-auto">@include('financeiro._situacao', ['situacao' => $titulo->situacao])</div>
                </div>
                <hr class="my-4">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-secondary fw-medium">Cliente</dt>
                    <dd class="col-sm-8">{{ $titulo->cliente->nome }} <span class="text-secondary">· {{ $titulo->cliente->documento }}</span></dd>
                    <dt class="col-sm-4 text-secondary fw-medium">Origem</dt>
                    <dd class="col-sm-8">
                        @if ($titulo->pedido)
                            <a href="{{ route('erp.pedidos.show', $titulo->pedido) }}" class="text-decoration-none">Pedido {{ $titulo->pedido->numero }} (ERP)</a>
                        @else — @endif
                    </dd>
                    <dt class="col-sm-4 text-secondary fw-medium">Vencimento</dt>
                    <dd class="col-sm-8">{{ $titulo->vencimento->format('d/m/Y') }}</dd>
                    @if ($titulo->pago_em)
                        <dt class="col-sm-4 text-secondary fw-medium">Pago em</dt>
                        <dd class="col-sm-8">{{ $titulo->pago_em->format('d/m/Y H:i') }} · @brl($titulo->valor_pago)</dd>
                    @endif
                </dl>
            </x-card>

            <x-card titulo="Eventos de webhook" descricao="Tudo que o banco enviou sobre esta cobrança">
                @forelse ($titulo->eventos as $evento)
                    @include('financeiro._evento')
                @empty
                    <p class="text-secondary mb-0">Nenhum webhook recebido ainda.</p>
                @endforelse
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card titulo="Cobrança" descricao="API do banco simulado">
                @if ($titulo->codigo_cobranca)
                    <div class="small text-secondary">Código</div>
                    <div class="fw-medium mb-3"><code>{{ $titulo->codigo_cobranca }}</code></div>
                    <div class="small text-secondary">Linha digitável (fictícia)</div>
                    <div class="font-monospace small mb-0 text-break">{{ $titulo->linha_digitavel }}</div>
                @elseif ($titulo->status === 'aberto')
                    <p class="text-secondary">Este título ainda não tem cobrança no banco.</p>
                    <form method="POST" action="{{ route('financeiro.titulos.cobranca', $titulo) }}">
                        @csrf
                        <button type="submit" class="btn btn-destaque"><i data-lucide="landmark"></i> Registrar cobrança</button>
                    </form>
                @else
                    <p class="text-secondary mb-0">Sem cobrança.</p>
                @endif
            </x-card>

            @if ($titulo->codigo_cobranca)
                <x-card titulo="Simular o banco" descricao="Dispara o webhook de pagamento como o banco faria">
                    <div class="d-flex flex-column gap-2">
                        <form method="POST" action="{{ route('financeiro.titulos.simular', $titulo) }}">
                            @csrf
                            <button type="submit" class="btn btn-destaque w-100"><i data-lucide="send"></i> Enviar webhook de pagamento</button>
                        </form>
                        <form method="POST" action="{{ route('financeiro.titulos.simular', $titulo) }}">
                            @csrf <input type="hidden" name="invalida" value="1">
                            <button type="submit" class="btn btn-outline-secondary w-100"><i data-lucide="shield-x"></i> Enviar com assinatura inválida</button>
                        </form>
                    </div>
                    <p class="small text-secondary mt-3 mb-0">
                        O webhook é validado por HMAC-SHA256, é idempotente pelo <code>id</code> do evento e só dá baixa em título aberto.
                        Pagar de novo um título já baixado é registrado como <em>ignorado</em>.
                    </p>
                </x-card>
            @endif
        </div>
    </div>
@endsection
