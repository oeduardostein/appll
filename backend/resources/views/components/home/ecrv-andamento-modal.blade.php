<div class="be-overlay ecrv-overlay hidden" id="ecrvAndamentoOverlay" aria-hidden="true">
    <div class="be-dialog ecrv-dialog ecrv-andamento-dialog" role="dialog" aria-modal="true" aria-labelledby="ecrvAndamentoTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="ecrvAndamentoTitle">Andamento do processo - passo 2</h2>
            <button class="be-dialog-close" type="button" id="ecrvAndamentoClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="ecrv-description" id="ecrvAndamentoDescription">
                Confirme os dados e preencha o captcha para consultar o andamento.
            </p>
            <input
                class="be-input be-input--disabled"
                id="ecrvAndamentoFichaInput"
                type="text"
                placeholder="Número da ficha"
                disabled
            >
            <input
                class="be-input be-input--disabled"
                id="ecrvAndamentoAnoInput"
                type="text"
                placeholder="Ano da ficha"
                disabled
            >
            <div class="be-dialog-error" id="ecrvAndamentoFichaError"></div>
            <div class="ecrv-captcha-section">
                <div class="ecrv-captcha-image-wrapper">
                    <div class="be-captcha-image">
                        <img id="ecrvAndamentoCaptchaImage" alt="Captcha">
                        <div class="be-captcha-loading hidden" id="ecrvAndamentoCaptchaLoading">
                            <span class="spinner"></span>
                            <span>Carregando captcha...</span>
                        </div>
                    </div>
                    <button class="ecrv-captcha-refresh" type="button" id="ecrvAndamentoCaptchaRefresh">Atualizar</button>
                </div>
                <input
                    class="be-input"
                    id="ecrvAndamentoCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
                <div class="be-dialog-error" id="ecrvAndamentoCaptchaError"></div>
            </div>

            <button class="be-dialog-submit ecrv-submit" type="button" id="ecrvAndamentoBtn">
                <span class="be-btn-text">Consultar andamento</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="ecrvAndamentoCancelBtn">Cancelar</button>
        </div>
    </div>
</div>
