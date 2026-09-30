<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class ErpSeeder extends Seeder
{
    private const PRODUTOS = [
        ['Notebook Pro 14"', 'Informática', 6499.90],
        ['Notebook Essencial 15"', 'Informática', 3299.00],
        ['Monitor 27" QHD', 'Informática', 1899.90],
        ['Monitor 24" Full HD', 'Informática', 899.00],
        ['Desktop Workstation i7', 'Informática', 7890.00],
        ['SSD NVMe 1TB', 'Componentes', 459.90],
        ['Memória DDR5 16GB', 'Componentes', 389.00],
        ['Placa de Vídeo RTX', 'Componentes', 3499.00],
        ['Teclado Mecânico ABNT2', 'Periféricos', 349.90],
        ['Mouse Sem Fio Ergonômico', 'Periféricos', 189.90],
        ['Headset com Microfone', 'Periféricos', 279.00],
        ['Webcam Full HD', 'Periféricos', 249.90],
        ['Dock USB-C 8 em 1', 'Periféricos', 399.00],
        ['Roteador Wi-Fi 6', 'Redes', 699.00],
        ['Switch Gigabit 16 portas', 'Redes', 1190.00],
        ['Cabo de Rede Cat6 (caixa 305m)', 'Redes', 899.90],
        ['Nobreak 1500VA', 'Energia', 1349.00],
        ['Estabilizador 1000VA', 'Energia', 329.90],
        ['Impressora Multifuncional Laser', 'Escritório', 1599.00],
        ['Cadeira Ergonômica', 'Escritório', 1290.00],
        ['Mesa Regulável Elétrica', 'Escritório', 2490.00],
        ['Licença Office 365 (anual)', 'Software', 499.00],
        ['Antivírus Corporativo (10 usuários)', 'Software', 890.00],
        ['Suporte de Monitor Articulado', 'Escritório', 219.90],
    ];

    public function run(): void
    {
        $clientes = Cliente::factory(45)->create();

        $produtos = collect(self::PRODUTOS)->map(fn ($p, $i) => Produto::create([
            'sku' => sprintf('PRD-%04d', $i + 1),
            'nome' => $p[0],
            'categoria' => $p[1],
            'preco' => $p[2],
            // alguns com estoque baixo para o alerta do dashboard
            'estoque' => $i % 7 === 0 ? fake()->numberBetween(0, 8) : fake()->numberBetween(20, 250),
        ]));

        // Pedidos nos últimos 12 meses; os antigos tendem a "entregue", os recentes a "pendente/pago".
        foreach (range(1, 180) as $_) {
            $data = fake()->dateTimeBetween('-12 months', 'now');
            $dias = now()->diffInDays($data, true);
            $status = match (true) {
                fake()->boolean(7) => 'cancelado',
                $dias > 20 => 'entregue',
                $dias > 7 => fake()->randomElement(['enviado', 'entregue']),
                default => fake()->randomElement(['pendente', 'pago', 'enviado']),
            };

            $pedido = Pedido::forceCreate([
                'cliente_id' => $clientes->random()->id,
                'status' => $status,
                'created_at' => $data,
                'updated_at' => $data,
            ]);

            $itens = $produtos->random(fake()->numberBetween(1, 4))->map(fn ($p) => [
                'produto_id' => $p->id,
                'quantidade' => fake()->numberBetween(1, 5),
                'preco_unitario' => $p->preco,
            ]);
            $pedido->itens()->createMany($itens);
            $pedido->total = $itens->sum(fn ($i) => $i['quantidade'] * $i['preco_unitario']);
            $pedido->timestamps = false;
            $pedido->save();
        }
    }
}
