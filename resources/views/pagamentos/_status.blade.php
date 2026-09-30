@php
    [$rotulo, $cor] = \App\Models\Pagamento::STATUS[$status];
@endphp
<x-badge :cor="$cor">{{ $rotulo }}</x-badge>
