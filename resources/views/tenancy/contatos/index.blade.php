@extends('layouts.app')

@section('header', 'Contatos')
@section('actions')
    <button type="button" class="btn btn-destaque" data-bs-toggle="modal" data-bs-target="#novo-contato"><i data-lucide="plus"></i> Novo contato</button>
    @include('tenancy._empresa')
@endsection

@section('content')
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover w-100" id="tabela">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Empresa</th><th>Telefone</th><th class="text-end">Oportunidades</th></tr></thead>
                <tbody>
                @foreach ($contatos as $c)
                    <tr>
                        <td><a href="{{ route('tenancy.contatos.show', $c) }}" class="fw-medium text-decoration-none">{{ $c->nome }}</a></td>
                        <td class="text-secondary">{{ $c->email }}</td>
                        <td>{{ $c->empresa }}</td>
                        <td class="text-secondary text-nowrap">{{ $c->telefone }}</td>
                        <td class="text-end tabular">{{ $c->oportunidades_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <x-modal id="novo-contato" titulo="Novo contato">
        <form method="POST" action="{{ route('tenancy.contatos.store') }}" id="form-contato" class="d-flex flex-column gap-3 text-body">
            @csrf
            <div>
                <label for="nome" class="form-label">Nome</label>
                <input id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror" value="{{ old('nome') }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="email" class="form-label">E-mail</label>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-7">
                    <label for="empresa" class="form-label">Empresa</label>
                    <input id="empresa" name="empresa" class="form-control" value="{{ old('empresa') }}">
                </div>
                <div class="col-5">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input id="telefone" name="telefone" type="tel" class="form-control" value="{{ old('telefone') }}">
                </div>
            </div>
        </form>
        <x-slot:rodape>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="form-contato" class="btn btn-destaque">Salvar</button>
        </x-slot:rodape>
    </x-modal>
@endsection

@push('scripts')
<script>
    new DataTable('#tabela');
    @if ($errors->any()) bootstrap.Modal.getOrCreateInstance('#novo-contato').show(); @endif
</script>
@endpush
