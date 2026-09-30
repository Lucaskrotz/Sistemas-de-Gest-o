@extends('layouts.app')

@section('header', 'Pedidos')
@section('actions')
    <a href="{{ route('erp.pedidos.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Novo pedido</a>
@endsection

@section('content')
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Data</th><th class="text-center">Itens</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach ($pedidos as $pedido)
                    <tr>
                        <td data-order="{{ $pedido->id }}"><a href="{{ route('erp.pedidos.show', $pedido) }}" class="fw-semibold text-destaque text-decoration-none">{{ $pedido->numero }}</a></td>
                        <td>{{ $pedido->cliente->nome }}</td>
                        <td class="text-secondary" data-order="{{ $pedido->created_at->timestamp }}">{{ $pedido->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $pedido->itens_count }}</td>
                        <td>@include('erp._status', ['status' => $pedido->status])</td>
                        <td class="text-end text-nowrap" data-order="{{ $pedido->total }}">@brl($pedido->total)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
<script>new DataTable('#tabela', { order: [[2, 'desc']] });</script>
@endpush
