<div class="be-overlay ecrv-overlay hidden" id="ecrvOverlay" aria-hidden="true">
    <div class="be-dialog ecrv-dialog" role="dialog" aria-modal="true" aria-labelledby="ecrvTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="ecrvTitle">Ficha cadastral - passo 1</h2>
            <button class="be-dialog-close" type="button" id="ecrvClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="ecrv-description" id="ecrvDescription">
                Informe somente a placa. Resolveremos o captcha automaticamente.
            </p>
            <input
                class="be-input"
                id="ecrvPlateInput"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
            >
            <div class="be-dialog-error" id="ecrvPlateError"></div>

            <div class="ecrv-captcha-section hidden" id="ecrvCaptchaSection">
                <div class="ecrv-captcha-image-wrapper">
                    <div class="be-captcha-image">
                        <img id="ecrvCaptchaImage" alt="Captcha">
                        <div class="be-captcha-loading hidden" id="ecrvCaptchaLoading">
                            <span class="spinner"></span>
                            <span>Carregando captcha...</span>
                        </div>
                    </div>
                    <button class="ecrv-captcha-refresh" type="button" id="ecrvCaptchaRefresh">Atualizar</button>
                </div>
                <input
                    class="be-input"
                    id="ecrvCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
                <div class="be-dialog-error" id="ecrvCaptchaError"></div>
            </div>

            <button class="be-dialog-submit ecrv-submit" type="button" id="ecrvAdvanceBtn">
                <span class="be-btn-text">Avançar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="ecrvCancelBtn">Cancelar</button>
        </div>
    </div>
</div>
