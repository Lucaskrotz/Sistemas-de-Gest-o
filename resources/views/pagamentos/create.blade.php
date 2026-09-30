@extends('layouts.app')

@section('header', 'Nova cobrança')

@section('content')
    <form method="POST" action="{{ route('pagamentos.store') }}" id="checkout" novalidate>
        @csrf
        {{-- Idempotency key: reenviar este mesmo formulário não cria uma segunda cobrança. --}}
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $chave) }}">
        <input type="hidden" name="metodo" id="metodo" value="{{ old('metodo', 'cartao') }}">

        <div class="row g-4">
            <div class="col-xl-7">
                <x-card titulo="Cobrança">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select id="cliente_id" name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                                <option value="">Selecione…</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                                @endforeach
                            </select>
                            @error('cliente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="valor" class="form-label">Valor (R$)</label>
                            <input id="valor" name="valor" type="number" step="0.01" min="1" max="100000" inputmode="decimal"
                                   class="form-control @error('valor') is-invalid @enderror" value="{{ old('valor', '299.00') }}" required>
                            @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input id="descricao" name="descricao" class="form-control @error('descricao') is-invalid @enderror" value="{{ old('descricao', 'Assinatura Plano Pro (mensal)') }}" required>
                            @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-card>

                <x-card titulo="Forma de pagamento">
                    <div class="tabs mb-4" role="group" aria-label="Forma de pagamento">
                        <button type="button" data-metodo="cartao" aria-pressed="false"><i data-lucide="credit-card"></i> Cartão</button>
                        <button type="button" data-metodo="pix" aria-pressed="false"><i data-lucide="qr-code"></i> Pix</button>
                    </div>

                    <fieldset id="campos-cartao" class="row g-3">
                        <div class="col-12">
                            <label for="numero" class="form-label">Número do cartão</label>
                            <div class="position-relative">
                                <input id="numero" name="numero" class="form-control font-monospace @error('numero') is-invalid @enderror"
                                       inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="0000 0000 0000 0000">
                                <span id="bandeira" class="position-absolute top-50 end-0 translate-middle-y me-3 small text-secondary"></span>
                            </div>
                            @error('numero')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="nome" class="form-label">Nome impresso</label>
                            <input id="nome" name="nome" class="form-control text-uppercase @error('nome') is-invalid @enderror" autocomplete="cc-name" value="{{ old('nome') }}">
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4">
                            <label for="validade" class="form-label">Validade</label>
                            <input id="validade" name="validade" class="form-control @error('validade') is-invalid @enderror" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/AA" maxlength="5" value="{{ old('validade') }}">
                            @error('validade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input id="cvv" name="cvv" class="form-control @error('cvv') is-invalid @enderror" inputmode="numeric" autocomplete="cc-csc" maxlength="4">
                            @error('cvv')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-5">
                            <label for="parcelas" class="form-label">Parcelas</label>
                            <select id="parcelas" name="parcelas" class="form-select"></select>
                        </div>
                    </fieldset>

                    <div id="campos-pix" class="text-secondary d-none">
                        <p class="mb-0 d-flex gap-2"><i data-lucide="info" class="mt-1"></i>
                            Será gerado um Pix copia-e-cola válido por {{ \App\Services\Pagamentos\GatewayFake::PIX_VALIDADE_MIN }} minutos.
                            O pagamento é simulado na tela da transação.</p>
                    </div>
                </x-card>
            </div>

            <div class="col-xl-5">
                <x-card titulo="Resumo">
                    <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Valor</span><span class="tabular" id="r-valor">—</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Taxa do gateway (<span id="r-pct"></span>)</span><span class="tabular" id="r-taxa">—</span></div>
                    <div class="d-flex justify-content-between border-top pt-2 mb-4"><span class="fw-medium">Você recebe</span><span class="fw-semibold tabular" id="r-liquido">—</span></div>
                    <button type="submit" class="btn btn-destaque w-100"><i data-lucide="lock"></i> <span id="r-botao">Pagar</span></button>
                </x-card>

                <x-card titulo="Cartões de teste" descricao="Qualquer nome, validade futura e CVV">
                    <table class="table table-sm mb-0">
                        <tbody>
                        @foreach (\App\Services\Pagamentos\GatewayFake::CARTOES_TESTE as $numero => $motivo)
                            <tr>
                                <td><button type="button" class="btn btn-ghost btn-sm font-monospace px-1" data-cartao="{{ $numero }}" title="Preencher">{{ trim(chunk_split($numero, 4, ' ')) }}</button></td>
                                <td class="text-end">@if ($motivo)<x-badge cor="danger">{{ rtrim($motivo, '.') }}</x-badge>@else<x-badge cor="success">Aprovado</x-badge>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const TAXAS = @json(\App\Services\Pagamentos\GatewayFake::TAXAS);
    const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const $ = (id) => document.getElementById(id);
    const campoMetodo = $('metodo'), numero = $('numero'), valor = $('valor'), parcelas = $('parcelas');

    function bandeira(n) {
        if (/^4/.test(n)) return 'Visa';
        if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
        if (/^3[47]/.test(n)) return 'Amex';
        return '';
    }

    function resumo() {
        const v = parseFloat(valor.value) || 0, pct = TAXAS[campoMetodo.value], taxa = Math.round(v * pct * 100) / 100;
        $('r-valor').textContent = brl(v);
        $('r-pct').textContent = `${(pct * 100).toLocaleString('pt-BR')}%`;
        $('r-taxa').textContent = `− ${brl(taxa)}`;
        $('r-liquido').textContent = brl(v - taxa);
        $('r-botao').textContent = campoMetodo.value === 'pix' ? 'Gerar Pix' : `Pagar ${brl(v)}`;

        const escolhida = parcelas.value || '{{ old('parcelas', 1) }}';
        parcelas.innerHTML = [...Array(12)].map((_, i) => `<option value="${i + 1}">${i + 1}x de ${brl(v / (i + 1))}</option>`).join('');
        parcelas.value = escolhida;
    }

    function trocarMetodo(m) {
        campoMetodo.value = m;
        document.querySelectorAll('[data-metodo]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.metodo === m));
        $('campos-cartao').classList.toggle('d-none', m !== 'cartao');
        $('campos-cartao').disabled = m !== 'cartao'; // campos de cartão não são enviados no Pix
        $('campos-pix').classList.toggle('d-none', m !== 'pix');
        resumo();
    }

    // Máscaras: número em grupos de 4, validade MM/AA.
    numero.addEventListener('input', () => {
        const d = numero.value.replace(/\D/g, '').slice(0, 19);
        numero.value = d.replace(/(.{4})/g, '$1 ').trim();
        $('bandeira').textContent = bandeira(d);
    });
    $('validade').addEventListener('input', (e) => {
        const d = e.target.value.replace(/\D/g, '').slice(0, 4);
        e.target.value = d.length > 2 ? `${d.slice(0, 2)}/${d.slice(2)}` : d;
    });
    $('cvv').addEventListener('input', (e) => e.target.value = e.target.value.replace(/\D/g, ''));
    valor.addEventListener('input', resumo);
    document.querySelectorAll('[data-metodo]').forEach((b) => b.addEventListener('click', () => trocarMetodo(b.dataset.metodo)));

    // Preenche um cartão de teste.
    document.querySelectorAll('[data-cartao]').forEach((b) => b.addEventListener('click', () => {
        trocarMetodo('cartao');
        numero.value = b.dataset.cartao; numero.dispatchEvent(new Event('input'));
        $('nome').value ||= 'CLIENTE DEMO';
        const d = new Date(); $('validade').value ||= `12/${String(d.getFullYear() + 3).slice(2)}`;
        $('cvv').value ||= '123';
    }));

    trocarMetodo(campoMetodo.value);
</script>
@endpush
