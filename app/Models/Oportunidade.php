<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oportunidade extends Model
{
    use BelongsToTenant;

    /** etapa => [rótulo, cor do badge] */
    public const ETAPAS = [
        'prospeccao' => ['Prospecção', 'secondary'],
        'proposta' => ['Proposta', 'info'],
        'negociacao' => ['Negociação', 'warning'],
        'ganho' => ['Ganho', 'success'],
        'perdido' => ['Perdido', 'danger'],
    ];

    protected $fillable = ['contato_id', 'responsavel_id', 'titulo', 'valor', 'etapa'];

    protected $casts = ['valor' => 'decimal:2'];

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contato::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
