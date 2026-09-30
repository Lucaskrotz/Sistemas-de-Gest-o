@extends('layouts.app')

@section('header', 'Painel de indicadores')

@push('styles')
<style>
    .grafico { position: relative; height: 280px; }
    .grafico-sm { height: 240px; }
    #painel[aria-busy=true] { opacity: .55; transition: opacity .2s; }
    .variacao { display: inline-flex; align-items: center; gap: .25rem; }
    .variacao svg { width: 14px; height: 14px; }
    details.tabela summary { color: var(--muted-foreground); font-size: .8125rem; margin-top: 1rem; list-style: none; display: inline-flex; align-items: center; gap: .375rem; }
    details.tabela summary::-webkit-details-marker { display: none; }
    details.tabela summary:hover { color: var(--foreground); }
    details.tabela[open] summary { margin-bottom: .5rem; }
</style>
@endpush

@section('content')
    {{-- Filtros: uma linha acima dos gráficos --}}
    <form id="filtros" class="d-flex flex-wrap align-items-end gap-3 mb-4" novalidate>
        <div class="tabs" role="group" aria-label="Período rápido">
            <button type="button" data-dias="7" aria-pressed="false">7 dias</button>
            <button type="button" data-dias="30" aria-pressed="false">30 dias</button>
            <button type="button" data-dias="90" aria-pressed="false">90 dias</button>
            <button type="button" data-meses="12" aria-pressed="false">12 meses</button>
        </div>
        <div class="d-flex align-items-end gap-2">
            <div>
                <label for="inicio" class="form-label small mb-1">De</label>
                <input type="date" id="inicio" class="form-control" required>
            </div>
            <div>
                <label for="fim" class="form-label small mb-1">Até</label>
                <input type="date" id="fim" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-outline-secondary">Aplicar</button>
        </div>
        <span class="small text-secondary ms-lg-auto" id="resumo-periodo" aria-live="polite"></span>
    </form>

    <div id="erro" class="alert alert-danger d-none" role="alert"></div>

    <div id="painel" aria-busy="true">
        <div class="row g-3 mb-4" id="kpis">
            @foreach (['faturamento' => 'Faturamento', 'pedidos' => 'Pedidos', 'ticket' => 'Ticket médio', 'sla' => 'SLA cumprido (chamados)'] as $k => $rotulo)
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column gap-1">
                            <div class="d-flex justify-content-between align-items-center text-secondary">
                                <span>{{ $rotulo }}</span>
                                <span class="badge text-bg-light variacao" data-variacao="{{ $k }}">—</span>
                            </div>
                            <div class="fs-3 fw-semibold tabular" data-kpi="{{ $k }}">—</div>
                            <div class="small text-secondary" data-anterior="{{ $k }}">&nbsp;</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <x-card titulo="Faturamento" descricao="Pedidos não cancelados" class="h-100 mb-0">
                    <div class="grafico"><canvas id="g-faturamento" role="img" aria-label="Gráfico de linha do faturamento no período"></canvas></div>
                    <details class="tabela"><summary><i data-lucide="table"></i> Ver tabela</summary><div data-tabela="faturamento"></div></details>
                </x-card>
            </div>
            <div class="col-xl-4">
                <x-card titulo="Top 5 produtos" descricao="Por faturamento" class="h-100 mb-0">
                    <div class="grafico"><canvas id="g-produtos" role="img" aria-label="Gráfico de barras dos produtos mais vendidos"></canvas></div>
                    <details class="tabela"><summary><i data-lucide="table"></i> Ver tabela</summary><div data-tabela="produtos"></div></details>
                </x-card>
            </div>
            <div class="col-xl-8">
                <x-card titulo="Chamados" descricao="Abertos × resolvidos no período" class="h-100 mb-0">
                    <div class="grafico grafico-sm"><canvas id="g-chamados" role="img" aria-label="Gráfico de barras de chamados abertos e resolvidos"></canvas></div>
                    <details class="tabela"><summary><i data-lucide="table"></i> Ver tabela</summary><div data-tabela="chamados"></div></details>
                </x-card>
            </div>
            <div class="col-xl-4">
                <x-card titulo="Faturamento por categoria" class="h-100 mb-0">
                    <div class="grafico grafico-sm"><canvas id="g-categorias" role="img" aria-label="Gráfico de barras do faturamento por categoria"></canvas></div>
                    <details class="tabela"><summary><i data-lucide="table"></i> Ver tabela</summary><div data-tabela="categorias"></div></details>
                </x-card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Paleta categórica validada (skill dataviz): slot 1 azul, slot 2 laranja.
    const SERIE_1 = '#2a78d6', SERIE_2 = '#eb6834';
    const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const brlCurto = (v) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 }).format(v);
    const num = (v, casas = 0) => v.toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
    const dataBr = (iso) => iso.split('-').reverse().join('/');
    const iso = (d) => d.toLocaleDateString('sv-SE');

    const eixoValor = (fmt) => ({ grid: { color: '#f0f0f0' }, ticks: { callback: (v) => fmt(v), maxTicksLimit: 5 }, beginAtZero: true });
    const semGrade = { grid: { display: false } };
    const barra = { borderRadius: 4, maxBarThickness: 22, borderSkipped: 'start' };

    const graficos = {
        faturamento: new Chart('g-faturamento', {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Faturamento', data: [], borderColor: SERIE_1, backgroundColor: 'rgba(42,120,214,.08)',
                fill: true, borderWidth: 2, tension: .35, pointRadius: 0, pointHoverRadius: 5, pointHitRadius: 12,
                pointHoverBackgroundColor: SERIE_1, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2 }] },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${brl(c.parsed.y)}` } } },
                scales: { x: { ...semGrade, ticks: { maxTicksLimit: 8, autoSkip: true } }, y: eixoValor(brlCurto) },
            },
        }),
        produtos: new Chart('g-produtos', {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Faturamento', data: [], backgroundColor: SERIE_1, ...barra }] },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${brl(c.parsed.x)}` } } },
                scales: { x: eixoValor(brlCurto), y: { ...semGrade, ticks: { color: '#0a0a0a', callback(v) { const t = this.getLabelForValue(v); return t.length > 18 ? t.slice(0, 17) + '…' : t; } } } },
            },
        }),
        chamados: new Chart('g-chamados', {
            type: 'bar',
            data: { labels: [], datasets: [
                { label: 'Abertos', data: [], backgroundColor: SERIE_1, ...barra, borderColor: '#fff', borderWidth: { top: 0, left: 1, right: 1 } },
                { label: 'Resolvidos', data: [], backgroundColor: SERIE_2, ...barra, borderColor: '#fff', borderWidth: { top: 0, left: 1, right: 1 } },
            ] },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end' } },
                scales: { x: { ...semGrade, ticks: { maxTicksLimit: 10 } }, y: { ...eixoValor((v) => num(v)), ticks: { precision: 0, maxTicksLimit: 5 } } },
            },
        }),
        categorias: new Chart('g-categorias', {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Faturamento', data: [], backgroundColor: SERIE_1, ...barra }] },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${brl(c.parsed.x)}` } } },
                scales: { x: eixoValor(brlCurto), y: { ...semGrade, ticks: { color: '#0a0a0a' } } },
            },
        }),
    };

    function atualizarGrafico(nome, labels, ...series) {
        const g = graficos[nome];
        g.data.labels = labels;
        series.forEach((s, i) => g.data.datasets[i].data = s);
        g.update();
    }

    // Visão em tabela de cada gráfico (acessibilidade / leitura exata).
    function tabela(nome, cabecalho, linhas) {
        const esc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        document.querySelector(`[data-tabela="${nome}"]`).innerHTML = `
            <div class="table-responsive" style="max-height: 260px"><table class="table table-sm">
                <thead><tr>${cabecalho.map((h, i) => `<th class="${i ? 'text-end' : ''}">${esc(h)}</th>`).join('')}</tr></thead>
                <tbody>${linhas.map((l) => `<tr>${l.map((c, i) => `<td class="${i ? 'text-end tabular' : ''}">${esc(c)}</td>`).join('')}</tr>`).join('')
                    || `<tr><td colspan="${cabecalho.length}" class="text-secondary">Sem dados no período.</td></tr>`}</tbody>
            </table></div>`;
    }

    const FORMATO = { faturamento: brl, pedidos: (v) => num(v), ticket: brl, sla: (v) => v === null ? '—' : `${num(v, 1)}%` };

    function kpis(dados, periodo) {
        const [antIni, antFim] = periodo.anterior;
        for (const [k, { valor, anterior }] of Object.entries(dados)) {
            document.querySelector(`[data-kpi="${k}"]`).textContent = FORMATO[k](valor);
            document.querySelector(`[data-anterior="${k}"]`).textContent = `Anterior: ${FORMATO[k](anterior)} (${dataBr(antIni)} – ${dataBr(antFim)})`;

            // SLA varia em pontos percentuais; os demais em %.
            let delta = null, texto = '—';
            if (valor !== null && anterior !== null) {
                if (k === 'sla') { delta = valor - anterior; texto = `${delta > 0 ? '+' : ''}${num(delta, 1)} p.p.`; }
                else if (anterior) { delta = (valor - anterior) / anterior * 100; texto = `${delta > 0 ? '+' : ''}${num(delta, 1)}%`; }
            }
            const icone = delta === null || Math.abs(delta) < 0.05 ? 'minus' : delta > 0 ? 'arrow-up-right' : 'arrow-down-right';
            const el = document.querySelector(`[data-variacao="${k}"]`);
            el.innerHTML = `<i data-lucide="${icone}"></i>${texto}`;
            el.setAttribute('aria-label', `Variação vs. período anterior: ${texto}`);
        }
        lucide.createIcons({ root: document.getElementById('kpis') });
    }

    async function carregar(inicio, fim) {
        const painel = document.getElementById('painel');
        const erro = document.getElementById('erro');
        painel.setAttribute('aria-busy', 'true');
        erro.classList.add('d-none');
        try {
            const d = await api(`{{ route('indicadores.dados') }}?inicio=${inicio}&fim=${fim}`);
            kpis(d.kpis, d.periodo);

            atualizarGrafico('faturamento', d.faturamento.labels, d.faturamento.valores);
            tabela('faturamento', [d.periodo.granularidade === 'mês' ? 'Mês' : 'Dia', 'Faturamento'],
                d.faturamento.labels.map((l, i) => [l, brl(d.faturamento.valores[i])]));

            atualizarGrafico('chamados', d.chamados.labels, d.chamados.abertos, d.chamados.resolvidos);
            tabela('chamados', [d.periodo.granularidade === 'mês' ? 'Mês' : 'Dia', 'Abertos', 'Resolvidos'],
                d.chamados.labels.map((l, i) => [l, d.chamados.abertos[i], d.chamados.resolvidos[i]]));

            for (const [nome, lista] of [['produtos', d.topProdutos], ['categorias', d.categorias]]) {
                atualizarGrafico(nome, lista.map((p) => p.nome), lista.map((p) => +p.valor));
                tabela(nome, [nome === 'produtos' ? 'Produto' : 'Categoria', 'Faturamento'], lista.map((p) => [p.nome, brl(+p.valor)]));
            }

            document.getElementById('resumo-periodo').textContent =
                `${dataBr(d.periodo.inicio)} – ${dataBr(d.periodo.fim)} · agrupado por ${d.periodo.granularidade}`;
            history.replaceState(null, '', `?inicio=${inicio}&fim=${fim}`);
        } catch (e) {
            erro.textContent = e.message;
            erro.classList.remove('d-none');
        } finally {
            painel.setAttribute('aria-busy', 'false');
        }
    }

    // ---- Filtros ----
    const campoIni = document.getElementById('inicio'), campoFim = document.getElementById('fim');
    const presets = document.querySelectorAll('.tabs button');

    function aplicar(inicio, fim, botao = null) {
        campoIni.value = inicio; campoFim.value = fim;
        presets.forEach((b) => b.setAttribute('aria-pressed', b === botao));
        carregar(inicio, fim);
    }

    presets.forEach((b) => b.addEventListener('click', () => {
        const hoje = new Date(), ini = new Date();
        if (b.dataset.meses) { ini.setMonth(ini.getMonth() - (b.dataset.meses - 1), 1); }
        else { ini.setDate(ini.getDate() - (b.dataset.dias - 1)); }
        aplicar(iso(ini), iso(hoje), b);
    }));

    document.getElementById('filtros').addEventListener('submit', (e) => {
        e.preventDefault();
        if (!campoIni.value || !campoFim.value) return;
        aplicar(campoIni.value, campoFim.value);
    });

    // Estado inicial: período da URL ou "30 dias".
    const url = new URLSearchParams(location.search);
    url.get('inicio') && url.get('fim')
        ? aplicar(url.get('inicio'), url.get('fim'))
        : document.querySelector('[data-dias="30"]').click();
</script>
@endpush
