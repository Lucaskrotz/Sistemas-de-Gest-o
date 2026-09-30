@extends('layouts.app')

@section('header', $cliente->exists ? 'Editar cliente' : 'Novo cliente')

@section('content')
    <x-card style="max-width: 820px">
        <form method="POST" action="{{ $cliente->exists ? route('erp.clientes.update', $cliente) : route('erp.clientes.store') }}">
            @csrf
            @if ($cliente->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="nome" class="form-label">Nome / Razão social</label>
                    <input id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror" value="{{ old('nome', $cliente->nome) }}" required autofocus>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="documento" class="form-label">CPF / CNPJ</label>
                    <input id="documento" name="documento" class="form-control @error('documento') is-invalid @enderror" value="{{ old('documento', $cliente->documento) }}" required>
                    @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label for="email" class="form-label">E-mail</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $cliente->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input id="telefone" name="telefone" type="tel" class="form-control" value="{{ old('telefone', $cliente->telefone) }}">
                </div>
                <div class="col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input id="cidade" name="cidade" class="form-control" value="{{ old('cidade', $cliente->cidade) }}">
                </div>
                <div class="col-md-4">
                    <label for="uf" class="form-label">UF</label>
                    <input id="uf" name="uf" maxlength="2" class="form-control text-uppercase @error('uf') is-invalid @enderror" value="{{ old('uf', $cliente->uf) }}">
                    @error('uf')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $cliente->ativo))>
                        <label class="form-check-label" for="ativo">Cliente ativo</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-destaque">Salvar</button>
                <a href="{{ route('erp.clientes.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </x-card>
@endsection
