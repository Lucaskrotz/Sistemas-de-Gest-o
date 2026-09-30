<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_itens';

    public $timestamps = false;

    protected $fillable = ['produto_id', 'quantidade', 'preco_unitario'];

    protected $casts = ['preco_unitario' => 'decimal:2'];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantidade * $this->preco_unitario;
    }
}
