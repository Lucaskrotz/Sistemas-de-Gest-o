<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Isolamento por tenant_id:
 *  - global scope filtra toda consulta pelo tenant atual; sem tenant definido, não retorna nada (fail-closed);
 *  - ao criar, tenant_id é preenchido com o tenant atual (e não pode ser outro);
 *  - tenant_id nunca muda depois de criado.
 * NUNCA usar withoutGlobalScopes() fora de código de admin/seeder.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenant = Tenant::atual();
            $tenant
                ? $query->where($query->qualifyColumn('tenant_id'), $tenant->id)
                : $query->whereRaw('1 = 0');
        });

        static::creating(function (Model $model) {
            $tenant = Tenant::atual() ?? throw new LogicException('Nenhum tenant definido para criar '.class_basename($model).'.');
            if ($model->tenant_id !== null && $model->tenant_id !== $tenant->id) {
                throw new LogicException('Tentativa de criar registro em outro tenant.');
            }
            $model->tenant_id = $tenant->id;
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException('tenant_id não pode ser alterado.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
