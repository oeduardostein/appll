<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Emissão da ATPV-e - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0b52c2;
            --primary-dark: #082f82;
            --accent: #3e6cf0;
            --bg: #f6f7fb;
            --card: #ffffff;
            --card-muted: #eef1ff;
            --border: #e1e8ff;
            --text-strong: #0f172a;
            --text-muted: #475569;
            --text-soft: #6b7280;
            --success: #0f9d58;
            --warning: #f59e0b;
            --error: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(11, 60, 180, 0.25), transparent 40%),
                var(--bg);
            color: var(--text-strong);
        }

        .atpv-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .atpv-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 28px 20px 34px;
            border-radius: 0 0 32px 32px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .atpv-header-title h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .atpv-header-title p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
        }

        .icon-button {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            outline: none;
        }

        .atpv-body {
            flex: 1;
            width: min(960px, 100%);
            margin: 0 auto;
            padding: 32px 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .atpv-card {
            background: var(--card);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 20px 32px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(15, 23, 42, 0.04);
        }

        .atpv-intro-card {
            background: linear-gradient(135deg, rgba(14, 83, 194, 0.12), rgba(69, 99, 170, 0.12));
            border: none;
        }

        .atpv-intro-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .atpv-intro-card p {
            color: var(--text-soft);
        }

        .intro-actions {
            margin-top: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .section-heading {
            margin-bottom: 14px;
        }

        .section-heading h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .section-heading p {
            color: var(--text-soft);
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
        }

        .input-group label {
            font-size: 13px;
            color: var(--text-soft);
        }

        .input-group input,
        .input-group select {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 14px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            transition: border-color 0.2s ease;
        }

        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .doc-selector {
            display: flex;
            gap: 10px;
            background: var(--card-muted);
            border-radius: 18px;
            padding: 6px;
        }

        .doc-selector button {
            flex: 1;
            border: none;
            background: transparent;
            border-radius: 16px;
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-strong);
            transition: background 0.2s ease, color 0.2s ease;
        }

        .doc-selector button.is-active {
            background: var(--primary);
            color: #fff;
        }

        .input-helper {
            font-size: 12px;
            color: var(--text-soft);
            margin-top: 4px;
        }

        .input-helper.warning {
            color: var(--warning);
        }

        .input-helper.success {
            color: var(--success);
        }

        .input-helper.error {
            color: var(--error);
        }

        .captcha-block {
            border-radius: 20px;
            border: 1px dashed var(--border);
            padding: 18px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .captcha-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 16px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 10px 24px rgba(11, 82, 194, 0.25);
        }

        .btn-secondary {
            background: #fff;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-ghost {
            background: transparent;
            color: var(--primary);
            border: 1px solid rgba(15, 23, 42, 0.1);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid rgba(220, 38, 38, 0.2);
            background: rgba(248, 113, 113, 0.15);
            color: var(--error);
            margin-bottom: 12px;
        }

        .hidden {
            display: none !important;
        }

        .manual-captcha {
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 16px;
            background: #fff;
        }

        .manual-captcha img {
            max-width: 260px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .manual-captcha .captcha-image-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .captcha-toggle {
            padding: 0;
            border-radius: 12px;
            border: 1px dashed var(--border);
            background: transparent;
            color: var(--primary);
        }

        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: var(--text-soft);
        }

        .terms-row input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
        }

        .signature-block {
            margin-top: 18px;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            padding-top: 18px;
        }

        .signature-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .success-top {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .success-icon {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            background: rgba(15, 157, 88, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg {
            width: 32px;
            height: 32px;
            color: var(--success);
        }

        .success-summary {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .success-summary dt {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-soft);
        }

        .success-summary dd {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .success-actions {
            margin-top: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .success-message-stack {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        @media (max-width: 640px) {
            .atpv-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .success-top {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="atpv-page">
        <header class="atpv-header">
            <div class="atpv-header-title">
                <p class="eyebrow">Serviços</p>
                <h1>Emissão da ATPV-e</h1>
                <p>Informe todos os dados do veículo e comprador para emitir sua autorização.</p>
            </div>
            <button type="button" class="icon-button" id="atpvBackBtn" aria-label="Voltar para o painel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
        </header>
        <main class="atpv-body">
            <section class="atpv-card atpv-intro-card">
                <div>
                    <p class="eyebrow">Fluxo completo</p>
                    <h2>Formulário completo</h2>
                    <p>Esta versão web replica o aplicativo Flutter: preencha os dados do veículo e comprador, resolva o captcha e conclua a emissão com registro da assinatura.</p>
                </div>
            </section>

            <section class="atpv-card" id="atpvFormSection">
                <form id="atpvForm" novalidate autocomplete="off">
                    <div class="section-heading">
                        <h3>Dados do veículo</h3>
                        <p>Informe o Renavam, placa e demais dados do proprietário atual.</p>
                    </div>
                    <div class="form-grid">
                        <label class="input-group">
                            <span>Placa</span>
                            <input type="text" id="plateInput" placeholder="ABC1D23" maxlength="7" inputmode="text" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Renavam</span>
                            <input type="text" id="renavamInput" maxlength="11" inputmode="numeric" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Chassi (opcional)</span>
                            <input type="text" id="chassiInput" maxlength="17" inputmode="text" autocomplete="off" />
                        </label>
                        <div class="input-group full-width">
                            <span>Documento do proprietário</span>
                            <div class="doc-selector" id="ownerDocSelector">
                                <button type="button" data-owner-doc-option="1" class="doc-option is-active">CPF</button>
                                <button type="button" data-owner-doc-option="2" class="doc-option">CNPJ</button>
                            </div>
                        </div>
                        <label class="input-group">
                            <span>CPF/CNPJ do proprietário atual</span>
                            <input type="text" id="ownerDocInput" inputmode="numeric" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>E-mail do proprietário (opcional)</span>
                            <input type="email" id="ownerEmailInput" autocomplete="email" />
                        </label>
                        <label class="input-group">
                            <span>Valor da venda (opcional)</span>
                            <input type="text" id="saleValueInput" inputmode="numeric" placeholder="0,00" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Hodômetro (opcional)</span>
                            <input type="text" id="odometerInput" inputmode="numeric" autocomplete="off" />
                        </label>
                    </div>

                    <div class="section-heading" style="margin-top: 32px;">
                        <h3>Dados do comprador</h3>
                        <p>Informe o CPF/CNPJ, endereço e UF conforme o cadastro no Detran.</p>
                    </div>
                    <div class="form-grid">
                        <div class="input-group full-width">
                            <span>Documento do comprador</span>
                            <div class="doc-selector" id="buyerDocSelector">
                                <button type="button" data-buyer-doc-option="1" class="doc-option is-active">CPF</button>
                                <button type="button" data-buyer-doc-option="2" class="doc-option">CNPJ</button>
                            </div>
                        </div>
                        <label class="input-group">
                            <span>CPF/CNPJ do comprador</span>
                            <input type="text" id="buyerDocInput" inputmode="numeric" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Nome completo do comprador</span>
                            <input type="text" id="buyerNameInput" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>E-mail do comprador (opcional)</span>
                            <input type="email" id="buyerEmailInput" autocomplete="email" />
                        </label>
                        <label class="input-group">
                            <span>CEP</span>
                            <div class="captcha-actions">
                                <input type="text" id="buyerCepInput" maxlength="9" placeholder="00000-000" inputmode="numeric" autocomplete="off" />
                                <button type="button" id="buyerCepLookupBtn" class="btn-ghost" style="padding: 10px 14px;">Buscar</button>
                            </div>
                            <span id="cepHelper" class="input-helper"></span>
                        </label>
                        <label class="input-group">
                            <span>Número</span>
                            <input type="text" id="buyerNumberInput" inputmode="numeric" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Complemento (opcional)</span>
                            <input type="text" id="buyerComplementInput" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Município</span>
                            <input type="text" id="buyerCityInput" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Bairro</span>
                            <input type="text" id="buyerNeighborhoodInput" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>Logradouro</span>
                            <input type="text" id="buyerStreetInput" autocomplete="off" />
                        </label>
                        <label class="input-group">
                            <span>UF</span>
                            <input type="text" id="buyerStateInput" maxlength="2" inputmode="text" autocomplete="off" />
                        </label>
                    </div>

                    <div class="section-heading" style="margin-top: 32px;">
                        <h3>Validação</h3>
                        <p>Resolva o captcha e confirme os termos para concluir a emissão.</p>
                    </div>
                    <div class="captcha-block">
                        <p style="font-weight: 600;">Captcha automático</p>
                        <p class="input-helper">Os captchas são resolvidos automaticamente, mas você pode trocar para o modo manual quando precisar.</p>
                        <button type="button" id="manualCaptchaToggle" class="btn captcha-toggle">Digitar captcha manualmente</button>
                        <div id="manualCaptchaMessage" class="input-helper warning hidden"></div>
                        <div id="manualCaptchaArea" class="manual-captcha hidden">
                            <div class="captcha-image-wrapper">
                                <img id="manualCaptchaImage" alt="Captcha" />
                                <button type="button" id="manualCaptchaRefresh" class="btn-ghost" style="padding: 8px 12px;">Atualizar</button>
                            </div>
                            <input type="text" id="manualCaptchaInput" placeholder="Digite o captcha" autocomplete="off" />
                            <span id="manualCaptchaError" class="input-helper error"></span>
                        </div>
                    </div>

                    <div class="terms-row">
                        <input type="checkbox" id="termsCheckbox" />
                        <label for="termsCheckbox">Confirmo que as informações estão corretas e autorizo o envio ao Detran.</label>
                    </div>

                    <div id="formError" class="alert hidden" role="alert" aria-live="assertive"></div>

                    <div style="margin-top: 18px;">
                        <button type="submit" id="atpvSubmitBtn" class="btn btn-primary">Enviar ATPV-e</button>
                    </div>
                </form>
            </section>

            <section id="atpvSuccessSection" class="atpv-card hidden" aria-live="polite" role="status">
                <div class="success-top">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 id="successTitle">ATPV-e emitida</h3>
                        <p id="successSubtitle" style="color: var(--text-soft);"></p>
                    </div>
                </div>
                <dl class="success-summary">
                    <div>
                        <dt>Placa</dt>
                        <dd id="summaryPlate"></dd>
                    </div>
                    <div>
                        <dt>Renavam</dt>
                        <dd id="summaryRenavam"></dd>
                    </div>
                    <div>
                        <dt>Número ATPV</dt>
                        <dd id="summaryAtpvNumber"></dd>
                    </div>
                </dl>
                <div id="signaturePrompt" class="signature-block">
                    <p style="font-weight: 600;">Deseja registrar a assinatura digital?</p>
                    <div class="signature-actions">
                        <button type="button" class="btn btn-primary signature-btn" data-signed="true">Sim, registrar agora</button>
                        <button type="button" class="btn btn-secondary signature-btn" data-signed="false">Assinar posteriormente</button>
                    </div>
                    <p id="signatureStatusMessage" class="input-helper success hidden"></p>
                </div>
                <div class="success-actions">
                    <button type="button" id="downloadPdfBtn" class="btn btn-outline">Baixar PDF da ATPV-e</button>
                    <button type="button" id="resetFormBtn" class="btn btn-ghost">Emitir outra ATPV-e</button>
                </div>
                <div class="success-message-stack">
                    <p id="successMessage" class="input-helper success hidden"></p>
                    <p id="pdfStatusMessage" class="input-helper"></p>
                </div>
            </section>
        </main>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
        let authToken = null;
        let ownerDocOption = 1;
        let buyerDocOption = 1;
        let manualCaptchaMode = false;
        let manualCaptchaImageUrl = null;
        let manualCaptchaLoading = false;
        let buyerMunicipioCode = null;
        let lastCaptchaValue = null;
        let submissionInProgress = false;
        let atpvRegistroId = null;

        const oldPlatePattern = /^[A-Z]{3}\d{4}$/;
        const mercosurPlatePattern = /^[A-Z]{3}\d[A-Z0-9]\d{2}$/;
        const renavamPattern = /^\d{11}$/;

        const plateInput = document.getElementById('plateInput');
        const renavamInput = document.getElementById('renavamInput');
        const chassiInput = document.getElementById('chassiInput');
        const ownerDocInput = document.getElementById('ownerDocInput');
        const ownerEmailInput = document.getElementById('ownerEmailInput');
        const saleValueInput = document.getElementById('saleValueInput');
        const odometerInput = document.getElementById('odometerInput');
        const buyerDocInput = document.getElementById('buyerDocInput');
        const buyerNameInput = document.getElementById('buyerNameInput');
        const buyerEmailInput = document.getElementById('buyerEmailInput');
        const buyerCepInput = document.getElementById('buyerCepInput');
        const buyerNumberInput = document.getElementById('buyerNumberInput');
        const buyerComplementInput = document.getElementById('buyerComplementInput');
        const buyerCityInput = document.getElementById('buyerCityInput');
        const buyerNeighborhoodInput = document.getElementById('buyerNeighborhoodInput');
        const buyerStreetInput = document.getElementById('buyerStreetInput');
        const buyerStateInput = document.getElementById('buyerStateInput');
        const buyerCepLookupBtn = document.getElementById('buyerCepLookupBtn');
        const cepHelper = document.getElementById('cepHelper');
        const manualCaptchaToggle = document.getElementById('manualCaptchaToggle');
        const manualCaptchaArea = document.getElementById('manualCaptchaArea');
        const manualCaptchaImage = document.getElementById('manualCaptchaImage');
        const manualCaptchaRefresh = document.getElementById('manualCaptchaRefresh');
        const manualCaptchaInput = document.getElementById('manualCaptchaInput');
        const manualCaptchaError = document.getElementById('manualCaptchaError');
        const manualCaptchaMessage = document.getElementById('manualCaptchaMessage');
        const termsCheckbox = document.getElementById('termsCheckbox');
        const formError = document.getElementById('formError');
        const atpvForm = document.getElementById('atpvForm');
        const atpvSubmitBtn = document.getElementById('atpvSubmitBtn');
        const atpvSuccessSection = document.getElementById('atpvSuccessSection');
        const successSubtitle = document.getElementById('successSubtitle');
        const summaryPlate = document.getElementById('summaryPlate');
        const summaryRenavam = document.getElementById('summaryRenavam');
        const summaryAtpvNumber = document.getElementById('summaryAtpvNumber');
        const successMessage = document.getElementById('successMessage');
        const pdfStatusMessage = document.getElementById('pdfStatusMessage');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');
        const resetFormBtn = document.getElementById('resetFormBtn');
        const signatureButtons = document.querySelectorAll('.signature-btn');
        const signatureStatusMessage = document.getElementById('signatureStatusMessage');
        const atpvBackBtn = document.getElementById('atpvBackBtn');
        const ownerDocSelector = document.getElementById('ownerDocSelector');
        const buyerDocSelector = document.getElementById('buyerDocSelector');

        const ownerDocButtons = ownerDocSelector.querySelectorAll('[data-owner-doc-option]');
        const buyerDocButtons = buyerDocSelector.querySelectorAll('[data-buyer-doc-option]');

        document.addEventListener('DOMContentLoaded', () => {
            if (!checkAuth()) return;
            bindEvents();
        });

        function bindEvents() {
            atpvForm.addEventListener('submit', handleAtpvSubmit);
            buyerCepLookupBtn.addEventListener('click', handleCepLookup);
            manualCaptchaToggle.addEventListener('click', () => setManualCaptchaMode(!manualCaptchaMode));
            manualCaptchaRefresh.addEventListener('click', loadManualCaptchaImage);
            manualCaptchaInput.addEventListener('input', () => {
                manualCaptchaInput.value = manualCaptchaInput.value.replace(/\s/g, '').toUpperCase();
                manualCaptchaError.textContent = '';
            });
            saleValueInput.addEventListener('input', handleSaleValueInput);
            buyerCepInput.addEventListener('input', handleCepInput);
            plateInput.addEventListener('input', () => {
                plateInput.value = normalizePlate(plateInput.value);
            });
            renavamInput.addEventListener('input', () => {
                renavamInput.value = normalizeRenavam(renavamInput.value);
            });
            ownerDocInput.addEventListener('input', () => formatDocumentInput(ownerDocInput, ownerDocOption));
            buyerDocInput.addEventListener('input', () => formatDocumentInput(buyerDocInput, buyerDocOption));
            buyerStateInput.addEventListener('input', () => {
                buyerStateInput.value = buyerStateInput.value.replace(/[^A-Za-z]/g, '').toUpperCase();
            });
            ownerDocButtons.forEach((button) => {
                button.addEventListener('click', () => selectOwnerDocOption(Number(button.dataset.ownerDocOption)));
            });
            buyerDocButtons.forEach((button) => {
                button.addEventListener('click', () => selectBuyerDocOption(Number(button.dataset.buyerDocOption)));
            });
            downloadPdfBtn.addEventListener('click', handlePdfDownload);
            resetFormBtn.addEventListener('click', () => {
                atpvSuccessSection.classList.add('hidden');
                clearSuccessState();
            });
            signatureButtons.forEach((button) => {
                button.addEventListener('click', () => handleSignatureChoice(button.dataset.signed === 'true'));
            });
            atpvBackBtn.addEventListener('click', () => {
                window.location.href = '/home';
            });
        }

        function checkAuth() {
            authToken = localStorage.getItem('auth_token');
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function selectOwnerDocOption(option) {
            ownerDocOption = option;
            ownerDocButtons.forEach((button) => {
                button.classList.toggle('is-active', Number(button.dataset.ownerDocOption) === option);
            });
            ownerDocInput.value = '';
        }

        function selectBuyerDocOption(option) {
            buyerDocOption = option;
            buyerDocButtons.forEach((button) => {
                button.classList.toggle('is-active', Number(button.dataset.buyerDocOption) === option);
            });
            buyerDocInput.value = '';
        }

        function handleSaleValueInput() {
            const digits = onlyDigits(saleValueInput.value);
            if (!digits) {
                saleValueInput.value = '';
                return;
            }
            const formatted = formatCurrencyFromDigits(digits);
            saleValueInput.value = formatted;
        }

        function handleCepInput() {
            const digits = onlyDigits(buyerCepInput.value);
            buyerCepInput.value = formatCep(digits);
            if (digits.length !== 8) {
                buyerMunicipioCode = null;
            }
        }

        async function handleCepLookup() {
            clearCepHelper();
            const digits = onlyDigits(buyerCepInput.value);
            if (digits.length !== 8) {
                setCepHelper('Informe um CEP com 8 dígitos.');
                buyerCepInput.focus();
                return;
            }
            const previousText = buyerCepLookupBtn.textContent;
            buyerCepLookupBtn.disabled = true;
            buyerCepLookupBtn.textContent = 'Buscando...';
            try {
                const response = await fetch(`${API_BASE_URL}/api/cep?cep=${digits}`);
                if (!response.ok) {
                    const message = await parseResponseError(response);
                    setCepHelper(message || 'Não foi possível consultar o CEP.');
                    return;
                }
                const data = await response.json();
                buyerCityInput.value = data.cidade || data.city || buyerCityInput.value;
                buyerNeighborhoodInput.value = data.bairro || buyerNeighborhoodInput.value;
                buyerStreetInput.value = data.logradouro || buyerStreetInput.value;
                buyerStateInput.value = (data.uf || buyerStateInput.value || '').toUpperCase();
                buyerComplementInput.value = data.complemento || buyerComplementInput.value;
                buyerMunicipioCode = data.codigo || data.codigo_ibge || null;
                setCepHelper('Endereço preenchido automaticamente.', 'success');
            } catch (error) {
                setCepHelper(error.message || 'Não foi possível consultar o CEP.');
            } finally {
                buyerCepLookupBtn.disabled = false;
                buyerCepLookupBtn.textContent = previousText;
            }
        }

        function setCepHelper(text, type) {
            cepHelper.textContent = text;
            cepHelper.classList.remove('success', 'error');
            if (type === 'success') {
                cepHelper.classList.add('success');
            } else if (type === 'error') {
                cepHelper.classList.add('error');
            }
        }

        function clearCepHelper() {
            cepHelper.textContent = '';
            cepHelper.classList.remove('success', 'error');
        }

        function setManualCaptchaMode(enabled, message = '') {
            manualCaptchaMode = enabled;
            manualCaptchaMessage.textContent = message;
            manualCaptchaMessage.classList.toggle('hidden', !message);
            manualCaptchaArea.classList.toggle('hidden', !enabled);
            manualCaptchaToggle.textContent = enabled ? 'Voltar ao captcha automático' : 'Digitar captcha manualmente';
            if (enabled) {
                loadManualCaptchaImage();
            } else {
                manualCaptchaInput.value = '';
                manualCaptchaError.textContent = '';
                clearManualCaptchaImage();
            }
        }

        async function loadManualCaptchaImage() {
            if (!manualCaptchaMode) return;
            manualCaptchaLoading = true;
            manualCaptchaError.textContent = '';
            manualCaptchaImage.src = '';
            manualCaptchaRefresh.disabled = true;
            clearManualCaptchaImage();
            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha?${Date.now()}`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                manualCaptchaImage.src = objectUrl;
                manualCaptchaImageUrl = objectUrl;
            } catch (error) {
                manualCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                manualCaptchaLoading = false;
                manualCaptchaRefresh.disabled = false;
            }
        }

        function clearManualCaptchaImage() {
            if (manualCaptchaImageUrl) {
                URL.revokeObjectURL(manualCaptchaImageUrl);
                manualCaptchaImageUrl = null;
            }
        }

        async function handleAtpvSubmit(event) {
            event.preventDefault();
            if (submissionInProgress) return;
            clearFormError();

            const plate = normalizePlate(plateInput.value);
            if (!isValidPlate(plate)) {
                setFormError('Informe uma placa válida.');
                return;
            }

            const renavam = normalizeRenavam(renavamInput.value);
            if (!renavamPattern.test(renavam)) {
                setFormError('Informe um Renavam válido.');
                return;
            }

            if (!buyerDocInput.value.trim()) {
                setFormError('Informe o documento do comprador.');
                return;
            }

            const buyerDigits = onlyDigits(buyerDocInput.value);
            if (buyerDocOption === 1 && buyerDigits.length !== 11) {
                setFormError('Informe um CPF válido para o comprador.');
                return;
            }
            if (buyerDocOption === 2 && buyerDigits.length !== 14) {
                setFormError('Informe um CNPJ válido para o comprador.');
                return;
            }

            if (!buyerNameInput.value.trim()) {
                setFormError('Informe o nome completo do comprador.');
                return;
            }

            if (!buyerCityInput.value.trim() || !buyerNeighborhoodInput.value.trim() || !buyerStreetInput.value.trim()) {
                setFormError('Informe o endereço completo do comprador.');
                return;
            }

            const stateValue = buyerStateInput.value.trim().toUpperCase();
            if (stateValue.length !== 2) {
                setFormError('Informe a UF com duas letras.');
                return;
            }

            if (!termsCheckbox.checked) {
                setFormError('Confirme que as informações estão corretas para continuar.');
                return;
            }

            let captcha;
            try {
                captcha = await obtainCaptchaValue();
            } catch (error) {
                setFormError(error?.message || 'Não foi possível resolver o captcha.');
                return;
            }

            if (!captcha) {
                return;
            }

            submissionInProgress = true;
            atpvSubmitBtn.disabled = true;
            atpvSubmitBtn.textContent = 'Enviando...';
            atpvSuccessSection.classList.add('hidden');
            setManualCaptchaMode(false);
            clearSuccessState();

            const ownerDigits = onlyDigits(ownerDocInput.value);
            const payload = {
                renavam,
                placa: plate,
                captcha,
                uf: stateValue,
                cpf_cnpj_comprador: buyerDigits,
                nome_comprador: buyerNameInput.value.trim(),
                opcao_pesquisa_comprador: buyerDocOption.toString(),
            };

            if (ownerDigits) {
                payload.cpf_cnpj_proprietario = ownerDigits;
                payload.opcao_pesquisa_proprietario = ownerDocOption.toString();
            }
            if (chassiInput.value.trim()) {
                payload.chassi = chassiInput.value.trim();
            }
            if (odometerInput.value.trim()) {
                payload.hodometro = odometerInput.value.trim();
            }
            if (ownerEmailInput.value.trim()) {
                payload.email_proprietario = ownerEmailInput.value.trim();
            }
            if (buyerEmailInput.value.trim()) {
                payload.email_comprador = buyerEmailInput.value.trim();
            }
            if (saleValueInput.value.trim()) {
                payload.valor_venda = saleValueInput.value.trim();
            }
            if (buyerCepInput.value.trim()) {
                payload.cep_comprador = buyerCepInput.value.trim();
            }
            if (buyerCityInput.value.trim()) {
                payload.municipio_comprador = buyerCityInput.value.trim();
            }
            if (buyerNeighborhoodInput.value.trim()) {
                payload.bairro_comprador = buyerNeighborhoodInput.value.trim();
            }
            if (buyerStreetInput.value.trim()) {
                payload.logradouro_comprador = buyerStreetInput.value.trim();
            }
            if (buyerNumberInput.value.trim()) {
                payload.numero_comprador = buyerNumberInput.value.trim();
            }
            if (buyerComplementInput.value.trim()) {
                payload.complemento_comprador = buyerComplementInput.value.trim();
            }
            if (buyerMunicipioCode) {
                payload.municipio2 = buyerMunicipioCode;
            }
            payload.method = 'pesquisar';

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/emissao-atpv`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) {
                    const message = await parseResponseError(response);
                    throw new Error(message || 'Não foi possível emitir a ATPV-e.');
                }
                const result = await response.json();
                if (result.status !== 'awaiting_signature') {
                    throw new Error(result.message || 'Não foi possível concluir a emissão.');
                }
                atpvRegistroId = result.registro_id;
                lastCaptchaValue = captcha;
                renderSuccess(result, plate, renavam);
            } catch (error) {
                setFormError(error?.message || 'Não foi possível emitir a ATPV-e.');
            } finally {
                submissionInProgress = false;
                atpvSubmitBtn.disabled = false;
                atpvSubmitBtn.textContent = 'Enviar ATPV-e';
            }
        }

        function renderSuccess(result, plate, renavam) {
            summaryPlate.textContent = plate;
            summaryRenavam.textContent = renavam;
            summaryAtpvNumber.textContent = result.numero_atpv || 'N/D';
            successSubtitle.textContent = result.message || 'Dados enviados para o Detran. Escolha a assinatura.';
            successMessage.textContent = 'Você pode baixar o PDF e registrar o tipo de assinatura.';
            successMessage.classList.remove('hidden');
            pdfStatusMessage.textContent = '';
            toggleSignatureMessage('');
            atpvSuccessSection.classList.remove('hidden');
            atpvSuccessSection.scrollIntoView({ behavior: 'smooth' });
            clearFormError();
        }

        function clearSuccessState() {
            successMessage.classList.add('hidden');
            toggleSignatureMessage('', true);
            pdfStatusMessage.textContent = '';
            summaryPlate.textContent = '';
            summaryRenavam.textContent = '';
            summaryAtpvNumber.textContent = '';
        }

        function toggleSignatureMessage(text, hide = false) {
            signatureStatusMessage.textContent = text;
            signatureStatusMessage.classList.toggle('hidden', hide || !text);
        }

        async function handleSignatureChoice(isDigital) {
            if (!atpvRegistroId) return;
            signatureButtons.forEach((button) => (button.disabled = true));
            const body = {
                registro_id: atpvRegistroId,
                assinatura_digital: isDigital,
            };
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/emissao-atpv/assinatura`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                if (!response.ok) {
                    const message = await parseResponseError(response);
                    throw new Error(message || 'Não foi possível registrar a assinatura.');
                }
                const result = await response.json();
                toggleSignatureMessage(result.message || 'Assinatura registrada com sucesso.');
            } catch (error) {
                toggleSignatureMessage(error?.message || 'Erro ao registrar a assinatura.');
            } finally {
                signatureButtons.forEach((button) => (button.disabled = false));
            }
        }

        async function handlePdfDownload() {
            if (!summaryPlate.textContent || !summaryRenavam.textContent) {
                setFormError('Emita a ATPV-e antes de baixar o PDF.');
                return;
            }
            if (!lastCaptchaValue) {
                setFormError('Resolva o captcha novamente antes de baixar o PDF.');
                return;
            }
            downloadPdfBtn.disabled = true;
            downloadPdfBtn.textContent = 'Baixando...';
            pdfStatusMessage.textContent = '';
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/emissao-atpv/pdf?placa=${summaryPlate.textContent}&renavam=${summaryRenavam.textContent}&captcha=${lastCaptchaValue}`);
                if (!response.ok) {
                    const message = await parseResponseError(response);
                    throw new Error(message || 'Não foi possível baixar o PDF.');
                }
                const blob = await response.blob();
                downloadBlob(blob, summaryPlate.textContent);
                pdfStatusMessage.textContent = 'PDF baixado com sucesso.';
            } catch (error) {
                pdfStatusMessage.textContent = error?.message || 'Erro ao baixar o PDF.';
            } finally {
                downloadPdfBtn.disabled = false;
                downloadPdfBtn.textContent = 'Baixar PDF da ATPV-e';
            }
        }

        function downloadBlob(blob, plate) {
            if (!blob) return;
            const sanitizedPlate = plate.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const timestamp = Date.now();
            const filename = `ATPV-${sanitizedPlate || 'veiculo'}-${timestamp}.pdf`;
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }

        function clearFormError() {
            formError.textContent = '';
            formError.classList.add('hidden');
        }

        function setFormError(message) {
            formError.textContent = message;
            formError.classList.remove('hidden');
        }

        async function obtainCaptchaValue() {
            if (manualCaptchaMode) {
                const value = manualCaptchaInput.value.trim().toUpperCase();
                if (!value) {
                    manualCaptchaError.textContent = 'Informe o captcha.';
                    throw new Error('Informe o captcha.');
                }
                return value;
            }

            try {
                const solution = await solveBaseCaptcha();
                lastCaptchaValue = solution;
                return solution;
            } catch (error) {
                const status = error?.status ?? 0;
                if (status >= 500) {
                    setManualCaptchaMode(true, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    manualCaptchaError.textContent = '';
                    return null;
                }
                throw error;
            }
        }

        async function fetchWithAuth(url, options = {}) {
            const headers = {
                Accept: 'application/json',
                Authorization: `Bearer ${authToken}`,
                ...(options.headers || {}),
            };
            const response = await fetch(url, { ...options, headers });
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Sessão inválida.');
            }
            return response;
        }

        function handleUnauthorized() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        async function solveBaseCaptcha() {
            const response = await fetch(`${API_BASE_URL}/api/captcha/solve`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const error = new Error(errorData.message || 'Não foi possível resolver o captcha automaticamente.');
                error.status = response.status;
                throw error;
            }
            const data = await response.json();
            const solution = data.solution ? String(data.solution).trim().toUpperCase() : '';
            if (!solution) {
                throw new Error('Resposta inválida ao resolver o captcha.');
            }
            return solution;
        }

        async function parseResponseError(response) {
            try {
                const data = await response.json();
                if (data) {
                    const message =
                        data.message ||
                        (Array.isArray(data.detalhes) ? data.detalhes[0] : null) ||
                        (Array.isArray(data.errors) ? data.errors[0] : null);
                    if (message) {
                        return typeof message === 'string' ? message : JSON.stringify(message);
                    }
                }
            } catch (_) {
                // Fallback to plain text when JSON is invalid.
            }
            const text = await response.text().catch(() => '');
            if (text) {
                return text;
            }
            return `Erro (HTTP ${response.status})`;
        }

        function normalizePlate(value) {
            return value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }

        function isValidPlate(value) {
            if (value.length !== 7) return false;
            return oldPlatePattern.test(value) || mercosurPlatePattern.test(value);
        }

        function normalizeRenavam(value) {
            return value.replace(/\D/g, '');
        }

        function onlyDigits(value) {
            return (value || '').replace(/\D/g, '');
        }

        function formatCep(digits) {
            if (!digits) return '';
            if (digits.length <= 5) return digits;
            return `${digits.slice(0, 5)}-${digits.slice(5, 8)}`;
        }

        function formatDocumentInput(input, option) {
            const digits = onlyDigits(input.value);
            const maxLen = option === 1 ? 11 : 14;
            const limited = digits.slice(0, maxLen);
            input.value = option === 1 ? formatCpf(limited) ?? limited : formatCnpj(limited) ?? limited;
        }

        function formatCpf(digits) {
            if (digits.length !== 11) return null;
            return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
        }

        function formatCnpj(digits) {
            if (digits.length !== 14) return null;
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
        }

        function formatCurrencyFromDigits(digits) {
            if (!digits) return '';
            const limited = digits.slice(0, 12);
            const numeric = parseInt(limited, 10) || 0;
            const cents = (numeric % 100).toString().padStart(2, '0');
            const integerPart = Math.floor(numeric / 100).toString();
            const integerFormatted = addThousandsSeparator(integerPart);
            return `${integerFormatted},${cents}`;
        }

        function addThousandsSeparator(value) {
            const reversed = value.split('').reverse();
            const grouped = [];
            for (let i = 0; i < reversed.length; i += 1) {
                grouped.push(reversed[i]);
                if ((i + 1) % 3 === 0 && i + 1 !== reversed.length) {
                    grouped.push('.');
                }
            }
            return grouped.reverse().join('');
        }

        window.addEventListener('beforeunload', () => {
            clearManualCaptchaImage();
        });
    </script>
</body>
</html>
