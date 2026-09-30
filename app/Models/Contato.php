<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contato extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['nome', 'email', 'empresa', 'telefone']; // tenant_id fora de propósito

    public function oportunidades(): HasMany
    {
        return $this->hasMany(Oportunidade::class);
    }
}
