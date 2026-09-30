@php
    [$rotulo, $cor] = \App\Models\Titulo::SITUACOES[$situacao];
@endphp
<x-badge :cor="$cor">{{ $rotulo }}</x-badge>
