<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedido extends Model
{
    /** status => cor do badge (Bootstrap) */
    public const STATUS = [
        'pendente' => 'warning',
        'pago' => 'primary',
        'enviado' => 'info',
        'entregue' => 'success',
        'cancelado' => 'secondary',
    ];

    protected $fillable = ['cliente_id', 'status', 'total', 'observacao'];

    protected $casts = ['total' => 'decimal:2'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function titulo(): HasOne
    {
        return $this->hasOne(Titulo::class);
    }

    public function getNumeroAttribute(): string
    {
        return '#'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}
