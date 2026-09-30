@extends('layouts.app')

@section('header', $contato->nome)
@section('actions')
    <a href="{{ route('tenancy.contatos.index') }}" class="btn btn-outline-secondary"><i data-lucide="arrow-left"></i> Contatos</a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-4">
            <x-card titulo="Contato">
                <dl class="small mb-0">
                    <dt class="text-secondary fw-medium">E-mail</dt><dd>{{ $contato->email }}</dd>
                    <dt class="text-secondary fw-medium">Empresa</dt><dd>{{ $contato->empresa ?? '—' }}</dd>
                    <dt class="text-secondary fw-medium">Telefone</dt><dd>{{ $contato->telefone ?? '—' }}</dd>
                    <dt class="text-secondary fw-medium">tenant_id</dt><dd class="mb-0"><code>{{ $contato->tenant_id }}</code></dd>
                </dl>
                <hr>
                <button type="button" class="btn btn-sm btn-outline-secondary text-danger" data-bs-toggle="modal" data-bs-target="#excluir-contato">
                    <i data-lucide="trash-2"></i> Excluir contato</button>
            </x-card>
        </div>
        <div class="col-xl-8">
            <x-card titulo="Oportunidades">
                @if ($contato->oportunidades->isEmpty())
                    <p class="text-secondary mb-0">Nenhuma oportunidade com este contato.</p>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Título</th><th>Responsável</th><th>Etapa</th><th class="text-end">Valor</th></tr></thead>
                            <tbody>
                            @foreach ($contato->oportunidades as $o)
                                <tr>
                                    <td class="fw-medium">{{ $o->titulo }}</td>
                                    <td class="text-secondary">{{ $o->responsavel?->name ?? '—' }}</td>
                                    <td><x-badge :cor="\App\Models\Oportunidade::ETAPAS[$o->etapa][1]">{{ \App\Models\Oportunidade::ETAPAS[$o->etapa][0] }}</x-badge></td>
                                    <td class="text-end tabular">@brl($o->valor)</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <x-modal id="excluir-contato" titulo="Excluir contato">
        Excluir <strong>{{ $contato->nome }}</strong> e as {{ $contato->oportunidades->count() }} oportunidade(s) dele?
        <x-slot:rodape>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <form method="POST" action="{{ route('tenancy.contatos.destroy', $contato) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
            </form>
        </x-slot:rodape>
    </x-modal>
@endsection
