<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->string('titulo');
            $table->text('descricao');
            $table->string('prioridade', 10);
            $table->string('status', 20)->default('aberto')->index();
            $table->string('responsavel')->nullable();
            $table->timestamp('prazo_sla');
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('chamado_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->string('tipo', 12)->default('evento'); // evento | comentario
            $table->string('autor');
            $table->text('descricao');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamado_historicos');
        Schema::dropIfExists('chamados');
    }
};
