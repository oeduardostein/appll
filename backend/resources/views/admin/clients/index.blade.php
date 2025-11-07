@extends('admin.layouts.app')

@section('content')
    <style>
        .admin-toolbar {
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .admin-toolbar__left {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .admin-toolbar__selection {
            font-size: 14px;
            color: var(--text-muted);
        }

        .admin-selection-actions {
            display: none;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .admin-selection-actions.is-visible {
            display: inline-flex;
        }

        .admin-filter-summary {
            font-size: 13px;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px solid #d7deeb;
            border-radius: 999px;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: border-color 160ms ease, color 160ms ease, background-color 160ms ease;
        }

        .admin-filter-summary.is-active {
            background: rgba(11, 78, 162, 0.08);
            color: var(--brand-primary);
            border-color: rgba(11, 78, 162, 0.3);
            font-weight: 600;
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .admin-button svg {
            flex-shrink: 0;
        }

        .admin-button--ghost {
            background: #eef2f9;
            border: 1px solid #d7deeb;
            color: var(--text-default);
        }

        .admin-button--ghost:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .admin-button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.28);
        }

        .admin-button--primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .admin-button--link {
            background: transparent;
            color: var(--brand-primary);
            border: none;
            padding: 8px 0;
            box-shadow: none;
        }

        .admin-button--link:hover {
            background: transparent;
            color: var(--brand-primary-hover);
            transform: none;
        }

        .admin-button:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .admin-search {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid #d7deeb;
            min-width: 260px;
        }

        .admin-search input {
            border: none;
            outline: none;
            font-size: 14px;
            background: transparent;
            width: 100%;
            color: var(--text-default);
        }

        .admin-search button {
            display: none;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table__empty {
            padding: 32px;
            text-align: center;
            color: var(--text-muted);
            font-size: 15px;
        }

        .admin-table__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-top: 1px solid #ecf1f8;
            font-size: 13px;
            color: var(--text-muted);
        }

        .admin-table__pagination {
            display: inline-flex;
            gap: 10px;
        }

        .admin-checkbox {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .admin-checkbox input {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 1px solid #c0d3f3;
            accent-color: var(--brand-primary);
            cursor: pointer;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-user__avatar {
            width: 46px;
            height: 46px;
            border-radius: 999px;
            background: #d9e5fb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--brand-primary);
            font-size: 16px;
        }

        .admin-user__info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .admin-user__info strong {
            display: block;
            font-size: 15px;
            color: var(--text-strong);
        }

        .admin-user__info span {
            font-size: 13px;
            color: var(--text-muted);
        }

        .admin-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .admin-modal.is-visible {
            display: flex;
        }

        .admin-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(12, 22, 36, 0.55);
        }

        .admin-modal__panel {
            position: relative;
            background: var(--surface);
            border-radius: 18px;
            width: min(500px, 92vw);
            padding: 28px 32px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.18),
                0 1px 0 rgba(255, 255, 255, 0.6);
            max-height: 90vh;
            overflow-y: auto;
        }

        .admin-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .admin-modal__header h2 {
            margin: 0;
            font-size: 22px;
            color: var(--text-strong);
        }

        .admin-modal__close {
            border: none;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            color: var(--text-muted);
        }

        .admin-modal__form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .admin-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .admin-field label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .admin-field input,
        .admin-field select {
            border: 1px solid #d0d9e3;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            background: var(--surface);
            color: var(--text-default);
        }

        .admin-field input:focus,
        .admin-field select:focus {
            outline: 2px solid rgba(11, 78, 162, 0.25);
            border-color: var(--brand-primary);
        }

        .admin-modal__hint {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }

        .admin-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 8px;
        }

        .admin-modal__actions--between {
            justify-content: space-between;
            align-items: center;
        }

        .admin-modal__action-group {
            display: inline-flex;
            gap: 12px;
        }

        .form-feedback {
            margin-top: -6px;
            font-size: 13px;
            color: #b91c1c;
            min-height: 18px;
        }

        .admin-button--danger {
            background: #dc2626;
            color: #fff;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.18);
        }

        .admin-button--danger:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none;
        }

        .admin-modal__filters-grid {
            display: grid;
            gap: 18px;
        }

        .admin-field--inline {
            gap: 12px;
        }

        .admin-field--inline > div {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .admin-field--inline input {
            flex: 1 1 160px;
        }

        .admin-permissions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .admin-permission-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #d7deeb;
            background: var(--surface);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-default);
            transition: all 160ms ease;
        }

        .admin-permission-chip input {
            opacity: 0;
            position: absolute;
            pointer-events: none;
        }

        .admin-permission-chip span {
            line-height: 1.2;
        }

        .admin-permission-chip.is-checked {
            border-color: rgba(11, 78, 162, 0.4);
            background: rgba(11, 78, 162, 0.08);
            color: var(--brand-primary);
        }
    </style>

    <header style="margin-bottom: 32px;">
        <h1 style="margin: 0; font-size: 34px; font-weight: 600; color: var(--text-strong);">Clientes</h1>
        <p style="margin: 8px 0 0; color: var(--text-muted); font-size: 15px;">
            Visão geral dos usuários cadastrados na plataforma.
        </p>
    </header>

    <section class="stat-grid" style="margin-bottom: 40px;">
        @foreach ($stats as $stat)
            <x-admin.stat-card
                :title="$stat['title']"
                :value="$stat['value']"
                :trend="$stat['trend']"
                :data-stat-key="$stat['key']"
            />
        @endforeach
    </section>

    <section class="admin-card admin-toolbar">
        <div class="admin-toolbar__left">
            <button type="button" class="admin-button admin-button--ghost" data-action="open-filters">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M2.667 4h10.666M4 4c0 3.2 2.133 5.333 4 5.333S12 7.2 12 4M6 12h4"
                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Filtros
            </button>

            <button type="button" class="admin-button admin-button--primary" data-action="open-create-modal">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 3.333v9.334M3.333 8h9.334" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Adicionar usuário
            </button>

            <span class="admin-filter-summary" data-filter-summary>Sem filtros aplicados</span>

            <span class="admin-toolbar__selection" data-selected-count>
                0 usuário selecionado
            </span>

            <div class="admin-selection-actions" data-selection-actions>
                <button type="button" class="admin-button admin-button--ghost" data-action="bulk-status">
                    Alterar status
                </button>
                <button type="button" class="admin-button admin-button--danger" data-action="bulk-delete">
                    Excluir selecionados
                </button>
            </div>
        </div>

        <form class="admin-search" data-search-form>
            <svg width="17" height="17" viewBox="0 0 20 20" fill="none">
                <path d="M18 18l-4.35-4.35m1.35-4.65a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" stroke="#8193ae"
                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <input type="search" placeholder="Pesquisar" data-search-input />
            <button type="submit" aria-label="Pesquisar"></button>
        </form>
    </section>

    <section class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 52px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" data-select-all />
                        </label>
                    </th>
                    <th>Nome do Usuário</th>
                    <th>Status</th>
                    <th>Créditos utilizados</th>
                    <th>Data do último acesso</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <tbody data-users-list>
                <tr data-empty-state>
                    <td colspan="6" class="admin-table__empty">
                        Nenhum usuário encontrado.
                    </td>
                </tr>
            </tbody>
        </table>

        <footer class="admin-table__footer">
            <span data-pagination-label>Página 1 de 1</span>

            <div class="admin-table__pagination">
                <button type="button" class="admin-button admin-button--ghost" data-action="prev-page">Anterior</button>
                <button type="button" class="admin-button admin-button--ghost" data-action="next-page">Próximo</button>
            </div>
        </footer>
    </section>

    <div class="admin-modal" data-modal="filters" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="filter-users-title">
            <header class="admin-modal__header">
                <h2 id="filter-users-title">Filtros avançados</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="filter-users-form" class="admin-modal__form">
                <div class="admin-modal__filters-grid">
                    <div class="admin-field">
                        <label for="filter-status">Status do usuário</label>
                        <select id="filter-status" name="status">
                            <option value="all">Todos</option>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>

                    <div class="admin-field admin-field--inline">
                        <label for="filter-created-from">Período de cadastro</label>
                        <div>
                            <input id="filter-created-from" name="created_from" type="date" />
                            <input id="filter-created-to" name="created_to" type="date" />
                        </div>
                    </div>

                </div>

                <div class="form-feedback" data-form-error="filters"></div>

                <div class="admin-modal__actions admin-modal__actions--between">
                    <button type="button" class="admin-button admin-button--link" data-action="reset-filters">
                        Limpar filtros
                    </button>

                    <div class="admin-modal__action-group">
                        <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                        <button type="submit" class="admin-button admin-button--primary" data-submit-label="Aplicar filtros">
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-modal" data-modal="create" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
            <header class="admin-modal__header">
                <h2 id="create-user-title">Cadastrar usuário</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="create-user-form" class="admin-modal__form">
                <div class="admin-field">
                    <label for="create-user-name">Nome completo</label>
                    <input id="create-user-name" name="name" type="text" placeholder="Nome do usuário" required />
                </div>

                <div class="admin-field">
                    <label for="create-user-email">E-mail</label>
                    <input id="create-user-email" name="email" type="email" placeholder="email@dominio.com" required />
                </div>

                <div class="admin-field">
                    <label for="create-user-password">Senha inicial</label>
                    <input id="create-user-password" name="password" type="password" placeholder="mínimo 8 caracteres" required />
                </div>

                <div class="admin-field">
                    <label for="create-user-status">Status</label>
                    <select id="create-user-status" name="is_active" required>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label>Acessos liberados</label>
                    <div class="admin-permissions-grid" data-create-permissions></div>
                    <p class="admin-modal__hint">
                        Escolha as telas do aplicativo que o usuário poderá acessar.
                    </p>
                </div>

                <p class="admin-modal__hint">
                    O usuário receberá um e-mail com as credenciais cadastradas.
                </p>

                <div class="form-feedback" data-form-error="create"></div>

                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--primary" data-submit-label="Salvar usuário">
                        Salvar usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-modal" data-modal="edit" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="edit-user-title">
            <header class="admin-modal__header">
                <h2 id="edit-user-title">Editar usuário</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="edit-user-form" class="admin-modal__form">
                <div class="admin-field">
                    <label for="edit-user-name">Nome completo</label>
                    <input id="edit-user-name" name="name" type="text" required />
                </div>

                <div class="admin-field">
                    <label for="edit-user-email">E-mail</label>
                    <input id="edit-user-email" name="email" type="email" required />
                </div>

                <div class="admin-field">
                    <label for="edit-user-password">Senha</label>
                    <input id="edit-user-password" name="password" type="password" placeholder="Deixe em branco para manter" />
                </div>

                <div class="admin-field">
                    <label for="edit-user-status">Status</label>
                    <select id="edit-user-status" name="is_active" required>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label>Permissões de acesso</label>
                    <div class="admin-permissions-grid" data-edit-permissions></div>
                </div>

                <p class="admin-modal__hint">
                    Último acesso registrado:
                    <strong data-edit-last-access>—</strong>
                </p>

                <div class="form-feedback" data-form-error="edit"></div>

                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--primary" data-submit-label="Atualizar usuário">
                        Atualizar usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-modal" data-modal="delete" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="delete-user-title">
            <header class="admin-modal__header">
                <h2 id="delete-user-title">Excluir usuário</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="delete-user-form" class="admin-modal__form">
                <p class="admin-modal__hint">
                    Confirme a exclusão do usuário <strong data-delete-user-name></strong>.
                </p>
                <p class="admin-modal__hint">
                    E-mail: <span data-delete-user-email></span>
                </p>

                <div class="form-feedback" data-form-error="delete"></div>

                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--danger" data-submit-label="Excluir usuário">
                        Excluir usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-modal" data-modal="bulk-status" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="bulk-status-title">
            <header class="admin-modal__header">
                <h2 id="bulk-status-title">Alterar status</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="bulk-status-form" class="admin-modal__form">
                <p class="admin-modal__hint">
                    Você selecionou <strong data-bulk-status-count>0 usuários</strong>.
                    Escolha o novo status para aplicar a todos.
                </p>

                <div class="admin-field">
                    <label for="bulk-status-select">Status</label>
                    <select id="bulk-status-select" name="is_active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>

                <div class="form-feedback" data-form-error="bulk-status"></div>

                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--primary" data-submit-label="Aplicar status">
                        Aplicar status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-modal" data-modal="bulk-delete" aria-hidden="true">
        <div class="admin-modal__backdrop" data-modal-close></div>
        <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title">
            <header class="admin-modal__header">
                <h2 id="bulk-delete-title">Excluir usuários selecionados</h2>
                <button type="button" class="admin-modal__close" data-modal-close aria-label="Fechar">×</button>
            </header>

            <form id="bulk-delete-form" class="admin-modal__form">
                <p class="admin-modal__hint">
                    Tem certeza de que deseja excluir <strong data-bulk-delete-count>0 usuários</strong>?
                    Esta ação não pode ser desfeita.
                </p>

                <div class="form-feedback" data-form-error="bulk-delete"></div>

                <div class="admin-modal__actions">
                    <button type="button" class="admin-button admin-button--ghost" data-modal-close>Cancelar</button>
                    <button type="submit" class="admin-button admin-button--danger" data-submit-label="Excluir usuários">
                        Excluir usuários
                    </button>
                </div>
            </form>
        </div>
    </div>

    <template id="user-row-template">
        <tr data-row>
            <td style="width: 52px;">
                <label class="admin-checkbox">
                    <input type="checkbox" data-user-select />
                </label>
            </td>
            <td>
                <div class="admin-user">
                    <span class="admin-user__avatar" data-user-avatar>U</span>
                    <div class="admin-user__info">
                        <strong data-user-name>Nome</strong>
                        <span data-user-email>email@dominio.com</span>
                    </div>
                </div>
            </td>
            <td>
                <span class="status-pill" data-user-status>Ativo</span>
            </td>
            <td>
                <span data-user-credits-used>0 créditos utilizados</span>
            </td>
            <td>
                <span data-user-last-login>—</span>
            </td>
            <td style="width: 120px;">
                <div class="action-buttons">
                    <button type="button" class="action-icon" title="Editar" data-action="edit-user">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M12 20h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button type="button" class="action-icon" title="Excluir" data-action="delete-user">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            <path d="M10 11v6m4-6v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path
                                d="M6 7h12l-.8 11.2A2 2 0 0 1 15.21 20H8.79a2 2 0 0 1-1.99-1.8L6 7Zm3-3h6a1 1 0 0 1 1 1v2H8V5a1 1 0 0 1 1-1Z"
                                stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    </template>

    <script>
        window.adminUsersState = {
            data: @json($initialUsers),
            pagination: @json($initialPagination),
            metrics: @json($initialMetrics),
        };
    </script>

    <script>
        window.availablePermissions = @json($availablePermissions);
    </script>

    <script>
        (() => {
            const defaultFilters = {
                status: 'all',
                created_from: '',
                created_to: '',
            };
            const availablePermissions = window.availablePermissions ?? [];

            const state = {
                users: window.adminUsersState?.data ?? [],
                pagination: window.adminUsersState?.pagination ?? {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                },
                metrics: window.adminUsersState?.metrics ?? {
                    total: 0,
                    active: 0,
                    inactive: 0,
                    new_this_month: 0,
                },
                selectedIds: [],
                search: '',
                perPage: window.adminUsersState?.pagination?.per_page ?? 10,
                filters: { ...defaultFilters, ...(window.adminUsersState?.filters ?? {}) },
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            const elements = {
                tbody: document.querySelector('[data-users-list]'),
                selectedCount: document.querySelector('[data-selected-count]'),
                selectionActions: document.querySelector('[data-selection-actions]'),
                paginationLabel: document.querySelector('[data-pagination-label]'),
                prevButton: document.querySelector('[data-action="prev-page"]'),
                nextButton: document.querySelector('[data-action="next-page"]'),
                selectAll: document.querySelector('[data-select-all]'),
                searchInput: document.querySelector('[data-search-input]'),
                searchForm: document.querySelector('[data-search-form]'),
                createModal: document.querySelector('[data-modal="create"]'),
                editModal: document.querySelector('[data-modal="edit"]'),
                deleteModal: document.querySelector('[data-modal="delete"]'),
                filterModal: document.querySelector('[data-modal="filters"]'),
                createForm: document.getElementById('create-user-form'),
                editForm: document.getElementById('edit-user-form'),
                deleteForm: document.getElementById('delete-user-form'),
                filterForm: document.getElementById('filter-users-form'),
                filterSummary: document.querySelector('[data-filter-summary]'),
                resetFiltersButton: document.querySelector('[data-action="reset-filters"]'),
                editLastAccess: document.querySelector('[data-edit-last-access]'),
                deleteUserName: document.querySelector('[data-delete-user-name]'),
                deleteUserEmail: document.querySelector('[data-delete-user-email]'),
                bulkStatusButton: document.querySelector('[data-action="bulk-status"]'),
                bulkDeleteButton: document.querySelector('[data-action="bulk-delete"]'),
                bulkStatusForm: document.getElementById('bulk-status-form'),
                bulkDeleteForm: document.getElementById('bulk-delete-form'),
                bulkStatusCount: document.querySelector('[data-bulk-status-count]'),
                bulkDeleteCount: document.querySelector('[data-bulk-delete-count]'),
                createPermissionsContainer: document.querySelector('[data-create-permissions]'),
                editPermissionsContainer: document.querySelector('[data-edit-permissions]'),
            };

            let currentUser = null;
            let userToDelete = null;
            let searchTimer = null;

            const endpoints = {
                list(page = 1, search = '') {
                    const url = new URL('{{ url('/admin/users') }}');
                    url.searchParams.set('page', page);
                    url.searchParams.set('per_page', state.perPage);

                    if (search) {
                        url.searchParams.set('search', search);
                    }

                    Object.entries(state.filters).forEach(([key, value]) => {
                        if (value === null || value === undefined || value === '') {
                            return;
                        }

                        if (key === 'status' && value === 'all') {
                            return;
                        }

                        url.searchParams.set(key, value);
                    });

                    return url.toString();
                },
                store: '{{ url('/admin/users') }}',
                update(id) {
                    return '{{ url('/admin/users') }}/' + id;
                },
                delete(id) {
                    return '{{ url('/admin/users') }}/' + id;
                },
                bulkStatus() {
                    return '{{ url('/admin/users/bulk/status') }}';
                },
                bulkDelete() {
                    return '{{ url('/admin/users/bulk') }}';
                },
            };

            function buildPermissionOptions(container, prefix) {
                if (!container) {
                    return;
                }

                container.innerHTML = '';
                availablePermissions.forEach((permission) => {
                    const chip = document.createElement('label');
                    chip.className = 'admin-permission-chip';
                    chip.dataset.permissionId = permission.id;

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = permission.id;
                    checkbox.id = `${prefix}-permission-${permission.id}`;

                    const text = document.createElement('span');
                    text.textContent = permission.name;

                    checkbox.addEventListener('change', () => {
                        chip.classList.toggle('is-checked', checkbox.checked);
                    });

                    chip.appendChild(checkbox);
                    chip.appendChild(text);
                    container.appendChild(chip);
                });
            }

            function setPermissionSelections(container, selected) {
                if (!container) {
                    return;
                }
                const selectedIds = new Set(
                    (selected ?? [])
                        .map((id) => Number(id))
                        .filter((id) => !Number.isNaN(id)),
                );

                container.querySelectorAll('label.admin-permission-chip').forEach((chip) => {
                    const input = chip.querySelector('input[type="checkbox"]');
                    if (!input) {
                        return;
                    }
                    const id = Number(input.value);
                    const isChecked = selectedIds.has(id);
                    input.checked = isChecked;
                    chip.classList.toggle('is-checked', isChecked);
                });
            }

            function getSelectedPermissions(container) {
                if (!container) {
                    return [];
                }

                return Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
                    .map((input) => Number(input.value))
                    .filter((id) => !Number.isNaN(id));
            }

            buildPermissionOptions(elements.createPermissionsContainer, 'create');
            buildPermissionOptions(elements.editPermissionsContainer, 'edit');

            function formatUserCount(count) {
                return `${count} usuário${count === 1 ? '' : 's'} selecionado${count === 1 ? '' : 's'}`;
            }

            function formatSelectionLabel(count) {
                return `${count} usuário${count === 1 ? '' : 's'}`;
            }

            function toggleSelectionActions(count) {
                if (!elements.selectionActions) {
                    return;
                }

                if (count > 0) {
                    elements.selectionActions.classList.add('is-visible');
                } else {
                    elements.selectionActions.classList.remove('is-visible');
                }
            }

            function updateSelectedState() {
                const checkboxes = Array.from(elements.tbody.querySelectorAll('input[data-user-select]'));
                const checked = checkboxes.filter((checkbox) => checkbox.checked);

                state.selectedIds = checked
                    .map((checkbox) => Number(checkbox.dataset.userId ?? checkbox.value))
                    .filter((id) => !Number.isNaN(id));

                const count = state.selectedIds.length;
                elements.selectedCount.textContent = count ? formatUserCount(count) : '0 usuário selecionado';
                elements.selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
                toggleSelectionActions(count);
            }

            function resetSelection() {
                state.selectedIds = [];
                elements.selectAll.checked = false;
                elements.selectedCount.textContent = '0 usuário selecionado';
                Array.from(elements.tbody.querySelectorAll('input[data-user-select]')).forEach((checkbox) => {
                    checkbox.checked = false;
                });
                toggleSelectionActions(0);
            }

            function hasSelection() {
                return Array.isArray(state.selectedIds) && state.selectedIds.length > 0;
            }

            function formatDateLabel(value) {
                if (!value) {
                    return null;
                }

                const parts = value.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }

                return value;
            }

            function updateFilterSummary() {
                if (!elements.filterSummary) {
                    return;
                }

                const summaryParts = [];

                if (state.filters.status && state.filters.status !== 'all') {
                    summaryParts.push(`Status: ${state.filters.status === 'active' ? 'Ativo' : 'Inativo'}`);
                }

                if (state.filters.created_from || state.filters.created_to) {
                    const fromLabel = formatDateLabel(state.filters.created_from) ?? 'início';
                    const toLabel = formatDateLabel(state.filters.created_to) ?? 'hoje';
                    summaryParts.push(`Período: ${fromLabel} a ${toLabel}`);
                }

                if (summaryParts.length === 0) {
                    elements.filterSummary.textContent = 'Sem filtros aplicados';
                    elements.filterSummary.classList.remove('is-active');
                    return;
                }

                elements.filterSummary.textContent = summaryParts.join(' · ');
                elements.filterSummary.classList.add('is-active');
            }

            function syncFilterForm() {
                if (!elements.filterForm) {
                    return;
                }

                const form = elements.filterForm;
                const setValue = (selector, value) => {
                    const field = form.querySelector(selector);
                    if (field) {
                        field.value = value ?? '';
                    }
                };

                setValue('[name="status"]', state.filters.status ?? 'all');
                setValue('[name="created_from"]', state.filters.created_from ?? '');
                setValue('[name="created_to"]', state.filters.created_to ?? '');
            }

            function resetFilters(apply = false) {
                state.filters = { ...defaultFilters };
                syncFilterForm();
                updateFilterSummary();
                setFormError('filters', '');

                if (apply) {
                    closeModal('filters');
                    fetchUsers(1);
                }
            }

            function renderStats() {
                const statsMapping = {
                    active: state.metrics.active ?? 0,
                    new_this_month: state.metrics.new_this_month ?? 0,
                    inactive: state.metrics.inactive ?? 0,
                };

                Object.entries(statsMapping).forEach(([key, value]) => {
                    const card = document.querySelector(`[data-stat-key="${key}"]`);
                    if (!card) {
                        return;
                    }

                    const valueElement = card.querySelector('.admin-stat-card__value');
                    if (valueElement) {
                        valueElement.textContent = `${value} usuário${value === 1 ? '' : 's'}`;
                    }
                });
            }

            function clearTableBody() {
                while (elements.tbody.firstChild) {
                    elements.tbody.removeChild(elements.tbody.firstChild);
                }
            }

            function createRow(user) {
                const template = document.getElementById('user-row-template');
                if (!template) {
                    return null;
                }

                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('[data-row]');
                if (!row) {
                    return null;
                }

                row.dataset.userId = String(user.id);

                const checkbox = row.querySelector('[data-user-select]');
                if (checkbox) {
                    checkbox.dataset.userId = String(user.id);
                    checkbox.value = String(user.id);
                }

                const nameElement = row.querySelector('[data-user-name]');
                if (nameElement) {
                    nameElement.textContent = user.name ?? 'Usuário sem nome';
                }

                const emailElement = row.querySelector('[data-user-email]');
                if (emailElement) {
                    emailElement.textContent = user.email ?? '—';
                }

                const avatarElement = row.querySelector('[data-user-avatar]');
                if (avatarElement) {
                    avatarElement.textContent = user.initials ?? 'U';
                }

                const statusElement = row.querySelector('[data-user-status]');
                if (statusElement) {
                    statusElement.textContent = user.status_label ?? (user.is_active ? 'Ativo' : 'Inativo');
                    statusElement.classList.toggle('inactive', !user.is_active);
                }

                const creditsElement = row.querySelector('[data-user-credits-used]');
                if (creditsElement) {
                    creditsElement.textContent =
                        user.credits_used_label ??
                        `${user.credits_used ?? 0} créditos utilizados`;
                }

                const lastLoginElement = row.querySelector('[data-user-last-login]');
                if (lastLoginElement) {
                    lastLoginElement.textContent = user.last_login_label ?? 'Nunca acessou';
                }

                const editButton = row.querySelector('[data-action="edit-user"]');
                if (editButton) {
                    editButton.addEventListener('click', () => openEditModal(user));
                }

                const deleteButton = row.querySelector('[data-action="delete-user"]');
                if (deleteButton) {
                    deleteButton.addEventListener('click', () => openDeleteModal(user));
                }

                const checkboxElement = row.querySelector('input[data-user-select]');
                if (checkboxElement) {
                    checkboxElement.addEventListener('change', updateSelectedState);
                }

                return fragment;
            }

            function renderTable() {
                clearTableBody();

                if (!state.users.length) {
                    const emptyRow = document.createElement('tr');
                    const emptyCell = document.createElement('td');
                    emptyCell.colSpan = 6;
                    emptyCell.className = 'admin-table__empty';
                    emptyCell.textContent = 'Nenhum usuário encontrado.';
                    emptyRow.appendChild(emptyCell);
                    elements.tbody.appendChild(emptyRow);
                } else {
                    state.users.forEach((user) => {
                        const row = createRow(user);
                        if (row) {
                            elements.tbody.appendChild(row);
                        }
                    });
                }

                elements.paginationLabel.textContent = `Página ${state.pagination.current_page} de ${Math.max(state.pagination.last_page, 1)}`;
                elements.prevButton.disabled = state.pagination.current_page <= 1;
                elements.nextButton.disabled = state.pagination.current_page >= state.pagination.last_page;

                resetSelection();
            }

            function openModal(name) {
                const modal = document.querySelector(`[data-modal="${name}"]`);
                if (!modal) {
                    return;
                }

                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal(name) {
                const modal = document.querySelector(`[data-modal="${name}"]`);
                if (!modal) {
                    return;
                }

                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            }

            function setFormError(formKey, message) {
                const feedback = document.querySelector(`[data-form-error="${formKey}"]`);
                if (feedback) {
                    feedback.textContent = message ?? '';
                }
            }

            function setSubmitting(form, isSubmitting) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (!submitButton) {
                    return;
                }

                const originalLabel = submitButton.getAttribute('data-submit-label') ?? submitButton.textContent;

                if (isSubmitting) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Processando...';
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }
            }

            async function handleRequest(url, options = {}) {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    ...options,
                });

                if (response.status === 401) {
                    window.location.href = '{{ route('admin.login') }}';
                    return null;
                }

                const responseText = await response.text();
                const payload = responseText ? JSON.parse(responseText) : null;

                if (!response.ok) {
                    const error = new Error(payload?.message ?? 'Não foi possível processar a solicitação.');
                    error.details = payload?.errors ?? null;
                    throw error;
                }

                return payload;
            }

            async function fetchUsers(page = state.pagination.current_page) {
                const url = endpoints.list(page, state.search);
                const payload = await handleRequest(url, { method: 'GET' });

                if (!payload) {
                    return;
                }

                state.users = payload.data ?? [];
                state.pagination = payload.meta?.pagination ?? state.pagination;
                state.metrics = payload.meta?.stats ?? state.metrics;
                if (payload.meta?.filters) {
                    state.filters = {
                        ...defaultFilters,
                        ...payload.meta.filters,
                    };
                }

                renderStats();
                renderTable();
                syncFilterForm();
                updateFilterSummary();
            }

            function resetCreateForm() {
                elements.createForm.reset();
                setFormError('create', '');
                setPermissionSelections(elements.createPermissionsContainer, []);
            }

            function openEditModal(user) {
                currentUser = user;
                if (!elements.editForm) {
                    return;
                }

                elements.editForm.reset();
                elements.editForm.querySelector('[name="name"]').value = user.name ?? '';
                elements.editForm.querySelector('[name="email"]').value = user.email ?? '';
                elements.editForm.querySelector('[name="is_active"]').value = user.is_active ? '1' : '0';
                const passwordField = elements.editForm.querySelector('[name="password"]');
                if (passwordField) {
                    passwordField.value = '';
                }

                if (elements.editLastAccess) {
                    elements.editLastAccess.textContent = user.last_login_label ?? 'Nunca acessou';
                }
                setPermissionSelections(elements.editPermissionsContainer, user.permission_ids ?? []);

                setFormError('edit', '');
                openModal('edit');
            }

            function openDeleteModal(user) {
                userToDelete = user;

                if (elements.deleteUserName) {
                    elements.deleteUserName.textContent = user.name ?? 'Usuário';
                }

                if (elements.deleteUserEmail) {
                    elements.deleteUserEmail.textContent = user.email ?? '—';
                }

                setFormError('delete', '');
                openModal('delete');
            }

            function attachModalCloseHandlers() {
                document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        const modal = trigger.closest('.admin-modal');
                        if (modal) {
                            modal.classList.remove('is-visible');
                            modal.setAttribute('aria-hidden', 'true');
                        }
                    });
                });
            }

            elements.createForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                setFormError('create', '');

                const formData = new FormData(elements.createForm);
                const payload = {
                    name: String(formData.get('name') ?? '').trim(),
                    email: String(formData.get('email') ?? '').trim(),
                    password: String(formData.get('password') ?? ''),
                    is_active: formData.get('is_active') === '1',
                    permissions: getSelectedPermissions(elements.createPermissionsContainer),
                };

                setSubmitting(elements.createForm, true);

                try {
                    await handleRequest(endpoints.store, {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    closeModal('create');
                    resetCreateForm();
                    await fetchUsers(1);
                } catch (error) {
                    if (error.details) {
                        const firstError = Object.values(error.details)[0];
                        setFormError('create', Array.isArray(firstError) ? firstError[0] : String(firstError));
                    } else {
                        setFormError('create', error.message);
                    }
                } finally {
                    setSubmitting(elements.createForm, false);
                }
            });

            elements.editForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!currentUser) {
                    return;
                }

                setFormError('edit', '');
                const formData = new FormData(elements.editForm);
                const payload = {
                    name: String(formData.get('name') ?? '').trim(),
                    email: String(formData.get('email') ?? '').trim(),
                    is_active: formData.get('is_active') === '1',
                    permissions: getSelectedPermissions(elements.editPermissionsContainer),
                };

                const password = String(formData.get('password') ?? '');
                if (password !== '') {
                    payload.password = password;
                }

                if (currentUser.last_login_at) {
                    payload.last_login_at = currentUser.last_login_at;
                }

                setSubmitting(elements.editForm, true);

                try {
                    await handleRequest(endpoints.update(currentUser.id), {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                    closeModal('edit');
                    await fetchUsers(state.pagination.current_page);
                } catch (error) {
                    if (error.details) {
                        const firstError = Object.values(error.details)[0];
                        setFormError('edit', Array.isArray(firstError) ? firstError[0] : String(firstError));
                    } else {
                        setFormError('edit', error.message);
                    }
                } finally {
                    setSubmitting(elements.editForm, false);
                }
            });

            elements.deleteForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!userToDelete) {
                    return;
                }

                setFormError('delete', '');
                setSubmitting(elements.deleteForm, true);

                try {
                    await handleRequest(endpoints.delete(userToDelete.id), {
                        method: 'DELETE',
                    });
                    closeModal('delete');
                    const targetPage = state.users.length === 1 && state.pagination.current_page > 1
                        ? state.pagination.current_page - 1
                        : state.pagination.current_page;
                    await fetchUsers(targetPage);
                } catch (error) {
                    if (error.details) {
                        const firstError = Object.values(error.details)[0];
                        setFormError('delete', Array.isArray(firstError) ? firstError[0] : String(firstError));
                    } else {
                        setFormError('delete', error.message);
                    }
                } finally {
                    setSubmitting(elements.deleteForm, false);
                    userToDelete = null;
                }
            });

            elements.bulkStatusButton?.addEventListener('click', () => {
                if (!hasSelection()) {
                    return;
                }

                if (elements.bulkStatusForm) {
                    elements.bulkStatusForm.reset();
                }

                if (elements.bulkStatusCount) {
                    elements.bulkStatusCount.textContent = formatSelectionLabel(state.selectedIds.length);
                }

                setFormError('bulk-status', '');
                openModal('bulk-status');
            });

            elements.bulkStatusForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!hasSelection()) {
                    closeModal('bulk-status');
                    return;
                }

                const formData = new FormData(elements.bulkStatusForm);
                const isActive = formData.get('is_active') === '1';

                setFormError('bulk-status', '');
                setSubmitting(elements.bulkStatusForm, true);

                try {
                    await handleRequest(endpoints.bulkStatus(), {
                        method: 'POST',
                        body: JSON.stringify({
                            user_ids: state.selectedIds,
                            is_active: isActive,
                        }),
                    });

                    closeModal('bulk-status');
                    resetSelection();
                    await fetchUsers(state.pagination.current_page);
                } catch (error) {
                    if (error.details) {
                        const firstError = Object.values(error.details)[0];
                        setFormError('bulk-status', Array.isArray(firstError) ? firstError[0] : String(firstError));
                    } else {
                        setFormError('bulk-status', error.message);
                    }
                } finally {
                    setSubmitting(elements.bulkStatusForm, false);
                }
            });

            elements.bulkDeleteButton?.addEventListener('click', () => {
                if (!hasSelection()) {
                    return;
                }

                if (elements.bulkDeleteCount) {
                    elements.bulkDeleteCount.textContent = formatSelectionLabel(state.selectedIds.length);
                }

                setFormError('bulk-delete', '');
                openModal('bulk-delete');
            });

            elements.bulkDeleteForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!hasSelection()) {
                    closeModal('bulk-delete');
                    return;
                }

                const selectedCount = state.selectedIds.length;

                setFormError('bulk-delete', '');
                setSubmitting(elements.bulkDeleteForm, true);

                try {
                    await handleRequest(endpoints.bulkDelete(), {
                        method: 'DELETE',
                        body: JSON.stringify({
                            user_ids: state.selectedIds,
                        }),
                    });

                    closeModal('bulk-delete');
                    const willEmptyPage = selectedCount >= state.users.length && state.pagination.current_page > 1;
                    const targetPage = willEmptyPage
                        ? state.pagination.current_page - 1
                        : state.pagination.current_page;
                    resetSelection();
                    await fetchUsers(targetPage);
                } catch (error) {
                    if (error.details) {
                        const firstError = Object.values(error.details)[0];
                        setFormError('bulk-delete', Array.isArray(firstError) ? firstError[0] : String(firstError));
                    } else {
                        setFormError('bulk-delete', error.message);
                    }
                } finally {
                    setSubmitting(elements.bulkDeleteForm, false);
                }
            });

            elements.prevButton?.addEventListener('click', () => {
                if (state.pagination.current_page > 1) {
                    fetchUsers(state.pagination.current_page - 1);
                }
            });

            elements.nextButton?.addEventListener('click', () => {
                if (state.pagination.current_page < state.pagination.last_page) {
                    fetchUsers(state.pagination.current_page + 1);
                }
            });

            elements.selectAll?.addEventListener('change', (event) => {
                const checked = event.target.checked;
                elements.tbody.querySelectorAll('input[data-user-select]').forEach((checkbox) => {
                    checkbox.checked = checked;
                });
                updateSelectedState();
            });

            document.querySelector('[data-action="open-create-modal"]')?.addEventListener('click', () => {
                resetCreateForm();
                openModal('create');
            });

            document.querySelector('[data-action="open-filters"]')?.addEventListener('click', () => {
                syncFilterForm();
                setFormError('filters', '');
                openModal('filters');
            });

            elements.filterForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                setFormError('filters', '');

                const formData = new FormData(elements.filterForm);
                const statusRaw = String(formData.get('status') ?? 'all').toLowerCase();
                const createdFrom = String(formData.get('created_from') ?? '').trim();
                const createdTo = String(formData.get('created_to') ?? '').trim();

                if (createdFrom && createdTo && createdFrom > createdTo) {
                    setFormError('filters', 'A data inicial não pode ser maior que a final.');
                    return;
                }

                const nextFilters = {
                    status: ['active', 'inactive'].includes(statusRaw) ? statusRaw : 'all',
                    created_from: createdFrom,
                    created_to: createdTo,
                };

                state.filters = nextFilters;
                closeModal('filters');
                updateFilterSummary();
                fetchUsers(1);
            });

            elements.resetFiltersButton?.addEventListener('click', () => {
                resetFilters(true);
            });

            elements.searchInput?.addEventListener('input', (event) => {
                const value = event.target.value.trim();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    state.search = value;
                    fetchUsers(1);
                }, 400);
            });

            elements.searchForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                state.search = elements.searchInput?.value?.trim() ?? '';
                fetchUsers(1);
            });

            attachModalCloseHandlers();
            renderStats();
            renderTable();
            syncFilterForm();
            updateFilterSummary();
        })();
    </script>
@endsection
