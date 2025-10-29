@extends('admin.layouts.app')

@section('content')
    <header style="margin-bottom: 32px;">
        <h1 style="margin: 0; font-size: 34px; font-weight: 600; color: var(--text-strong);">Clientes</h1>
        <p style="margin: 8px 0 0; color: var(--text-muted); font-size: 15px;">
            Visão geral dos usuários cadastrados na plataforma.
        </p>
    </header>

    <section class="stat-grid" style="margin-bottom: 40px;">
        @foreach ($stats as $stat)
            <x-admin.stat-card :title="$stat['title']" :value="$stat['value']" :trend="$stat['trend']" />
        @endforeach
    </section>

    <section class="admin-card" style="padding: 24px 28px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 18px; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <button type="button" style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 18px;
                background: #eef2f9;
                border: 1px solid #d7deeb;
                color: var(--text-default);
                border-radius: 12px;
                font-weight: 600;
                cursor: pointer;
            ">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M2.667 4h10.666M4 4c0 3.2 2.133 5.333 4 5.333S12 7.2 12 4M6 12h4"
                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Filtros
            </button>

            <button type="button" style="
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 12px 20px;
                border-radius: 12px;
                border: none;
                font-weight: 600;
                font-size: 14px;
                letter-spacing: 0.02em;
                cursor: pointer;
                background: var(--brand-primary);
                color: #fff;
                box-shadow: 0 12px 24px rgba(11, 78, 162, 0.28);
            ">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 3.333v9.334M3.333 8h9.334" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Adicionar usuário
            </button>

            <span style="font-size: 14px; color: var(--text-muted);">
                {{ $selectedCount }} usuário selecionado
            </span>
        </div>

        <label style="
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid #d7deeb;
            min-width: 260px;
        ">
            <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                <path d="M18 18l-4.35-4.35m1.35-4.65a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" stroke="#8193ae"
                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <input
                type="search"
                placeholder="Pesquisar"
                style="
                    border: none;
                    outline: none;
                    font-size: 14px;
                    background: transparent;
                    width: 100%;
                    color: var(--text-default);
                "
            />
        </label>
    </section>

    <section class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 52px;">
                        <input
                            type="checkbox"
                            checked
                            style="width: 18px; height: 18px; border-radius: 6px; border: 1px solid #c0d3f3; accent-color: var(--brand-primary);"
                        />
                    </th>
                    <th>Nome do Usuário</th>
                    <th>Status</th>
                    <th>Créditos disponíveis</th>
                    <th>Data do último acesso</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $index => $client)
                    <x-admin.client-row :client="$client" :checked="$loop->first" />
                @endforeach
            </tbody>
        </table>

        <footer style="
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-top: 1px solid #ecf1f8;
            font-size: 13px;
            color: var(--text-muted);
        ">
            <span>Página {{ $pagination['current_page'] }} de {{ $pagination['last_page'] }}</span>

            <button type="button" style="
                padding: 10px 18px;
                border-radius: 12px;
                border: 1px solid #d0d9e3;
                background: #f7f9fc;
                font-weight: 600;
                color: var(--text-default);
                cursor: pointer;
            ">
                Próximo
            </button>
        </footer>
    </section>
@endsection
