<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('atpv_requests', function (Blueprint $table) {
            $table->string('municipio_codigo', 10)->nullable()->after('cep_comprador');
            $table->string('numero_atpv', 30)->nullable()->after('status');
            $table->boolean('assinatura_digital')->nullable()->after('numero_atpv');
            $table->timestamp('assinatura_registrada_em')->nullable()->after('assinatura_digital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atpv_requests', function (Blueprint $table) {
            $table->dropColumn([
                'municipio_codigo',
                'numero_atpv',
                'assinatura_digital',
                'assinatura_registrada_em',
            ]);
        });
    }
};
