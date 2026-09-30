<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoEvento extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['tipo', 'dados'];

    protected $casts = ['dados' => 'array', 'created_at' => 'datetime'];

    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(Pagamento::class);
    }
}
