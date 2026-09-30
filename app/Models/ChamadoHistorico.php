<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChamadoHistorico extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['tipo', 'autor', 'descricao', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
