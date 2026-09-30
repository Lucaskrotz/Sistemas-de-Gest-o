{{-- Seletor de empresa (topo). Trocar = logar como o usuário principal da outra empresa. --}}
@php($atual = \App\Models\Tenant::atual())
<div class="dropdown">
    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $atual->cor }}" aria-hidden="true"></span>
        {{ $atual->nome }} <i data-lucide="chevrons-up-down" class="text-secondary"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        <h6 class="dropdown-header">Empresas (tenants)</h6>
        @foreach (\App\Models\Tenant::orderBy('nome')->get() as $t)
            <form method="POST" action="{{ route('tenancy.trocar', $t) }}">
                @csrf
                <button type="submit" class="dropdown-item" @disabled($t->is($atual))>
                    <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $t->cor }}" aria-hidden="true"></span>
                    <span class="flex-grow-1">{{ $t->nome }}</span>
                    @if ($t->is($atual))<i data-lucide="check"></i>@endif
                </button>
            </form>
        @endforeach
    </div>
</div>
