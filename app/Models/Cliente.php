<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'documento', 'email', 'telefone', 'cidade', 'uf', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}
