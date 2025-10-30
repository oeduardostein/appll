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
            'weeklySignups' => [12, 18, 14, 23, 19, 27, 21],
            'weeklyLabels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            'planDistribution' => [
                ['label' => 'Plano Básico', 'value' => 48],
                ['label' => 'Plano Premium', 'value' => 32],
                ['label' => 'Plano Empresarial', 'value' => 20],
            ],
            'creditUsage' => [
                ['label' => 'Campanhas Ativas', 'value' => 62],
                ['label' => 'Campanhas Pausadas', 'value' => 18],
                ['label' => 'Campanhas Concluídas', 'value' => 20],
            ],
        ];

        return view('admin.reports.index', [
            'tableRows' => $tableRows,
            'chartData' => $chartData,
        ]);
    }
}
