@extends('layouts.app')

@section('header', 'Dashboard')
@section('actions')
    <a href="{{ route('erp.pedidos.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Novo pedido</a>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-stat label="Faturamento do mês" icone="wallet" :valor="'R$ '.number_format($faturamento, 2, ',', '.')" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Pedidos no mês" icone="shopping-cart" :valor="$qtdPedidos" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Ticket médio" icone="receipt" :valor="'R$ '.number_format($ticketMedio, 2, ',', '.')" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Clientes ativos" icone="users" :valor="$clientesAtivos" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <x-card titulo="Últimos pedidos">
                <x-slot:acoes><a href="{{ route('erp.pedidos.index') }}" class="small text-destaque text-decoration-none">Ver todos</a></x-slot:acoes>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach ($ultimosPedidos as $pedido)
                            <tr>
                                <td><a href="{{ route('erp.pedidos.show', $pedido) }}" class="fw-semibold text-destaque text-decoration-none">{{ $pedido->numero }}</a></td>
                                <td>{{ $pedido->cliente->nome }}</td>
                                <td class="text-secondary">{{ $pedido->created_at->format('d/m/Y') }}</td>
                                <td>@include('erp._status', ['status' => $pedido->status])</td>
                                <td class="text-end">@brl($pedido->total)</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card titulo="Estoque baixo">
                @forelse ($estoqueBaixo as $produto)
                    <div class="d-flex align-items-center justify-content-between py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                        <div>
                            <a href="{{ route('erp.produtos.edit', $produto) }}" class="text-body text-decoration-none fw-medium">{{ $produto->nome }}</a>
                            <div class="small text-secondary">{{ $produto->sku }}</div>
                        </div>
                        <x-badge :cor="$produto->estoque ? 'warning' : 'danger'">{{ $produto->estoque }} un.</x-badge>
                    </div>
                @empty
                    <p class="text-secondary mb-0">Nenhum produto abaixo de {{ \App\Models\Produto::ESTOQUE_BAIXO }} unidades.</p>
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
