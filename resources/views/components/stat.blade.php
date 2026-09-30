@props(['label', 'valor', 'detalhe' => null, 'icone' => null])

{{-- "Section card" do shadcn: descrição em muted, número grande tabular, rodapé opcional. --}}
<div {{ $attributes->merge(['class' => 'card h-100']) }}>
    <div class="card-body d-flex flex-column gap-1">
        <div class="d-flex align-items-center justify-content-between text-secondary">
            <span>{{ $label }}</span>
            @if ($icone)<i data-lucide="{{ $icone }}" aria-hidden="true"></i>@endif
        </div>
        <div class="fs-3 fw-semibold tabular">{{ $valor }}</div>
        @if ($detalhe)
            <div class="small text-secondary">{{ $detalhe }}</div>
        @endif
    </div>
</div>
