@extends('layouts.app')

@section('header', 'Novo chamado')

@section('content')
    <x-card style="max-width: 820px">
        <form method="POST" action="{{ route('chamados.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="cliente_id" class="form-label">Cliente</label>
                    <select id="cliente_id" name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                        <option value="">Selecione…</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="prioridade" class="form-label">Prioridade</label>
                    <select id="prioridade" name="prioridade" class="form-select @error('prioridade') is-invalid @enderror" required>
                        @foreach (\App\Models\Chamado::PRIORIDADES as $chave => [$rotulo, , $horas])
                            <option value="{{ $chave }}" @selected(old('prioridade', 'media') === $chave)>{{ $rotulo }} — SLA {{ $horas }}h</option>
                        @endforeach
                    </select>
                    @error('prioridade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="titulo" class="form-label">Assunto</label>
                    <input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" required>
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="5" class="form-control @error('descricao') is-invalid @enderror" required>{{ old('descricao') }}</textarea>
                    @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="responsavel" class="form-label">Responsável</label>
                    <select id="responsavel" name="responsavel" class="form-select">
                        <option value="">Sem responsável</option>
                        @foreach (\App\Models\Chamado::ATENDENTES as $atendente)
                            <option @selected(old('responsavel') === $atendente)>{{ $atendente }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-destaque">Abrir chamado</button>
                <a href="{{ route('chamados.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </x-card>
@endsection
