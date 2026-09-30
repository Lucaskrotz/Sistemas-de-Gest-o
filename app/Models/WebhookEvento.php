<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvento extends Model
{
    public const UPDATED_AT = null;

    /** status => cor do badge */
    public const STATUS = [
        'processado' => 'success',
        'ignorado' => 'secondary',
        'rejeitado' => 'danger',
        'erro' => 'warning',
    ];

    protected $fillable = ['titulo_id', 'evento_id', 'tipo', 'payload', 'assinatura', 'assinatura_valida', 'status', 'mensagem', 'created_at'];

    protected $casts = ['assinatura_valida' => 'boolean', 'created_at' => 'datetime'];

    public function titulo(): BelongsTo
    {
        return $this->belongsTo(Titulo::class);
    }
}
