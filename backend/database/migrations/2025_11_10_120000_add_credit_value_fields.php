<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'default_credit_value')) {
                $table->unsignedDecimal('default_credit_value', 8, 2)
                    ->default(0)
                    ->after('slug');
            }
        });

        Schema::table('user_permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_permissions', 'credit_value')) {
                $table->unsignedDecimal('credit_value', 8, 2)
                    ->nullable()
                    ->after('permission_id');
            }
        });

        Schema::table('pesquisas', function (Blueprint $table): void {
            if (! Schema::hasColumn('pesquisas', 'credit_value')) {
                $table->unsignedDecimal('credit_value', 8, 2)
                    ->default(0)
                    ->after('opcao_pesquisa');
            }
        });

        Schema::table('atpv_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('atpv_requests', 'credit_value')) {
                $table->unsignedDecimal('credit_value', 8, 2)
                    ->default(0)
                    ->after('status');
            }
        });

        $defaults = config('credit-values.services', []);
        foreach ($defaults as $slug => $value) {
            DB::table('permissions')
                ->where('slug', $slug)
                ->update(['default_credit_value' => $value]);
        }

        foreach ([
            'Base estadual' => 1.0,
            'Base outros estados' => 1.0,
            'BIN' => 1.0,
            'Gravame' => 1.0,
            'RENAINF' => 1.0,
            'Bloqueios ativos' => 1.0,
            'Processo e-CRVsp' => 1.0,
            'Emissão do CRLV-e' => 5.0,
        ] as $nome => $value) {
            DB::table('pesquisas')
                ->where('nome', $nome)
                ->whereNull('credit_value')
                ->update(['credit_value' => $value]);
        }

        DB::table('atpv_requests')
            ->whereNull('credit_value')
            ->update(['credit_value' => 5.0]);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            if (Schema::hasColumn('permissions', 'default_credit_value')) {
                $table->dropColumn('default_credit_value');
            }
        });

        Schema::table('user_permissions', function (Blueprint $table): void {
            if (Schema::hasColumn('user_permissions', 'credit_value')) {
                $table->dropColumn('credit_value');
            }
        });

        Schema::table('pesquisas', function (Blueprint $table): void {
            if (Schema::hasColumn('pesquisas', 'credit_value')) {
                $table->dropColumn('credit_value');
            }
        });

        Schema::table('atpv_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('atpv_requests', 'credit_value')) {
                $table->dropColumn('credit_value');
            }
        });
    }
};
