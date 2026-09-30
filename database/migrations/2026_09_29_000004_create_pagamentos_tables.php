<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 24)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->decimal('taxa', 10, 2);
            $table->decimal('valor_liquido', 12, 2);
            $table->string('metodo', 10); // pix | cartao
            $table->string('status', 12)->index();
            // Cartão: só bandeira e 4 últimos dígitos. Número completo e CVV nunca são gravados.
            $table->string('bandeira', 20)->nullable();
            $table->char('cartao_final', 4)->nullable();
            $table->unsignedTinyInteger('parcelas')->default(1);
            $table->text('pix_copia_cola')->nullable();
            $table->timestamp('expira_em')->nullable();
            $table->timestamps();
        });

        Schema::create('pagamento_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagamento_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 40)->index();
            $table->json('dados');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_eventos');
        Schema::dropIfExists('pagamentos');
    }
};
