@extends('layouts.app')

@section('header', 'Produtos')
@section('actions')
    <a href="{{ route('erp.produtos.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Novo produto</a>
@endsection

@section('content')
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>SKU</th><th>Produto</th><th>Categoria</th><th class="text-end">Preço</th><th class="text-end">Estoque</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                @foreach ($produtos as $produto)
                    <tr>
                        <td class="text-secondary text-nowrap">{{ $produto->sku }}</td>
                        <td class="fw-medium">{{ $produto->nome }}</td>
                        <td>{{ $produto->categoria }}</td>
                        <td class="text-end text-nowrap" data-order="{{ $produto->preco }}">@brl($produto->preco)</td>
                        <td class="text-end" data-order="{{ $produto->estoque }}">
                            <span @class(['fw-semibold text-danger' => $produto->estoque < \App\Models\Produto::ESTOQUE_BAIXO])>{{ $produto->estoque }}</span>
                        </td>
                        <td><x-badge :cor="$produto->ativo ? 'success' : 'secondary'">{{ $produto->ativo ? 'Ativo' : 'Inativo' }}</x-badge></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('erp.produtos.edit', $produto) }}" class="btn btn-sm btn-light btn-icone" aria-label="Editar {{ $produto->nome }}"><i data-lucide="pencil"></i></a>
                            <button type="button" class="btn btn-sm btn-light btn-icone text-danger" aria-label="Excluir {{ $produto->nome }}"
                                    data-excluir="{{ route('erp.produtos.destroy', $produto) }}" data-nome="{{ $produto->nome }}"><i data-lucide="trash-2"></i></button>
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
<script>new DataTable('#tabela', { order: [[1, 'asc']], columnDefs: [{ targets: -1, orderable: false, searchable: false }] });</script>
@endpush
