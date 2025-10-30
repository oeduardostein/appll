<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $tableRows = collect(range(1, 12))->map(function ($index) {
            return [
                'id' => $index,
                'client' => "Cliente {$index}",
                'email' => "cliente{$index}@exemplo.com",
                'plan' => $index % 2 === 0 ? 'Premium' : 'Básico',
                'credits_used' => rand(20, 120),
                'conversion_rate' => rand(35, 90) / 100,
                'last_activity' => now()->subDays(rand(1, 20))->format('d/m/Y H:i'),
            ];
        });

        $chartData = [
            'dailyConsults' => [
                'labels' => range(1, 30),
                'values' => [12, 18, 24, 37, 41, 58, 69, 72, 80, 65, 78, 85, 90, 320, 280, 210, 260, 195, 180, 140, 120, 160, 130, 110, 95, 88, 76, 68, 54, 100],
            ],
            'topUsers' => [
                ['label' => 'Ana', 'value' => 38],
                ['label' => 'João', 'value' => 31],
                ['label' => 'Pedro', 'value' => 24],
                ['label' => 'Roberta', 'value' => 18],
                ['label' => 'Paulo', 'value' => 14],
            ],
            'weeklyRevenue' => [
                'labels' => ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
                'values' => [4200, 3670, 4450, 4680],
            ],
            'creditDistribution' => [
                ['label' => 'Base estadual', 'value' => 40],
                ['label' => 'Emissão da ATPV-e', 'value' => 20],
                ['label' => 'CRLV-e', 'value' => 20],
                ['label' => 'Base outros estados', 'value' => 25],
            ],
        ];

        $statCards = [
            [
                'title' => 'Consultas realizadas no mês',
                'value' => '5.240 consultas',
            ],
            [
                'title' => 'Novos Usuários',
                'value' => '10.900 créditos',
            ],
            [
                'title' => 'Receita estimada',
                'value' => 'R$ 12.500,00',
            ],
        ];

        return view('admin.reports.index', [
            'tableRows' => $tableRows,
            'chartData' => $chartData,
            'statCards' => $statCards,
        ]);
    }
}
