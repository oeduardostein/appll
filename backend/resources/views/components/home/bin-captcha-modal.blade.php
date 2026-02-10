<div class="be-overlay hidden" id="binCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="binCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="binCaptchaTitle">Pesquisa BIN</h2>
            <button class="be-dialog-close" type="button" id="binCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="binCaptchaSearchOption" value="placa" checked>
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa + Renavam</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="binCaptchaSearchOption" value="chassi">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Chassi</span>
                </label>
            </div>
            <div class="be-field-group" id="binCaptchaPlacaGroup">
                <div class="be-radio-group">
                    <label class="be-radio-option">
                        <input type="radio" name="binCaptchaPlateFormat" value="antiga">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Antiga (ABC-1234)</span>
                    </label>
                    <label class="be-radio-option">
                        <input type="radio" name="binCaptchaPlateFormat" value="mercosul">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                    </label>
                </div>
                <input
                    class="be-input"
                    id="binCaptchaPlacaInput"
                    type="text"
                    placeholder="Selecione o padrão da placa"
                    maxlength="8"
                    autocomplete="off"
                    disabled
                >
                <input
                    class="be-input"
                    id="binCaptchaRenavamInput"
                    type="text"
                    placeholder="Renavam"
                    maxlength="11"
                    autocomplete="off"
                >
            </div>
            <div class="be-field-group hidden" id="binCaptchaChassiGroup">
                <input
                    class="be-input"
                    id="binCaptchaChassiInput"
                    type="text"
                    placeholder="Chassi"
                    maxlength="17"
                    autocomplete="off"
                >
            </div>
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="binCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="binCaptchaImageWrap">
                    <div class="be-captcha-loading" id="binCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="binCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="binCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="binCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="binCaptchaSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="binCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
