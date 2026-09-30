@extends('layouts.app')

@section('header', 'Painel')
@section('actions')
    @include('tenancy._empresa')
@endsection

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4">
        <span class="logo" style="--destaque: {{ $tenant->cor }}; width: 2.75rem; height: 2.75rem"><i data-lucide="building"></i></span>
        <div>
            <h2 class="h4 mb-0">{{ $tenant->nome }}</h2>
            <div class="text-secondary">Plano {{ $tenant->plano }} · <code>tenant_id = {{ $tenant->id }}</code></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-stat label="Contatos" icone="contact" :valor="$contatos" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Oportunidades abertas" icone="handshake" :valor="$abertas" :detalhe="'R$ '.number_format($pipeline, 2, ',', '.').' em pipeline'" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Ganho" icone="trophy" :valor="'R$ '.number_format($ganho, 2, ',', '.')" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat label="Equipe" icone="users" :valor="$equipe" detalhe="usuários desta empresa" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <x-card titulo="Oportunidades recentes">
                <x-slot:acoes><a href="{{ route('tenancy.oportunidades.index') }}" class="small text-destaque text-decoration-none">Ver pipeline</a></x-slot:acoes>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Oportunidade</th><th>Contato</th><th>Etapa</th><th class="text-end">Valor</th></tr></thead>
                        <tbody>
                        @foreach ($recentes as $o)
                            <tr>
                                <td class="fw-medium">{{ $o->titulo }}</td>
                                <td><a href="{{ route('tenancy.contatos.show', $o->contato) }}" class="text-decoration-none">{{ $o->contato->nome }}</a></td>
                                <td><x-badge :cor="\App\Models\Oportunidade::ETAPAS[$o->etapa][1]">{{ \App\Models\Oportunidade::ETAPAS[$o->etapa][0] }}</x-badge></td>
                                <td class="text-end tabular">@brl($o->valor)</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card titulo="Isolamento em ação" descricao="Banco único — cada empresa só enxerga as próprias linhas">
                <div class="table-responsive">
                    <table class="table mb-3">
                        <thead><tr><th>Empresa</th><th class="text-end">Usuários</th><th class="text-end">Contatos</th><th class="text-end">Oport.</th></tr></thead>
                        <tbody>
                        @foreach ($auditoria as $linha)
                            @php($minha = $linha['tenant']->is($tenant))
                            <tr @class(['fw-semibold' => $minha])>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $linha['tenant']->cor }}" aria-hidden="true"></span>
                                        {{ $linha['tenant']->nome }}
                                    </span>
                                    @if ($minha)<x-badge cor="light" class="ms-1">você</x-badge>@endif
                                </td>
                                <td class="text-end tabular">{{ $linha['usuarios'] }}</td>
                                <td class="text-end tabular">{{ $linha['contatos'] }}</td>
                                <td class="text-end tabular">{{ $linha['oportunidades'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <ul class="small text-secondary ps-3 mb-0">
                    <li>Contagem acima = auditoria (admin), agregada direto no banco.</li>
                    <li>Todo o resto da tela passa pelo <code>BelongsToTenant</code>: global scope por <code>tenant_id</code>.</li>
                    <li>O contato <code>compras@cliente-comum.test</code> existe nas duas empresas — e-mail é único <em>por tenant</em>.</li>
                    <li>Troque de empresa no seletor do topo para ver o outro lado.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
