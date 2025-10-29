@props([
    'title',
    'value',
    'trend' => null,
    'icon' => 'arrow',
])

<article class="admin-card admin-stat-card">
    <style>
        .admin-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 28px;
            gap: 18px;
        }

        .admin-stat-card__icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: rgba(11, 78, 162, 0.1);
            display: grid;
            place-items: center;
        }

        .admin-stat-card__title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .admin-stat-card__value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .admin-stat-card__trend {
            margin-top: 4px;
            font-size: 12px;
            color: var(--brand-primary);
            font-weight: 600;
        }
    </style>

    <div class="admin-stat-card__icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14m0 0-4-4m4 4 4-4" stroke="var(--brand-primary)" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </div>

    <div>
        <div class="admin-stat-card__title">{{ $title }}</div>
        <div class="admin-stat-card__value">{{ $value }}</div>

        @if ($trend)
            <div class="admin-stat-card__trend">{{ $trend }}</div>
        @endif
    </div>
</article>
