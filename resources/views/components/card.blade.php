@props(['titulo' => null, 'descricao' => null])

<div {{ $attributes->merge(['class' => 'card mb-4']) }}>
    @if ($titulo || isset($acoes))
        <div class="card-header d-flex align-items-start gap-3">
            <div>
                <h2 class="card-title">{{ $titulo }}</h2>
                @if ($descricao)<p class="card-description">{{ $descricao }}</p>@endif
            </div>
            <div class="ms-auto">{{ $acoes ?? '' }}</div>
        </div>
    @endif
    <div class="card-body">{{ $slot }}</div>
</div>
