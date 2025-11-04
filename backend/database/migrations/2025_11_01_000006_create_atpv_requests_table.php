<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atpv_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('renavam', 20);
            $table->string('placa', 10);
            $table->string('chassi', 32)->nullable();
            $table->string('hodometro', 16)->nullable();
            $table->string('email_proprietario', 150)->nullable();
            $table->string('cpf_cnpj_proprietario', 20)->nullable();
            $table->string('cpf_cnpj_comprador', 20)->nullable();
            $table->string('nome_comprador', 150)->nullable();
            $table->string('email_comprador', 150)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('valor_venda', 32)->nullable();
            $table->string('cep_comprador', 16)->nullable();
            $table->string('municipio_comprador', 150)->nullable();
            $table->string('bairro_comprador', 150)->nullable();
            $table->string('logradouro_comprador', 150)->nullable();
            $table->string('numero_comprador', 20)->nullable();
            $table->string('complemento_comprador', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->json('response_payload')->nullable();
            $table->json('response_errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atpv_requests');
    }
};
