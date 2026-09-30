<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pagamento extends Model
{
    /** status => [rótulo, cor do badge] */
    public const STATUS = [
        'pendente' => ['Aguardando pagamento', 'warning'],
        'confirmado' => ['Confirmado', 'primary'],
        'recebido' => ['Recebido', 'success'],
        'recusado' => ['Recusado', 'danger'],
        'estornado' => ['Estornado', 'secondary'],
        'expirado' => ['Expirado', 'secondary'],
    ];

    /** evento => [descrição, cor, ícone Lucide] */
    public const EVENTOS = [
        'PAYMENT_CREATED' => ['Cobrança criada', 'secondary', 'circle-plus'],
        'PAYMENT_CONFIRMED' => ['Pagamento confirmado (autorizado)', 'primary', 'badge-check'],
        'PAYMENT_RECEIVED' => ['Valor recebido / liquidado', 'success', 'banknote'],
        'PAYMENT_REFUSED' => ['Pagamento recusado', 'danger', 'circle-x'],
        'PAYMENT_REFUNDED' => ['Pagamento estornado', 'warning', 'undo-2'],
        'PAYMENT_OVERDUE' => ['Cobrança expirada', 'secondary', 'timer-off'],
    ];

    public const METODOS = ['pix' => 'Pix', 'cartao' => 'Cartão de crédito'];

    protected $fillable = ['codigo', 'idempotency_key', 'cliente_id', 'descricao', 'valor', 'taxa', 'valor_liquido', 'metodo',
        'status', 'bandeira', 'cartao_final', 'parcelas', 'pix_copia_cola', 'expira_em'];

    protected $casts = ['valor' => 'decimal:2', 'taxa' => 'decimal:2', 'valor_liquido' => 'decimal:2', 'expira_em' => 'datetime'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(PagamentoEvento::class)->orderBy('id');
    }

    public function getMeioAttribute(): string
    {
        return $this->metodo === 'cartao'
            ? "{$this->bandeira} •••• {$this->cartao_final}".($this->parcelas > 1 ? " · {$this->parcelas}x" : '')
            : 'Pix';
    }
}
