<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtpvRequest;
use App\Models\Pesquisa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'new_users' => 'Usuários novos',
        'credits_used' => 'Créditos usados',
        'atpv_issued' => 'ATPV emitidos',
    ];

    private const PERIODS = [
        'day' => 'Dia',
        'week' => 'Semana',
        'month' => 'Mês',
        'year' => 'Ano',
    ];

    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $stats = $this->buildStats($filters['start'], $filters['end']);
        $report = $this->buildReport($filters);

        $statCards = collect(self::REPORT_TYPES)->map(function (string $label, string $key) use ($stats, $filters) {
            $value = $stats[$key] ?? 0;

            return [
                'key' => $key,
                'title' => $label,
                'value' => number_format($value, 0, ',', '.') . match ($key) {
                    'credits_used' => ' créditos',
                    'atpv_issued' => ' emissões',
                    default => ' usuários',
                },
                'active' => $filters['report_type'] === $key,
            ];
        })->values()->all();

        return view('admin.reports.index', [
            'statCards' => $statCards,
            'tableRows' => $report['rows'],
            'tableColumns' => $report['columns'],
            'filters' => [
                'report_type' => $filters['report_type'],
                'period' => $filters['period'],
                'reference' => $filters['reference_input'],
                'period_label' => $filters['period_label'],
                'search' => $filters['search'],
                'report_options' => self::REPORT_TYPES,
                'period_options' => self::PERIODS,
            ],
            'exportBaseUrl' => route('admin.reports.export'),
            'searchPlaceholder' => $report['search_placeholder'],
            'summaryTotal' => $report['summary_total'],
            'chartData' => $this->buildChartData($filters),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $report = $this->buildReport($filters);

        $filename = sprintf(
            'relatorio_%s_%s_a_%s.csv',
            $filters['report_type'],
            $filters['start']->format('Ymd'),
            $filters['end']->format('Ymd')
        );

        $columns = $report['columns'];
        $rows = $report['rows'];

        return Response::streamDownload(function () use ($columns, $rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, collect($columns)->pluck('label')->all(), ';');

            foreach ($rows as $row) {
                $line = collect($columns)
                    ->map(fn (array $column) => $row[$column['key']] ?? '')
                    ->all();

                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{
     *     report_type: string,
     *     period: string,
     *     reference_input: string,
     *     search: string,
     *     start: Carbon,
     *     end: Carbon,
     *     period_label: string
     * }
     */
    private function resolveFilters(Request $request): array
    {
        $reportType = $request->query('report_type', 'new_users');
        $reportType = array_key_exists($reportType, self::REPORT_TYPES) ? $reportType : 'new_users';

        $period = $request->query('period', 'month');
        $period = array_key_exists($period, self::PERIODS) ? $period : 'month';

        $referenceInput = trim((string) $request->query('reference', ''));
        $reference = $this->parseReference($period, $referenceInput);
        $search = trim((string) $request->query('search', ''));

        [$start, $end] = $this->determineRange($period, $reference);

        return [
            'report_type' => $reportType,
            'period' => $period,
            'reference_input' => $this->formatReferenceInput($period, $reference),
            'search' => $search,
            'start' => $start,
            'end' => $end,
            'period_label' => $this->buildPeriodLabel($period, $start, $end),
        ];
    }

    private function parseReference(string $period, string $value): Carbon
    {
        $timezone = config('app.timezone');

        try {
            return match ($period) {
                'day' => Carbon::createFromFormat('Y-m-d', $value, $timezone)->startOfDay(),
                'week' => Carbon::createFromFormat('o-\WW', $value, $timezone)->startOfWeek(),
                'year' => Carbon::createFromFormat('Y', $value, $timezone)->startOfYear(),
                default => Carbon::createFromFormat('Y-m', $value, $timezone)->startOfMonth(),
            };
        } catch (\Throwable $e) {
            return now($timezone)->startOfMonth();
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function determineRange(string $period, Carbon $reference): array
    {
        return match ($period) {
            'day' => [$reference->copy()->startOfDay(), $reference->copy()->endOfDay()],
            'week' => [$reference->copy()->startOfWeek(), $reference->copy()->endOfWeek()],
            'year' => [$reference->copy()->startOfYear(), $reference->copy()->endOfYear()],
            default => [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()],
        };
    }

    private function formatReferenceInput(string $period, Carbon $reference): string
    {
        return match ($period) {
            'day' => $reference->format('Y-m-d'),
            'week' => $reference->format('o-\WW'),
            'year' => $reference->format('Y'),
            default => $reference->format('Y-m'),
        };
    }

    private function buildStats(Carbon $start, Carbon $end): array
    {
        return [
            'new_users' => User::query()
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'credits_used' => Pesquisa::query()
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'atpv_issued' => AtpvRequest::query()
                ->whereBetween('created_at', [$start, $end])
                ->count(),
        ];
    }

    /**
     * @param array{
     *     report_type: string,
     *     period: string,
     *     reference_input: string,
     *     search: string,
     *     start: Carbon,
     *     end: Carbon,
     *     period_label: string
     * } $filters
     *
     * @return array{
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
     *     summary_total: int,
     *     search_placeholder: string
     * }
     */
    private function buildReport(array $filters): array
    {
        return match ($filters['report_type']) {
            'credits_used' => $this->buildCreditsReport($filters),
            'atpv_issued' => $this->buildAtpvReport($filters),
            default => $this->buildNewUsersReport($filters),
        };
    }

    private function buildNewUsersReport(array $filters): array
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'is_active', 'created_at'])
            ->whereBetween('created_at', [$filters['start'], $filters['end']]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderByDesc('created_at')->get();

        $rows = $users->map(function (User $user) {
            return [
                'id' => (string) $user->id,
                'client' => $user->name,
                'email' => $user->email,
                'extra' => $user->is_active ? 'Ativo' : 'Inativo',
                'last_activity' => optional($user->created_at)
                    ->timezone(config('app.timezone'))
                    ->format('d/m/Y H:i'),
            ];
        })->all();

        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'email', 'label' => 'E-mail'],
                ['key' => 'extra', 'label' => 'Status'],
                ['key' => 'last_activity', 'label' => 'Data de cadastro'],
            ],
            'rows' => $rows,
            'summary_total' => $users->count(),
            'search_placeholder' => 'Pesquisar por nome ou e-mail',
        ];
    }

    private function buildCreditsReport(array $filters): array
    {
        $query = User::query()
            ->select(['id', 'name', 'email'])
            ->whereHas('pesquisas', function (Builder $builder) use ($filters) {
                $builder->whereBetween('created_at', [$filters['start'], $filters['end']]);
            })
            ->withCount([
                'pesquisas as credits_total' => function (Builder $builder) use ($filters) {
                    $builder->whereBetween('created_at', [$filters['start'], $filters['end']]);
                },
            ])
            ->withMax([
                'pesquisas as last_credit_at' => function (Builder $builder) use ($filters) {
                    $builder->whereBetween('created_at', [$filters['start'], $filters['end']]);
                },
            ], 'created_at');

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderByDesc('credits_total')->get();

        $rows = $users->map(function (User $user) {
            return [
                'id' => (string) $user->id,
                'client' => $user->name,
                'email' => $user->email,
                'credits' => number_format((int) ($user->credits_total ?? 0), 0, ',', '.'),
                'last_activity' => $user->last_credit_at
                    ? Carbon::parse($user->last_credit_at)->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : '—',
            ];
        })->all();

        $totalCredits = $users->sum(static fn (User $user) => (int) ($user->credits_total ?? 0));

        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'email', 'label' => 'E-mail'],
                ['key' => 'credits', 'label' => 'Créditos utilizados'],
                ['key' => 'last_activity', 'label' => 'Último uso'],
            ],
            'rows' => $rows,
            'summary_total' => $totalCredits,
            'search_placeholder' => 'Pesquisar por cliente ou e-mail',
        ];
    }

    private function buildAtpvReport(array $filters): array
    {
        $query = AtpvRequest::query()
            ->select(['id', 'user_id', 'placa', 'renavam', 'status', 'created_at'])
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$filters['start'], $filters['end']]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('placa', 'like', '%' . $search . '%')
                    ->orWhere('renavam', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function (Builder $sub) use ($search) {
                        $sub
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $requests = $query->orderByDesc('created_at')->get();

        $rows = $requests->map(function (AtpvRequest $request) {
            return [
                'id' => (string) $request->id,
                'client' => $request->user?->name ?? '—',
                'email' => $request->user?->email ?? '—',
                'plate' => Str::upper((string) $request->placa),
                'extra' => Str::headline((string) $request->status),
                'last_activity' => optional($request->created_at)
                    ->timezone(config('app.timezone'))
                    ->format('d/m/Y H:i'),
            ];
        })->all();

        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'email', 'label' => 'E-mail'],
                ['key' => 'plate', 'label' => 'Placa'],
                ['key' => 'extra', 'label' => 'Status'],
                ['key' => 'last_activity', 'label' => 'Data da emissão'],
            ],
            'rows' => $rows,
            'summary_total' => $requests->count(),
            'search_placeholder' => 'Pesquisar por usuário, placa ou status',
        ];
    }

    private function buildChartData(array $filters): array
    {
        // Placeholder to keep existing layout behaviour; charts serão revisados em etapa futura.
        return [
            'dailyConsults' => [
                'labels' => [],
                'values' => [],
            ],
            'topUsers' => [],
            'weeklyRevenue' => [
                'labels' => [],
                'values' => [],
            ],
            'creditDistribution' => [],
        ];
    }

    private function buildPeriodLabel(string $period, Carbon $start, Carbon $end): string
    {
        $locale = app()->getLocale() ?: 'pt_BR';

        return match ($period) {
            'day' => $start->copy()->locale($locale)->translatedFormat('d \\d\\e F \\d\\e Y'),
            'week' => sprintf(
                'Semana %02d (%s a %s)',
                $start->isoWeek(),
                $start->copy()->locale($locale)->translatedFormat('d/m'),
                $end->copy()->locale($locale)->translatedFormat('d/m')
            ),
            'year' => $start->format('Y'),
            default => $start->copy()->locale($locale)->translatedFormat('F \\d\\e Y'),
        };
    }
}
