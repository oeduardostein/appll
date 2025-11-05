<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CreditManagementController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $this->resolveMonth($request->query('month'));
        $monthKey = $selectedMonth->format('Y-m');

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

        $monthOptions = $this->buildMonthOptions();

        return view('admin.payments.index', [
            'users' => $users,
            'selectedMonth' => $selectedMonth,
            'selectedMonthKey' => $monthKey,
            'monthOptions' => $monthOptions,
        ]);
    }

    public function markPaid(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'], config('app.timezone'))
            ->startOfMonth();

        Payment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month->toDateString(),
            ],
            [
                'paid' => true,
            ]
        );

        return redirect()
            ->route('admin.payments.index', ['month' => $month->format('Y-m')])
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

        return redirect()
            ->route('admin.payments.index', ['month' => $month->format('Y-m')])
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

        return collect(range(0, 11))
            ->map(static function (int $offset) use ($currentMonth) {
                return $currentMonth->clone()->subMonths($offset);
            })
            ->map(static function (Carbon $month) {
                return [
                    'key' => $month->format('Y-m'),
                    'label' => ucfirst($month->translatedFormat('F \\d\\e Y')),
                ];
            })
            ->all();
    }
}
