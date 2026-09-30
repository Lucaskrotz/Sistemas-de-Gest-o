<?php

namespace Database\Seeders;

use App\Models\Chamado;
use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ChamadosSeeder extends Seeder
{
    private const ASSUNTOS = [
        ['Nota fiscal não foi emitida', 'O pedido foi faturado mas a NF-e não aparece no portal.'],
        ['Erro ao acessar o sistema', 'Ao tentar logar aparece "sessão expirada" mesmo após limpar o cache.'],
        ['Boleto com valor divergente', 'O boleto gerado está com valor diferente do pedido.'],
        ['Produto chegou com defeito', 'Monitor chegou com a tela trincada. Solicito troca.'],
        ['Atraso na entrega', 'Pedido consta como enviado há 10 dias e não chegou.'],
        ['Solicitação de segunda via', 'Preciso da segunda via da nota do último pedido.'],
        ['Integração com e-commerce parada', 'Os pedidos da loja virtual pararam de sincronizar desde ontem.'],
        ['Relatório de vendas não carrega', 'O relatório mensal fica carregando e não abre.'],
        ['Alterar endereço de entrega', 'Gostaria de alterar o endereço de entrega do pedido em aberto.'],
        ['Dúvida sobre garantia', 'Qual o prazo de garantia do notebook adquirido?'],
        ['Impressora não conecta na rede', 'A multifuncional comprada não aparece na rede Wi-Fi.'],
        ['Cancelamento de pedido', 'Solicito o cancelamento do pedido por compra duplicada.'],
        ['Licença de software não ativa', 'A chave de ativação enviada retorna "inválida".'],
        ['Cobrança em duplicidade', 'Fui cobrado duas vezes no cartão pelo mesmo pedido.'],
    ];

    public function run(): void
    {
        $clientes = Cliente::where('ativo', true)->pluck('id');

        foreach (range(1, 42) as $_) {
            [$titulo, $descricao] = fake()->randomElement(self::ASSUNTOS);
            $prioridade = fake()->randomElement(['urgente', 'alta', 'alta', 'media', 'media', 'media', 'baixa', 'baixa']);
            // Resolvidos espalhados em 12 dias; os em aberto são recentes (poucos vencidos, alguns em risco).
            $status = fake()->boolean(55) ? 'resolvido' : fake()->randomElement(['aberto', 'aberto', 'andamento', 'andamento', 'aguardando']);
            $abertoEm = now()->subMinutes($status === 'resolvido'
                ? fake()->numberBetween(60 * 5, 60 * 24 * 12)
                : fake()->numberBetween(10, 60 * 16));
            $prazo = Chamado::prazoPara($prioridade, $abertoEm);
            // ~80% resolvidos dentro do prazo
            $resolvidoEm = $status === 'resolvido'
                ? $abertoEm->copy()->addMinutes(fake()->numberBetween(20, (int) ($abertoEm->diffInMinutes($prazo) * (fake()->boolean(80) ? 0.9 : 1.6))))->min(now())
                : null;

            $chamado = Chamado::forceCreate([
                'cliente_id' => $clientes->random(),
                'titulo' => $titulo,
                'descricao' => $descricao,
                'prioridade' => $prioridade,
                'status' => $status,
                'responsavel' => $status === 'aberto' && fake()->boolean() ? null : fake()->randomElement(Chamado::ATENDENTES),
                'prazo_sla' => $prazo,
                'resolvido_em' => $resolvidoEm,
                'created_at' => $abertoEm,
                'updated_at' => $resolvidoEm ?? $abertoEm,
            ]);

            $historico = [['evento', 'Portal do cliente', 'Chamado aberto', $abertoEm]];
            if ($chamado->responsavel) {
                $historico[] = ['evento', 'Sistema', "Responsável: {$chamado->responsavel}", $abertoEm->copy()->addMinutes(5)];
            }
            if ($status !== 'aberto') {
                $quando = $abertoEm->copy()->addMinutes(15);
                $historico[] = ['comentario', $chamado->responsavel ?? 'Suporte', 'Recebemos sua solicitação e já estamos analisando.', $quando];
                $historico[] = ['evento', $chamado->responsavel ?? 'Suporte', 'Status alterado: Aberto → '.Chamado::STATUS[$status], $resolvidoEm ?? $quando->copy()->addMinutes(1)];
            }
            foreach ($historico as [$tipo, $autor, $texto, $em]) {
                $chamado->historicos()->create(['tipo' => $tipo, 'autor' => $autor, 'descricao' => $texto, 'created_at' => $em]);
            }
        }
    }
}
