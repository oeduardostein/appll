<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::query()->updateOrCreate(
            ['slug' => 'teste_planilha_gravame'],
            [
                'name' => 'Teste Planilha Gravame',
                'default_credit_value' => 0,
            ]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'consulta_placas_0km'],
            [
                'name' => 'Consulta Placas 0KM',
                'default_credit_value' => 0,
            ]
        );
    }
}
