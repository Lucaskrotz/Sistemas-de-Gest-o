@extends('layouts.app')

@section('header', 'Títulos a receber')
@section('actions')
    <form method="POST" action="{{ route('financeiro.importar') }}">
        @csrf
        <button type="submit" class="btn btn-destaque">
            <i data-lucide="download"></i> Importar do ERP
            @if ($pedidosSemTitulo)<span class="badge text-bg-light ms-1">{{ $pedidosSemTitulo }}</span>@endif
        </button>
    </form>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-stat label="A receber (no prazo)" icone="wallet" :valor="'R$ '.number_format($aReceber, 2, ',', '.')" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Vencido" icone="alarm-clock-off" :valor="'R$ '.number_format($vencido, 2, ',', '.')" :detalhe="$qtdVencidos.' título(s)'" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Recebido no mês" icone="circle-check" :valor="'R$ '.number_format($recebidoMes, 2, ',', '.')" detalhe="baixas via webhook" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Sem cobrança registrada" icone="file-clock" :valor="$semCobranca" detalhe="títulos em aberto" /></div>
    </div>

    <x-card>
        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filtrar por situação">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro="">Todos</button>
            @foreach (\App\Models\Titulo::SITUACOES as $chave => [$rotulo])
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro="{{ $rotulo }}">{{ $rotulo }}</button>
            @endforeach
        </div>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Título</th><th>Cliente</th><th>Descrição</th><th>Vencimento</th><th>Situação</th><th>Cobrança</th><th class="text-end">Valor</th></tr></thead>
                <tbody>
                @foreach ($titulos as $titulo)
                    <tr>
                        <td><a href="{{ route('financeiro.titulos.show', $titulo) }}" class="fw-medium text-destaque text-decoration-none">{{ $titulo->numero }}</a></td>
                        <td>{{ $titulo->cliente->nome }}</td>
                        <td class="text-secondary">{{ $titulo->descricao }}</td>
                        <td data-order="{{ $titulo->vencimento->format('Ymd') }}">{{ $titulo->vencimento->format('d/m/Y') }}</td>
                        <td data-search="{{ \App\Models\Titulo::SITUACOES[$titulo->situacao][0] }}">@include('financeiro._situacao', ['situacao' => $titulo->situacao])</td>
                        <td class="text-secondary small text-nowrap">{{ $titulo->codigo_cobranca ?? '—' }}</td>
                        <td class="text-end tabular text-nowrap" data-order="{{ $titulo->valor }}">@brl($titulo->valor)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
<script>
    const tabela = new DataTable('#tabela', { order: [[3, 'desc']] });
    const botoes = document.querySelectorAll('[data-filtro]');
    botoes.forEach((b) => b.addEventListener('click', () => {
        botoes.forEach((x) => x.classList.toggle('active', x === b));
        tabela.column(4).search(b.dataset.filtro ? `^${b.dataset.filtro}$` : '', { regex: true, smart: false }).draw();
    }));
    botoes[0].classList.add('active');
</script>
@endpush
