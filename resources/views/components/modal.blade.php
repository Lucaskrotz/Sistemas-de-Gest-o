@props(['id', 'titulo', 'tamanho' => null])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $tamanho ? "modal-{$tamanho}" : '' }}">
        <div {{ $attributes->merge(['class' => 'modal-content']) }}>
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}-titulo">{{ $titulo }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">{{ $slot }}</div>
            @isset($rodape)
                <div class="modal-footer">{{ $rodape }}</div>
            @endisset
        </div>
    </div>
</div>
