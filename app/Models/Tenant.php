<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['nome', 'slug', 'cor', 'plano'];

    /** Tenant da requisição atual (definido pelo middleware IdentificarTenant). */
    public static function atual(): ?self
    {
        return app()->bound('tenant.atual') ? app('tenant.atual') : null;
    }

    public static function definir(?self $tenant): void
    {
        app()->instance('tenant.atual', $tenant);
    }

    /** Executa $callback "dentro" de um tenant (seeders e testes). */
    public static function comoTenant(self $tenant, callable $callback): mixed
    {
        $anterior = self::atual();
        self::definir($tenant);
        try {
            return $callback();
        } finally {
            self::definir($anterior);
        }
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
