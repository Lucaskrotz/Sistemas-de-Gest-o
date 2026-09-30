@extends('layouts.app')

@section('header', 'Clientes')
@section('actions')
    <a href="{{ route('erp.clientes.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Novo cliente</a>
@endsection

@section('content')
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Nome</th><th>CPF/CNPJ</th><th>E-mail</th><th>Cidade</th><th class="text-center">Pedidos</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @foreach ($clientes as $cliente)
                    <tr>
                        <td class="fw-medium">{{ $cliente->nome }}</td>
                        <td class="text-secondary text-nowrap">{{ $cliente->documento }}</td>
                        <td>{{ $cliente->email }}</td>
                        <td>{{ $cliente->cidade }}{{ $cliente->uf ? '/'.$cliente->uf : '' }}</td>
                        <td class="text-center">{{ $cliente->pedidos_count }}</td>
                        <td><x-badge :cor="$cliente->ativo ? 'success' : 'secondary'">{{ $cliente->ativo ? 'Ativo' : 'Inativo' }}</x-badge></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('erp.clientes.edit', $cliente) }}" class="btn btn-sm btn-light btn-icone" aria-label="Editar {{ $cliente->nome }}"><i data-lucide="pencil"></i></a>
                            <button type="button" class="btn btn-sm btn-light btn-icone text-danger" aria-label="Excluir {{ $cliente->nome }}"
                                    data-excluir="{{ route('erp.clientes.destroy', $cliente) }}" data-nome="{{ $cliente->nome }}"><i data-lucide="trash-2"></i></button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    @include('erp._excluir')
@endsection

@push('scripts')
<script>new DataTable('#tabela', { columnDefs: [{ targets: -1, orderable: false, searchable: false }] });</script>
@endpush
