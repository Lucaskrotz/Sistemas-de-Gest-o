<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('documento', 18)->unique();
            $table->string('email');
            $table->string('telefone', 20)->nullable();
            $table->string('cidade')->nullable();
            $table->char('uf', 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 30)->unique();
            $table->string('nome');
            $table->string('categoria');
            $table->decimal('preco', 10, 2);
            $table->unsignedInteger('estoque')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pendente')->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('pedido_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantidade');
            $table->decimal('preco_unitario', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_itens');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('clientes');
    }
};
