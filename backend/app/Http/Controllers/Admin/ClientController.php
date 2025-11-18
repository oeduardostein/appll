<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\Permission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $perPage = 10;

        $users = User::query()
            ->with(['permissions:id,name,slug'])
            ->withCount(['pesquisas as credits_used'])
            ->latest('created_at')
            ->paginate($perPage);

        $metrics = $this->buildMetrics();

        /** @var Collection<int, array<string, mixed>> $initialUsers */
        $initialUsers = UserResource::collection($users->getCollection())->resolve();

        $statCards = [
            [
                'key' => 'active',
                'title' => 'Usuários Ativos',
                'value' => sprintf('%d usuários', $metrics['active']),
                'trend' => null,
            ],
            [
                'key' => 'new_this_month',
                'title' => 'Novos Usuários (mês)',
                'value' => sprintf('%d usuários', $metrics['new_this_month']),
                'trend' => null,
            ],
            [
                'key' => 'inactive',
                'title' => 'Usuários Inativos',
                'value' => sprintf('%d usuários', $metrics['inactive']),
                'trend' => null,
            ],
        ];

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.clients.index', [
            'stats' => $statCards,
            'selectedCount' => 0,
            'initialUsers' => $initialUsers,
            'initialMetrics' => $metrics,
            'initialPagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'availablePermissions' => $permissions,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $selectedMonthDate = $this->resolveSelectedMonth($request->query('month'));
        $report = $this->buildCreditReport($user, $selectedMonthDate);

        return view('admin.clients.show', [
            'user' => $user,
            'selectedMonth' => $selectedMonthDate->format('Y-m'),
            'selectedMonthLabel' => $this->formatMonthLabel($selectedMonthDate),
            'availableMonths' => $this->buildMonthOptions(),
            'creditSummary' => $report['creditSummary'],
            'creditBreakdown' => $report['creditBreakdown'],
            'totalCredits' => $report['totalCredits'],
        ]);
    }

    public function export(Request $request, User $user)
    {
        $data = $request->validate([
            'format' => ['required', 'in:pdf,csv'],
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $selectedMonth = $this->resolveSelectedMonth($data['month'] ?? null);
        $report = $this->buildCreditReport($user, $selectedMonth);
        $periodLabel = $this->formatMonthLabel($selectedMonth);
        $filenameBase = sprintf(
            'relatorio_cliente_%s_%s',
            Str::slug($user->name),
            $selectedMonth->format('Y_m')
        );

        if ($data['format'] === 'pdf') {
            $pdf = Pdf::loadView('admin.clients.exports.credit-report', [
                'user' => $user,
                'selectedMonthLabel' => $periodLabel,
                'creditSummary' => $report['creditSummary'],
                'creditBreakdown' => $report['creditBreakdown'],
                'totalCredits' => $report['totalCredits'],
            ])->setPaper('a4', 'portrait');

            return $pdf->download($filenameBase . '.pdf');
        }

        $breakdown = $report['creditBreakdown'];

        return response()->streamDownload(function () use ($breakdown, $periodLabel, $user, $report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Cliente', $user->name], ';');
            fputcsv($handle, ['Período', $periodLabel], ';');
            fputcsv($handle, []);
            fputcsv($handle, ['Serviço', 'Créditos utilizados'], ';');

            foreach ($breakdown as $item) {
                fputcsv($handle, [$item['label'], $item['count']], ';');
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Total', $report['totalCredits']], ';');
            fclose($handle);
        }, $filenameBase . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildMetrics(): array
    {
        $total = User::query()->count();
        $active = User::query()->where('is_active', true)->count();
        $inactive = User::query()->where('is_active', false)->count();
        $newThisMonth = User::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'new_this_month' => $newThisMonth,
        ];
    }

    private function resolveSelectedMonth(?string $input): Carbon
    {
        if ($input && preg_match('/^\d{4}-\d{2}$/', $input) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', $input)->startOfMonth();
            } catch (\Throwable $e) { // fallback for invalid date
            }
        }

        return now()->startOfMonth();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function buildMonthOptions(int $months = 12): array
    {
        $now = now();

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($now) {
                $date = $now->copy()->subMonthsNoOverflow($offset)->startOfMonth();

                return [
                    'value' => $date->format('Y-m'),
                    'label' => $this->formatMonthLabel($date),
                ];
            })
            ->all();
    }

    private function formatMonthLabel(Carbon $date): string
    {
        $label = $date
            ->copy()
            ->locale('pt_BR')
            ->translatedFormat('F \\d\\e Y');

        return Str::ucfirst($label);
    }

    /**
     * @return array{
     *     creditSummary: array<int, array<string, mixed>>,
     *     creditBreakdown: array<int, array<string, int|string>>,
     *     totalCredits: int
     * }
     */
    private function buildCreditReport(User $user, Carbon $selectedMonth): array
    {
        $periodStart = $selectedMonth->copy()->startOfMonth();
        $periodEnd = $selectedMonth->copy()->endOfMonth();

        $pesquisaBreakdown = $user->pesquisas()
            ->selectRaw('nome, COUNT(*) as total')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupBy('nome')
            ->orderByDesc('total')
            ->get()
            ->map(static fn ($row) => [
                'label' => (string) $row->nome,
                'count' => (int) $row->total,
            ]);

        $namedCounts = $pesquisaBreakdown
            ->keyBy('label')
            ->map(static fn (array $item) => (int) $item['count']);

        $atpvCount = $user->atpvRequests()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $creditBreakdown = $pesquisaBreakdown->values()->all();
        if ($atpvCount > 0) {
            $creditBreakdown[] = [
                'label' => 'Emissão da ATPV-e',
                'count' => $atpvCount,
            ];
        }

        $totalFromPesquisas = $pesquisaBreakdown->sum('count');
        $totalCredits = $totalFromPesquisas + $atpvCount;
        $baseCount = $namedCounts->get('Base estadual', 0) + $namedCounts->get('Base outros estados', 0);
        $crlvCount = $namedCounts->get('Emissão do CRLV-e', 0);

        $summaryCards = [
            [
                'key' => 'total',
                'label' => 'Total de créditos',
                'value' => $totalCredits,
                'description' => 'Créditos utilizados no período',
            ],
            [
                'key' => 'crlv',
                'label' => 'CRLV-e',
                'value' => $crlvCount,
                'description' => 'Emissões registradas',
            ],
            [
                'key' => 'base',
                'label' => 'Bases estaduais',
                'value' => $baseCount,
                'description' => 'Consultas nas bases estadual e outros estados',
            ],
            [
                'key' => 'atpv',
                'label' => 'ATPV-e',
                'value' => $atpvCount,
                'description' => 'Solicitações processadas',
            ],
        ];

        return [
            'creditSummary' => $summaryCards,
            'creditBreakdown' => $creditBreakdown,
            'totalCredits' => $totalCredits,
        ];
    }
}
