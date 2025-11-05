<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtpvRequest;
use App\Models\Pesquisa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $reportData = $this->buildReportData($filters);

        $statCards = collect([
            [
                'key' => 'new_users',
                'title' => 'Usuários novos',
                'value' => number_format($stats['new_users'], 0, ',', '.') . ' usuários',
            ],
            [
                'key' => 'credits_used',
                'title' => 'Créditos usados',
                'value' => number_format($stats['credits_used'], 0, ',', '.') . ' créditos',
            ],
            [
                'key' => 'atpv_issued',
                'title' => 'ATPV emitidos',
                'value' => number_format($stats['atpv_issued'], 0, ',', '.') . ' emissões',
            ],
        ])->map(function (array $card) use ($filters) {
            $card['active'] = $card['key'] === $filters['report_type'];

            return $card;
        })->all();

        return view('admin.reports.index', [
            'statCards' => $statCards,
            'table' => $reportData['table'],
            'chart' => $reportData['chart'],
            'summary' => $reportData['summary'],
            'filters' => [
                'report_type' => $filters['report_type'],
                'period' => $filters['period'],
                'period_label' => $filters['period_label'],
                'reference_input' => $filters['reference_input'],
                'search' => $filters['search'],
                'report_options' => self::REPORT_TYPES,
                'period_options' => self::PERIODS,
            ],
            'searchPlaceholder' => $reportData['search_placeholder'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $reportData = $this->buildReportData($filters);

        $filename = sprintf(
            'relatorio_%s_%s_a_%s.csv',
            $filters['report_type'],
            $filters['start']->format('Ymd'),
            $filters['end']->format('Ymd')
        );

        $columns = $reportData['table']['columns'];
        $rows = $reportData['table']['rows'];

        return Response::streamDownload(function () use ($columns, $rows): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, collect($columns)->pluck('label')->all(), ';');

            foreach ($rows as $row) {
                $values = collect($columns)
                    ->map(fn ($column) => Arr::get($row, $column['key'], ''))
                    ->all();

                fputcsv($output, $values, ';');
            }

            fclose($output);
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
                'month' => Carbon::createFromFormat('Y-m', $value, $timezone)->startOfMonth(),
                'year' => Carbon::createFromFormat('Y', $value, $timezone)->startOfYear(),
                default => now($timezone),
            };
        } catch (\Throwable $e) {
            return now($timezone);
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
     *     table: array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, string>>},
     *     chart: array{labels: array<int, string>, values: array<int, int>, dataset_label: string, value_suffix: string},
     *     summary: array{total: int, period_label: string},
     *     search_placeholder: string
     * }
     */
    private function buildReportData(array $filters): array
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
            ->whereBetween('created_at', [$filters['start'], $filters['end']])
            ->orderByDesc('created_at');

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->get();

        $rows = $users->map(function (User $user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->is_active ? 'Ativo' : 'Inativo',
                'created_at' => $user->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—',
            ];
        })->all();

        $timeline = $this->buildTimeline(
            $users,
            'created_at',
            $filters['start'],
            $filters['end'],
            $filters['period']
        );

        return [
            'table' => [
                'columns' => [
                    ['key' => 'name', 'label' => 'Nome'],
                    ['key' => 'email', 'label' => 'E-mail'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'created_at', 'label' => 'Data de cadastro'],
                ],
                'rows' => $rows,
            ],
            'chart' => [
                'labels' => $timeline['labels'],
                'values' => $timeline['values'],
                'dataset_label' => 'Usuários novos',
                'value_suffix' => ' usuários',
            ],
            'summary' => [
                'total' => $users->count(),
                'period_label' => $filters['period_label'],
            ],
            'search_placeholder' => 'Buscar por nome ou e-mail',
        ];
    }

    private function buildCreditsReport(array $filters): array
    {
        $creditsQuery = User::query()
            ->select(['id', 'name', 'email', 'is_active'])
            ->whereHas('pesquisas', function ($query) use ($filters): void {
                $query->whereBetween('created_at', [$filters['start'], $filters['end']]);
            })
            ->withCount([
                'pesquisas as credits_total' => function ($query) use ($filters): void {
                    $query->whereBetween('created_at', [$filters['start'], $filters['end']]);
                },
            ])
            ->withMax([
                'pesquisas as last_credit_at' => function ($query) use ($filters): void {
                    $query->whereBetween('created_at', [$filters['start'], $filters['end']]);
                },
            ], 'created_at')
            ->orderByDesc('credits_total');

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $creditsQuery->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $creditsQuery->get();
        $totalCredits = $users->sum(static fn (User $user) => (int) ($user->credits_total ?? 0));

        $rows = $users->map(function (User $user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'credits_total' => number_format((int) ($user->credits_total ?? 0), 0, ',', '.'),
                'last_credit_at' => $user->last_credit_at
                    ? Carbon::parse($user->last_credit_at)->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : '—',
            ];
        })->all();

        $credits = Pesquisa::query()
            ->select(['id', 'user_id', 'created_at'])
            ->whereBetween('created_at', [$filters['start'], $filters['end']]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $credits->whereHas('user', function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $timeline = $this->buildTimeline(
            $credits->get(),
            'created_at',
            $filters['start'],
            $filters['end'],
            $filters['period']
        );

        return [
            'table' => [
                'columns' => [
                    ['key' => 'name', 'label' => 'Usuário'],
                    ['key' => 'email', 'label' => 'E-mail'],
                    ['key' => 'credits_total', 'label' => 'Créditos usados'],
                    ['key' => 'last_credit_at', 'label' => 'Último uso'],
                ],
                'rows' => $rows,
            ],
            'chart' => [
                'labels' => $timeline['labels'],
                'values' => $timeline['values'],
                'dataset_label' => 'Créditos usados',
                'value_suffix' => ' créditos',
            ],
            'summary' => [
                'total' => $totalCredits,
                'period_label' => $filters['period_label'],
            ],
            'search_placeholder' => 'Buscar por usuário ou e-mail',
        ];
    }

    private function buildAtpvReport(array $filters): array
    {
        $requestsQuery = AtpvRequest::query()
            ->select(['id', 'user_id', 'placa', 'renavam', 'status', 'created_at'])
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$filters['start'], $filters['end']])
            ->orderByDesc('created_at');

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $requestsQuery->where(function ($builder) use ($search): void {
                $builder
                    ->where('placa', 'like', '%' . $search . '%')
                    ->orWhere('renavam', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $requests = $requestsQuery->get();

        $rows = $requests->map(function (AtpvRequest $request) {
            return [
                'user' => $request->user?->name ?? '—',
                'email' => $request->user?->email ?? '—',
                'placa' => strtoupper((string) $request->placa),
                'status' => Str::headline((string) $request->status),
                'created_at' => $request->created_at
                    ? $request->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : '—',
            ];
        })->all();

        $timeline = $this->buildTimeline(
            $requests,
            'created_at',
            $filters['start'],
            $filters['end'],
            $filters['period']
        );

        return [
            'table' => [
                'columns' => [
                    ['key' => 'user', 'label' => 'Usuário'],
                    ['key' => 'email', 'label' => 'E-mail'],
                    ['key' => 'placa', 'label' => 'Placa'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'created_at', 'label' => 'Data da emissão'],
                ],
                'rows' => $rows,
            ],
            'chart' => [
                'labels' => $timeline['labels'],
                'values' => $timeline['values'],
                'dataset_label' => 'ATPV emitidos',
                'value_suffix' => ' emissões',
            ],
            'summary' => [
                'total' => count($rows),
                'period_label' => $filters['period_label'],
            ],
            'search_placeholder' => 'Buscar por usuário, placa ou status',
        ];
    }

    /**
     * @param Collection<int, mixed> $items
     */
    private function buildTimeline(Collection $items, string $dateKey, Carbon $start, Carbon $end, string $period): array
    {
        $locale = app()->getLocale() ?: 'pt_BR';

        $grouped = $items->groupBy(function ($item) use ($dateKey, $period): string {
            $date = Carbon::parse(
                is_array($item) ? Arr::get($item, $dateKey) : $item->{$dateKey},
                config('app.timezone')
            );

            return match ($period) {
                'day' => $date->format('Y-m-d'),
                'week' => $date->startOfWeek()->format('Y-m-d'),
                'year' => $date->format('Y'),
                default => $date->format('Y-m'),
            };
        });

        $labels = [];
        $values = [];

        for ($cursor = $this->alignStart($start, $period); $cursor <= $end; $cursor = $this->incrementCursor($cursor, $period)) {
            $bucketKey = match ($period) {
                'day' => $cursor->format('Y-m-d'),
                'week' => $cursor->startOfWeek()->format('Y-m-d'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('Y-m'),
            };

            $bucketItems = $grouped->get($bucketKey, collect());

            $labels[] = match ($period) {
                'day' => $cursor->copy()->locale($locale)->isoFormat('DD/MM'),
                'week' => sprintf(
                    'Semana %02d · %s',
                    $cursor->isoWeek(),
                    $cursor->copy()->locale($locale)->isoFormat('MMM')
                ),
                'year' => $cursor->format('Y'),
                default => $cursor->copy()->locale($locale)->isoFormat('MMM YYYY'),
            };

            $values[] = $bucketItems instanceof Collection ? $bucketItems->count() : collect($bucketItems)->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function alignStart(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'year' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function incrementCursor(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'day' => $date->copy()->addDay(),
            'week' => $date->copy()->addWeek(),
            'year' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };
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
            'year' => $start->copy()->format('Y'),
            default => $start->copy()->locale($locale)->translatedFormat('F \\d\\e Y'),
        };
    }
}
