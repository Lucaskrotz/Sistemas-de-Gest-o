<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Titulo extends Model
{
    /** situação exibida => [rótulo, cor do badge]. "vencido" é derivado (aberto + vencimento passado). */
    public const SITUACOES = [
        'aberto' => ['Em aberto', 'primary'],
        'vencido' => ['Vencido', 'danger'],
        'pago' => ['Pago', 'success'],
        'cancelado' => ['Cancelado', 'secondary'],
    ];

    protected $attributes = ['status' => 'aberto'];

    protected $fillable = ['pedido_id', 'cliente_id', 'descricao', 'valor', 'vencimento', 'status',
        'codigo_cobranca', 'linha_digitavel', 'pago_em', 'valor_pago'];

    protected $casts = ['valor' => 'decimal:2', 'valor_pago' => 'decimal:2', 'vencimento' => 'date', 'pago_em' => 'datetime'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(WebhookEvento::class)->latest('id');
    }

    public function getSituacaoAttribute(): string
    {
        return $this->status === 'aberto' && $this->vencimento->isBefore(today()) ? 'vencido' : $this->status;
    }

    public function getNumeroAttribute(): string
    {
        return 'TIT-'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}
