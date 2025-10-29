<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $stats = [
            [
                'title' => 'Usuários Ativos',
                'value' => '320 usuários',
                'trend' => '+12%',
            ],
            [
                'title' => 'Novos Usuários',
                'value' => '320 usuários',
                'trend' => '+8%',
            ],
            [
                'title' => 'Usuários Inativos',
                'value' => '320 usuários',
                'trend' => '-5%',
            ],
        ];

        $clients = collect([
            [
                'name' => 'John Doe',
                'email' => 'john.doe@gmail.com',
                'status' => 'Ativo',
                'credits' => '30 créditos',
                'last_access' => '12/08/2025',
                'avatar' => 'https://i.pravatar.cc/150?img=1',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@gmail.com',
                'status' => 'Ativo',
                'credits' => '24 créditos',
                'last_access' => '11/08/2025',
                'avatar' => 'https://i.pravatar.cc/150?img=2',
            ],
            [
                'name' => 'Lucas Moreira',
                'email' => 'lucas.moreira@gmail.com',
                'status' => 'Inativo',
                'credits' => '0 créditos',
                'last_access' => '01/08/2025',
                'avatar' => 'https://i.pravatar.cc/150?img=3',
            ],
            [
                'name' => 'Ana Julia',
                'email' => 'ana.julia@gmail.com',
                'status' => 'Ativo',
                'credits' => '45 créditos',
                'last_access' => '10/08/2025',
                'avatar' => 'https://i.pravatar.cc/150?img=4',
            ],
            [
                'name' => 'Carlos Lima',
                'email' => 'carlos.lima@gmail.com',
                'status' => 'Ativo',
                'credits' => '30 créditos',
                'last_access' => '09/08/2025',
                'avatar' => 'https://i.pravatar.cc/150?img=5',
            ],
        ]);

        return view('admin.clients.index', [
            'stats' => $stats,
            'clients' => $clients,
            'selectedCount' => 1,
            'pagination' => [
                'current_page' => 1,
                'last_page' => 10,
            ],
        ]);
    }
}
