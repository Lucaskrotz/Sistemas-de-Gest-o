@extends('layouts.app')

@section('header', 'Equipe')
@section('actions')
    @include('tenancy._empresa')
@endsection

@section('content')
    <x-card titulo="Usuários da empresa" descricao="Só usuários com o mesmo tenant_id aparecem aqui">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Usuário</th><th>E-mail</th><th class="text-end">Oportunidades</th><th class="text-end">Valor em carteira</th></tr></thead>
                <tbody>
                @foreach ($membros as $m)
                    <tr>
                        <td>
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="avatar">{{ collect(explode(' ', $m->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</span>
                                <span class="fw-medium">{{ $m->name }}</span>
                                @if ($m->is(auth()->user()))<x-badge cor="light">você</x-badge>@endif
                            </span>
                        </td>
                        <td class="text-secondary">{{ $m->email }}</td>
                        <td class="text-end tabular">{{ $porResponsavel[$m->id]->total ?? 0 }}</td>
                        <td class="text-end tabular">@brl($porResponsavel[$m->id]->valor ?? 0)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
