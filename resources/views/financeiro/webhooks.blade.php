@extends('layouts.app')

@section('header', 'Webhooks recebidos')

@section('content')
    <div class="row g-4">
        <div class="col-xl-8">
            <x-card titulo="Log de eventos" descricao="Últimos {{ $eventos->count() }} eventos, do mais recente ao mais antigo">
                @forelse ($eventos as $evento)
                    @include('financeiro._evento', ['mostrarTitulo' => true])
                @empty
                    <p class="text-secondary mb-0">Nenhum webhook recebido.</p>
                @endforelse
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card titulo="Endpoint" descricao="Público, sem sessão — autenticado pela assinatura">
                <div class="small text-secondary mb-1">URL</div>
                <code class="d-block mb-3 text-break">POST {{ route('financeiro.webhook') }}</code>
                <div class="small text-secondary mb-1">Exemplo (assinatura inválida → 401)</div>
<pre class="small bg-body-tertiary border rounded-2 p-3 mb-3" style="white-space: pre-wrap; word-break: break-all">curl -X POST {{ route('financeiro.webhook') }} \
  -H 'Content-Type: application/json' \
  -H 'X-Assinatura: sha256=abc' \
  -d '{"id":"evt_1","tipo":"cobranca.paga","dados":{"cobranca_id":"COB-X"}}'</pre>
                <ul class="small text-secondary ps-3 mb-0">
                    <li><code>X-Assinatura</code> = <code>sha256=</code> + HMAC-SHA256 do corpo bruto.</li>
                    <li>Mesmo <code>id</code> já processado → <strong>ignorado</strong> (200).</li>
                    <li>Assinatura inválida → <strong>rejeitado</strong> (401).</li>
                    <li>Cobrança inexistente → <strong>erro</strong> (422).</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
