@props([
    'user' => [
        'name' => 'Administrador',
        'email' => 'admin@example.com',
        'role' => 'Administrador',
    ],
])

@php
    $navConfig = [
        [
            'label' => 'Clientes',
            'route_name' => 'admin.clients.index',
            'active_pattern' => 'admin.clients.*',
        ],
        [
            'label' => 'Gestão de créditos',
            'route_name' => 'admin.payments.index',
            'active_pattern' => 'admin.payments.*',
        ],
        [
            'label' => 'Relatórios',
            'route_name' => 'admin.reports.index',
            'active_pattern' => 'admin.reports.*',
        ],
        [
            'label' => 'Teste Planilha',
            'route_name' => 'admin.teste-planilha.index',
            'active_pattern' => 'admin.teste-planilha.*',
        ],
        [
            'label' => 'Configurações',
            'route_name' => 'admin.settings.index',
            'active_pattern' => 'admin.settings.*',
        ],
    ];

    $navItems = collect($navConfig)
        ->filter(fn ($item) => Route::has($item['route_name']))
        ->map(fn ($item) => [
            'label' => $item['label'],
            'route' => route($item['route_name']),
            'active' => request()->routeIs($item['active_pattern']),
        ])
        ->values();

    $initials = collect(explode(' ', trim($user['name'] ?? 'A')))
        ->filter()
        ->map(static fn ($segment) => mb_substr($segment, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<header class="admin-topbar">
    <style>
        .admin-topbar {
            background: var(--brand-primary);
            color: #fff;
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
        }

        .admin-topbar__brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-topbar__logo {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .admin-topbar__logo-image {
            display: block;
            height: 42px;
            width: auto;
        }

        .admin-topbar nav {
            display: inline-flex;
            align-items: center;
            gap: 24px;
        }

        .admin-topbar__link {
            position: relative;
            font-size: 15px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            padding: 10px 0;
            transition: color 160ms ease;
        }

        .admin-topbar__link:hover,
        .admin-topbar__link:focus {
            color: #fff;
        }

        .admin-topbar__link.is-active {
            color: #fff;
        }

        .admin-topbar__link.is-active::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 100%;
            height: 3px;
            border-radius: 999px;
            background: #fff;
        }

        .admin-topbar__user {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-topbar__avatar {
            width: 54px;
            height: 54px;
            border-radius: 999px;
            background: #fff;
            display: grid;
            place-items: center;
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 18px;
        }

        .admin-topbar__user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 14px;
        }

        .admin-topbar__logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            border: none;
            cursor: pointer;
            color: #fff;
            transition: background-color 160ms ease, transform 160ms ease;
        }

        .admin-topbar__logout:hover {
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-1px);
        }

        @media (max-width: 1024px) {
            .admin-topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="admin-topbar__brand">
        <a href="{{ route('admin.clients.index') }}" class="admin-topbar__logo" aria-label="Início">
            <img src="{{ asset('backend/public/images/logoLL.png') }}" alt="Logo LL" class="admin-topbar__logo-image">
        </a>

        <nav aria-label="Navegação principal">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['route'] }}"
                    class="admin-topbar__link {{ $item['active'] ? 'is-active' : '' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="admin-topbar__user">
        <div class="admin-topbar__avatar">
            {{ $initials ?: 'A' }}
        </div>

        <div class="admin-topbar__user-info">
            <strong>{{ $user['name'] ?? 'Administrador' }}</strong>
            <span style="color: rgba(255, 255, 255, 0.85); font-size: 13px;">
                {{ $user['email'] ?? 'sem-email@dominio.com' }}
            </span>
            <span style="color: rgba(255, 255, 255, 0.75); font-size: 13px;">
                {{ $user['role'] ?? 'Administrador' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="admin-topbar__logout" title="Sair">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M9 6V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-1"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M15 12H4m0 0 3 3m-3-3 3-3" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </form>
    </div>
</header>
