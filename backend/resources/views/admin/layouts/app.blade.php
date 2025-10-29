<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} · Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <style>
            :root {
                --brand-primary: #0b4ea2;
                --brand-primary-hover: #093f82;
                --brand-light: #eff4ff;
                --text-strong: #12263a;
                --text-default: #475569;
                --text-muted: #6b7280;
                --surface: #ffffff;
                --surface-muted: #f3f5f9;
                --border: #d0d9e3;
                font-family: 'Instrument Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--surface-muted);
                color: var(--text-default);
                font-family: inherit;
            }

            a {
                color: inherit;
            }

            .admin-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 40px 32px 64px;
            }

            .admin-card {
                background: var(--surface);
                border-radius: 18px;
                box-shadow:
                    0 24px 48px rgba(15, 23, 42, 0.08),
                    0 1px 0 rgba(255, 255, 255, 0.6);
            }

            .stat-grid {
                display: grid;
                gap: 20px;
            }

            @media (min-width: 1024px) {
                .stat-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            .table-wrapper {
                overflow: hidden;
                border-radius: 16px;
                background: var(--surface);
                box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            thead th {
                text-align: left;
                padding: 18px 24px;
                font-size: 13px;
                font-weight: 600;
                color: var(--text-muted);
                background: #f7f9fc;
            }

            tbody tr + tr td {
                border-top: 1px solid #ecf1f8;
            }

            tbody td {
                padding: 16px 24px;
                font-size: 14px;
                vertical-align: middle;
            }

            .status-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-weight: 600;
                font-size: 13px;
                padding: 6px 14px;
                border-radius: 999px;
                background: #defce6;
                color: #0a7a2f;
            }

            .status-pill.inactive {
                background: #fee2e2;
                color: #b91c1c;
            }

            .action-buttons {
                display: inline-flex;
                gap: 10px;
            }

            .action-icon {
                width: 32px;
                height: 32px;
                border-radius: 10px;
                background: transparent;
                border: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color 160ms ease, transform 160ms ease;
            }

            .action-icon:hover {
                background: rgba(15, 23, 42, 0.08);
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body>
        @php($adminUser = session('admin_user', ['name' => 'Lucas', 'role' => 'Administrador']))

        <div class="min-h-screen">
            <x-admin.topbar :user="$adminUser" />

            <main class="admin-container">
                @yield('content')
            </main>
        </div>
    </body>
</html>
