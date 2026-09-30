@extends('layouts.app')

@php
    // Ações disponíveis por método + status (espelham as transições do GatewayFake).
    $acoes = array_filter([
        'receber-pix' => $pagamento->metodo === 'pix' && $pagamento->status === 'pendente' ? ['Simular pagamento do Pix', 'btn-destaque', 'qr-code'] : null,
        'expirar' => $pagamento->metodo === 'pix' && $pagamento->status === 'pendente' ? ['Expirar cobrança', 'btn-outline-secondary', 'timer-off'] : null,
        'liquidar' => $pagamento->metodo === 'cartao' && $pagamento->status === 'confirmado' ? ['Liquidar (antecipar D+30)', 'btn-destaque', 'banknote'] : null,
        'estornar' => in_array($pagamento->status, ['confirmado', 'recebido']) ? ['Estornar', 'btn-outline-secondary', 'undo-2'] : null,
    ]);
@endphp

@section('header')
    <span class="font-monospace">{{ $pagamento->codigo }}</span>
@endsection
@section('actions')
    <a href="{{ route('pagamentos.index') }}" class="btn btn-outline-secondary"><i data-lucide="arrow-left"></i> Transações</a>
@endsection

@push('styles')
<style>
    .linha-tempo { list-style: none; margin: 0; padding: 0; position: relative; }
    .linha-tempo::before { content: ''; position: absolute; left: 15px; top: 6px; bottom: 6px; width: 1px; background: var(--border); }
    .linha-tempo > li { position: relative; padding-left: 46px; padding-bottom: 1.5rem; }
    .linha-tempo > li:last-child { padding-bottom: 0; }
    .linha-tempo .marco { position: absolute; left: 0; top: 0; width: 32px; height: 32px; border-radius: 50%; background: var(--background);
        border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <x-card titulo="Linha do tempo" descricao="Eventos PAYMENT_* emitidos pelo gateway">
                <ol class="linha-tempo">
                    @foreach ($pagamento->eventos as $evento)
                        @php
                            [$desc, $cor, $icone] = \App\Models\Pagamento::EVENTOS[$evento->tipo];
                        @endphp
                        <li>
                            <span class="marco text-{{ $cor === 'secondary' ? 'secondary' : $cor }}" aria-hidden="true"><i data-lucide="{{ $icone }}"></i></span>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <x-badge :cor="$cor" class="font-monospace">{{ $evento->tipo }}</x-badge>
                                <span class="fw-medium">{{ $desc }}</span>
                                <time class="small text-secondary ms-auto" datetime="{{ $evento->created_at->toIso8601String() }}">{{ $evento->created_at->format('d/m/Y H:i:s') }}</time>
                            </div>
                            @isset($evento->dados['motivo'])<div class="small text-danger mt-1">{{ $evento->dados['motivo'] }}</div>@endisset
                            <details class="mt-2">
                                <summary class="small text-secondary">Payload</summary>
                                <pre class="small bg-body-tertiary border rounded-2 p-3 mt-2 mb-0" style="white-space: pre-wrap">{{ json_encode($evento->dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary">{{ $pagamento->descricao }}</span>
                    @include('pagamentos._status', ['status' => $pagamento->status])
                </div>
                <div class="fs-2 fw-semibold tabular mb-3">@brl($pagamento->valor)</div>
                <dl class="row small mb-0">
                    <dt class="col-5 text-secondary fw-medium">Cliente</dt><dd class="col-7">{{ $pagamento->cliente->nome }}</dd>
                    <dt class="col-5 text-secondary fw-medium">Meio</dt><dd class="col-7">{{ $pagamento->meio }}</dd>
                    <dt class="col-5 text-secondary fw-medium">Taxa</dt><dd class="col-7 tabular">@brl($pagamento->taxa)</dd>
                    <dt class="col-5 text-secondary fw-medium">Líquido</dt><dd class="col-7 tabular">@brl($pagamento->valor_liquido)</dd>
                    <dt class="col-5 text-secondary fw-medium">Criado em</dt><dd class="col-7">{{ $pagamento->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-5 text-secondary fw-medium">Idempotency key</dt><dd class="col-7 font-monospace text-break mb-0">{{ $pagamento->idempotency_key }}</dd>
                </dl>
            </x-card>

            @if ($pagamento->metodo === 'pix' && $pagamento->status === 'pendente')
                <x-card titulo="Pix copia e cola" :descricao="'Expira '.$pagamento->expira_em->diffForHumans()">
                    <textarea class="form-control font-monospace small mb-2" rows="4" readonly id="pix" aria-label="Código Pix">{{ $pagamento->pix_copia_cola }}</textarea>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('pix').value); this.lastChild.textContent = ' Copiado'">
                        <i data-lucide="copy"></i> Copiar</button>
                </x-card>
            @endif

            @if ($acoes)
                <x-card titulo="Simular o gateway" descricao="Cada ação dispara o evento correspondente">
                    <div class="d-flex flex-column gap-2">
                        @foreach ($acoes as $acao => [$rotulo, $classe, $icone])
                            <form method="POST" action="{{ route('pagamentos.acao', $pagamento) }}">
                                @csrf <input type="hidden" name="acao" value="{{ $acao }}">
                                <button type="submit" class="btn {{ $classe }} w-100"><i data-lucide="{{ $icone }}"></i> {{ $rotulo }}</button>
                            </form>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>
@endsection
