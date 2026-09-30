<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->date('vencimento')->index();
            $table->string('status', 12)->default('aberto')->index(); // aberto | pago | cancelado
            $table->string('codigo_cobranca', 30)->nullable()->unique();
            $table->string('linha_digitavel', 60)->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->decimal('valor_pago', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titulo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('evento_id', 60)->nullable()->index();
            $table->string('tipo', 40);
            $table->text('payload');
            $table->string('assinatura', 100)->nullable();
            $table->boolean('assinatura_valida');
            $table->string('status', 12); // processado | ignorado | rejeitado | erro
            $table->string('mensagem');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_eventos');
        Schema::dropIfExists('titulos');
    }
};
