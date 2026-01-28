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
    }
}
