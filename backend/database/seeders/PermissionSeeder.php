<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'atpv', 'name' => 'ATPV', 'default_credit_value' => 5.00],
            ['slug' => 'consulta_placas_0km', 'name' => 'Consulta Placas 0KM', 'default_credit_value' => 0.00],
            ['slug' => 'crlv', 'name' => 'CRLV', 'default_credit_value' => 5.00],
            ['slug' => 'pesquisa_andamento_processo', 'name' => 'PESQUISA ANDAMENTO PROCESSO', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_base_estadual', 'name' => 'PESQUISA BASE ESTADUAL', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_base_outros_estados', 'name' => 'PESQUISA BASE OUTROS ESTADOS', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_bin', 'name' => 'PESQUISA BIN', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_bloqueios_ativos', 'name' => 'PESQUISA BLOQUEIOS ATIVOS', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_gravame', 'name' => 'PESQUISA GRAVAME', 'default_credit_value' => 1.00],
            ['slug' => 'pesquisa_renainf', 'name' => 'PESQUISA RENAINF', 'default_credit_value' => 1.00],
            ['slug' => 'teste_planilha_gravame', 'name' => 'Teste Planilha Gravame', 'default_credit_value' => 0.00],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'default_credit_value' => $permission['default_credit_value'],
                ]
            );
        }
    }
}
