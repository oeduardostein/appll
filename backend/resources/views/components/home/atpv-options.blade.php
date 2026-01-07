<div class="be-overlay hidden" id="atpvOptionsOverlay" aria-hidden="true">
    <div class="be-dialog atpv-options-dialog" role="dialog" aria-modal="true" aria-labelledby="atpvOptionsTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="atpvOptionsTitle">Preenchimento da ATPV-e</h2>
            <button class="be-dialog-close" type="button" id="atpvOptionsClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="atpv-options-grid">
            <div class="atpv-option-card atpv-option-card--primary">
                <div class="atpv-option-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18h6"></path>
                        <path d="M12 4v14"></path>
                        <circle cx="12" cy="12" r="5"></circle>
                    </svg>
                </div>
                <div class="atpv-option-content">
                    <div class="atpv-option-title">Consultar intenção de venda</div>
                    <div class="atpv-option-description">
                        Use a placa e o Renavam para recuperar dados já informados ao Detran.
                    </div>
                    <button type="button" id="atpvConsultOptionBtn" class="be-dialog-submit">
                        <span class="be-btn-text">Consultar agora</span>
                        <span class="be-btn-spinner" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="atpv-option-card">
                <div class="atpv-option-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 5h16v14H4z"></path>
                        <path d="M4 9h16"></path>
                        <path d="M8 12h2"></path>
                        <path d="M8 15h2"></path>
                        <path d="M13 12h4"></path>
                        <path d="M13 15h4"></path>
                    </svg>
                </div>
                <div class="atpv-option-content">
                    <div class="atpv-option-title">Preencher formulário</div>
                    <div class="atpv-option-description">
                        Prefere informar tudo do zero? Abra o formulário completo de emissão.
                    </div>
                    <button type="button" id="atpvFormOptionBtn" class="be-dialog-cancel">
                        Ir para o formulário
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
