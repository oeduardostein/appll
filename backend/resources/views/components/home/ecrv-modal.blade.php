<div class="be-overlay ecrv-overlay hidden" id="ecrvOverlay" aria-hidden="true">
    <div class="be-dialog ecrv-dialog" role="dialog" aria-modal="true" aria-labelledby="ecrvTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="ecrvTitle">Ficha cadastral - passo 1</h2>
            <button class="be-dialog-close" type="button" id="ecrvClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="ecrv-description" id="ecrvDescription">
                Selecione o padrão da placa e informe o valor. Resolveremos o captcha automaticamente.
            </p>
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="ecrvPlateFormat" value="antiga">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa antiga (ABC-1234)</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="ecrvPlateFormat" value="mercosul">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                </label>
            </div>
            <input
                class="be-input"
                id="ecrvPlateInput"
                type="text"
                placeholder="Selecione o padrão da placa"
                maxlength="8"
                autocomplete="off"
                disabled
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
