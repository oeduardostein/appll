<div class="be-overlay hidden" id="gravameCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="gravameCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="gravameCaptchaTitle">Consultar gravame</h2>
            <button class="be-dialog-close" type="button" id="gravameCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <p class="be-dialog-helper">Informe o captcha exibido para continuar.</p>
            <input
                class="be-input"
                id="gravameCaptchaPlate"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
                disabled
            >
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
