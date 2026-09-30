@extends('layouts.app')

@section('header', 'Quadro de chamados')
@section('actions')
    <a href="{{ route('chamados.create') }}" class="btn btn-destaque"><i data-lucide="plus"></i> Novo chamado</a>
@endsection

@push('styles')
<style>
    .kanban { display: grid; grid-template-columns: repeat(4, minmax(270px, 1fr)); gap: 1rem; overflow-x: auto; padding-bottom: .5rem; }
    .coluna { background: var(--muted); border-radius: calc(var(--radius) + 4px); padding: .5rem; min-height: 60vh; transition: background .2s, box-shadow .2s; }
    .coluna.alvo { background: color-mix(in srgb, var(--destaque) 10%, var(--muted)); box-shadow: inset 0 0 0 2px var(--destaque); }
    .cartao { background: #fff; border: 1px solid var(--border); border-left: 4px solid var(--cor-prio); border-radius: var(--radius);
        padding: .75rem; margin-bottom: .6rem; cursor: grab; transition: box-shadow .2s, transform .2s; }
    .cartao:hover { box-shadow: 0 4px 12px rgba(15,23,42,.08); }
    .cartao.arrastando { opacity: .5; transform: rotate(1deg); }
    .cartao .titulo { color: var(--foreground); font-weight: 600; text-decoration: none; }
    .cartao .titulo:hover { color: var(--destaque); }
    .cartao .iniciais { width: 26px; height: 26px; font-size: .7rem; background: var(--muted); color: var(--foreground); }
</style>
@endpush

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat label="Em aberto" icone="inbox" :valor="$abertos" /></div>
        <div class="col-6 col-xl-3"><x-stat label="SLA vencido" icone="alarm-clock-off" :valor="$vencidos" /></div>
        <div class="col-6 col-xl-3"><x-stat label="Em risco" icone="triangle-alert" :valor="$emRisco" detalhe="menos de 25% do prazo" /></div>
        <div class="col-6 col-xl-3"><x-stat label="SLA cumprido" icone="circle-check" :valor="$slaCumprido === null ? '—' : $slaCumprido.'%'" detalhe="últimos 30 dias" /></div>
    </div>

    <p class="small text-secondary d-flex align-items-center gap-1"><i data-lucide="move"></i> Arraste os cards entre as colunas para mudar o status.</p>

    <div class="kanban">
        @foreach (\App\Models\Chamado::STATUS as $status => $rotulo)
            <section class="coluna" data-status="{{ $status }}" aria-label="{{ $rotulo }}">
                <h2 class="h6 d-flex align-items-center justify-content-between mb-3 px-1">
                    {{ $rotulo }} <span class="badge text-bg-light border contador">{{ count($colunas[$status] ?? []) }}</span>
                </h2>
                @foreach ($colunas[$status] ?? [] as $chamado)
                    @php
                        [$prioRotulo, $prioCor] = \App\Models\Chamado::PRIORIDADES[$chamado->prioridade];
                        [$slaTexto, $slaCor] = $chamado->sla();
                    @endphp
                    <article class="cartao" draggable="true" data-id="{{ $chamado->id }}"
                             data-url="{{ route('chamados.update', $chamado) }}" style="--cor-prio: var(--bs-{{ $prioCor }})">
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>{{ $chamado->numero }}</span>
                            <x-badge :cor="$prioCor">{{ $prioRotulo }}</x-badge>
                        </div>
                        <a href="{{ route('chamados.show', $chamado) }}" class="titulo d-block mb-1">{{ $chamado->titulo }}</a>
                        <div class="small text-secondary mb-2 text-truncate">{{ $chamado->cliente->nome }}</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <x-badge :cor="$slaCor" class="sla fw-medium">{{ $slaTexto }}</x-badge>
                            @if ($chamado->responsavel)
                                <span class="iniciais rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold"
                                      title="{{ $chamado->responsavel }}" aria-label="Responsável: {{ $chamado->responsavel }}">
                                    {{ collect(explode(' ', $chamado->responsavel))->map(fn ($p) => $p[0])->join('') }}
                                </span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @endforeach
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast text-bg-danger" role="alert" aria-live="assertive"><div class="toast-body"></div></div>
    </div>
@endsection

@push('scripts')
<script>
    let arrastado = null;
    const contar = () => document.querySelectorAll('.coluna').forEach((c) => c.querySelector('.contador').textContent = c.querySelectorAll('.cartao').length);

    document.querySelectorAll('.cartao').forEach((card) => {
        card.addEventListener('dragstart', (e) => { arrastado = card; card.classList.add('arrastando'); e.dataTransfer.effectAllowed = 'move'; });
        card.addEventListener('dragend', () => card.classList.remove('arrastando'));
    });

    document.querySelectorAll('.coluna').forEach((coluna) => {
        coluna.addEventListener('dragover', (e) => { e.preventDefault(); coluna.classList.add('alvo'); });
        coluna.addEventListener('dragleave', (e) => { if (!coluna.contains(e.relatedTarget)) coluna.classList.remove('alvo'); });
        coluna.addEventListener('drop', async (e) => {
            e.preventDefault();
            coluna.classList.remove('alvo');
            const card = arrastado;
            arrastado = null;
            if (!card || card.parentElement === coluna) return;
            const origem = card.parentElement;

            coluna.insertBefore(card, coluna.querySelector('.cartao')); // topo da coluna
            contar();
            try {
                const { sla } = await api(card.dataset.url, { method: 'PATCH', body: { status: coluna.dataset.status } });
                const badge = card.querySelector('.sla');
                badge.className = badge.className.replace(/text-bg-\w+/, `text-bg-${sla.cor}`);
                badge.textContent = sla.texto;
            } catch (erro) {
                origem.append(card); // desfaz
                contar();
                const toast = document.getElementById('toast');
                toast.querySelector('.toast-body').textContent = `Não foi possível mover: ${erro.message}`;
                bootstrap.Toast.getOrCreateInstance(toast).show();
            }
        });
    });
</script>
@endpush
