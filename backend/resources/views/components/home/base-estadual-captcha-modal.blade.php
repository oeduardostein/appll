<div class="be-overlay hidden" id="baseCaptchaOverlay" aria-hidden="true">
    <div class="be-dialog" role="dialog" aria-modal="true" aria-labelledby="baseCaptchaTitle">
        <div class="be-dialog-header">
            <h2 class="be-dialog-title" id="baseCaptchaTitle">Consulta base estadual</h2>
            <button class="be-dialog-close" type="button" id="baseCaptchaClose" aria-label="Fechar">&times;</button>
        </div>
        <div class="be-dialog-body">
            <input
                class="be-input"
                id="baseCaptchaPlate"
                type="text"
                placeholder="Placa"
                maxlength="7"
                autocomplete="off"
                disabled
            >
            <div class="be-captcha-box">
                <div class="be-captcha-header">
                    <span>Captcha</span>
                    <button class="be-captcha-refresh" type="button" id="baseCaptchaRefresh">Atualizar</button>
                </div>
                <div class="be-captcha-image" id="baseCaptchaImageWrap">
                    <div class="be-captcha-loading" id="baseCaptchaLoading">
                        <span class="spinner"></span>
                        <span>Carregando captcha...</span>
                    </div>
                    <img id="baseCaptchaImage" alt="Captcha">
                </div>
                <input
                    class="be-input"
                    id="baseCaptchaInput"
                    type="text"
                    placeholder="Digite o captcha"
                    maxlength="10"
                    autocomplete="off"
                >
            </div>
            <div class="be-dialog-error" id="baseCaptchaError"></div>
            <button class="be-dialog-submit" type="button" id="baseCaptchaSubmit">
                <span class="be-btn-text">Consultar</span>
                <span class="be-btn-spinner" aria-hidden="true"></span>
            </button>
            <button class="be-dialog-cancel" type="button" id="baseCaptchaCancel">Cancelar</button>
        </div>
    </div>
</div>
