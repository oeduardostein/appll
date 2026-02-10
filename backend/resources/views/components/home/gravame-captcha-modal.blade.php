<div class="be-overlay hidden" id="gravameCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="gravameCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="gravameCaptchaTitle">Consultar gravame</h2>
            <button class="be-dialog-close" type="button" id="gravameCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">Informe placa ou chassi e o captcha exibido para continuar.</p>
            <div class="be-radio-group">
                <label class="be-radio-option">
                    <input type="radio" name="gravameCaptchaSearchOption" value="placa" checked>
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Placa</span>
                </label>
                <label class="be-radio-option">
                    <input type="radio" name="gravameCaptchaSearchOption" value="chassi">
                    <span class="be-radio-mark"></span>
                    <span class="be-radio-text">Chassi</span>
                </label>
            </div>
            <div class="be-field-group" id="gravameCaptchaPlacaGroup">
                <div class="be-radio-group">
                    <label class="be-radio-option">
                        <input type="radio" name="gravameCaptchaPlateFormat" value="antiga">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Antiga (ABC-1234)</span>
                    </label>
                    <label class="be-radio-option">
                        <input type="radio" name="gravameCaptchaPlateFormat" value="mercosul">
                        <span class="be-radio-mark"></span>
                        <span class="be-radio-text">Mercosul (ABC-1D23)</span>
                    </label>
                </div>
                <input
                    class="be-input"
                    id="gravameCaptchaPlate"
                    type="text"
                    placeholder="Selecione o padrão da placa"
                    maxlength="8"
                    autocomplete="off"
                    disabled
                >
            </div>
            <div class="be-field-group hidden" id="gravameCaptchaChassiGroup">
                <input
                    class="be-input"
                    id="gravameCaptchaChassiInput"
                    type="text"
                    placeholder="Chassi"
                    maxlength="17"
                    autocomplete="off"
                >
            </div>
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="gravameCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="gravameCaptchaImageWrap">
                    <div class="be-captcha-loading" id="gravameCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="gravameCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="gravameCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="gravameCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="gravameCaptchaSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="gravameCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
