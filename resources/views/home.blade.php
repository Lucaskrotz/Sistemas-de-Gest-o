@extends('layouts.app')

@section('title', 'Projetos')
@section('header', 'Portfólio · Sistemas de gestão')

@section('content')
    <div class="mb-4" style="max-width: 640px">
        <h2 class="h3 mb-2">Seis sistemas, um só app Laravel</h2>
        <p class="text-secondary mb-0">
            Cada demo abre já logada com um usuário fictício. Os dados são gerados automaticamente e resetados todo dia.
        </p>
    </div>

    <div class="row g-3">
        @foreach (config('modulos') as $chave => $modulo)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100" style="--destaque: {{ $modulo['cor'] }}">
                    <div class="card-body d-flex flex-column gap-3">
                        <span class="logo"><i data-lucide="{{ $modulo['icone'] }}"></i></span>
                        <div class="flex-grow-1">
                            <h3 class="card-title mb-2">{{ $modulo['nome'] }}</h3>
                            <p class="card-description mt-0">{{ $modulo['descricao'] }}</p>
                        </div>
                        <a href="{{ route('demo', $chave) }}" class="btn btn-outline-secondary align-self-start">
                            Abrir demo <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
