<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Home - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --accent: #2F80ED;
            --bg: #F8FAFC;
            --card: #E7EDFF;
            --card-shadow: 0 6px 12px rgba(14, 59, 145, 0.08);
            --white: #FFFFFF;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --text-soft: #667085;
            --divider: #E4E7EC;
            --disclaimer: #F0F4FF;
            --error: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--text-strong);
            min-height: 100vh;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        .header {
            background: var(--primary);
            border-radius: 0 0 32px 32px;
            padding: 28px 20px 36px;
            color: var(--white);
        }

        .header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand-avatar {
            width: 56px;
            height: 56px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            flex-shrink: 0;
        }

        .brand-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .icon-button svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .icon-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-outline {
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: var(--white);
            padding: 10px 16px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-outline svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .header-info {
            font-size: 16px;
            line-height: 1.5;
            font-weight: 600;
            color: var(--white);
        }

        .content {
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 20px 40px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hidden {
            display: none !important;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: #FFE5E5;
            border: 1px solid var(--error);
            color: #C62828;
        }

        .actions-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .actions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .action-card {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .action-card__main {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 18px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            cursor: pointer;
        }

        .action-card__icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .action-card__icon svg {
            width: 26px;
            height: 26px;
            display: block;
        }

        .action-card__title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .action-card__description {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-top: 4px;
        }

        .action-card__chevron {
            margin-left: auto;
            color: #6377B8;
            transition: transform 0.2s ease;
        }

        .action-card__chevron svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .action-card--expanded .action-card__chevron {
            transform: rotate(90deg);
        }

        .action-card__sublist {
            background: var(--white);
            padding: 12px 18px 16px;
            display: none;
        }

        .action-card--expanded .action-card__sublist {
            display: block;
        }

        .action-subitem {
            width: 100%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 6px;
            cursor: pointer;
            text-align: left;
        }

        .action-subitem + .action-subitem {
            border-top: 1px solid var(--divider);
            margin-top: 8px;
            padding-top: 16px;
        }

        .action-subitem__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .action-subitem__icon svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .action-subitem__label {
            font-size: 15px;
            font-weight: 500;
            color: #1D2939;
            flex: 1;
        }

        .action-subitem__chevron {
            color: #7D8FBD;
        }

        .action-subitem__chevron svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .disclaimer-card {
            background: var(--disclaimer);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #1D1B20;
        }

        .disclaimer-card h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .disclaimer-card p {
            font-size: 14px;
            line-height: 1.5;
        }

        .text-link-button {
            border: none;
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 0;
        }

        .text-link-button svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .recent-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .recent-card {
            background: var(--white);
            border-radius: 24px;
            padding: 18px 20px;
            box-shadow: 0 8px 12px rgba(16, 24, 40, 0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .recent-card__plate {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .recent-card__summary {
            font-size: 15px;
            color: #475467;
            line-height: 1.4;
            white-space: pre-line;
        }

        .recent-card__date {
            text-align: right;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-soft);
        }

        .empty-state,
        .status-message {
            font-size: 14px;
            color: var(--text-soft);
        }

        .status-message.error {
            color: var(--error);
        }

        .loading {
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-soft);
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #E2E8F0;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .be-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 900;
        }

        .be-overlay.show {
            display: flex;
        }

        .be-dialog {
            width: min(420px, 92vw);
            background: #ECECF4;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
        }

        .be-dialog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .be-dialog-title {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }

        .be-dialog-close {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            font-size: 24px;
            line-height: 1;
            color: #64748B;
            cursor: pointer;
        }

        .be-dialog-body {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bloqueios-dialog {
            width: min(460px, 96vw);
            background: #F4F5F9;
            border-radius: 32px;
            padding: 24px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.25);
        }

        .bloqueios-dialog-body {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bloqueios-dialog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .bloqueios-dialog-title {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }

        .bloqueios-source-toggle {
            display: flex;
            gap: 10px;
            background: #E7ECFF;
            border-radius: 999px;
            padding: 4px;
        }

        .bloqueios-source-button {
            flex: 1;
            border: none;
            background: transparent;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 0;
            color: #1E293B;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .bloqueios-source-button.active {
            background: #0047AB;
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(0, 71, 171, 0.25);
        }

        .bloqueios-chassi-input {
            width: 100%;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
            padding: 14px 16px;
            font-size: 16px;
            background: #ffffff;
            color: #1E293B;
            text-transform: uppercase;
        }

        .bloqueios-chassi-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 71, 171, 0.2);
        }

        .bloqueios-error {
            min-height: 18px;
            font-size: 13px;
            color: var(--error);
            line-height: 1.2;
        }

        .bloqueios-submit-button {
            padding: 14px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 18px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .bloqueios-submit-button.loading .be-btn-text {
            display: none;
        }

        .bloqueios-submit-button.loading .be-btn-spinner {
            display: inline-block;
        }

        .bloqueios-submit-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .bloqueios-captcha-dialog {
            width: min(440px, 90vw);
            background: #FFFFFF;
            border-radius: 24px;
            padding: 22px;
        }

        .bloqueios-captcha-body {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .bloqueios-captcha-image-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .bloqueios-captcha-refresh {
            border: none;
            background: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .bloqueios-captcha-input {
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            padding: 12px 14px;
            font-size: 16px;
            background: #fff;
            color: #1E293B;
            text-transform: uppercase;
        }

        .bloqueios-captcha-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 71, 171, 0.2);
        }

        .ecrv-dialog {
            width: min(440px, 90vw);
            background: #F4F5F9;
            border-radius: 32px;
            padding: 22px;
            box-shadow: 0 28px 48px rgba(15, 23, 42, 0.25);
        }

        .ecrv-andamento-dialog {
            width: min(460px, 92vw);
        }

        .ecrv-description {
            font-size: 14px;
            color: var(--text-soft);
            margin-bottom: 16px;
        }

        .ecrv-captcha-section {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ecrv-captcha-image-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .ecrv-captcha-refresh {
            border: none;
            background: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .be-input--disabled {
            background: #EDF0F6;
            color: var(--text-strong);
            cursor: not-allowed;
        }

        .ecrv-submit.loading .be-btn-text,
        .ecrv-captcha-refresh:disabled {
            display: none;
        }

        .ecrv-submit.loading .be-btn-spinner {
            display: inline-block;
        }

        .ecrv-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .be-dialog-helper {
            font-size: 14px;
            line-height: 1.4;
            color: var(--text-soft);
        }

        .be-radio-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .be-radio-option {
            flex: 1;
            min-width: 140px;
            background: #F5F6FF;
            border-radius: 16px;
            padding: 10px 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .be-radio-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .be-radio-mark {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid #94A3B8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .be-radio-mark::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--primary);
            transform: scale(0);
            transition: transform 0.2s ease;
        }

        .be-radio-option input:checked + .be-radio-mark {
            border-color: var(--primary);
        }

        .be-radio-option input:checked + .be-radio-mark::after {
            transform: scale(1);
        }

        .be-radio-option input:checked ~ .be-radio-text {
            color: var(--primary);
            font-weight: 600;
        }

        .be-radio-option:hover {
            border-color: rgba(0, 71, 171, 0.25);
            background: #EEF2FF;
        }

        .be-radio-text {
            font-size: 14px;
            color: var(--text-strong);
        }

        .be-field-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .be-captcha-box {
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .be-captcha-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
        }

        .be-captcha-refresh {
            border: none;
            background: none;
            color: var(--accent);
            font-weight: 600;
            cursor: pointer;
        }

        .be-captcha-image {
            position: relative;
            min-height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8FAFC;
            border-radius: 14px;
            border: 1px dashed #CBD5F5;
            overflow: hidden;
        }

        .be-captcha-image img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .be-captcha-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text-soft);
        }

        .be-input {
            width: 100%;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 14px 16px;
            font-size: 16px;
            background: #FFFFFF;
            color: #1E293B;
        }

        .be-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 71, 171, 0.15);
        }

        .be-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='10' viewBox='0 0 16 10'%3E%3Cpath fill='%23667085' d='M1.41 0 8 6.58 14.59 0 16 1.41 8 9.41 0 1.41z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 14px;
            padding-right: 42px;
        }

        .be-dialog-error {
            color: var(--error);
            font-size: 13px;
            min-height: 16px;
        }

        .be-dialog-submit {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .be-dialog-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .be-dialog-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .be-dialog-submit .be-btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        .be-dialog-submit.loading .be-btn-text {
            display: none;
        }

        .be-dialog-submit.loading .be-btn-spinner {
            display: inline-block;
        }

        .be-dialog-cancel {
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--accent);
            cursor: pointer;
            align-self: center;
        }

        .error-overlay .alert {
            display: none;
        }

        .error-overlay.show {
            display: flex;
        }

        .error-dialog {
            width: min(420px, 90vw);
            background: #fff;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 24px 40px rgba(15, 23, 42, 0.35);
            text-align: center;
        }

        .error-dialog-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-strong);
        }

        .error-dialog-message {
            font-size: 15px;
            color: var(--text-soft);
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .error-dialog button {
            border: none;
            background: var(--primary);
            color: #fff;
            border-radius: 16px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .error-dialog button:hover {
            transform: translateY(-1px);
        }

        @media (min-width: 768px) {
            .header {
                padding: 32px 40px 44px;
            }

            .header-inner,
            .content {
                max-width: 860px;
            }

            .action-card__title {
                font-size: 19px;
            }

            .action-card__description {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    @include('components.home.header')

    <main class="content">
        @include('components.home.actions')
        @include('components.home.disclaimer')
        @include('components.home.recent')
    </main>

    @include('components.home.base-estadual-modal')
    @include('components.home.base-estadual-captcha-modal')
    @include('components.home.base-outros-estados-modal')
    @include('components.home.base-outros-estados-captcha-modal')
    @include('components.home.bin-modal')
    @include('components.home.bin-captcha-modal')
    @include('components.home.gravame-modal')
    @include('components.home.gravame-captcha-modal')
    @include('components.home.renainf-modal')
    @include('components.home.renainf-captcha-modal')
    @include('components.home.bloqueios-modal')
    @include('components.home.bloqueios-captcha-modal')
    @include('components.home.ecrv-modal')
    @include('components.home.ecrv-andamento-modal')
    <div class="be-overlay error-overlay hidden" id="errorOverlay" aria-hidden="true">
        <div class="error-dialog" role="alert" aria-live="assertive">
            <div class="error-dialog-title">Ops, algo deu errado</div>
            <div class="error-dialog-message" id="errorOverlayMessage">Mensagem de erro.</div>
            <button type="button" id="errorOverlayClose">Fechar</button>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
        let authToken = null;
        let permissionSlugs = null;

        const userInfoEl = document.getElementById('userInfo');
        const permissionsLoading = document.getElementById('permissionsLoading');
        const permissionsError = document.getElementById('permissionsError');
        const permissionsRetry = document.getElementById('permissionsRetry');
        const permissionsErrorText = document.getElementById('permissionsErrorText');
        const noActionsMessage = document.getElementById('noActionsMessage');
        const actionsList = document.getElementById('actionsList');

        const recentLoading = document.getElementById('recentLoading');
        const recentError = document.getElementById('recentError');
        const recentEmpty = document.getElementById('recentEmpty');
        const recentList = document.getElementById('recentList');
        const recentRetry = document.getElementById('recentRetry');

        function parseUser() {
            const raw = localStorage.getItem('user');
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (error) {
                return null;
            }
        }

        function updateHeaderCredits({ status, count }) {
            const user = parseUser();
            const name = user?.username || user?.name || 'Usuário';
            let creditsLabel = 'Créditos usados este mês: --';

            if (status === 'loading') {
                creditsLabel = 'Créditos usados este mês: carregando...';
            } else if (status === 'error') {
                creditsLabel = 'Créditos usados este mês: indisponível';
            } else if (status === 'loaded') {
                creditsLabel = `Créditos usados este mês: ${count}`;
            }

            userInfoEl.textContent = `Usuário: ${name} • ${creditsLabel}`;
        }

        function handleUnauthorized() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        async function fetchWithAuth(url, options = {}) {
            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {}),
                'Authorization': `Bearer ${authToken}`,
            };
            const response = await fetch(url, { ...options, headers });
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Não autenticado.');
            }
            return response;
        }

        function checkAuth() {
            authToken = localStorage.getItem('auth_token');
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            updateHeaderCredits({ status: 'loading', count: 0 });
            return true;
        }

        async function loadMonthlyCredits() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/pesquisas/ultimo-mes`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar créditos.');
                }
                const data = await response.json();
                const count = Array.isArray(data.data) ? data.data.length : 0;
                updateHeaderCredits({ status: 'loaded', count });
            } catch (error) {
                updateHeaderCredits({ status: 'error', count: 0 });
            }
        }

        function setPermissionsLoading(isLoading) {
            permissionsLoading.classList.toggle('show', isLoading);
            permissionsError.classList.toggle('hidden', true);
            if (isLoading) {
                actionsList.classList.add('hidden');
                noActionsMessage.classList.add('hidden');
            }
        }

        function applyPermissions() {
            if (!permissionSlugs) return;

            const allowed = new Set(permissionSlugs);
            const actionCards = Array.from(actionsList.querySelectorAll('.action-card'));

            actionCards.forEach((card) => {
                const required = card.dataset.permission;
                if (required) {
                    card.classList.toggle('hidden', !allowed.has(required));
                    return;
                }

                if (card.dataset.action === 'pesquisas') {
                    const subItems = Array.from(card.querySelectorAll('.action-subitem'));
                    let hasVisibleSub = false;

                    subItems.forEach((item) => {
                        const permission = item.dataset.permission;
                        const isAllowed = !permission || allowed.has(permission);
                        item.classList.toggle('hidden', !isAllowed);
                        if (isAllowed) {
                            hasVisibleSub = true;
                        }
                    });

                    card.classList.toggle('hidden', !hasVisibleSub);
                }
            });

            const visibleCards = actionCards.filter((card) => !card.classList.contains('hidden'));
            const hasVisible = visibleCards.length !== 0;
            noActionsMessage.classList.toggle('hidden', hasVisible);
            actionsList.classList.toggle('hidden', !hasVisible);
        }

        async function loadPermissions() {
            setPermissionsLoading(true);
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/user/permissions`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar permissões.');
                }
                const data = await response.json();
                permissionSlugs = Array.isArray(data.slugs)
                    ? data.slugs.map((slug) => String(slug))
                    : [];

                setPermissionsLoading(false);
                actionsList.classList.remove('hidden');
                permissionsError.classList.add('hidden');
                applyPermissions();
            } catch (error) {
                permissionSlugs = null;
                permissionsLoading.classList.remove('show');
                permissionsError.classList.remove('hidden');
                permissionsErrorText.textContent = 'Não foi possível carregar as permissões.';
                actionsList.classList.add('hidden');
                noActionsMessage.classList.add('hidden');
            }
        }

        function formatDate(value) {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            const day = date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
            const time = date.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            });
            return `${day} – ${time}`;
        }

        function createRecentCard(item) {
            const plate = (item.placa || '').trim().toUpperCase();
            const chassi = (item.chassi || '').trim().toUpperCase();
            const leading = plate || chassi || item.nome || 'Consulta';

            const details = [item.nome || ''];
            if (item.renavam) {
                details.push(`Renavam: ${item.renavam}`);
            }
            if (item.chassi) {
                details.push(`Chassi: ${item.chassi}`);
            }
            if (item.opcao_pesquisa) {
                details.push(`Opção: ${item.opcao_pesquisa}`);
            }

            const card = document.createElement('div');
            card.className = 'recent-card';

            const plateEl = document.createElement('div');
            plateEl.className = 'recent-card__plate';
            plateEl.textContent = leading;

            const summaryEl = document.createElement('div');
            summaryEl.className = 'recent-card__summary';
            summaryEl.textContent = details.filter(Boolean).join('\n');

            const dateEl = document.createElement('div');
            dateEl.className = 'recent-card__date';
            dateEl.textContent = formatDate(item.created_at);

            card.appendChild(plateEl);
            card.appendChild(summaryEl);
            card.appendChild(dateEl);

            return card;
        }

        async function loadRecentVehicles() {
            recentLoading.classList.add('show');
            recentError.classList.add('hidden');
            recentEmpty.classList.add('hidden');
            recentRetry.classList.add('hidden');
            recentList.innerHTML = '';

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/pesquisas`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar pesquisas.');
                }
                const data = await response.json();
                const items = Array.isArray(data.data) ? data.data : [];

                if (items.length === 0) {
                    recentEmpty.classList.remove('hidden');
                } else {
                    items.forEach((item) => {
                        recentList.appendChild(createRecentCard(item));
                    });
                }
            } catch (error) {
                recentError.textContent = 'Não foi possível carregar as pesquisas recentes.';
                recentError.classList.remove('hidden');
                recentRetry.classList.remove('hidden');
            } finally {
                recentLoading.classList.remove('show');
            }
        }

        const baseQueryOverlay = document.getElementById('baseQueryOverlay');
        const baseQueryClose = document.getElementById('baseQueryClose');
        const baseCancelBtn = document.getElementById('baseCancelBtn');
        const basePlateInput = document.getElementById('basePlateInput');
        const basePlateError = document.getElementById('basePlateError');
        const baseConsultBtn = document.getElementById('baseConsultBtn');

        const baseCaptchaOverlay = document.getElementById('baseCaptchaOverlay');
        const baseCaptchaClose = document.getElementById('baseCaptchaClose');
        const baseCaptchaCancel = document.getElementById('baseCaptchaCancel');
        const baseCaptchaRefresh = document.getElementById('baseCaptchaRefresh');
        const baseCaptchaPlate = document.getElementById('baseCaptchaPlate');
        const baseCaptchaInput = document.getElementById('baseCaptchaInput');
        const baseCaptchaImage = document.getElementById('baseCaptchaImage');
        const baseCaptchaLoading = document.getElementById('baseCaptchaLoading');
        const baseCaptchaError = document.getElementById('baseCaptchaError');
        const baseCaptchaSubmit = document.getElementById('baseCaptchaSubmit');

        const otherStatesOverlay = document.getElementById('otherStatesOverlay');
        const otherStatesClose = document.getElementById('otherStatesClose');
        const otherStatesCancel = document.getElementById('otherStatesCancel');
        const otherStatesChassi = document.getElementById('otherStatesChassi');
        const otherStatesUf = document.getElementById('otherStatesUf');
        const otherStatesError = document.getElementById('otherStatesError');
        const otherStatesSubmit = document.getElementById('otherStatesSubmit');

        const otherStatesCaptchaOverlay = document.getElementById('otherStatesCaptchaOverlay');
        const otherStatesCaptchaClose = document.getElementById('otherStatesCaptchaClose');
        const otherStatesCaptchaCancel = document.getElementById('otherStatesCaptchaCancel');
        const otherStatesCaptchaRefresh = document.getElementById('otherStatesCaptchaRefresh');
        const otherStatesCaptchaChassi = document.getElementById('otherStatesCaptchaChassi');
        const otherStatesCaptchaUf = document.getElementById('otherStatesCaptchaUf');
        const otherStatesCaptchaInput = document.getElementById('otherStatesCaptchaInput');
        const otherStatesCaptchaImage = document.getElementById('otherStatesCaptchaImage');
        const otherStatesCaptchaLoading = document.getElementById('otherStatesCaptchaLoading');
        const otherStatesCaptchaError = document.getElementById('otherStatesCaptchaError');
        const otherStatesCaptchaSubmit = document.getElementById('otherStatesCaptchaSubmit');

        const binOverlay = document.getElementById('binOverlay');
        const binClose = document.getElementById('binClose');
        const binCancel = document.getElementById('binCancel');
        const binError = document.getElementById('binError');
        const binSubmit = document.getElementById('binSubmit');
        const binPlacaInput = document.getElementById('binPlacaInput');
        const binRenavamInput = document.getElementById('binRenavamInput');
        const binChassiInput = document.getElementById('binChassiInput');
        const binPlacaGroup = document.getElementById('binPlacaGroup');
        const binChassiGroup = document.getElementById('binChassiGroup');
        const binOptionInputs = Array.from(document.querySelectorAll('input[name="binSearchOption"]'));

        const binCaptchaOverlay = document.getElementById('binCaptchaOverlay');
        const binCaptchaClose = document.getElementById('binCaptchaClose');
        const binCaptchaCancel = document.getElementById('binCaptchaCancel');
        const binCaptchaRefresh = document.getElementById('binCaptchaRefresh');
        const binCaptchaError = document.getElementById('binCaptchaError');
        const binCaptchaSubmit = document.getElementById('binCaptchaSubmit');
        const binCaptchaInput = document.getElementById('binCaptchaInput');
        const binCaptchaPlacaInput = document.getElementById('binCaptchaPlacaInput');
        const binCaptchaRenavamInput = document.getElementById('binCaptchaRenavamInput');
        const binCaptchaChassiInput = document.getElementById('binCaptchaChassiInput');
        const binCaptchaPlacaGroup = document.getElementById('binCaptchaPlacaGroup');
        const binCaptchaChassiGroup = document.getElementById('binCaptchaChassiGroup');
        const binCaptchaOptionInputs = Array.from(document.querySelectorAll('input[name="binCaptchaSearchOption"]'));
        const binCaptchaImage = document.getElementById('binCaptchaImage');
        const binCaptchaLoading = document.getElementById('binCaptchaLoading');
        const renainfOverlay = document.getElementById('renainfOverlay');
        const renainfClose = document.getElementById('renainfClose');
        const renainfCancel = document.getElementById('renainfCancel');
        const renainfPlateInput = document.getElementById('renainfPlateInput');
        const renainfStatusSelect = document.getElementById('renainfStatusSelect');
        const renainfUfSelect = document.getElementById('renainfUfSelect');
        const renainfStartDate = document.getElementById('renainfStartDate');
        const renainfEndDate = document.getElementById('renainfEndDate');
        const renainfError = document.getElementById('renainfError');
        const renainfSubmit = document.getElementById('renainfSubmit');

        const renainfCaptchaOverlay = document.getElementById('renainfCaptchaOverlay');
        const renainfCaptchaClose = document.getElementById('renainfCaptchaClose');
        const renainfCaptchaCancel = document.getElementById('renainfCaptchaCancel');
        const renainfCaptchaRefresh = document.getElementById('renainfCaptchaRefresh');
        const renainfCaptchaPlate = document.getElementById('renainfCaptchaPlate');
        const renainfCaptchaStatus = document.getElementById('renainfCaptchaStatus');
        const renainfCaptchaUf = document.getElementById('renainfCaptchaUf');
        const renainfCaptchaStart = document.getElementById('renainfCaptchaStart');
        const renainfCaptchaEnd = document.getElementById('renainfCaptchaEnd');
        const renainfCaptchaInput = document.getElementById('renainfCaptchaInput');
        const renainfCaptchaImage = document.getElementById('renainfCaptchaImage');
        const renainfCaptchaLoading = document.getElementById('renainfCaptchaLoading');
        const renainfCaptchaError = document.getElementById('renainfCaptchaError');
        const renainfCaptchaSubmit = document.getElementById('renainfCaptchaSubmit');
        const gravameOverlay = document.getElementById('gravameOverlay');
        const gravameClose = document.getElementById('gravameClose');
        const gravameCancel = document.getElementById('gravameCancel');
        const gravamePlateInput = document.getElementById('gravamePlateInput');
        const gravameError = document.getElementById('gravameError');
        const gravameSubmit = document.getElementById('gravameSubmit');

        const gravameCaptchaOverlay = document.getElementById('gravameCaptchaOverlay');
        const gravameCaptchaClose = document.getElementById('gravameCaptchaClose');
        const gravameCaptchaCancel = document.getElementById('gravameCaptchaCancel');
        const gravameCaptchaRefresh = document.getElementById('gravameCaptchaRefresh');
        const gravameCaptchaPlate = document.getElementById('gravameCaptchaPlate');
        const gravameCaptchaInput = document.getElementById('gravameCaptchaInput');
        const gravameCaptchaImage = document.getElementById('gravameCaptchaImage');
        const gravameCaptchaLoading = document.getElementById('gravameCaptchaLoading');
        const gravameCaptchaError = document.getElementById('gravameCaptchaError');
        const gravameCaptchaSubmit = document.getElementById('gravameCaptchaSubmit');

        const bloqueiosOverlay = document.getElementById('bloqueiosOverlay');
        const bloqueiosClose = document.getElementById('bloqueiosClose');
        const bloqueiosCancel = document.getElementById('bloqueiosCancelBtn');
        const bloqueiosChassiInput = document.getElementById('bloqueiosChassiInput');
        const bloqueiosError = document.getElementById('bloqueiosError');
        const bloqueiosSearchBtn = document.getElementById('bloqueiosSearchBtn');
        const bloqueiosSourceButtons = Array.from(document.querySelectorAll('.bloqueios-source-button'));
        const bloqueiosCaptchaOverlay = document.getElementById('bloqueiosCaptchaOverlay');
        const bloqueiosCaptchaClose = document.getElementById('bloqueiosCaptchaClose');
        const bloqueiosCaptchaCancel = document.getElementById('bloqueiosCaptchaCancel');
        const bloqueiosCaptchaRefresh = document.getElementById('bloqueiosCaptchaRefresh');
        const bloqueiosCaptchaInput = document.getElementById('bloqueiosCaptchaInput');
        const bloqueiosCaptchaImage = document.getElementById('bloqueiosCaptchaImage');
        const bloqueiosCaptchaLoading = document.getElementById('bloqueiosCaptchaLoading');
        const bloqueiosCaptchaError = document.getElementById('bloqueiosCaptchaError');
        const bloqueiosCaptchaSubmit = document.getElementById('bloqueiosCaptchaSubmit');
        let bloqueiosSelectedSource = 'DETRAN';
        let bloqueiosCaptchaMeta = null;

        const errorOverlay = document.getElementById('errorOverlay');
        const errorOverlayMessage = document.getElementById('errorOverlayMessage');
        const errorOverlayClose = document.getElementById('errorOverlayClose');

        const oldPlatePattern = /^[A-Z]{3}[0-9]{4}$/;
        const mercosurPlatePattern = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/;
        const chassiPattern = /^[A-HJ-NPR-Z0-9]{17}$/;
        const renavamPattern = /^\d{11}$/;

        function normalizePlate(value) {
            return value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }

        function isValidPlate(value) {
            const normalized = normalizePlate(value);
            if (normalized.length !== 7) {
                return false;
            }
            return oldPlatePattern.test(normalized) || mercosurPlatePattern.test(normalized);
        }

        function normalizeChassi(value) {
            return value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }

        function isValidChassi(value) {
            const normalized = normalizeChassi(value);
            if (normalized.length !== 17) {
                return false;
            }
            return chassiPattern.test(normalized);
        }

        function normalizeRenavam(value) {
            return value.replace(/\D/g, '');
        }

        function isValidRenavam(value) {
            const normalized = normalizeRenavam(value);
            return renavamPattern.test(normalized);
        }

        const renainfStatusLabels = {
            '1': 'Multas em cobrança',
            '2': 'Todas',
        };

        let pendingRenainfRequest = null;

        function formatDateForApi(value) {
            if (!value) return '';
            const parts = value.split('-');
            if (parts.length !== 3) return '';
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function formatDateForDisplay(value) {
            if (!value) return '';
            const parts = value.split('-');
            if (parts.length !== 3) return value;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function getDefaultRenainfDates() {
            const today = new Date();
            const past = new Date(today);
            past.setDate(past.getDate() - 30);
            return {
                start: past.toISOString().slice(0, 10),
                end: today.toISOString().slice(0, 10),
            };
        }

        function openBaseEstadualModal() {
            basePlateInput.value = '';
            basePlateError.textContent = '';
            baseQueryOverlay.classList.remove('hidden');
            baseQueryOverlay.classList.add('show');
            baseQueryOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => basePlateInput.focus(), 0);
        }

        function closeBaseEstadualModal() {
            baseQueryOverlay.classList.remove('show');
            baseQueryOverlay.classList.add('hidden');
            baseQueryOverlay.setAttribute('aria-hidden', 'true');
        }

        function openBaseCaptchaModal(placa, message = '') {
            baseCaptchaPlate.value = placa;
            baseCaptchaInput.value = '';
            baseCaptchaError.textContent = message;
            baseCaptchaOverlay.classList.remove('hidden');
            baseCaptchaOverlay.classList.add('show');
            baseCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadBaseCaptchaImage();
            setTimeout(() => baseCaptchaInput.focus(), 0);
        }

        function closeBaseCaptchaModal() {
            baseCaptchaOverlay.classList.remove('show');
            baseCaptchaOverlay.classList.add('hidden');
            baseCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearCaptchaImage();
        }

        function setBaseConsultLoading(isLoading) {
            baseConsultBtn.disabled = isLoading;
            baseConsultBtn.classList.toggle('loading', isLoading);
        }

        function setBaseCaptchaLoading(isLoading) {
            baseCaptchaSubmit.disabled = isLoading;
            baseCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        async function solveBaseCaptcha() {
            const response = await fetch(`${API_BASE_URL}/api/captcha/solve`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Não foi possível resolver o captcha automaticamente.');
            }
            const data = await response.json();
            const solution = data.solution ? String(data.solution).trim() : '';
            if (!solution) {
                throw new Error('Resposta inválida ao resolver o captcha.');
            }
            return solution.toUpperCase();
        }

        function clearCaptchaImage() {
            const currentUrl = baseCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete baseCaptchaImage.dataset.objectUrl;
            }
            baseCaptchaImage.src = '';
        }

        async function loadBaseCaptchaImage() {
            baseCaptchaError.textContent = '';
            baseCaptchaLoading.classList.remove('hidden');
            baseCaptchaImage.classList.add('hidden');
            clearCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                baseCaptchaImage.src = objectUrl;
                baseCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                baseCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                baseCaptchaLoading.classList.add('hidden');
                baseCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        function showErrorModal(message) {
            if (!errorOverlay || !errorOverlayMessage) return;
            errorOverlayMessage.textContent = message || 'Ocorreu um erro inesperado.';
            errorOverlay.classList.remove('hidden');
            errorOverlay.classList.add('show');
            errorOverlay.setAttribute('aria-hidden', 'false');
        }

        function closeErrorModal() {
            if (!errorOverlay) return;
            errorOverlay.classList.remove('show');
            errorOverlay.classList.add('hidden');
            errorOverlay.setAttribute('aria-hidden', 'true');
        }

        async function fetchBaseEstadual(placa, captcha) {
            const params = new URLSearchParams({
                placa: placa,
                renavam: '',
                captcha: captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/base-estadual?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar base estadual.' }));
                throw new Error(errorData.message || 'Erro ao consultar base estadual.');
            }

            return await response.json();
        }

        function redirectToBaseEstadualResult(result) {
            sessionStorage.setItem('base_estadual_result', JSON.stringify(result));
            window.location.href = '/resultado-base-estadual';
        }

        async function registerBaseEstadualPesquisa(placa) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Base estadual',
                        placa: placa,
                        renavam: null,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa:', error);
            }
        }

        async function performBaseEstadualSearch() {
            const placa = normalizePlate(basePlateInput.value);
            if (!placa) {
                basePlateError.textContent = 'Informe a placa do veículo.';
                return;
            }
            if (!isValidPlate(placa)) {
                basePlateError.textContent = 'Placa inválida.';
                return;
            }

            basePlateError.textContent = '';
            setBaseConsultLoading(true);

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeBaseEstadualModal();
                    openBaseCaptchaModal(placa, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchBaseEstadual(placa, captcha);
                await registerBaseEstadualPesquisa(placa);
                closeBaseEstadualModal();
                redirectToBaseEstadualResult(result);
            } catch (error) {
                const message = error.message || 'Não foi possível consultar a base estadual.';
                if (message.toLowerCase().includes('captcha')) {
                    closeBaseEstadualModal();
                    openBaseCaptchaModal(placa, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                basePlateError.textContent = message;
            } finally {
                setBaseConsultLoading(false);
            }
        }

        async function performBaseCaptchaSearch() {
            const placa = normalizePlate(baseCaptchaPlate.value);
            const captcha = baseCaptchaInput.value.trim().toUpperCase();

            if (!captcha) {
                baseCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            baseCaptchaError.textContent = '';
            setBaseCaptchaLoading(true);

            try {
                const result = await fetchBaseEstadual(placa, captcha);
                await registerBaseEstadualPesquisa(placa);
                closeBaseCaptchaModal();
                redirectToBaseEstadualResult(result);
            } catch (error) {
                baseCaptchaError.textContent = error.message || 'Não foi possível consultar a base estadual.';
                loadBaseCaptchaImage();
            } finally {
                setBaseCaptchaLoading(false);
            }
        }

        function setBloqueiosSource(source) {
            const normalized = source === 'RENAJUD' ? 'RENAJUD' : 'DETRAN';
            bloqueiosSelectedSource = normalized;
            bloqueiosSourceButtons.forEach((button) => {
                const isActive = button.dataset.source === normalized;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
        }

        function openBloqueiosModal() {
            bloqueiosChassiInput.value = '';
            bloqueiosError.textContent = '';
            setBloqueiosSource('DETRAN');
            bloqueiosOverlay.classList.remove('hidden');
            bloqueiosOverlay.classList.add('show');
            bloqueiosOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => bloqueiosChassiInput.focus(), 0);
        }

        function closeBloqueiosModal() {
            bloqueiosOverlay.classList.remove('show');
            bloqueiosOverlay.classList.add('hidden');
            bloqueiosOverlay.setAttribute('aria-hidden', 'true');
        }

        function setBloqueiosLoading(isLoading) {
            bloqueiosSearchBtn.disabled = isLoading;
            bloqueiosSearchBtn.classList.toggle('loading', isLoading);
        }

        function openBloqueiosCaptchaModal(meta, message = '') {
            bloqueiosCaptchaMeta = meta;
            bloqueiosCaptchaInput.value = '';
            bloqueiosCaptchaError.textContent = message;
            bloqueiosCaptchaOverlay.classList.remove('hidden');
            bloqueiosCaptchaOverlay.classList.add('show');
            bloqueiosCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadBloqueiosCaptchaImage();
            setTimeout(() => bloqueiosCaptchaInput.focus(), 0);
        }

        function closeBloqueiosCaptchaModal() {
            bloqueiosCaptchaOverlay.classList.remove('show');
            bloqueiosCaptchaOverlay.classList.add('hidden');
            bloqueiosCaptchaOverlay.setAttribute('aria-hidden', 'true');
            bloqueiosCaptchaError.textContent = '';
            bloqueiosCaptchaMeta = null;
            clearBloqueiosCaptchaImage();
        }

        function setBloqueiosCaptchaLoading(isLoading) {
            bloqueiosCaptchaSubmit.disabled = isLoading;
            bloqueiosCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        function clearBloqueiosCaptchaImage() {
            const currentUrl = bloqueiosCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete bloqueiosCaptchaImage.dataset.objectUrl;
            }
            bloqueiosCaptchaImage.src = '';
        }

        async function loadBloqueiosCaptchaImage() {
            bloqueiosCaptchaError.textContent = '';
            bloqueiosCaptchaLoading.classList.remove('hidden');
            bloqueiosCaptchaImage.classList.add('hidden');
            clearBloqueiosCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                bloqueiosCaptchaImage.src = objectUrl;
                bloqueiosCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                bloqueiosCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                bloqueiosCaptchaLoading.classList.add('hidden');
                bloqueiosCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchBloqueiosResult(chassi, opcao, captcha) {
            const params = new URLSearchParams({
                chassi,
                opcaoPesquisa: opcao,
                captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/bloqueios-ativos?${params}`, { cache: 'no-store' });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erro ao consultar bloqueios ativos.');
            }

            return await response.json();
        }

        async function registerBloqueiosPesquisa(chassi, opcao) {
            if (!authToken) return;

            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Bloqueios ativos',
                        placa: null,
                        renavam: null,
                        chassi,
                        opcao_pesquisa: opcao,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa de bloqueios ativos:', error);
            }
        }

        function redirectToBloqueiosResult(result, origin, chassi, opcao) {
            const payload = JSON.stringify({
                payload: result,
                origin,
                chassi,
                opcao,
                storedAt: Date.now(),
            });
            sessionStorage.setItem('bloqueios_ativos_result', payload);
            localStorage.setItem('bloqueios_ativos_result', payload);
            window.location.href = '/resultado-bloqueios-ativos';
        }

        async function performBloqueiosSearch() {
            const chassi = normalizeChassi(bloqueiosChassiInput.value);
            if (!chassi) {
                bloqueiosError.textContent = 'Informe o chassi.';
                return;
            }
            if (!isValidChassi(chassi)) {
                bloqueiosError.textContent = 'Chassi inválido.';
                return;
            }

            bloqueiosError.textContent = '';
            setBloqueiosLoading(true);

            const origin = bloqueiosSelectedSource;
            const opcao = origin === 'RENAJUD' ? '2' : '1';

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeBloqueiosModal();
                    openBloqueiosCaptchaModal(
                        { chassi, opcao, origin },
                        'Captcha automático indisponível. Digite o captcha manualmente.'
                    );
                    return;
                }

                const result = await fetchBloqueiosResult(chassi, opcao, captcha.toUpperCase());
                await registerBloqueiosPesquisa(chassi, opcao);
                closeBloqueiosModal();
                redirectToBloqueiosResult(result, origin, chassi, opcao);
            } catch (error) {
                const message = error.message || 'Não foi possível consultar bloqueios ativos.';
                if (message.toLowerCase().includes('captcha')) {
                    closeBloqueiosModal();
                    openBloqueiosCaptchaModal(
                        { chassi, opcao, origin },
                        'Captcha automático falhou. Digite o captcha manualmente.'
                    );
                    return;
                }
                bloqueiosError.textContent = message;
            } finally {
                setBloqueiosLoading(false);
            }
        }

        async function performBloqueiosCaptchaSearch() {
            const captcha = bloqueiosCaptchaInput.value.trim().toUpperCase();
            if (!captcha) {
                bloqueiosCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            const meta = bloqueiosCaptchaMeta;
            if (!meta) {
                bloqueiosCaptchaError.textContent = 'Reinicie a pesquisa e tente novamente.';
                return;
            }

            bloqueiosCaptchaError.textContent = '';
            setBloqueiosCaptchaLoading(true);

            try {
                const result = await fetchBloqueiosResult(meta.chassi, meta.opcao, captcha);
                await registerBloqueiosPesquisa(meta.chassi, meta.opcao);
                closeBloqueiosCaptchaModal();
                redirectToBloqueiosResult(result, meta.origin, meta.chassi, meta.opcao);
            } catch (error) {
                bloqueiosCaptchaError.textContent = error.message || 'Não foi possível concluir a pesquisa.';
                loadBloqueiosCaptchaImage();
            } finally {
                setBloqueiosCaptchaLoading(false);
            }
        }

        bloqueiosSourceButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const source = button.dataset.source || 'DETRAN';
                setBloqueiosSource(source);
            });
        });

        bloqueiosClose.addEventListener('click', closeBloqueiosModal);
        bloqueiosCancel.addEventListener('click', closeBloqueiosModal);
        bloqueiosSearchBtn.addEventListener('click', performBloqueiosSearch);
        bloqueiosCaptchaRefresh.addEventListener('click', loadBloqueiosCaptchaImage);
        bloqueiosCaptchaSubmit.addEventListener('click', performBloqueiosCaptchaSearch);
        bloqueiosCaptchaClose.addEventListener('click', closeBloqueiosCaptchaModal);
        bloqueiosCaptchaCancel.addEventListener('click', closeBloqueiosCaptchaModal);

        setBloqueiosSource(bloqueiosSelectedSource);

        const ecrvOverlay = document.getElementById('ecrvOverlay');
        const ecrvClose = document.getElementById('ecrvClose');
        const ecrvCancel = document.getElementById('ecrvCancelBtn');
        const ecrvPlateInput = document.getElementById('ecrvPlateInput');
        const ecrvPlateError = document.getElementById('ecrvPlateError');
        const ecrvAdvanceBtn = document.getElementById('ecrvAdvanceBtn');
        const ecrvDescription = document.getElementById('ecrvDescription');
        const ecrvCaptchaSection = document.getElementById('ecrvCaptchaSection');
        const ecrvCaptchaImage = document.getElementById('ecrvCaptchaImage');
        const ecrvCaptchaLoading = document.getElementById('ecrvCaptchaLoading');
        const ecrvCaptchaError = document.getElementById('ecrvCaptchaError');
        const ecrvCaptchaInput = document.getElementById('ecrvCaptchaInput');
        const ecrvCaptchaRefresh = document.getElementById('ecrvCaptchaRefresh');

        const ecrvAndamentoOverlay = document.getElementById('ecrvAndamentoOverlay');
        const ecrvAndamentoClose = document.getElementById('ecrvAndamentoClose');
        const ecrvAndamentoCancel = document.getElementById('ecrvAndamentoCancelBtn');
        const ecrvAndamentoFichaInput = document.getElementById('ecrvAndamentoFichaInput');
        const ecrvAndamentoAnoInput = document.getElementById('ecrvAndamentoAnoInput');
        const ecrvAndamentoCaptchaImage = document.getElementById('ecrvAndamentoCaptchaImage');
        const ecrvAndamentoCaptchaLoading = document.getElementById('ecrvAndamentoCaptchaLoading');
        const ecrvAndamentoCaptchaInput = document.getElementById('ecrvAndamentoCaptchaInput');
        const ecrvAndamentoCaptchaError = document.getElementById('ecrvAndamentoCaptchaError');
        const ecrvAndamentoCaptchaRefresh = document.getElementById('ecrvAndamentoCaptchaRefresh');
        const ecrvAndamentoBtn = document.getElementById('ecrvAndamentoBtn');
        const ecrvAndamentoDescription = document.getElementById('ecrvAndamentoDescription');

        let ecrvRequireCaptcha = false;
        let ecrvConsultaMeta = null;
        let ecrvAndamentoMeta = null;

        function setEcrvDescription(requireCaptcha) {
            ecrvDescription.textContent = requireCaptcha
                ? 'Digite a placa e o captcha exibido para recuperar o número da ficha.'
                : 'Informe somente a placa. Resolveremos o captcha automaticamente.';
        }

        function openEcrvModal({ requireCaptcha = false, plate = '', captchaMessage = '' } = {}) {
            ecrvRequireCaptcha = requireCaptcha;
            ecrvPlateInput.value = plate;
            ecrvPlateError.textContent = '';
            ecrvCaptchaError.textContent = captchaMessage;
            setEcrvDescription(requireCaptcha);
            ecrvCaptchaSection.classList.toggle('hidden', !requireCaptcha);
            if (requireCaptcha) {
                loadEcrvCaptchaImage();
            } else {
                clearEcrvCaptchaImage();
            }
            ecrvOverlay.classList.remove('hidden');
            ecrvOverlay.classList.add('show');
            ecrvOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => ecrvPlateInput.focus(), 0);
        }

        function closeEcrvModal() {
            ecrvOverlay.classList.remove('show');
            ecrvOverlay.classList.add('hidden');
            ecrvOverlay.setAttribute('aria-hidden', 'true');
            ecrvCaptchaError.textContent = '';
            ecrvCaptchaInput.value = '';
            ecrvPlateError.textContent = '';
            clearEcrvCaptchaImage();
        }

        function setEcrvLoading(isLoading) {
            ecrvAdvanceBtn.disabled = isLoading;
            ecrvAdvanceBtn.classList.toggle('loading', isLoading);
        }

        function setEcrvAndamentoLoading(isLoading) {
            ecrvAndamentoBtn.disabled = isLoading;
            ecrvAndamentoBtn.classList.toggle('loading', isLoading);
        }

        function clearEcrvCaptchaImage() {
            const currentUrl = ecrvCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete ecrvCaptchaImage.dataset.objectUrl;
            }
            ecrvCaptchaImage.src = '';
        }

        function clearEcrvAndamentoCaptchaImage() {
            const currentUrl = ecrvAndamentoCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete ecrvAndamentoCaptchaImage.dataset.objectUrl;
            }
            ecrvAndamentoCaptchaImage.src = '';
        }

        async function loadEcrvCaptchaImage() {
            ecrvCaptchaError.textContent = '';
            ecrvCaptchaLoading.classList.remove('hidden');
            ecrvCaptchaImage.classList.add('hidden');
            clearEcrvCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                ecrvCaptchaImage.src = objectUrl;
                ecrvCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                ecrvCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                ecrvCaptchaLoading.classList.add('hidden');
                ecrvCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function loadEcrvAndamentoCaptchaImage() {
            ecrvAndamentoCaptchaError.textContent = '';
            ecrvAndamentoCaptchaLoading.classList.remove('hidden');
            ecrvAndamentoCaptchaImage.classList.add('hidden');
            clearEcrvAndamentoCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                ecrvAndamentoCaptchaImage.src = objectUrl;
                ecrvAndamentoCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                ecrvAndamentoCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                ecrvAndamentoCaptchaLoading.classList.add('hidden');
                ecrvAndamentoCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchFichaCadastral(plate, captcha) {
            const params = new URLSearchParams({
                placa: plate,
                captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/ficha-cadastral/consulta?${params}`, { cache: 'no-store' });
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Erro ao consultar a ficha cadastral.');
            }

            return await response.json();
        }

        async function fetchFichaAndamento(numeroFicha, anoFicha, placa, captcha) {
            const params = new URLSearchParams({
                numero_ficha: numeroFicha,
                ano_ficha: anoFicha,
                placa: placa,
                captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/ficha-cadastral/andamento?${params}`, { cache: 'no-store' });
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Erro ao consultar o andamento do processo.');
            }

            return await response.json();
        }

        function normalizeString(value) {
            if (value === undefined || value === null) {
                return '';
            }
            return String(value).trim();
        }

        function openEcrvAndamentoModal(meta, message = '') {
            ecrvAndamentoMeta = meta;
            ecrvAndamentoFichaInput.value = meta.numeroFicha || '';
            ecrvAndamentoAnoInput.value = meta.anoFicha || '';
            ecrvAndamentoCaptchaInput.value = '';
            ecrvAndamentoCaptchaError.textContent = message;
            ecrvAndamentoCaptchaRefresh.disabled = false;
            ecrvAndamentoOverlay.classList.remove('hidden');
            ecrvAndamentoOverlay.classList.add('show');
            ecrvAndamentoOverlay.setAttribute('aria-hidden', 'false');
            loadEcrvAndamentoCaptchaImage();
            setTimeout(() => ecrvAndamentoCaptchaInput.focus(), 0);
        }

        function closeEcrvAndamentoModal() {
            ecrvAndamentoOverlay.classList.remove('show');
            ecrvAndamentoOverlay.classList.add('hidden');
            ecrvAndamentoOverlay.setAttribute('aria-hidden', 'true');
            ecrvAndamentoCaptchaError.textContent = '';
            ecrvAndamentoCaptchaInput.value = '';
            clearEcrvAndamentoCaptchaImage();
        }

        function redirectToEcrvResult(payload, plate, numeroFicha, anoFicha) {
            sessionStorage.setItem('ecrv_result', JSON.stringify({
                placa: plate,
                numeroFicha,
                anoFicha,
                fichaPayload: payload.fichaPayload,
                andamentoPayload: payload.andamentoPayload,
            }));
            window.location.href = '/resultado-ecrv';
        }

        async function registerEcrvPesquisa(plate, renavam, chassi) {
            if (!authToken) return;

            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Processo e-CRVsp',
                        placa: plate,
                        renavam: renavam || null,
                        chassi: chassi || null,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa e-CRVsp:', error);
            }
        }

        function completeEcrvFlow(andamentoPayload) {
            if (!ecrvConsultaMeta) return;
            const { plate, numeroFicha, anoFicha, renavam, chassi, fichaPayload } = ecrvConsultaMeta;
            registerEcrvPesquisa(plate, renavam, chassi);
            redirectToEcrvResult(
                {
                    fichaPayload,
                    andamentoPayload,
                },
                plate,
                numeroFicha,
                anoFicha,
            );
        }

        async function attemptEcrvAndamento(autoSolve, captchaOverride = '') {
            if (!ecrvConsultaMeta) {
                throw new Error('Dados da ficha não encontrados.');
            }
            const captcha = autoSolve ? await solveBaseCaptcha() : captchaOverride;
            if (!captcha) {
                throw new Error('Informe o captcha.');
            }
            return await fetchFichaAndamento(
                ecrvConsultaMeta.numeroFicha,
                ecrvConsultaMeta.anoFicha,
                ecrvConsultaMeta.plate,
                captcha,
            );
        }

        async function handleEcrvAndamento(autoSolve = true, captchaOverride = '') {
            try {
                const result = await attemptEcrvAndamento(autoSolve, captchaOverride);
                closeEcrvAndamentoModal();
                completeEcrvFlow(result);
            } catch (error) {
                const message = (error && error.message) ? error.message : 'Não foi possível consultar o andamento.';
                if (autoSolve && message.toLowerCase().includes('captcha')) {
                    openEcrvAndamentoModal(
                        {
                            plate: ecrvConsultaMeta.plate,
                            numeroFicha: ecrvConsultaMeta.numeroFicha,
                            anoFicha: ecrvConsultaMeta.anoFicha,
                        },
                        'Captcha automático falhou. Digite o captcha manualmente.',
                    );
                    return;
                }
                showErrorModal(message);
            }
        }

        async function finalizeFichaConsulta(result, plate) {
            const normalized = result?.payload?.normalized?.dados_da_ficha_cadastral || {};
            const numeroFicha = normalizeString(normalized?.n_da_ficha);
            const anoFicha = normalizeString(normalized?.ano_ficha);
            if (!numeroFicha || !anoFicha) {
                ecrvPlateError.textContent = 'Consulta retornou sem número/ano da ficha. Verifique os dados informados.';
                return;
            }

            ecrvConsultaMeta = {
                plate,
                numeroFicha,
                anoFicha,
                renavam: normalizeString(normalized?.renavam) || null,
                chassi: normalizeString(normalized?.chassi) || null,
                fichaPayload: result,
            };

            closeEcrvModal();
            await handleEcrvAndamento(true);
        }

        async function performEcrvConsulta() {
            const plate = normalizePlate(ecrvPlateInput.value);
            if (!plate) {
                ecrvPlateError.textContent = 'Informe a placa.';
                return;
            }
            if (!isValidPlate(plate)) {
                ecrvPlateError.textContent = 'Placa inválida.';
                return;
            }

            ecrvPlateError.textContent = '';
            ecrvCaptchaError.textContent = '';
            setEcrvLoading(true);

            try {
                let captchaValue;
                if (ecrvRequireCaptcha) {
                    captchaValue = ecrvCaptchaInput.value.trim().toUpperCase();
                    if (!captchaValue) {
                        ecrvCaptchaError.textContent = 'Informe o captcha.';
                        return;
                    }
                } else {
                    try {
                        captchaValue = await solveBaseCaptcha();
                    } catch (captchaError) {
                        closeEcrvModal();
                        openEcrvModal({
                            requireCaptcha: true,
                            plate,
                            captchaMessage: 'Captcha automático indisponível. Digite o captcha manualmente.',
                        });
                        return;
                    }
                }

                const result = await fetchFichaCadastral(plate, captchaValue);
                await finalizeFichaConsulta(result, plate);
            } catch (error) {
                const message = (error && error.message) ? error.message : 'Não foi possível consultar a ficha cadastral.';
                if (!ecrvRequireCaptcha && message.toLowerCase().includes('captcha')) {
                    closeEcrvModal();
                    openEcrvModal({
                        requireCaptcha: true,
                        plate,
                        captchaMessage: 'Captcha automático falhou. Digite o captcha manualmente.',
                    });
                    return;
                }
                if (ecrvRequireCaptcha) {
                    ecrvCaptchaError.textContent = message;
                } else {
                    ecrvPlateError.textContent = message;
                }
            } finally {
                setEcrvLoading(false);
            }
        }

        async function performEcrvAndamentoCaptchaSearch() {
            const meta = ecrvAndamentoMeta;
            if (!meta) {
                ecrvAndamentoCaptchaError.textContent = 'Reinicie a pesquisa e tente novamente.';
                return;
            }
            const captchaValue = ecrvAndamentoCaptchaInput.value.trim().toUpperCase();
            if (!captchaValue) {
                ecrvAndamentoCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            ecrvAndamentoCaptchaError.textContent = '';
            setEcrvAndamentoLoading(true);

            try {
                await handleEcrvAndamento(false, captchaValue);
            } finally {
                setEcrvAndamentoLoading(false);
            }
        }

        ecrvClose.addEventListener('click', closeEcrvModal);
        ecrvCancel.addEventListener('click', closeEcrvModal);
        ecrvAdvanceBtn.addEventListener('click', performEcrvConsulta);
        ecrvCaptchaRefresh.addEventListener('click', loadEcrvCaptchaImage);

        ecrvAndamentoClose.addEventListener('click', closeEcrvAndamentoModal);
        ecrvAndamentoCancel.addEventListener('click', closeEcrvAndamentoModal);
        ecrvAndamentoBtn.addEventListener('click', performEcrvAndamentoCaptchaSearch);
        ecrvAndamentoCaptchaRefresh.addEventListener('click', loadEcrvAndamentoCaptchaImage);

        if (errorOverlayClose) {
            errorOverlayClose.addEventListener('click', closeErrorModal);
        }
        if (errorOverlay) {
            errorOverlay.addEventListener('click', (event) => {
                if (event.target === errorOverlay) {
                    closeErrorModal();
                }
            });
        }

        function openOtherStatesModal() {
            otherStatesChassi.value = '';
            otherStatesUf.value = '';
            otherStatesError.textContent = '';
            otherStatesOverlay.classList.remove('hidden');
            otherStatesOverlay.classList.add('show');
            otherStatesOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => otherStatesChassi.focus(), 0);
        }

        function closeOtherStatesModal() {
            otherStatesOverlay.classList.remove('show');
            otherStatesOverlay.classList.add('hidden');
            otherStatesOverlay.setAttribute('aria-hidden', 'true');
        }

        function openOtherStatesCaptchaModal(chassi, uf, message = '') {
            otherStatesCaptchaChassi.value = chassi;
            otherStatesCaptchaUf.value = uf;
            otherStatesCaptchaInput.value = '';
            otherStatesCaptchaError.textContent = message;
            otherStatesCaptchaOverlay.classList.remove('hidden');
            otherStatesCaptchaOverlay.classList.add('show');
            otherStatesCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadOtherStatesCaptchaImage();
            setTimeout(() => otherStatesCaptchaInput.focus(), 0);
        }

        function closeOtherStatesCaptchaModal() {
            otherStatesCaptchaOverlay.classList.remove('show');
            otherStatesCaptchaOverlay.classList.add('hidden');
            otherStatesCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearOtherStatesCaptchaImage();
        }

        function setOtherStatesLoading(isLoading) {
            otherStatesSubmit.disabled = isLoading;
            otherStatesSubmit.classList.toggle('loading', isLoading);
        }

        function setOtherStatesCaptchaLoading(isLoading) {
            otherStatesCaptchaSubmit.disabled = isLoading;
            otherStatesCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        function clearOtherStatesCaptchaImage() {
            const currentUrl = otherStatesCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete otherStatesCaptchaImage.dataset.objectUrl;
            }
            otherStatesCaptchaImage.src = '';
        }

        async function loadOtherStatesCaptchaImage() {
            otherStatesCaptchaError.textContent = '';
            otherStatesCaptchaLoading.classList.remove('hidden');
            otherStatesCaptchaImage.classList.add('hidden');
            clearOtherStatesCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                otherStatesCaptchaImage.src = objectUrl;
                otherStatesCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                otherStatesCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                otherStatesCaptchaLoading.classList.add('hidden');
                otherStatesCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchOtherStates(chassi, uf, captcha) {
            const params = new URLSearchParams({
                chassi: chassi,
                uf: uf,
                captcha: captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/another-base-estadual?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar outros estados.' }));
                throw new Error(errorData.message || 'Erro ao consultar outros estados.');
            }

            return await response.json();
        }

        function redirectToOtherStatesResult(result, chassi, uf) {
            sessionStorage.setItem('base_outros_estados_result', JSON.stringify(result));
            sessionStorage.setItem('base_outros_estados_meta', JSON.stringify({ chassi, uf }));
            window.location.href = '/resultado-base-outros-estados';
        }

        async function registerOtherStatesPesquisa(chassi, uf) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Base outros estados',
                        chassi: chassi,
                        opcaoPesquisa: uf,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa:', error);
            }
        }

        async function performOtherStatesSearch() {
            const chassi = normalizeChassi(otherStatesChassi.value);
            const uf = (otherStatesUf.value || '').trim().toUpperCase();

            if (!chassi) {
                otherStatesError.textContent = 'Informe o chassi.';
                return;
            }
            if (!isValidChassi(chassi)) {
                otherStatesError.textContent = 'Chassi inválido.';
                return;
            }
            if (!uf) {
                otherStatesError.textContent = 'Selecione a UF.';
                return;
            }

            otherStatesError.textContent = '';
            setOtherStatesLoading(true);

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeOtherStatesModal();
                    openOtherStatesCaptchaModal(chassi, uf, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchOtherStates(chassi, uf, captcha);
                await registerOtherStatesPesquisa(chassi, uf);
                closeOtherStatesModal();
                redirectToOtherStatesResult(result, chassi, uf);
            } catch (error) {
                const message = error.message || 'Não foi possível consultar a base de outros estados.';
                if (message.toLowerCase().includes('captcha')) {
                    closeOtherStatesModal();
                    openOtherStatesCaptchaModal(chassi, uf, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                otherStatesError.textContent = message;
            } finally {
                setOtherStatesLoading(false);
            }
        }

        async function performOtherStatesCaptchaSearch() {
            const chassi = normalizeChassi(otherStatesCaptchaChassi.value);
            const uf = (otherStatesCaptchaUf.value || '').trim().toUpperCase();
            const captcha = otherStatesCaptchaInput.value.trim().toUpperCase();

            if (!captcha) {
                otherStatesCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            otherStatesCaptchaError.textContent = '';
            setOtherStatesCaptchaLoading(true);

            try {
                const result = await fetchOtherStates(chassi, uf, captcha);
                await registerOtherStatesPesquisa(chassi, uf);
                closeOtherStatesCaptchaModal();
                redirectToOtherStatesResult(result, chassi, uf);
            } catch (error) {
                otherStatesCaptchaError.textContent =
                    error.message || 'Não foi possível consultar a base de outros estados.';
                loadOtherStatesCaptchaImage();
            } finally {
                setOtherStatesCaptchaLoading(false);
            }
        }

        function openRenainfModal() {
            const defaults = getDefaultRenainfDates();
            renainfPlateInput.value = '';
            renainfStatusSelect.value = '2';
            renainfUfSelect.value = '';
            renainfStartDate.value = defaults.start;
            renainfEndDate.value = defaults.end;
            renainfError.textContent = '';
            renainfOverlay.classList.remove('hidden');
            renainfOverlay.classList.add('show');
            renainfOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => renainfPlateInput.focus(), 0);
        }

        function closeRenainfModal() {
            renainfOverlay.classList.remove('show');
            renainfOverlay.classList.add('hidden');
            renainfOverlay.setAttribute('aria-hidden', 'true');
        }

        function openRenainfCaptchaModal(meta, message = '') {
            pendingRenainfRequest = meta;
            renainfCaptchaPlate.value = meta.plate || '';
            renainfCaptchaStatus.value = meta.statusCode || '2';
            renainfCaptchaUf.value = meta.uf || '';
            renainfCaptchaStart.value = meta.startDate || '';
            renainfCaptchaEnd.value = meta.endDate || '';
            renainfCaptchaInput.value = '';
            renainfCaptchaError.textContent = message;
            renainfCaptchaOverlay.classList.remove('hidden');
            renainfCaptchaOverlay.classList.add('show');
            renainfCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadRenainfCaptchaImage();
            setTimeout(() => renainfCaptchaInput.focus(), 0);
        }

        function closeRenainfCaptchaModal() {
            renainfCaptchaOverlay.classList.remove('show');
            renainfCaptchaOverlay.classList.add('hidden');
            renainfCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearRenainfCaptchaImage();
            pendingRenainfRequest = null;
        }

        function setRenainfLoading(isLoading) {
            renainfSubmit.disabled = isLoading;
            renainfSubmit.classList.toggle('loading', isLoading);
        }

        function setRenainfCaptchaLoading(isLoading) {
            renainfCaptchaSubmit.disabled = isLoading;
            renainfCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        function clearRenainfCaptchaImage() {
            const currentUrl = renainfCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete renainfCaptchaImage.dataset.objectUrl;
            }
            renainfCaptchaImage.src = '';
        }

        async function loadRenainfCaptchaImage() {
            renainfCaptchaError.textContent = '';
            renainfCaptchaLoading.classList.remove('hidden');
            renainfCaptchaImage.classList.add('hidden');
            clearRenainfCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                renainfCaptchaImage.src = objectUrl;
                renainfCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                renainfCaptchaError.textContent =
                    error.message || 'Não foi possível carregar o captcha.';
            } finally {
                renainfCaptchaLoading.classList.add('hidden');
                renainfCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchRenainf(payload) {
            const params = new URLSearchParams({
                placa: payload.plate,
                indExigib: payload.statusCode,
                uf: payload.uf,
                periodoIni: payload.periodoIni,
                periodoFin: payload.periodoFin,
                captchaResponse: payload.captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/renainf?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar RENAINF.' }));
                throw new Error(errorData.message || 'Erro ao consultar RENAINF.');
            }

            return await response.json();
        }

        function redirectToRenainfResult(result, meta) {
            sessionStorage.setItem('renainf_result', JSON.stringify(result));
            sessionStorage.setItem('renainf_meta', JSON.stringify(meta));
            window.location.href = '/resultado-renainf';
        }

        async function registerRenainfPesquisa(meta) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'RENAINF',
                        placa: meta.plate,
                        opcaoPesquisa: `${meta.statusCode}-${meta.statusLabel}`,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa RENAINF:', error);
            }
        }

        async function performRenainfSearch() {
            const plate = normalizePlate(renainfPlateInput.value);
            const statusCode = renainfStatusSelect.value || '2';
            const statusLabel = renainfStatusSelect.selectedOptions[0]?.textContent?.trim() || renainfStatusLabels[statusCode];
            const uf = (renainfUfSelect.value || '').trim().toUpperCase();
            const start = renainfStartDate.value;
            const end = renainfEndDate.value;

            if (!plate) {
                renainfError.textContent = 'Informe a placa do veículo.';
                return;
            }
            if (!isValidPlate(plate)) {
                renainfError.textContent = 'Placa inválida.';
                return;
            }
            if (!uf) {
                renainfError.textContent = 'Selecione a UF.';
                return;
            }
            if (!start || !end) {
                renainfError.textContent = 'Informe o período.';
                return;
            }
            if (new Date(start) > new Date(end)) {
                renainfError.textContent = 'A data inicial deve ser anterior à final.';
                return;
            }

            renainfError.textContent = '';
            setRenainfLoading(true);

            const meta = {
                plate,
                statusCode,
                statusLabel: statusLabel || renainfStatusLabels[statusCode],
                uf,
                startDate: start,
                endDate: end,
            };

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeRenainfModal();
                    openRenainfCaptchaModal(meta, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchRenainf({
                    plate,
                    statusCode,
                    statusLabel: meta.statusLabel,
                    uf,
                    periodoIni: formatDateForApi(start),
                    periodoFin: formatDateForApi(end),
                    captcha,
                });
                await registerRenainfPesquisa(meta);
                closeRenainfModal();
                redirectToRenainfResult(result, meta);
            } catch (error) {
                const message = error.message || 'Não foi possível concluir a pesquisa RENAINF.';
                if (message.toLowerCase().includes('captcha')) {
                    closeRenainfModal();
                    openRenainfCaptchaModal(meta, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                renainfError.textContent = message;
            } finally {
                setRenainfLoading(false);
            }
        }

        async function performRenainfCaptchaSearch() {
            const captcha = renainfCaptchaInput.value.trim().toUpperCase();
            const meta = pendingRenainfRequest;

            if (!meta) {
                renainfCaptchaError.textContent = 'Dados da consulta estão faltando.';
                return;
            }
            if (!captcha) {
                renainfCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            renainfCaptchaError.textContent = '';
            setRenainfCaptchaLoading(true);

            try {
                const result = await fetchRenainf({
                    plate: meta.plate,
                    statusCode: meta.statusCode,
                    statusLabel: meta.statusLabel,
                    uf: meta.uf,
                    periodoIni: formatDateForApi(meta.startDate),
                    periodoFin: formatDateForApi(meta.endDate),
                    captcha,
                });
                await registerRenainfPesquisa(meta);
                closeRenainfCaptchaModal();
                redirectToRenainfResult(result, meta);
            } catch (error) {
                renainfCaptchaError.textContent = error.message || 'Não foi possível concluir a pesquisa RENAINF.';
                loadRenainfCaptchaImage();
            } finally {
                setRenainfCaptchaLoading(false);
            }
        }

        function openGravameModal() {
            gravamePlateInput.value = '';
            gravameError.textContent = '';
            gravameOverlay.classList.remove('hidden');
            gravameOverlay.classList.add('show');
            gravameOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => gravamePlateInput.focus(), 0);
        }

        function closeGravameModal() {
            gravameOverlay.classList.remove('show');
            gravameOverlay.classList.add('hidden');
            gravameOverlay.setAttribute('aria-hidden', 'true');
        }

        function openGravameCaptchaModal(placa, message = '') {
            gravameCaptchaPlate.value = placa;
            gravameCaptchaInput.value = '';
            gravameCaptchaError.textContent = message;
            gravameCaptchaOverlay.classList.remove('hidden');
            gravameCaptchaOverlay.classList.add('show');
            gravameCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadGravameCaptchaImage();
            setTimeout(() => gravameCaptchaInput.focus(), 0);
        }

        function closeGravameCaptchaModal() {
            gravameCaptchaOverlay.classList.remove('show');
            gravameCaptchaOverlay.classList.add('hidden');
            gravameCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearGravameCaptchaImage();
        }

        function setGravameLoading(isLoading) {
            gravameSubmit.disabled = isLoading;
            gravameSubmit.classList.toggle('loading', isLoading);
        }

        function setGravameCaptchaLoading(isLoading) {
            gravameCaptchaSubmit.disabled = isLoading;
            gravameCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        function clearGravameCaptchaImage() {
            const currentUrl = gravameCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete gravameCaptchaImage.dataset.objectUrl;
            }
            gravameCaptchaImage.src = '';
        }

        async function loadGravameCaptchaImage() {
            gravameCaptchaError.textContent = '';
            gravameCaptchaLoading.classList.remove('hidden');
            gravameCaptchaImage.classList.add('hidden');
            clearGravameCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                gravameCaptchaImage.src = objectUrl;
                gravameCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                gravameCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                gravameCaptchaLoading.classList.add('hidden');
                gravameCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchGravame(placa, captcha) {
            const params = new URLSearchParams({
                placa: placa,
                captcha: captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/gravame?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar gravame.' }));
                throw new Error(errorData.message || 'Erro ao consultar gravame.');
            }

            return await response.json();
        }

        function redirectToGravameResult(result, meta) {
            sessionStorage.setItem('gravame_result', JSON.stringify(result));
            sessionStorage.setItem('gravame_meta', JSON.stringify(meta));
            window.location.href = '/resultado-gravame';
        }

        async function registerGravamePesquisa(placa) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Gravame',
                        placa: placa,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa Gravame:', error);
            }
        }

        async function performGravameSearch() {
            const placa = normalizePlate(gravamePlateInput.value);

            if (!placa) {
                gravameError.textContent = 'Informe a placa do veículo.';
                return;
            }
            if (!isValidPlate(placa)) {
                gravameError.textContent = 'Placa inválida.';
                return;
            }

            gravameError.textContent = '';
            setGravameLoading(true);

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeGravameModal();
                    openGravameCaptchaModal(placa, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchGravame(placa, captcha);
                await registerGravamePesquisa(placa);
                closeGravameModal();
                const veiculoData = result?.veiculo || {};
                redirectToGravameResult(result, {
                    placa,
                    renavam: veiculoData.renavam || '',
                    uf: veiculoData.uf || '',
                });
            } catch (error) {
                const message = error.message || 'Não foi possível concluir a pesquisa Gravame.';
                if (message.toLowerCase().includes('captcha')) {
                    closeGravameModal();
                    openGravameCaptchaModal(placa, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                gravameError.textContent = message;
            } finally {
                setGravameLoading(false);
            }
        }

        async function performGravameCaptchaSearch() {
            const placa = normalizePlate(gravameCaptchaPlate.value);
            const captcha = gravameCaptchaInput.value.trim().toUpperCase();

            if (!captcha) {
                gravameCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            gravameCaptchaError.textContent = '';
            setGravameCaptchaLoading(true);

            try {
                const result = await fetchGravame(placa, captcha);
                await registerGravamePesquisa(placa);
                closeGravameCaptchaModal();
                const veiculoData = result?.veiculo || {};
                redirectToGravameResult(result, {
                    placa,
                    renavam: veiculoData.renavam || '',
                    uf: veiculoData.uf || '',
                });
            } catch (error) {
                gravameCaptchaError.textContent = error.message || 'Não foi possível concluir a pesquisa Gravame.';
                loadGravameCaptchaImage();
            } finally {
                setGravameCaptchaLoading(false);
            }
        }

        function getSelectedBinOption(inputs, fallback = 'placa') {
            const selected = inputs.find((input) => input.checked);
            return selected ? selected.value : fallback;
        }

        function setSelectedBinOption(inputs, value) {
            inputs.forEach((input) => {
                input.checked = input.value === value;
            });
        }

        function updateBinMode(option, placaGroup, chassiGroup) {
            const isChassi = option === 'chassi';
            placaGroup.classList.toggle('hidden', isChassi);
            chassiGroup.classList.toggle('hidden', !isChassi);
        }

        function openBinModal() {
            binPlacaInput.value = '';
            binRenavamInput.value = '';
            binChassiInput.value = '';
            binError.textContent = '';
            setSelectedBinOption(binOptionInputs, 'placa');
            updateBinMode('placa', binPlacaGroup, binChassiGroup);
            binOverlay.classList.remove('hidden');
            binOverlay.classList.add('show');
            binOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => binPlacaInput.focus(), 0);
        }

        function closeBinModal() {
            binOverlay.classList.remove('show');
            binOverlay.classList.add('hidden');
            binOverlay.setAttribute('aria-hidden', 'true');
        }

        function openBinCaptchaModal(values, message = '') {
            const option = values.option || 'placa';
            setSelectedBinOption(binCaptchaOptionInputs, option);
            updateBinMode(option, binCaptchaPlacaGroup, binCaptchaChassiGroup);
            binCaptchaPlacaInput.value = values.placa || '';
            binCaptchaRenavamInput.value = values.renavam || '';
            binCaptchaChassiInput.value = values.chassi || '';
            binCaptchaInput.value = '';
            binCaptchaError.textContent = message;
            binCaptchaOverlay.classList.remove('hidden');
            binCaptchaOverlay.classList.add('show');
            binCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadBinCaptchaImage();
            setTimeout(() => binCaptchaInput.focus(), 0);
        }

        function closeBinCaptchaModal() {
            binCaptchaOverlay.classList.remove('show');
            binCaptchaOverlay.classList.add('hidden');
            binCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearBinCaptchaImage();
        }

        function setBinLoading(isLoading) {
            binSubmit.disabled = isLoading;
            binSubmit.classList.toggle('loading', isLoading);
        }

        function setBinCaptchaLoading(isLoading) {
            binCaptchaSubmit.disabled = isLoading;
            binCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        function clearBinCaptchaImage() {
            const currentUrl = binCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete binCaptchaImage.dataset.objectUrl;
            }
            binCaptchaImage.src = '';
        }

        async function loadBinCaptchaImage() {
            binCaptchaError.textContent = '';
            binCaptchaLoading.classList.remove('hidden');
            binCaptchaImage.classList.add('hidden');
            clearBinCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                binCaptchaImage.src = objectUrl;
                binCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                binCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                binCaptchaLoading.classList.add('hidden');
                binCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchBin(payload) {
            const params = new URLSearchParams({
                captcha: payload.captcha,
                opcao: payload.option,
            });

            if (payload.option === '1') {
                params.set('chassi', payload.chassi);
            } else {
                params.set('placa', payload.placa);
                params.set('renavam', payload.renavam);
            }

            const response = await fetch(`${API_BASE_URL}/api/bin?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar BIN.' }));
                throw new Error(errorData.message || 'Erro ao consultar BIN.');
            }

            return await response.json();
        }

        function redirectToBinResult(result, meta) {
            sessionStorage.setItem('bin_result', JSON.stringify(result));
            sessionStorage.setItem('bin_meta', JSON.stringify(meta));
            window.location.href = '/resultado-bin';
        }

        async function registerBinPesquisa(meta) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'BIN',
                        placa: meta.placa || null,
                        renavam: meta.renavam || null,
                        chassi: meta.chassi || null,
                        opcaoPesquisa: meta.option,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa BIN:', error);
            }
        }

        function buildBinPayload(option, placa, renavam, chassi, captcha) {
            return {
                option,
                placa: placa,
                renavam: renavam,
                chassi: chassi,
                captcha: captcha,
            };
        }

        async function performBinSearch() {
            const option = getSelectedBinOption(binOptionInputs);
            const isChassi = option === 'chassi';
            const placa = normalizePlate(binPlacaInput.value);
            const renavam = normalizeRenavam(binRenavamInput.value);
            const chassi = normalizeChassi(binChassiInput.value);

            if (isChassi) {
                if (!chassi) {
                    binError.textContent = 'Informe o chassi.';
                    return;
                }
                if (!isValidChassi(chassi)) {
                    binError.textContent = 'Chassi inválido.';
                    return;
                }
            } else {
                if (!placa) {
                    binError.textContent = 'Informe a placa.';
                    return;
                }
                if (!isValidPlate(placa)) {
                    binError.textContent = 'Placa inválida.';
                    return;
                }
                if (!renavam) {
                    binError.textContent = 'Informe o renavam.';
                    return;
                }
                if (!isValidRenavam(renavam)) {
                    binError.textContent = 'Renavam inválido.';
                    return;
                }
            }

            binError.textContent = '';
            setBinLoading(true);

            const searchOption = isChassi ? '1' : '2';
            const meta = {
                option: searchOption,
                placa: isChassi ? '' : placa,
                renavam: isChassi ? '' : renavam,
                chassi: isChassi ? chassi : '',
            };

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeBinModal();
                    openBinCaptchaModal({ ...meta, option: option }, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchBin(buildBinPayload(searchOption, meta.placa, meta.renavam, meta.chassi, captcha));
                await registerBinPesquisa(meta);
                closeBinModal();
                redirectToBinResult(result, meta);
            } catch (error) {
                const message = error.message || 'Não foi possível concluir a pesquisa BIN.';
                if (message.toLowerCase().includes('captcha')) {
                    closeBinModal();
                    openBinCaptchaModal({ ...meta, option: option }, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                binError.textContent = message;
            } finally {
                setBinLoading(false);
            }
        }

        async function performBinCaptchaSearch() {
            const option = getSelectedBinOption(binCaptchaOptionInputs);
            const isChassi = option === 'chassi';
            const placa = normalizePlate(binCaptchaPlacaInput.value);
            const renavam = normalizeRenavam(binCaptchaRenavamInput.value);
            const chassi = normalizeChassi(binCaptchaChassiInput.value);
            const captcha = binCaptchaInput.value.trim().toUpperCase();

            if (!captcha) {
                binCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            if (isChassi) {
                if (!chassi || !isValidChassi(chassi)) {
                    binCaptchaError.textContent = 'Chassi inválido.';
                    return;
                }
            } else {
                if (!placa || !isValidPlate(placa)) {
                    binCaptchaError.textContent = 'Placa inválida.';
                    return;
                }
                if (!renavam || !isValidRenavam(renavam)) {
                    binCaptchaError.textContent = 'Renavam inválido.';
                    return;
                }
            }

            binCaptchaError.textContent = '';
            setBinCaptchaLoading(true);

            const searchOption = isChassi ? '1' : '2';
            const meta = {
                option: searchOption,
                placa: isChassi ? '' : placa,
                renavam: isChassi ? '' : renavam,
                chassi: isChassi ? chassi : '',
            };

            try {
                const result = await fetchBin(buildBinPayload(searchOption, meta.placa, meta.renavam, meta.chassi, captcha));
                await registerBinPesquisa(meta);
                closeBinCaptchaModal();
                redirectToBinResult(result, meta);
            } catch (error) {
                binCaptchaError.textContent = error.message || 'Não foi possível concluir a pesquisa BIN.';
                loadBinCaptchaImage();
            } finally {
                setBinCaptchaLoading(false);
            }
        }

        function setupActionToggles() {
            document.querySelectorAll('[data-toggle]').forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const card = toggle.closest('.action-card');
                    if (!card) return;
                    const expanded = card.classList.toggle('action-card--expanded');
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            });

            document.querySelectorAll('[data-action="base-estadual"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openBaseEstadualModal();
                });
            });

            document.querySelectorAll('[data-action="base-outros-estados"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openOtherStatesModal();
                });
            });

            document.querySelectorAll('[data-action="bin"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openBinModal();
                });
            });
            document.querySelectorAll('[data-action="renainf"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openRenainfModal();
                });
            });
            document.querySelectorAll('[data-action="gravame"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openGravameModal();
                });
            });
            document.querySelectorAll('[data-action="bloqueios-ativos"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openBloqueiosModal();
                });
            });
            document.querySelectorAll('[data-action="andamento-ecrv"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openEcrvModal();
                });
            });

            document.querySelectorAll('[data-href]').forEach((item) => {
                item.addEventListener('click', () => {
                    const href = item.dataset.href;
                    if (href) {
                        window.location.href = href;
                    }
                });
            });

            document.querySelectorAll('[data-disabled="true"]').forEach((item) => {
                item.addEventListener('click', () => {
                    showErrorModal('Funcionalidade em desenvolvimento.');
                });
            });
        }

        document.getElementById('logoutBtn').addEventListener('click', async function() {
            if (!confirm('Deseja realmente sair?')) {
                return;
            }

            try {
                if (authToken) {
                    await fetch(`${API_BASE_URL}/api/auth/logout`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${authToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });
                }
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            } finally {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
        });

        document.getElementById('profileBtn').addEventListener('click', function() {
            window.location.href = '/perfil';
        });

        document.getElementById('openPortalBtn').addEventListener('click', function() {
            window.open('https://www.e-crvsp.sp.gov.br/', '_blank', 'noopener');
        });

        baseQueryClose.addEventListener('click', closeBaseEstadualModal);
        baseCancelBtn.addEventListener('click', closeBaseEstadualModal);
        baseQueryOverlay.addEventListener('click', (event) => {
            if (event.target === baseQueryOverlay) {
                closeBaseEstadualModal();
            }
        });
        basePlateInput.addEventListener('input', () => {
            basePlateInput.value = normalizePlate(basePlateInput.value);
            basePlateError.textContent = '';
        });
        basePlateInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performBaseEstadualSearch();
            }
        });
        baseConsultBtn.addEventListener('click', performBaseEstadualSearch);

        baseCaptchaClose.addEventListener('click', closeBaseCaptchaModal);
        baseCaptchaCancel.addEventListener('click', closeBaseCaptchaModal);
        baseCaptchaRefresh.addEventListener('click', loadBaseCaptchaImage);
        baseCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === baseCaptchaOverlay) {
                closeBaseCaptchaModal();
            }
        });
        baseCaptchaInput.addEventListener('input', () => {
            baseCaptchaInput.value = baseCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            baseCaptchaError.textContent = '';
        });
        baseCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performBaseCaptchaSearch();
            }
        });
        baseCaptchaSubmit.addEventListener('click', performBaseCaptchaSearch);

        otherStatesClose.addEventListener('click', closeOtherStatesModal);
        otherStatesCancel.addEventListener('click', closeOtherStatesModal);
        otherStatesOverlay.addEventListener('click', (event) => {
            if (event.target === otherStatesOverlay) {
                closeOtherStatesModal();
            }
        });
        otherStatesChassi.addEventListener('input', () => {
            otherStatesChassi.value = normalizeChassi(otherStatesChassi.value);
            otherStatesError.textContent = '';
        });
        otherStatesUf.addEventListener('change', () => {
            otherStatesError.textContent = '';
        });
        otherStatesChassi.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performOtherStatesSearch();
            }
        });
        otherStatesSubmit.addEventListener('click', performOtherStatesSearch);

        otherStatesCaptchaClose.addEventListener('click', closeOtherStatesCaptchaModal);
        otherStatesCaptchaCancel.addEventListener('click', closeOtherStatesCaptchaModal);
        otherStatesCaptchaRefresh.addEventListener('click', loadOtherStatesCaptchaImage);
        otherStatesCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === otherStatesCaptchaOverlay) {
                closeOtherStatesCaptchaModal();
            }
        });
        otherStatesCaptchaInput.addEventListener('input', () => {
            otherStatesCaptchaInput.value = otherStatesCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            otherStatesCaptchaError.textContent = '';
        });
        otherStatesCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performOtherStatesCaptchaSearch();
            }
        });
        otherStatesCaptchaSubmit.addEventListener('click', performOtherStatesCaptchaSearch);

        renainfClose.addEventListener('click', closeRenainfModal);
        renainfCancel.addEventListener('click', closeRenainfModal);
        renainfOverlay.addEventListener('click', (event) => {
            if (event.target === renainfOverlay) {
                closeRenainfModal();
            }
        });
        renainfPlateInput.addEventListener('input', () => {
            renainfPlateInput.value = normalizePlate(renainfPlateInput.value);
            renainfError.textContent = '';
        });
        renainfStatusSelect.addEventListener('change', () => {
            renainfError.textContent = '';
        });
        renainfUfSelect.addEventListener('change', () => {
            renainfError.textContent = '';
        });
        renainfStartDate.addEventListener('change', () => {
            renainfError.textContent = '';
        });
        renainfEndDate.addEventListener('change', () => {
            renainfError.textContent = '';
        });
        renainfSubmit.addEventListener('click', performRenainfSearch);

        renainfCaptchaClose.addEventListener('click', closeRenainfCaptchaModal);
        renainfCaptchaCancel.addEventListener('click', closeRenainfCaptchaModal);
        renainfCaptchaRefresh.addEventListener('click', loadRenainfCaptchaImage);
        renainfCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === renainfCaptchaOverlay) {
                closeRenainfCaptchaModal();
            }
        });
        renainfCaptchaInput.addEventListener('input', () => {
            renainfCaptchaInput.value = renainfCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            renainfCaptchaError.textContent = '';
        });
        renainfCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performRenainfCaptchaSearch();
            }
        });
        renainfCaptchaSubmit.addEventListener('click', performRenainfCaptchaSearch);

        binClose.addEventListener('click', closeBinModal);
        binCancel.addEventListener('click', closeBinModal);
        binOverlay.addEventListener('click', (event) => {
            if (event.target === binOverlay) {
                closeBinModal();
            }
        });
        binOptionInputs.forEach((input) => {
            input.addEventListener('change', () => {
                const option = getSelectedBinOption(binOptionInputs);
                updateBinMode(option, binPlacaGroup, binChassiGroup);
                binError.textContent = '';
            });
        });
        binPlacaInput.addEventListener('input', () => {
            binPlacaInput.value = normalizePlate(binPlacaInput.value);
            binError.textContent = '';
        });
        binRenavamInput.addEventListener('input', () => {
            binRenavamInput.value = normalizeRenavam(binRenavamInput.value);
            binError.textContent = '';
        });
        binChassiInput.addEventListener('input', () => {
            binChassiInput.value = normalizeChassi(binChassiInput.value);
            binError.textContent = '';
        });
        binSubmit.addEventListener('click', performBinSearch);

        binCaptchaClose.addEventListener('click', closeBinCaptchaModal);
        binCaptchaCancel.addEventListener('click', closeBinCaptchaModal);
        binCaptchaRefresh.addEventListener('click', loadBinCaptchaImage);
        binCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === binCaptchaOverlay) {
                closeBinCaptchaModal();
            }
        });
        binCaptchaOptionInputs.forEach((input) => {
            input.addEventListener('change', () => {
                const option = getSelectedBinOption(binCaptchaOptionInputs);
                updateBinMode(option, binCaptchaPlacaGroup, binCaptchaChassiGroup);
                binCaptchaError.textContent = '';
            });
        });
        binCaptchaPlacaInput.addEventListener('input', () => {
            binCaptchaPlacaInput.value = normalizePlate(binCaptchaPlacaInput.value);
            binCaptchaError.textContent = '';
        });
        binCaptchaRenavamInput.addEventListener('input', () => {
            binCaptchaRenavamInput.value = normalizeRenavam(binCaptchaRenavamInput.value);
            binCaptchaError.textContent = '';
        });
        binCaptchaChassiInput.addEventListener('input', () => {
            binCaptchaChassiInput.value = normalizeChassi(binCaptchaChassiInput.value);
            binCaptchaError.textContent = '';
        });
        binCaptchaInput.addEventListener('input', () => {
            binCaptchaInput.value = binCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            binCaptchaError.textContent = '';
        });
        binCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performBinCaptchaSearch();
            }
        });
        binCaptchaSubmit.addEventListener('click', performBinCaptchaSearch);

        gravameClose.addEventListener('click', closeGravameModal);
        gravameCancel.addEventListener('click', closeGravameModal);
        gravameOverlay.addEventListener('click', (event) => {
            if (event.target === gravameOverlay) {
                closeGravameModal();
            }
        });
        gravamePlateInput.addEventListener('input', () => {
            gravamePlateInput.value = normalizePlate(gravamePlateInput.value);
            gravameError.textContent = '';
        });
        gravamePlateInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performGravameSearch();
            }
        });
        gravameSubmit.addEventListener('click', performGravameSearch);

        gravameCaptchaClose.addEventListener('click', closeGravameCaptchaModal);
        gravameCaptchaCancel.addEventListener('click', closeGravameCaptchaModal);
        gravameCaptchaRefresh.addEventListener('click', loadGravameCaptchaImage);
        gravameCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === gravameCaptchaOverlay) {
                closeGravameCaptchaModal();
            }
        });
        gravameCaptchaInput.addEventListener('input', () => {
            gravameCaptchaInput.value = gravameCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            gravameCaptchaError.textContent = '';
        });
        gravameCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performGravameCaptchaSearch();
            }
        });
        gravameCaptchaSubmit.addEventListener('click', performGravameCaptchaSearch);

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }
            if (!binCaptchaOverlay.classList.contains('hidden')) {
                closeBinCaptchaModal();
                return;
            }
            if (!renainfCaptchaOverlay.classList.contains('hidden')) {
                closeRenainfCaptchaModal();
                return;
            }
            if (!renainfOverlay.classList.contains('hidden')) {
                closeRenainfModal();
                return;
            }
            if (!binOverlay.classList.contains('hidden')) {
                closeBinModal();
                return;
            }
            if (!gravameCaptchaOverlay.classList.contains('hidden')) {
                closeGravameCaptchaModal();
                return;
            }
            if (!gravameOverlay.classList.contains('hidden')) {
                closeGravameModal();
                return;
            }
            if (!otherStatesCaptchaOverlay.classList.contains('hidden')) {
                closeOtherStatesCaptchaModal();
                return;
            }
            if (!otherStatesOverlay.classList.contains('hidden')) {
                closeOtherStatesModal();
                return;
            }
            if (!baseCaptchaOverlay.classList.contains('hidden')) {
                closeBaseCaptchaModal();
                return;
            }
            if (!baseQueryOverlay.classList.contains('hidden')) {
                closeBaseEstadualModal();
            }
        });

        permissionsRetry.addEventListener('click', loadPermissions);
        recentRetry.addEventListener('click', loadRecentVehicles);

        if (checkAuth()) {
            setupActionToggles();
            loadMonthlyCredits();
            loadPermissions();
            loadRecentVehicles();
        }
    </script>
</body>
</html>
