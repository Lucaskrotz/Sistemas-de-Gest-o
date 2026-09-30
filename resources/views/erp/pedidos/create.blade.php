@extends('layouts.app')

@section('header', 'Novo pedido')

@section('content')
    <form method="POST" action="{{ route('erp.pedidos.store') }}">
        @csrf
        <div class="row g-4">
            <div class="col-xl-8">
                <x-card titulo="Itens">
                    <x-slot:acoes>
                        <button type="button" class="btn btn-sm btn-light d-inline-flex align-items-center gap-1" id="add-item">
                            <i data-lucide="plus"></i> Adicionar item
                        </button>
                    </x-slot:acoes>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th style="min-width: 240px">Produto</th><th style="width: 120px">Qtd.</th><th class="text-end">Unitário</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                            <tbody id="itens"></tbody>
                        </table>
                    </div>
                </x-card>
            </div>
            <div class="col-xl-4">
                <x-card titulo="Resumo">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select id="cliente_id" name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                            <option value="">Selecione…</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observação</label>
                        <textarea id="observacao" name="observacao" rows="3" class="form-control">{{ old('observacao') }}</textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mb-3">
                        <span class="text-secondary">Total</span>
                        <span class="fs-4 fw-bold" id="total" aria-live="polite">R$ 0,00</span>
                    </div>
                    <button type="submit" class="btn btn-destaque w-100 justify-content-center">Criar pedido</button>
                </x-card>
            </div>
        </div>
    </form>

    <template id="linha">
        <tr>
            <td>
                <select class="form-select form-select-sm produto" aria-label="Produto" required>
                    <option value="">Selecione…</option>
                    @foreach ($produtos as $produto)
                        <option value="{{ $produto->id }}" data-preco="{{ $produto->preco }}" data-estoque="{{ $produto->estoque }}">
                            {{ $produto->nome }} ({{ $produto->estoque }} em estoque)
                        </option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" min="1" value="1" class="form-control form-control-sm qtd" aria-label="Quantidade" required></td>
            <td class="text-end text-nowrap unitario">—</td>
            <td class="text-end text-nowrap fw-medium subtotal">—</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-light btn-icone text-danger remover" aria-label="Remover item"><i data-lucide="x"></i></button></td>
        </tr>
    </template>
@endsection

@push('scripts')
<script>
    const tbody = document.getElementById('itens');
    const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    // Renomeia os campos itens[i][...] na ordem atual e recalcula totais.
    function atualizar() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach((tr, i) => {
            const opt = tr.querySelector('.produto').selectedOptions[0];
            const qtd = tr.querySelector('.qtd');
            tr.querySelector('.produto').name = `itens[${i}][produto_id]`;
            qtd.name = `itens[${i}][quantidade]`;
            qtd.max = opt?.dataset.estoque ?? '';
            const preco = parseFloat(opt?.dataset.preco ?? 0);
            const subtotal = preco * (parseInt(qtd.value) || 0);
            tr.querySelector('.unitario').textContent = preco ? brl(preco) : '—';
            tr.querySelector('.subtotal').textContent = preco ? brl(subtotal) : '—';
            total += subtotal;
        });
        document.getElementById('total').textContent = brl(total);
        tbody.querySelectorAll('.remover').forEach((b) => b.disabled = tbody.children.length === 1);
    }

    function adicionar(item = {}) {
        tbody.append(document.getElementById('linha').content.cloneNode(true));
        const tr = tbody.lastElementChild;
        if (item.produto_id) tr.querySelector('.produto').value = item.produto_id;
        if (item.quantidade) tr.querySelector('.qtd').value = item.quantidade;
        lucide.createIcons({ root: tr });
        atualizar();
    }

    document.getElementById('add-item').addEventListener('click', () => adicionar());
    tbody.addEventListener('input', atualizar);
    tbody.addEventListener('click', (e) => {
        if (e.target.closest('.remover')) { e.target.closest('tr').remove(); atualizar(); }
    });

    const antigos = @json(array_values(old('itens', [])));
    antigos.length ? antigos.forEach(adicionar) : adicionar();
</script>
@endpush
