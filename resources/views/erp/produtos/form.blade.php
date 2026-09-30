@extends('layouts.app')

@section('header', $produto->exists ? 'Editar produto' : 'Novo produto')

@section('content')
    <x-card style="max-width: 820px">
        <form method="POST" action="{{ $produto->exists ? route('erp.produtos.update', $produto) : route('erp.produtos.store') }}">
            @csrf
            @if ($produto->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="sku" class="form-label">SKU</label>
                    <input id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $produto->sku) }}" required>
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label for="nome" class="form-label">Nome</label>
                    <input id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror" value="{{ old('nome', $produto->nome) }}" required autofocus>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoria</label>
                    <input id="categoria" name="categoria" class="form-control @error('categoria') is-invalid @enderror" value="{{ old('categoria', $produto->categoria) }}" required>
                    @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="preco" class="form-label">Preço (R$)</label>
                    <input id="preco" name="preco" type="number" step="0.01" min="0" inputmode="decimal" class="form-control @error('preco') is-invalid @enderror" value="{{ old('preco', $produto->preco) }}" required>
                    @error('preco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="estoque" class="form-label">Estoque</label>
                    <input id="estoque" name="estoque" type="number" min="0" class="form-control @error('estoque') is-invalid @enderror" value="{{ old('estoque', $produto->estoque ?? 0) }}" required>
                    @error('estoque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" @checked(old('ativo', $produto->ativo))>
                        <label class="form-check-label" for="ativo">Disponível para venda</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-destaque">Salvar</button>
                <a href="{{ route('erp.produtos.index') }}" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </x-card>
@endsection
