@extends('layouts.app')

@section('header')
    Pedido {{ $pedido->numero }}
@endsection
@section('actions')
    <a href="{{ route('erp.pedidos.index') }}" class="btn btn-light d-inline-flex align-items-center gap-1"><i data-lucide="arrow-left"></i> Voltar</a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-8">
            <x-card titulo="Itens">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Produto</th><th class="text-center">Qtd.</th><th class="text-end">Unitário</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        @foreach ($pedido->itens as $item)
                            <tr>
                                <td><span class="fw-medium">{{ $item->produto->nome }}</span> <span class="small text-secondary">{{ $item->produto->sku }}</span></td>
                                <td class="text-center">{{ $item->quantidade }}</td>
                                <td class="text-end">@brl($item->preco_unitario)</td>
                                <td class="text-end">@brl($item->subtotal)</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-end border-0">Total</th><th class="text-end border-0 fs-5">@brl($pedido->total)</th></tr>
                        </tfoot>
                    </table>
                </div>
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card titulo="Detalhes">
                <dl class="mb-0">
                    <dt class="small text-secondary fw-medium">Cliente</dt>
                    <dd>{{ $pedido->cliente->nome }}<br><span class="small text-secondary">{{ $pedido->cliente->documento }} · {{ $pedido->cliente->email }}</span></dd>
                    <dt class="small text-secondary fw-medium">Data</dt>
                    <dd>{{ $pedido->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="small text-secondary fw-medium">Status</dt>
                    <dd>@include('erp._status', ['status' => $pedido->status])</dd>
                    @if ($pedido->observacao)
                        <dt class="small text-secondary fw-medium">Observação</dt>
                        <dd class="mb-0">{{ $pedido->observacao }}</dd>
                    @endif
                </dl>
            </x-card>

            @if ($pedido->status !== 'cancelado')
                <x-card titulo="Alterar status">
                    <form method="POST" action="{{ route('erp.pedidos.status', $pedido) }}" class="d-flex gap-2">
                        @csrf @method('PATCH')
                        <label for="status" class="visually-hidden">Novo status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach (array_keys(\App\Models\Pedido::STATUS) as $status)
                                <option value="{{ $status }}" @selected($pedido->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-destaque">Salvar</button>
                    </form>
                    <p class="small text-secondary mt-2 mb-0">Cancelar devolve os itens ao estoque.</p>
                </x-card>
            @endif
        </div>
    </div>
@endsection
