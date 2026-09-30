<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chamado extends Model
{
    /** Colunas do Kanban, na ordem. */
    public const STATUS = [
        'aberto' => 'Aberto',
        'andamento' => 'Em andamento',
        'aguardando' => 'Aguardando cliente',
        'resolvido' => 'Resolvido',
    ];

    /** prioridade => [rótulo, cor do badge, horas de SLA] */
    public const PRIORIDADES = [
        'urgente' => ['Urgente', 'danger', 4],
        'alta' => ['Alta', 'warning', 8],
        'media' => ['Média', 'primary', 24],
        'baixa' => ['Baixa', 'secondary', 72],
    ];

    public const ATENDENTES = ['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Diego Rocha'];

    protected $fillable = ['cliente_id', 'titulo', 'descricao', 'prioridade', 'status', 'responsavel', 'prazo_sla', 'resolvido_em'];

    protected $casts = ['prazo_sla' => 'datetime', 'resolvido_em' => 'datetime'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(ChamadoHistorico::class)->latest('id');
    }

    public static function prazoPara(string $prioridade, ?CarbonInterface $inicio = null): CarbonInterface
    {
        return ($inicio ?? now())->copy()->addHours(self::PRIORIDADES[$prioridade][2]);
    }

    public function registrar(string $descricao, string $autor, string $tipo = 'evento'): void
    {
        $this->historicos()->create(compact('descricao', 'autor', 'tipo'));
    }

    public function mudarStatus(string $novo, string $autor): void
    {
        if ($novo === $this->status) {
            return;
        }
        $antigo = self::STATUS[$this->status];
        $this->update(['status' => $novo, 'resolvido_em' => $novo === 'resolvido' ? now() : null]);
        $this->registrar("Status alterado: {$antigo} → ".self::STATUS[$novo], $autor);
    }

    /** Situação do SLA: [texto, cor]. "Em risco" = menos de 25% da janela restante. */
    public function sla(): array
    {
        if ($this->resolvido_em) {
            return $this->resolvido_em->lte($this->prazo_sla) ? ['SLA cumprido', 'success'] : ['SLA violado', 'danger'];
        }
        $quanto = $this->prazo_sla->diffForHumans(['parts' => 1, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]);
        if ($this->prazo_sla->isPast()) {
            return ["Vencido há {$quanto}", 'danger'];
        }
        $janela = $this->created_at->diffInSeconds($this->prazo_sla);
        $restante = now()->diffInSeconds($this->prazo_sla);

        return ["Vence em {$quanto}", $restante < $janela * 0.25 ? 'warning' : 'secondary'];
    }

    public function getNumeroAttribute(): string
    {
        return '#'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}
