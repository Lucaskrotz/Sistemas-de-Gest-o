@php
    // Módulo atual pelo nome da rota ("erp.index" → config('modulos.erp')); null na home.
    $chave = strtok(request()->route()?->getName() ?? '', '.');
    $modulo = config("modulos.{$chave}");
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $modulo['nome'] ?? config('app.name')) · Portfólio</title>

    {{-- Tema antes de renderizar (sem "piscar" branco): escolha salva ou preferência do sistema. --}}
    <script>
        (() => {
            let tema = null;
            try { tema = localStorage.getItem('tema'); } catch (e) {}
            const escuro = tema === 'dark' || (tema !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    {{-- Design system shadcn/ui sobre Bootstrap --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>:root { --destaque: {{ $modulo['cor'] ?? '#171717' }}; }</style>
    @stack('styles')
</head>
<body>
<div class="d-flex min-vh-100">
    @if ($modulo)
        <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menu do módulo">
            <div class="d-flex flex-column h-100 p-2">
                <div class="d-flex align-items-center gap-2 p-2 mb-2">
                    <span class="logo"><i data-lucide="{{ $modulo['icone'] }}"></i></span>
                    <div class="lh-sm">
                        <div class="fw-semibold">{{ $modulo['nome'] }}</div>
                        <div class="small text-secondary">Portfólio · demo</div>
                    </div>
                </div>

                <div class="grupo">Menu</div>
                <nav class="nav flex-column gap-1">
                    @foreach ($modulo['menu'] as $item)
                        @continue(! Route::has($item['rota']))
                        @php($ativo = request()->routeIs(...(array) ($item['ativo'] ?? $item['rota'])))
                        <a href="{{ route($item['rota']) }}" @class(['nav-link', 'active' => $ativo]) @if ($ativo) aria-current="page" @endif>
                            @isset($item['icone'])<i data-lucide="{{ $item['icone'] }}"></i>@endisset
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="mt-auto">
                    <a href="{{ route('home') }}" class="nav-link mb-2"><i data-lucide="arrow-left"></i> Todos os projetos</a>
                    @auth
                        <div class="d-flex align-items-center gap-2 p-2 border-top pt-3">
                            <span class="avatar">{{ collect(explode(' ', auth()->user()->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</span>
                            <div class="lh-sm flex-grow-1 text-truncate">
                                <div class="fw-medium text-truncate">{{ auth()->user()->name }}</div>
                                <div class="small text-secondary text-truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm btn-icone" aria-label="Sair" title="Sair"><i data-lucide="log-out"></i></button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </aside>
    @endif

    <main class="flex-grow-1" style="min-width: 0">
        @if (config('app.snapshot'))
            {{-- Versão estática (GitHub Pages): somente leitura --}}
            <div class="faixa-estatica px-3 px-lg-4 py-2 d-flex flex-wrap align-items-center gap-2 small">
                <i data-lucide="eye"></i>
                <span><strong>Versão estática</strong> · somente leitura, dados fictícios de {{ now()->format('d/m/Y') }}.</span>
                @if (config('app.url_interativa'))
                    <a href="{{ rtrim(config('app.url_interativa'), '/') }}/{{ $chave ? 'demo/'.$chave : '' }}" class="ms-auto fw-medium">
                        Abrir versão interativa <i data-lucide="arrow-up-right"></i></a>
                @endif
            </div>
        @endif
        <header class="topo px-3 px-lg-4 d-flex align-items-center gap-2">
            @if ($modulo)
                <button class="btn btn-ghost btn-sm btn-icone d-lg-none" data-bs-toggle="offcanvas"
                        data-bs-target="#sidebar" aria-label="Abrir menu"><i data-lucide="panel-left"></i></button>
                <span class="separador d-lg-none"></span>
            @endif
            <h1>@yield('header', $modulo['nome'] ?? config('app.name'))</h1>
            <div class="ms-auto d-flex gap-2">
                @yield('actions')
                {{-- Mode toggle (padrão shadcn): Claro / Escuro / Sistema --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-icone" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Alternar tema" title="Tema">
                        <i data-lucide="sun" class="icone-claro"></i><i data-lucide="moon" class="icone-escuro"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width: 9rem">
                        <button type="button" class="dropdown-item" data-tema="light"><i data-lucide="sun"></i> Claro</button>
                        <button type="button" class="dropdown-item" data-tema="dark"><i data-lucide="moon"></i> Escuro</button>
                        <button type="button" class="dropdown-item" data-tema="system"><i data-lucide="monitor"></i> Sistema</button>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-3 p-lg-4">
            @foreach (['success' => ['success', 'circle-check'], 'error' => ['danger', 'circle-alert']] as $flash => [$cor, $icone])
                @if (session($flash))
                    <div class="alert alert-{{ $cor }} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <i data-lucide="{{ $icone }}"></i> {{ session($flash) }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif
            @endforeach

            @if ($errors->any())
                <div class="alert alert-danger d-flex gap-2" role="alert">
                    <i data-lucide="circle-alert" class="mt-1"></i>
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@1.49.0/dist/umd/lucide.min.js"></script>
<script>
    // Antes das páginas: ícones viram SVG enquanto todas as linhas ainda estão no DOM (antes do DataTables paginar).
    lucide.createIcons();

    $.extend(true, DataTable.defaults, {
        language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/pt-BR.json' },
        pageLength: 10,
    });

    // ---- Tema claro/escuro ----
    window.cor = (token) => getComputedStyle(document.documentElement).getPropertyValue(token).trim();
    const midiaEscura = matchMedia('(prefers-color-scheme: dark)');
    const temaSalvo = () => { try { return localStorage.getItem('tema') || 'system'; } catch (e) { return 'system'; } };

    function aplicarTema(tema) {
        const escuro = tema === 'dark' || (tema === 'system' && midiaEscura.matches);
        document.documentElement.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');
        document.querySelectorAll('[data-tema]').forEach((b) => b.classList.toggle('active', b.dataset.tema === tema));
        temaGraficos();
    }
    document.querySelectorAll('[data-tema]').forEach((b) => b.addEventListener('click', () => {
        try { localStorage.setItem('tema', b.dataset.tema); } catch (e) {}
        aplicarTema(b.dataset.tema);
    }));
    midiaEscura.addEventListener('change', () => temaSalvo() === 'system' && aplicarTema('system'));

    // Gráficos no estilo shadcn/ui charts: grade horizontal discreta, sem linhas de eixo, tooltip em card.
    // Cores vêm dos tokens CSS (--chart-*), então acompanham o tema.
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.font.family = "'Geist', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Object.assign(Chart.defaults.plugins.tooltip, {
        borderWidth: 1, padding: 10, cornerRadius: 8, boxPadding: 4, usePointStyle: true, titleFont: { weight: 600 },
    });
    Object.assign(Chart.defaults.plugins.legend.labels, { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, boxHeight: 8 });
    Chart.defaults.scale.grid.drawTicks = false;
    Chart.defaults.scale.border.display = false;
    Chart.defaults.scale.ticks.padding = 8;

    function temaGraficos() {
        Chart.defaults.color = cor('--chart-texto');
        Chart.defaults.borderColor = cor('--chart-grid');
        Object.assign(Chart.defaults.plugins.tooltip, {
            backgroundColor: cor('--chart-tooltip'), titleColor: cor('--foreground'), bodyColor: cor('--foreground'),
            footerColor: cor('--muted-foreground'), borderColor: cor('--border'),
        });
        Chart.defaults.plugins.legend.labels.color = cor('--chart-rotulo');
        Object.values(Chart.instances).forEach((g) => g.update('none'));
    }
    aplicarTema(temaSalvo());

    window.SNAPSHOT = @json(config('app.snapshot'));
    if (SNAPSHOT) {
        // Versão estática: nenhum formulário é enviado; avisa e aponta para a versão interativa.
        document.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();
            const div = document.createElement('div');
            div.className = 'toast align-items-center show position-fixed bottom-0 end-0 m-3';
            div.setAttribute('role', 'status');
            div.style.zIndex = 2000;
            div.innerHTML = `<div class="toast-body d-flex gap-2 align-items-start">
                <span>Esta é a versão estática (somente leitura). Ações como salvar, mover e simular funcionam na versão interativa.</span>
                @if (config('app.url_interativa'))<a class="fw-medium text-nowrap" href="{{ rtrim(config('app.url_interativa'), '/') }}/{{ $chave ? 'demo/'.$chave : '' }}">Abrir</a>@endif
            </div>`;
            document.body.append(div);
            setTimeout(() => div.remove(), 6000);
        }, true);
    }

    // Feedback de envio: desabilita o botão e mostra spinner (evita duplo clique).
    document.addEventListener('submit', (e) => {
        const btn = e.target.querySelector('button[type=submit], button:not([type])');
        if (!btn || e.defaultPrevented) return;
        btn.disabled = true;
        btn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
    });

    // fetch com CSRF + JSON; lança erro com a mensagem do Laravel em respostas != 2xx.
    window.api = async (url, { method = 'GET', body } = {}) => {
        if (SNAPSHOT && method !== 'GET') throw new Error('indisponível na versão estática');
        const res = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message ?? `Erro ${res.status}`);
        return data;
    };
</script>
@stack('scripts')
</body>
</html>
