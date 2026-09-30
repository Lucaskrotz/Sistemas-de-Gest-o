<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cor', 7);
            $table->string('plano', 20);
            $table->timestamps();
        });

        // Usuário pertence a no máximo uma empresa (null = usuários demo dos outros módulos).
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('email');
            $table->string('empresa')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']); // e-mail único por empresa, não global
        });

        Schema::create('oportunidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contato_id')->constrained('contatos')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->decimal('valor', 12, 2);
            $table->string('etapa', 12)->index();
            $table->timestamps();
            $table->index(['tenant_id', 'etapa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
        Schema::dropIfExists('contatos');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('tenant_id'));
        Schema::dropIfExists('tenants');
    }
};
