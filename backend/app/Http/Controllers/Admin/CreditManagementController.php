<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CreditManagementController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $this->resolveMonth($request->query('month'));
        $monthKey = $selectedMonth->format('Y-m');
        $searchQuery = trim((string) $request->query('search', ''));

        $startOfMonth = $selectedMonth->clone()->startOfMonth();
        $endOfMonth = $selectedMonth->clone()->endOfMonth();

        $users = User::query()
            ->select(['id', 'name', 'email', 'is_active'])
            ->withCount([
                'pesquisas as monthly_credits_used' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query
                        ->where('created_at', '>=', $startOfMonth)
                        ->where('created_at', '<=', $endOfMonth);
                },
            ])
            ->withExists([
                'payments as has_paid' => function (Builder $query) use ($startOfMonth): void {
                    $query
                        ->whereDate('month', $startOfMonth->toDateString())
                        ->where('paid', true);
                },
            ])
            ->orderBy('name')
            ->get();
        $users = $users->map(function ($user) {
            $credits = (int) $user->monthly_credits_used;
            $hasPending = ! (bool) $user->has_paid && $credits > 0;
            $status = match (true) {
                $hasPending => 'pending',
                $credits === 0 => 'no_usage',
                default => 'paid',
            };

            $user->setAttribute('has_pending_payment', $hasPending);
            $user->setAttribute('effective_payment_status', $status);

            return $user;
        });

        if ($searchQuery !== '') {
            $needle = Str::lower($searchQuery);
            $users = $users->filter(static function ($user) use ($needle) {
                $haystack = [
                    $user->name,
                    $user->email,
                    (string) $user->monthly_credits_used,
                    $user->is_active ? 'ativo' : 'inativo',
                    match ($user->effective_payment_status) {
                        'pending' => 'pendente',
                        'paid' => 'pago',
                        default => 'sem consumo',
                    },
                ];

                foreach ($haystack as $value) {
                    if ($value === null) {
                        continue;
                    }

                    if (Str::contains(Str::lower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $monthOptions = $this->buildMonthOptions();

        return view('admin.payments.index', [
            'users' => $users,
            'selectedMonth' => $selectedMonth,
            'selectedMonthKey' => $monthKey,
            'monthOptions' => $monthOptions,
            'searchQuery' => $searchQuery,
        ]);
    }

    public function markPaid(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'], config('app.timezone'))
            ->startOfMonth();
        $search = trim((string) $request->input('search', ''));

        Payment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month->toDateString(),
            ],
            [
                'paid' => true,
            ]
        );

        $redirectParams = [
            'month' => $month->format('Y-m'),
        ];

        if ($search !== '') {
            $redirectParams['search'] = $search;
        }

        return redirect()
            ->route('admin.payments.index', $redirectParams)
            ->with('status', 'Pagamento marcado como concluído.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $user->forceFill(['is_active' => false])->save();

        $month = Carbon::createFromFormat('Y-m', $validated['month'], config('app.timezone'))
            ->startOfMonth();
        $search = trim((string) $request->input('search', ''));

        $redirectParams = [
            'month' => $month->format('Y-m'),
        ];

        if ($search !== '') {
            $redirectParams['search'] = $search;
        }

        return redirect()
            ->route('admin.payments.index', $redirectParams)
            ->with('status', 'Usuário inativado com sucesso.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        if (is_string($month)) {
            try {
                return Carbon::createFromFormat('Y-m', $month, config('app.timezone'))->startOfMonth();
            } catch (\Throwable $e) {
                // fallback to current month
            }
        }

        return now()->startOfMonth();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function buildMonthOptions(): array
    {
        $currentMonth = now()->startOfMonth();
        $locale = app()->getLocale() ?: 'pt_BR';

        return collect(range(0, 11))
            ->map(static function (int $offset) use ($currentMonth) {
                return $currentMonth->clone()->subMonths($offset);
            })
            ->map(static function (Carbon $month) use ($locale) {
                $rawLabel = $month->clone()->locale($locale)->isoFormat('MMMM [de] YYYY');
                $label = Str::ucfirst(Str::lower($rawLabel));

                return [
                    'key' => $month->format('Y-m'),
                    'label' => $label,
                ];
            })
            ->all();
    }
}
