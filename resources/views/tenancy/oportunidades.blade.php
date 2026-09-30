@extends('layouts.app')

@section('header', 'Oportunidades')
@section('actions')
    <button type="button" class="btn btn-destaque" data-bs-toggle="modal" data-bs-target="#nova-oportunidade"><i data-lucide="plus"></i> Nova oportunidade</button>
    @include('tenancy._empresa')
@endsection

@push('styles')
<style>
    .pipeline { display: grid; grid-template-columns: repeat(5, minmax(220px, 1fr)); gap: 1rem; overflow-x: auto; padding-bottom: .5rem; }
    .etapa { background: var(--muted); border-radius: calc(var(--radius) + 4px); padding: .5rem; min-height: 50vh; }
    .oport { background: var(--background); border: 1px solid var(--border); border-radius: var(--radius); padding: .75rem; margin-bottom: .5rem; box-shadow: var(--shadow-xs); }
</style>
@endpush

@section('content')
    <div class="pipeline">
        @foreach (\App\Models\Oportunidade::ETAPAS as $etapa => [$rotulo, $cor])
            @php($lista = $colunas[$etapa] ?? collect())
            <section class="etapa" aria-label="{{ $rotulo }}">
                <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                    <h2 class="h6 mb-0 d-flex align-items-center gap-2"><x-badge :cor="$cor">{{ $rotulo }}</x-badge> <span class="text-secondary small">{{ $lista->count() }}</span></h2>
                    <span class="small text-secondary tabular">@brl($lista->sum('valor'))</span>
                </div>
                @foreach ($lista as $o)
                    <article class="oport">
                        <div class="fw-medium">{{ $o->titulo }}</div>
                        <a href="{{ route('tenancy.contatos.show', $o->contato) }}" class="small text-secondary text-decoration-none d-block text-truncate">{{ $o->contato->nome }}</a>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="fw-semibold tabular">@brl($o->valor)</span>
                            <span class="small text-secondary text-truncate ms-2">{{ $o->responsavel?->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('tenancy.oportunidades.update', $o) }}" class="mt-2">
                            @csrf @method('PATCH')
                            <label class="visually-hidden" for="etapa-{{ $o->id }}">Mover “{{ $o->titulo }}” para</label>
                            <select id="etapa-{{ $o->id }}" name="etapa" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                @foreach (\App\Models\Oportunidade::ETAPAS as $chave => [$r])
                                    <option value="{{ $chave }}" @selected($chave === $o->etapa)>{{ $r }}</option>
                                @endforeach
                            </select>
                        </form>
                    </article>
                @endforeach
            </section>
        @endforeach
    </div>

    <x-modal id="nova-oportunidade" titulo="Nova oportunidade">
        <form method="POST" action="{{ route('tenancy.oportunidades.store') }}" id="form-oportunidade" class="d-flex flex-column gap-3 text-body">
            @csrf
            <div>
                <label for="titulo" class="form-label">Título</label>
                <input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" required>
                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="contato_id" class="form-label">Contato</label>
                <select id="contato_id" name="contato_id" class="form-select @error('contato_id') is-invalid @enderror" required>
                    <option value="">Selecione…</option>
                    @foreach ($contatos as $c)
                        <option value="{{ $c->id }}" @selected(old('contato_id') == $c->id)>{{ $c->nome }}</option>
                    @endforeach
                </select>
                @error('contato_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label for="valor" class="form-label">Valor (R$)</label>
                    <input id="valor" name="valor" type="number" step="0.01" min="0" class="form-control @error('valor') is-invalid @enderror" value="{{ old('valor') }}" required>
                    @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label for="responsavel_id" class="form-label">Responsável</label>
                    <select id="responsavel_id" name="responsavel_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($membros as $m)
                            <option value="{{ $m->id }}" @selected(old('responsavel_id') == $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <x-slot:rodape>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="form-oportunidade" class="btn btn-destaque">Criar</button>
        </x-slot:rodape>
    </x-modal>
@endsection

@if ($errors->any())
    @push('scripts')<script>bootstrap.Modal.getOrCreateInstance('#nova-oportunidade').show();</script>@endpush
@endif
