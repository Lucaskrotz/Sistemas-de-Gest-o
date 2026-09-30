{{-- Modal de exclusão único por página; botões com data-excluir="{url}" data-nome="{nome}". --}}
<x-modal id="modal-excluir" titulo="Confirmar exclusão">
    Excluir <strong id="excluir-nome"></strong>? Esta ação não pode ser desfeita.
    <x-slot:rodape>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <form method="POST" id="form-excluir">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Excluir</button>
        </form>
    </x-slot:rodape>
</x-modal>

@push('scripts')
<script>
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-excluir]');
        if (!btn) return;
        document.getElementById('form-excluir').action = btn.dataset.excluir;
        document.getElementById('excluir-nome').textContent = btn.dataset.nome;
        bootstrap.Modal.getOrCreateInstance('#modal-excluir').show();
    });
</script>
@endpush
