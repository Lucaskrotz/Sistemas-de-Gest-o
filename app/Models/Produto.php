<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    public const ESTOQUE_BAIXO = 10;

    protected $fillable = ['sku', 'nome', 'categoria', 'preco', 'estoque', 'ativo'];

    protected $casts = ['preco' => 'decimal:2', 'estoque' => 'integer', 'ativo' => 'boolean'];
}
