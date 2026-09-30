@extends('layouts.app')

@section('header', 'Transações')
@section('actions')
    <a href="{{ route('pagamentos.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Nova cobrança</a>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-stat label="Volume aprovado" icone="trending-up" :valor="'R$ '.number_format($volume, 2, ',', '.')" :detalhe="'Líquido R$ '.number_format($liquido, 2, ',', '.')" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Aprovação no cartão" icone="credit-card" :valor="$aprovacao === null ? '—' : number_format($aprovacao, 1, ',', '.').'%'" detalhe="aprovados ÷ decididos" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Aguardando pagamento" icone="hourglass" :valor="$pendentes" detalhe="Pix não pagos" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Estornado" icone="undo-2" :valor="'R$ '.number_format($estornado, 2, ',', '.')" /></div>
    </div>

    <x-card>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Transação</th><th>Data</th><th>Cliente</th><th>Descrição</th><th>Meio</th><th>Status</th><th class="text-end">Valor</th></tr></thead>
                <tbody>
                @foreach ($pagamentos as $p)
                    <tr>
                        <td><a href="{{ route('pagamentos.show', $p) }}" class="font-monospace small text-destaque text-decoration-none">{{ $p->codigo }}</a></td>
                        <td class="text-nowrap text-secondary" data-order="{{ $p->created_at->timestamp }}">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $p->cliente->nome }}</td>
                        <td class="text-secondary">{{ $p->descricao }}</td>
                        <td class="text-nowrap"><span class="d-inline-flex align-items-center gap-1"><i data-lucide="{{ $p->metodo === 'pix' ? 'qr-code' : 'credit-card' }}"></i> {{ $p->meio }}</span></td>
                        <td data-search="{{ \App\Models\Pagamento::STATUS[$p->status][0] }}">@include('pagamentos._status', ['status' => $p->status])</td>
                        <td class="text-end tabular text-nowrap" data-order="{{ $p->valor }}">@brl($p->valor)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
<script>new DataTable('#tabela', { order: [[1, 'desc']] });</script>
@endpush
