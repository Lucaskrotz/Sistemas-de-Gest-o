@extends('layouts.app')

@section('header', 'Eventos do gateway')

@section('content')
    <x-card>
        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filtrar por tipo de evento">
            <button type="button" class="btn btn-sm btn-outline-secondary active" data-filtro="">Todos</button>
            @foreach (array_keys(\App\Models\Pagamento::EVENTOS) as $tipo)
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" data-filtro="{{ $tipo }}">{{ $tipo }}</button>
            @endforeach
        </div>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Quando</th><th>Evento</th><th>Transação</th><th>Cliente</th><th>Detalhe</th><th class="text-end">Valor</th></tr></thead>
                <tbody>
                @foreach ($eventos as $e)
                    <tr>
                        <td class="text-nowrap text-secondary" data-order="{{ $e->created_at->timestamp }}.{{ $e->id }}">{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                        <td data-search="{{ $e->tipo }}"><x-badge :cor="\App\Models\Pagamento::EVENTOS[$e->tipo][1]" class="font-monospace">{{ $e->tipo }}</x-badge></td>
                        <td><a href="{{ route('pagamentos.show', $e->pagamento) }}" class="font-monospace small text-destaque text-decoration-none">{{ $e->pagamento->codigo }}</a></td>
                        <td>{{ $e->pagamento->cliente->nome }}</td>
                        <td class="small text-secondary">{{ $e->dados['motivo'] ?? $e->pagamento->meio }}</td>
                        <td class="text-end tabular text-nowrap" data-order="{{ $e->pagamento->valor }}">@brl($e->pagamento->valor)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
<script>
    const tabela = new DataTable('#tabela', { order: [[0, 'desc']], pageLength: 25 });
    const botoes = document.querySelectorAll('[data-filtro]');
    botoes.forEach((b) => b.addEventListener('click', () => {
        botoes.forEach((x) => x.classList.toggle('active', x === b));
        tabela.column(1).search(b.dataset.filtro ? `^${b.dataset.filtro}$` : '', { regex: true, smart: false }).draw();
    }));
</script>
@endpush
