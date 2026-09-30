{{-- Uma linha do log de webhook: status, mensagem, payload recolhível e reenvio. --}}
<div class="d-flex flex-column gap-2 py-3 {{ $loop->last ? '' : 'border-bottom' }}">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <x-badge :cor="\App\Models\WebhookEvento::STATUS[$evento->status]">{{ ucfirst($evento->status) }}</x-badge>
        <code class="small">{{ $evento->tipo }}</code>
        <span class="small text-secondary d-inline-flex align-items-center gap-1">
            <i data-lucide="{{ $evento->assinatura_valida ? 'shield-check' : 'shield-x' }}"></i>
            {{ $evento->assinatura_valida ? 'assinatura válida' : 'assinatura inválida' }}
        </span>
        <span class="small text-secondary ms-auto" title="{{ $evento->created_at->format('d/m/Y H:i:s') }}">{{ $evento->created_at->diffForHumans() }}</span>
    </div>
    <div>{{ $evento->mensagem }}
        @if (($mostrarTitulo ?? false) && $evento->titulo)
            · <a href="{{ route('financeiro.titulos.show', $evento->titulo) }}" class="text-destaque text-decoration-none">{{ $evento->titulo->numero }}</a>
        @endif
    </div>
    <details>
        <summary class="small text-secondary">Payload e cabeçalho</summary>
        <pre class="small bg-body-tertiary border rounded-2 p-3 mt-2 mb-2" style="white-space: pre-wrap; word-break: break-all">X-Assinatura: {{ $evento->assinatura ?? '(ausente)' }}

{{ json_encode(json_decode($evento->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $evento->payload }}</pre>
        <form method="POST" action="{{ route('financeiro.webhooks.reenviar', $evento) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i data-lucide="rotate-cw"></i> Reenviar este webhook</button>
        </form>
    </details>
</div>
