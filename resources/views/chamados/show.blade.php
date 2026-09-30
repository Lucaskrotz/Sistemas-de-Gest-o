@extends('layouts.app')

@php
    [$prioRotulo, $prioCor, $horas] = \App\Models\Chamado::PRIORIDADES[$chamado->prioridade];
    [$slaTexto, $slaCor] = $chamado->sla();
@endphp

@section('header')
    Chamado {{ $chamado->numero }}
@endsection
@section('actions')
    <a href="{{ route('chamados.index') }}" class="btn btn-light d-inline-flex align-items-center gap-1"><i data-lucide="arrow-left"></i> Quadro</a>
@endsection

@push('styles')
<style>
    .timeline { list-style: none; padding: 0; margin: 0; position: relative; }
    .timeline::before { content: ''; position: absolute; left: 15px; top: 4px; bottom: 4px; width: 2px; background: var(--border); }
    .timeline li { position: relative; padding-left: 44px; padding-bottom: 1.25rem; }
    .timeline .marco { position: absolute; left: 0; top: 0; width: 32px; height: 32px; border-radius: 50%; background: #fff;
        border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--muted-foreground); }
    .timeline .comentario .marco { border-color: var(--destaque); color: var(--destaque); }
    .timeline .comentario .texto { background: var(--muted); border: 1px solid var(--border); border-radius: .5rem; padding: .6rem .8rem; white-space: pre-line; }
    .timeline svg.lucide { width: 15px; height: 15px; }
</style>
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-xl-8">
            <x-card>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <x-badge :cor="$prioCor">{{ $prioRotulo }}</x-badge>
                    <x-badge cor="light" class="border">{{ \App\Models\Chamado::STATUS[$chamado->status] }}</x-badge>
                    <x-badge :cor="$slaCor">{{ $slaTexto }}</x-badge>
                </div>
                <h2 class="h4 fw-semibold">{{ $chamado->titulo }}</h2>
                <p class="mb-0" style="white-space: pre-line">{{ $chamado->descricao }}</p>
            </x-card>

            <x-card titulo="Histórico">
                <form method="POST" action="{{ route('chamados.comentar', $chamado) }}" class="mb-4">
                    @csrf
                    <label for="comentario" class="form-label">Novo comentário</label>
                    <textarea id="comentario" name="comentario" rows="3" class="form-control mb-2 @error('comentario') is-invalid @enderror" required>{{ old('comentario') }}</textarea>
                    <button type="submit" class="btn btn-destaque btn-sm">Comentar</button>
                </form>

                <ol class="timeline">
                    @foreach ($chamado->historicos as $h)
                        <li class="{{ $h->tipo }}">
                            <span class="marco" aria-hidden="true"><i data-lucide="{{ $h->tipo === 'comentario' ? 'message-square' : 'activity' }}"></i></span>
                            <div class="small text-secondary mb-1">
                                <span class="fw-semibold text-body">{{ $h->autor }}</span>
                                · <time datetime="{{ $h->created_at->toIso8601String() }}" title="{{ $h->created_at->format('d/m/Y H:i') }}">{{ $h->created_at->diffForHumans() }}</time>
                            </div>
                            <div class="texto">{{ $h->descricao }}</div>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card titulo="Detalhes">
                <dl class="mb-0">
                    <dt class="small text-secondary fw-medium">Cliente</dt>
                    <dd>{{ $chamado->cliente->nome }}<br><span class="small text-secondary">{{ $chamado->cliente->email }}</span></dd>
                    <dt class="small text-secondary fw-medium">Aberto em</dt>
                    <dd>{{ $chamado->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="small text-secondary fw-medium">Prazo SLA ({{ $horas }}h)</dt>
                    <dd>{{ $chamado->prazo_sla->format('d/m/Y H:i') }}</dd>
                    @if ($chamado->resolvido_em)
                        <dt class="small text-secondary fw-medium">Resolvido em</dt>
                        <dd class="mb-0">{{ $chamado->resolvido_em->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
            </x-card>

            <x-card titulo="Atendimento">
                <form method="POST" action="{{ route('chamados.update', $chamado) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach (\App\Models\Chamado::STATUS as $chave => $rotulo)
                                <option value="{{ $chave }}" @selected($chamado->status === $chave)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="responsavel" class="form-label">Responsável</label>
                        <select id="responsavel" name="responsavel" class="form-select">
                            <option value="">Sem responsável</option>
                            @foreach (\App\Models\Chamado::ATENDENTES as $atendente)
                                <option @selected($chamado->responsavel === $atendente)>{{ $atendente }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-destaque w-100 justify-content-center">Salvar</button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
